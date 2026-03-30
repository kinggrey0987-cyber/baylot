<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\LeadClaim;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('admin.dashboard', [
            'agentCount' => User::whereIn('role', ['agent', 'broker'])->count(),
            'buyerCount' => User::where('role', 'buyer')->count(),
            'listingByStatus' => Listing::query()
                ->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status'),
            'appointmentsByStatus' => Appointment::query()
                ->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status'),
            'recentClaims' => LeadClaim::with(['lead.listing', 'agent'])->latest('claimed_at')->limit(10)->get(),
        ]);
    }
}
