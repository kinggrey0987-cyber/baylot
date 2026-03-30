<x-app-layout>
    <div class="app-section-head">
        <div>
            <h1 class="app-section-title">Lead Queue</h1>
            <p class="app-section-subtitle">Claim shared leads and track your active claims.</p>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-lg-7">
            <div class="card module-card">
                <div class="card-header">Claimable Shared Leads</div>
                <div class="table-responsive">
                    <table class="table module-table mb-0">
                        <thead><tr><th>Buyer</th><th>Listing</th><th>Project</th><th class="text-end">Action</th></tr></thead>
                        <tbody>
                        @forelse($claimableLeads as $lead)
                            <tr>
                                <td>{{ $lead->buyer->name }}</td>
                                <td>{{ $lead->listing->title }}</td>
                                <td>{{ $lead->listing->project?->name }}</td>
                                <td class="text-end">
                                    <form method="POST" action="{{ route('agent.leads.claim', $lead) }}">
                                        @csrf
                                        <button class="btn btn-sm btn-primary" type="submit">Claim</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="table-empty">No claimable leads.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-5">
            <div class="card module-card">
                <div class="card-header">My Claimed Leads</div>
                <ul class="list-group list-group-flush">
                    @forelse($claimedLeads as $lead)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>{{ $lead->listing->title }} - {{ $lead->buyer->name }}</span>
                            <span class="badge text-bg-success status-badge">claimed</span>
                        </li>
                    @empty
                        <li class="list-group-item table-empty">No claimed leads yet.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</x-app-layout>
