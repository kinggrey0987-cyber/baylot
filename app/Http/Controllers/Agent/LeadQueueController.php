<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\LeadClaim;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class LeadQueueController extends Controller
{
    public function index(): View
    {
        $agent = auth()->user();
        $brokerId = $agent->broker_id;

        $claimableLeads = Lead::with(['listing.project', 'buyer', 'appointment', 'messages'])
            ->where('status', 'new')
            ->whereHas('listing', function ($query) use ($brokerId): void {
                $query->where('listing_type', 'shared')
                    ->whereHas('project', function ($projectQuery) use ($brokerId): void {
                        if (is_null($brokerId)) {
                            $projectQuery->whereNull('broker_id');

                            return;
                        }

                        $projectQuery->where('broker_id', $brokerId);
                    });
            })
            ->latest()
            ->get();

        $claimedLeads = Lead::with(['listing.project', 'buyer', 'appointment', 'claim', 'messages'])
            ->whereHas('claim', fn ($query) => $query->where('agent_id', $agent->id))
            ->latest()
            ->get();

        return view('agent.leads.queue', compact('claimableLeads', 'claimedLeads'));
    }

    public function claim(Lead $lead): RedirectResponse
    {
        Gate::authorize('claim', $lead);

        try {
            DB::transaction(function () use ($lead): void {
                $lockedLead = Lead::with(['listing.project', 'appointment', 'claim'])
                    ->lockForUpdate()
                    ->findOrFail($lead->id);

                if ($lockedLead->status !== 'new' || $lockedLead->claim) {
                    abort(409, 'This lead is already claimed.');
                }

                LeadClaim::create([
                    'lead_id' => $lockedLead->id,
                    'agent_id' => auth()->id(),
                    'claimed_at' => now(),
                ]);

                $lockedLead->update(['status' => 'claimed']);

                if ($lockedLead->appointment) {
                    $lockedLead->appointment->update(['assigned_agent_id' => auth()->id()]);
                }
            });
        } catch (QueryException $exception) {
            return back()->withErrors(['claim' => 'Lead claim failed due to race condition. Please refresh the queue.']);
        }

        return back()->with('success', 'Lead claimed successfully.');
    }
}
