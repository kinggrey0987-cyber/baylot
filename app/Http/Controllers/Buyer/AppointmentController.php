<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Services\ListingStatusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class AppointmentController extends Controller
{
    public function __construct(private readonly ListingStatusService $listingStatusService)
    {
    }

    public function cancel(Appointment $appointment): RedirectResponse
    {
        Gate::authorize('cancelByBuyer', $appointment);

        $appointment->update(['status' => 'cancelled']);
        $this->listingStatusService->syncFromAppointment($appointment->refresh()->loadMissing('lead.listing'));

        return back()->with('success', 'Appointment cancelled.');
    }
}
