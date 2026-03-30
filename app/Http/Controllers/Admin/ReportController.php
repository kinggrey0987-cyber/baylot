<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Lead;
use App\Models\LeadClaim;
use App\Models\Listing;
use Illuminate\Contracts\View\View;

class ReportController extends Controller
{
    public function index(): View
    {
        return view('admin.reports.index', [
            'listingStatus' => Listing::query()->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status'),
            'agentActivity' => LeadClaim::query()
                ->join('users', 'users.id', '=', 'lead_claims.agent_id')
                ->selectRaw('users.name, COUNT(*) as total_claims')
                ->groupBy('users.name')
                ->orderByDesc('total_claims')
                ->get(),
            'appointmentsOverview' => Appointment::query()->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status'),
            'sharedQueue' => Lead::with(['listing.project', 'buyer', 'claim.agent'])
                ->where('status', 'new')
                ->whereHas('listing', fn ($query) => $query->where('listing_type', 'shared'))
                ->latest()
                ->limit(30)
                ->get(),
        ]);
    }
}
