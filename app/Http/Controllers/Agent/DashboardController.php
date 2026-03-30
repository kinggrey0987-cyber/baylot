<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Lead;
use App\Models\Listing;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $agent = auth()->user();

        $brokerId = $agent->broker_id;

        return view('agent.dashboard', [
            'exclusiveListings' => Listing::where('owner_agent_id', $agent->id)->count(),
            'pendingAppointments' => Appointment::where('assigned_agent_id', $agent->id)->where('status', 'pending')->count(),
            'pendingAppointmentRequests' => Appointment::with(['lead.listing', 'lead.buyer'])
                ->where('assigned_agent_id', $agent->id)
                ->where('status', 'pending')
                ->latest()
                ->limit(8)
                ->get(),
            'claimableSharedLeads' => Lead::where('status', 'new')
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
                ->count(),
        ]);
    }
}
