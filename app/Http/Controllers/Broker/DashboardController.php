<?php

namespace App\Http\Controllers\Broker;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\Listing;
use App\Models\Project;
use App\Models\User;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $broker = auth()->user();

        return view('broker.dashboard', [
            'teamAgentCount' => User::query()
                ->where('role', 'agent')
                ->where('broker_id', $broker->id)
                ->count(),
            'activeTeamAgentCount' => User::query()
                ->where('role', 'agent')
                ->where('broker_id', $broker->id)
                ->where('is_active', true)
                ->count(),
            'projectCount' => Project::query()
                ->ownedByBroker($broker->id)
                ->count(),
            'sharedListingsCount' => Listing::query()
                ->sharedOwnedByBroker($broker->id)
                ->count(),
            'openSharedLeadsCount' => Lead::query()
                ->where('status', 'new')
                ->whereHas('listing', fn ($query) => $query->sharedOwnedByBroker($broker->id))
                ->count(),
            'recentSharedLeads' => Lead::query()
                ->with(['listing.project', 'buyer'])
                ->whereHas('listing', fn ($query) => $query->sharedOwnedByBroker($broker->id))
                ->latest()
                ->limit(8)
                ->get(),
        ]);
    }
}
