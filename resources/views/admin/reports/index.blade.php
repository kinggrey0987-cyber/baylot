<x-app-layout>
    <div class="app-section-head">
        <div>
            <h1 class="app-section-title">Reports</h1>
            <p class="app-section-subtitle">Track listing statuses, agent activity, appointments, and lead queue.</p>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-lg-4">
            <div class="card module-card">
                <div class="card-header">Listings by Status</div>
                <ul class="list-group list-group-flush">
                    @foreach($listingStatus as $status => $total)
                        <li class="list-group-item d-flex justify-content-between"><span class="text-capitalize">{{ $status }}</span><strong>{{ $total }}</strong></li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="card module-card">
                <div class="card-header">Agent Activity</div>
                <ul class="list-group list-group-flush">
                    @foreach($agentActivity as $row)
                        <li class="list-group-item d-flex justify-content-between"><span>{{ $row->name }}</span><strong>{{ $row->total_claims }}</strong></li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="card module-card">
                <div class="card-header">Appointments Overview</div>
                <ul class="list-group list-group-flush">
                    @foreach($appointmentsOverview as $status => $total)
                        <li class="list-group-item d-flex justify-content-between"><span class="text-capitalize">{{ $status }}</span><strong>{{ $total }}</strong></li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>

    <div class="card module-card mt-3">
        <div class="card-header">Shared Leads Queue</div>
        <div class="table-responsive">
            <table class="table module-table mb-0">
                <thead><tr><th>Listing</th><th>Project</th><th>Buyer</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse($sharedQueue as $lead)
                        @php
                            $statusClass = match ($lead->status) {
                                'pending' => 'text-bg-warning',
                                'claimed', 'approved', 'completed' => 'text-bg-success',
                                'cancelled', 'rejected' => 'text-bg-danger',
                                default => 'text-bg-secondary',
                            };
                        @endphp
                        <tr>
                            <td>{{ $lead->listing->title }}</td>
                            <td>{{ $lead->listing->project?->name }}</td>
                            <td>{{ $lead->buyer->name }}</td>
                            <td><span class="badge {{ $statusClass }} status-badge">{{ $lead->status }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="table-empty">No shared leads in queue.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
