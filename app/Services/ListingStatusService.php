<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Listing;

class ListingStatusService
{
    public function syncFromAppointment(Appointment $appointment): void
    {
        $listing = $appointment->lead?->listing;

        if (! $listing instanceof Listing) {
            return;
        }

        if (! is_null($listing->available_units)) {
            $nextStatus = $listing->available_units <= 0 ? 'unavailable' : 'available';

            if ($listing->status !== $nextStatus) {
                $listing->update(['status' => $nextStatus]);
            }

            return;
        }

        $hasConfirmedSale = Appointment::query()
            ->whereHas('lead', function ($query) use ($listing): void {
                $query->where('listing_id', $listing->id);
            })
            ->where('status', 'completed')
            ->whereNotNull('sale_confirmed_at')
            ->exists();

        if ($hasConfirmedSale) {
            if ($listing->status !== 'sold') {
                $listing->update(['status' => 'sold']);
            }

            return;
        }

        if (in_array($listing->status, ['sold', 'unavailable'], true)) {
            return;
        }

        $hasQualifiedReservation = Appointment::query()
            ->whereHas('lead', function ($query) use ($listing): void {
                $query->where('listing_id', $listing->id);
            })
            ->whereIn('status', ['approved', 'completed'])
            ->whereNotNull('down_payment_received_at')
            ->exists();

        $nextStatus = $hasQualifiedReservation ? 'reserved' : 'available';

        if ($listing->status !== $nextStatus) {
            $listing->update(['status' => $nextStatus]);
        }
    }
}
