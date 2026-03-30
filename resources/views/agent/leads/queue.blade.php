<x-app-layout>
    <div class="app-section-head">
        <div>
            <h1 class="app-section-title">Lead Queue</h1>
            <p class="app-section-subtitle">Reply to buyer chats, claim site-visit-only requests, and track active conversations.</p>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-lg-7">
            <div class="card module-card">
                <div class="card-header">Claimable Shared Leads</div>
                <div class="table-responsive">
                    <table class="table module-table mb-0">
                        <thead><tr><th>Buyer</th><th>Listing</th><th>Buyer Message</th><th>Project</th><th class="text-end">Action</th></tr></thead>
                        <tbody>
                        @forelse($claimableLeads as $lead)
                            @php
                                $latestBuyerMessage = $lead->messages
                                    ->where('sender_id', $lead->buyer_id)
                                    ->sortByDesc('created_at')
                                    ->first();

                                $hasBuyerMessage = filled($lead->message) || ! is_null($latestBuyerMessage);
                                $messagePreview = $latestBuyerMessage?->message ?: $lead->message;
                            @endphp
                            <tr>
                                <td>{{ $lead->buyer->name }}</td>
                                <td>{{ $lead->listing->title }}</td>
                                <td class="small">
                                    @if($hasBuyerMessage)
                                        {{ \Illuminate\Support\Str::limit($messagePreview, 90) }}
                                        <div class="text-muted">Replying in chat auto-claims this lead.</div>
                                    @else
                                        <span class="text-muted">No message yet (site visit only).</span>
                                    @endif
                                </td>
                                <td>{{ $lead->listing->project?->name }}</td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-2 flex-wrap">
                                        <a class="btn btn-sm btn-outline-primary" href="{{ route('leads.conversation.show', $lead) }}">Open Chat</a>
                                        @if(! $hasBuyerMessage)
                                            <form method="POST" action="{{ route('agent.leads.claim', $lead) }}">
                                                @csrf
                                                <button class="btn btn-sm btn-primary" type="submit">Claim</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="table-empty">No claimable leads.</td></tr>
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
                        <li class="list-group-item d-flex justify-content-between align-items-center gap-2">
                            <div>
                                <div>{{ $lead->listing->title }} - {{ $lead->buyer->name }}</div>
                                <div class="small text-muted">Updated {{ $lead->updated_at?->diffForHumans() }}</div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge text-bg-success status-badge">claimed</span>
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('leads.conversation.show', $lead) }}">Open Chat</a>
                            </div>
                        </li>
                    @empty
                        <li class="list-group-item table-empty">No claimed leads yet.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</x-app-layout>
