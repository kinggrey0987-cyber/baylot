<x-app-layout>
    <div class="app-section-head">
        <div>
            <h1 class="app-section-title">Broker Dashboard</h1>
            <p class="app-section-subtitle">Manage your brokerage team, projects, and shared inventory.</p>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-md-4 col-lg-2">
            <div class="stat-card h-100">
                <p class="label">Team Agents</p>
                <p class="value">{{ $teamAgentCount }}</p>
            </div>
        </div>
        <div class="col-12 col-md-4 col-lg-2">
            <div class="stat-card h-100">
                <p class="label">Active Agents</p>
                <p class="value">{{ $activeTeamAgentCount }}</p>
            </div>
        </div>
        <div class="col-12 col-md-4 col-lg-2">
            <div class="stat-card h-100">
                <p class="label">Projects</p>
                <p class="value">{{ $projectCount }}</p>
            </div>
        </div>
        <div class="col-12 col-md-4 col-lg-2">
            <div class="stat-card h-100">
                <p class="label">Shared Listings</p>
                <p class="value">{{ $sharedListingsCount }}</p>
            </div>
        </div>
        <div class="col-12 col-md-4 col-lg-4">
            <div class="stat-card h-100">
                <p class="label">Open Shared Leads</p>
                <p class="value">{{ $openSharedLeadsCount }}</p>
            </div>
        </div>
    </div>

    <div class="card module-card mt-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Recent Shared Leads</span>
            <a href="{{ route('broker.shared-listings.index') }}" class="btn btn-sm btn-outline-primary">Manage Shared Listings</a>
        </div>
        <div class="table-responsive">
            <table class="table module-table mb-0">
                <thead><tr><th>Buyer</th><th>Listing</th><th>Project</th><th>Status</th><th>Requested</th></tr></thead>
                <tbody>
                    @forelse($recentSharedLeads as $lead)
                        <tr>
                            <td>{{ $lead->buyer?->name ?? 'Unknown buyer' }}</td>
                            <td>{{ $lead->listing?->title ?? 'Listing unavailable' }}</td>
                            <td>{{ $lead->listing?->project?->name ?? 'No project' }}</td>
                            <td><span class="badge text-bg-secondary status-badge">{{ ucfirst($lead->status) }}</span></td>
                            <td>{{ $lead->created_at?->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="table-empty">No shared leads yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
