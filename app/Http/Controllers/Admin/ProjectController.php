<?php

namespace App\Http\Controllers\Admin;

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
        return view('admin.projects.index', [
            'projects' => Project::query()
                ->withCount([
                    'listings as shared_listings_count' => fn ($query) => $query->where('listing_type', 'shared'),
                ])
                ->latest()
                ->paginate(15),
            'activeAgentCount' => User::whereIn('role', ['agent', 'broker'])->where('is_active', true)->count(),
        ]);
    }

    public function store(StoreProjectRequest $request): RedirectResponse
    {
        $project = Project::create([
            'name' => $request->string('name')->toString(),
            'description' => $request->string('description')->toString(),
            'created_by_admin_id' => $request->user()->id,
        ]);

        return redirect()
            ->route('admin.shared-listings.index', ['project_id' => $project->id])
            ->with('success', 'Project created. Next step: add the collective listing details, coordinates, and photos.');
    }

    public function update(StoreProjectRequest $request, Project $project): RedirectResponse
    {
        $project->update($request->validated());

        return back()->with('success', 'Project updated.');
    }

    public function destroy(Project $project): RedirectResponse
    {
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
