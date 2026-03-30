<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Listing;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $buyerId = auth()->id();
        $securedListings = Listing::query()
            ->with(['owner'])
            ->where(function ($query) use ($buyerId): void {
                $query->where(function ($subQuery) use ($buyerId): void {
                    $subQuery->where('status', 'reserved')
                        ->whereHas('leads', function ($leadQuery) use ($buyerId): void {
                            $leadQuery->where('buyer_id', $buyerId)
                                ->whereHas('appointment', function ($appointmentQuery): void {
                                    $appointmentQuery->whereIn('status', ['approved', 'completed'])
                                        ->whereNotNull('down_payment_received_at');
                                });
                        });
                })->orWhere(function ($subQuery) use ($buyerId): void {
                    $subQuery->where('status', 'sold')
                        ->whereHas('leads', function ($leadQuery) use ($buyerId): void {
                            $leadQuery->where('buyer_id', $buyerId)
                                ->whereHas('appointment', function ($appointmentQuery): void {
                                    $appointmentQuery->where('status', 'completed')
                                        ->whereNotNull('sale_confirmed_at');
                                });
                        });
                });
            })
            ->latest()
            ->get();

        return view('buyer.dashboard', [
            'securedListings' => $securedListings,
            'reservedCount' => $securedListings->where('status', 'reserved')->count(),
            'ownedCount' => $securedListings->where('status', 'sold')->count(),
            'appointments' => Appointment::with(['lead.listing', 'assignedAgent'])
                ->whereHas('lead', fn ($query) => $query->where('buyer_id', $buyerId))
                ->latest()
                ->paginate(15),
        ]);
    }
}
