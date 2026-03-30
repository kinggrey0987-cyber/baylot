<x-app-layout>
    <div class="app-section-head">
        <div>
            <h1 class="app-section-title">Agent Dashboard</h1>
            <p class="app-section-subtitle">Monitor your listing performance, appointments, and claim opportunities.</p>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-md-4">
            <div class="stat-card h-100">
                <p class="label">Exclusive Listings</p>
                <p class="value">{{ $exclusiveListings }}</p>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="stat-card h-100">
                <p class="label">Pending Appointments</p>
                <p class="value">{{ $pendingAppointments }}</p>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="stat-card h-100">
                <p class="label">Claimable Leads</p>
                <p class="value">{{ $claimableSharedLeads }}</p>
            </div>
        </div>
    </div>

    <div class="card module-card mt-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Pending Site Visit Requests</span>
            <a href="{{ route('agent.appointments.index') }}" class="btn btn-sm btn-outline-primary">View all</a>
        </div>
        <div class="table-responsive">
            <table class="table module-table mb-0">
                <thead><tr><th>Buyer</th><th>Listing</th><th>Requested Date &amp; Time</th><th>Status</th></tr></thead>
                <tbody>
                @forelse($pendingAppointmentRequests as $appointment)
                    @php
                        $appointmentStatusLabel = match ($appointment->status) {
                            'completed' => 'Site Visit Done',
                            default => ucfirst($appointment->status),
                        };
                    @endphp
                    <tr>
                        <td>{{ $appointment->lead->buyer->name }}</td>
                        <td>{{ $appointment->lead->listing->title }}</td>
                        <td>{{ $appointment->requested_start?->format('M d, Y h:i A') }}</td>
                        <td><span class="badge text-bg-warning status-badge">{{ $appointmentStatusLabel }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="table-empty">No pending site visit requests.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
