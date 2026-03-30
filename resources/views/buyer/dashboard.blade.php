<x-app-layout>
    <div class="app-section-head d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h1 class="app-section-title">Buyer Dashboard</h1>
            <p class="app-section-subtitle">Track your site visits and appointment statuses.</p>
        </div>
        <a href="{{ route('buyer.wishlist.index') }}" class="btn btn-outline-primary btn-sm">My Wishlist</a>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12 col-md-6">
            <div class="card module-card h-100">
                <div class="card-body">
                    <div class="text-muted small">Reserved Listings</div>
                    <div class="h3 mb-0">{{ $reservedCount }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6">
            <div class="card module-card h-100">
                <div class="card-body">
                    <div class="text-muted small">Owned Listings</div>
                    <div class="h3 mb-0">{{ $ownedCount }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card module-card mb-3">
        <div class="card-header">My Reserved / Owned Listings</div>
        <div class="table-responsive">
            <table class="table module-table mb-0">
                <thead><tr><th>Listing</th><th>Property Type</th><th>Status</th><th>Price</th><th>Handled By</th></tr></thead>
                <tbody>
                @forelse($securedListings as $listing)
                    @php
                        $listingStatusClass = match ($listing->status) {
                            'reserved' => 'text-bg-warning',
                            'sold' => 'text-bg-dark',
                            default => 'text-bg-secondary',
                        };
                    @endphp
                    <tr>
                        <td>{{ $listing->title }}</td>
                        <td>{{ ucwords(str_replace('_', ' ', $listing->property_type ?? 'lot')) }}</td>
                        <td><span class="badge {{ $listingStatusClass }} status-badge">{{ ucfirst($listing->status) }}</span></td>
                        <td>₱{{ number_format($listing->price, 2) }}</td>
                        <td>{{ $listing->owner?->name ?? 'Agency listing' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="table-empty">No reserved or owned listings yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card module-card">
        <div class="card-header">My Appointments</div>
        <div class="table-responsive">
            <table class="table module-table mb-0">
                <thead><tr><th>Listing</th><th>Agent</th><th>Schedule</th><th>Status</th><th class="text-end">Action</th></tr></thead>
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
                    @endphp
                    <tr>
                        <td>{{ $appointment->lead->listing->title }}</td>
                        <td>{{ $appointment->assignedAgent?->name ?? 'Unassigned' }}</td>
                        <td>{{ $appointment->requested_start?->format('M d, Y h:i A') }}</td>
                        <td><span class="badge {{ $statusClass }} status-badge">{{ $appointmentStatusLabel }}</span></td>
                        <td class="text-end">
                            @if($appointment->status === 'pending')
                                <form method="POST" action="{{ route('buyer.appointments.cancel', $appointment) }}">
                                    @csrf @method('PATCH')
                                    <button class="btn btn-sm btn-outline-danger" type="submit">Cancel</button>
                                </form>
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="table-empty">No appointments yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
