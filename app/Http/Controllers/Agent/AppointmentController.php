<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateAppointmentStatusRequest;
use App\Models\Appointment;
use App\Services\ListingStatusService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class AppointmentController extends Controller
{
    public function __construct(private readonly ListingStatusService $listingStatusService)
    {
    }

    public function index(): View
    {
        return view('agent.appointments.index', [
            'appointments' => Appointment::with(['lead.listing', 'lead.buyer'])
                ->where('assigned_agent_id', auth()->id())
                ->latest()
                ->paginate(15),
        ]);
    }

    public function update(UpdateAppointmentStatusRequest $request, Appointment $appointment): RedirectResponse
    {
        abort_if($appointment->assigned_agent_id !== auth()->id(), 403);

        $status = $request->string('status')->toString();
        $listingOutcome = $request->input('listing_outcome');
        $hasListingOutcome = $request->filled('listing_outcome');
        $hasDownPaymentInput = $request->has('down_payment_received');
        $downPaymentReceivedAt = $appointment->down_payment_received_at;

        if ($hasListingOutcome && $status === 'completed') {
            $downPaymentReceivedAt = $listingOutcome === 'reserved'
                ? ($appointment->down_payment_received_at ?? now())
                : null;
        } elseif ($hasDownPaymentInput) {
            $downPaymentReceived = $request->boolean('down_payment_received');
            $downPaymentReceivedAt = $downPaymentReceived ? ($appointment->down_payment_received_at ?? now()) : null;
        }

        if (! in_array($status, ['approved', 'completed'], true)) {
            $downPaymentReceivedAt = null;
        }

        $saleConfirmed = $request->boolean('sale_confirmed') && $status === 'completed';

        $payload = [
            'status' => $status,
            'agent_notes' => $request->string('agent_notes')->toString(),
            'down_payment_received_at' => $downPaymentReceivedAt,
            'sale_confirmed_at' => $saleConfirmed ? ($appointment->sale_confirmed_at ?? now()) : null,
        ];

        if ($request->filled('requested_at')) {
            $requestedAt = $request->date('requested_at');
            $payload['requested_start'] = $requestedAt;
            $payload['requested_end'] = $requestedAt?->copy()->addHour();
        }

        $appointment->update($payload);
        $this->listingStatusService->syncFromAppointment($appointment->refresh()->loadMissing('lead.listing'));

        return back()->with('success', 'Appointment updated.');
    }
}
