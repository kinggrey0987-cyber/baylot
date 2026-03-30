<?php

namespace App\Http\Controllers\Broker;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProjectRequest;
use App\Models\Appointment;
use App\Models\Listing;
use App\Models\Project;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class ProjectController extends Controller
{
    public function index(): View
    {
        $broker = auth()->user();

        return view('admin.projects.index', [
            'projects' => Project::query()
                ->ownedByBroker($broker->id)
                ->withCount([
                    'listings as shared_listings_count' => fn ($query) => $query->where('listing_type', 'shared'),
                ])
                ->latest()
                ->paginate(15),
            'activeAgentCount' => User::query()
                ->where('role', 'agent')
                ->where('broker_id', $broker->id)
                ->where('is_active', true)
                ->count(),
        ]);
    }

    public function store(StoreProjectRequest $request): RedirectResponse
    {
        $project = Project::create([
            'name' => $request->string('name')->toString(),
            'description' => $request->string('description')->toString(),
            'broker_id' => $request->user()->id,
            'created_by_admin_id' => null,
        ]);

        $agentIds = $request->user()->managedAgents()->where('is_active', true)->pluck('id')->all();

        if (! empty($agentIds)) {
            $project->agents()->sync($agentIds);
        }

        return redirect()
            ->route('broker.shared-listings.index', ['project_id' => $project->id])
            ->with('success', 'Project created. Next step: add collective listing details, coordinates, and photos.');
    }

    public function update(StoreProjectRequest $request, Project $project): RedirectResponse
    {
        abort_if($project->broker_id !== $request->user()->id, 403, 'You can only update your own projects.');

        $project->update($request->validated());

        return back()->with('success', 'Project updated.');
    }

    public function destroy(Project $project): RedirectResponse
    {
        abort_if($project->broker_id !== auth()->id(), 403, 'You can only delete your own projects.');

        $activeAppointmentsCount = Appointment::query()
            ->whereIn('status', ['pending', 'approved', 'rescheduled'])
            ->whereHas('lead.listing', fn ($query) => $query
                ->where('listing_type', 'shared')
                ->where('project_id', $project->id))
            ->count();

        if ($activeAppointmentsCount > 0) {
            return back()->withErrors([
                'project' => "Cannot delete this project while {$activeAppointmentsCount} active site visit request(s) exist. Complete, cancel, or reject those appointments first.",
            ]);
        }

        try {
            DB::transaction(function () use ($project): void {
                Listing::query()
                    ->where('listing_type', 'shared')
                    ->where('project_id', $project->id)
                    ->delete();

                $project->delete();
            });
        } catch (QueryException $exception) {
            return back()->withErrors(['project' => 'Project deletion failed. Please try again.']);
        }

        return back()->with('success', 'Project deleted.');
    }
}
