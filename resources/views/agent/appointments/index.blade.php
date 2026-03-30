<x-app-layout>
    <div class="app-section-head">
        <div>
            <h1 class="app-section-title">Assigned Appointments</h1>
            <p class="app-section-subtitle">Review appointment requests and update statuses.</p>
        </div>
    </div>

    <div class="card module-card">
        <div class="card-header">Assigned Appointments</div>
        <div class="table-responsive">
            <table class="table module-table mb-0">
                <thead><tr><th>Buyer</th><th>Listing</th><th>Schedule</th><th>Status</th><th>Listing Availability</th><th class="text-end">Update</th></tr></thead>
                <tbody>
                @forelse($appointments as $appointment)
                    @php
                        $statusClass = match ($appointment->status) {
                            'approved', 'completed' => 'text-bg-success',
                            'pending', 'rescheduled' => 'text-bg-warning',
                            'cancelled', 'rejected' => 'text-bg-danger',
                            default => 'text-bg-secondary',
                        };

                        $appointmentStatusLabel = match ($appointment->status) {
                            'completed' => 'Site Visit Done',
                            default => ucfirst($appointment->status),
                        };

                        $listingStatusClass = match ($appointment->lead->listing->status) {
                            'available' => 'text-bg-success',
                            'reserved' => 'text-bg-warning',
                            'sold' => 'text-bg-dark',
                            default => 'text-bg-secondary',
                        };
                    @endphp
                    <tr>
                        <td>{{ $appointment->lead->buyer->name }}</td>
                        <td>{{ $appointment->lead->listing->title }}</td>
                        <td>{{ $appointment->requested_start?->format('M d, Y h:i A') }}</td>
                        <td><span class="badge {{ $statusClass }} status-badge">{{ $appointmentStatusLabel }}</span></td>
                        <td><span class="badge {{ $listingStatusClass }} status-badge">{{ ucfirst($appointment->lead->listing->status) }}</span></td>
                        <td class="text-end">
                            <form method="POST" action="{{ route('agent.appointments.update', $appointment) }}" class="d-inline-flex align-items-center gap-2">
                                @csrf @method('PUT')
                                <input
                                    type="datetime-local"
                                    name="requested_at"
                                    value="{{ $appointment->requested_start?->format('Y-m-d\\TH:i') }}"
                                    class="form-control form-control-sm"
                                    step="900"
                                    title="Requested date and time"
                                >
                                <select name="status" class="form-select form-select-sm appointment-status-select">
                                    @foreach([
                                        'approved' => 'Approved',
                                        'rescheduled' => 'Rescheduled',
                                        'completed' => 'Site Visit Done',
                                        'cancelled' => 'Cancelled',
                                        'rejected' => 'Rejected',
                                    ] as $statusValue => $statusLabel)
                                        <option value="{{ $statusValue }}" @selected($appointment->status === $statusValue)>{{ $statusLabel }}</option>
                                    @endforeach
                                </select>
                                <select name="listing_outcome" class="form-select form-select-sm listing-outcome-select" title="Listing availability after site visit">
                                    <option value="available" @selected(is_null($appointment->down_payment_received_at))>Still Available</option>
                                    <option value="reserved" @selected(!is_null($appointment->down_payment_received_at))>Reserved</option>
                                </select>
                                <input type="hidden" name="sale_confirmed" value="0">
                                <div class="form-check mb-0">
                                    <input class="form-check-input sale-confirmed-input" type="checkbox" name="sale_confirmed" value="1" id="sale_confirmed_{{ $appointment->id }}" @checked($appointment->sale_confirmed_at) @disabled($appointment->status !== 'completed')>
                                    <label class="form-check-label small" for="sale_confirmed_{{ $appointment->id }}">Sale confirmed</label>
                                </div>
                                <button class="btn btn-sm btn-primary">Save</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="table-empty">No appointments assigned yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body">{{ $appointments->links() }}</div>
    </div>
</x-app-layout>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('form[action*="/agent/appointments/"]').forEach((form) => {
            const statusSelect = form.querySelector('.appointment-status-select');
            const saleCheckbox = form.querySelector('.sale-confirmed-input');
            const listingOutcomeSelect = form.querySelector('.listing-outcome-select');
            const scheduleInput = form.querySelector('input[name="requested_at"]');

            if (!statusSelect || !saleCheckbox || !listingOutcomeSelect) {
                return;
            }

            const syncScheduleValidity = () => {
                if (!scheduleInput || !scheduleInput.value) {
                    scheduleInput?.setCustomValidity('');
                    return true;
                }

                const selectedAt = new Date(scheduleInput.value);

                if (Number.isNaN(selectedAt.getTime())) {
                    scheduleInput.setCustomValidity('');
                    return true;
                }

                const totalMinutes = (selectedAt.getHours() * 60) + selectedAt.getMinutes();
                const isWithinWorkingHours = totalMinutes >= 480 && totalMinutes <= 960;

                scheduleInput.setCustomValidity(
                    isWithinWorkingHours ? '' : 'Please choose a start time between 8:00 AM and 4:00 PM only.'
                );

                return isWithinWorkingHours;
            };

            const syncSaleConfirmedState = () => {
                const siteVisitDone = statusSelect.value === 'completed';

                saleCheckbox.disabled = !siteVisitDone;
                listingOutcomeSelect.disabled = !siteVisitDone;

                if (!siteVisitDone) {
                    saleCheckbox.checked = false;
                }
            };

            statusSelect.addEventListener('change', syncSaleConfirmedState);
            scheduleInput?.addEventListener('change', syncScheduleValidity);
            form.addEventListener('submit', (event) => {
                if (!syncScheduleValidity()) {
                    event.preventDefault();
                    scheduleInput?.reportValidity();
                }
            });
            syncSaleConfirmedState();
            syncScheduleValidity();
        });
    });
</script>
