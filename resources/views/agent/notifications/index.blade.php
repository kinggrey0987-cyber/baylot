<x-app-layout>
    <div class="app-section-head d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h1 class="app-section-title">Notifications</h1>
            <p class="app-section-subtitle">Review new shared leads that are ready to claim.</p>
        </div>

        @if($unreadNotificationsCount > 0)
            <form method="POST" action="{{ route('agent.notifications.read-all') }}" class="module-form">
                @csrf @method('PATCH')
                <button class="btn btn-outline-primary btn-sm" type="submit">Mark all as read ({{ $unreadNotificationsCount }})</button>
            </form>
        @endif
    </div>

    <div class="card module-card">
        <div class="table-responsive">
            <table class="table module-table mb-0">
                <thead>
                    <tr>
                        <th>Lead Alert</th>
                        <th>Requested Visit</th>
                        <th>Received</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($notifications as $notification)
                        @php
                            $data = $notification->data;
                            $requestedStart = $data['requested_start'] ?? null;
                        @endphp
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $data['title'] ?? 'New lead notification' }}</div>
                                <div>{{ $data['listing_title'] ?? 'Listing' }}</div>
                                <div class="small text-muted">Project: {{ $data['project_name'] ?? 'Shared Project' }} | Buyer: {{ $data['buyer_name'] ?? 'Unknown' }}</div>
                                @if(! empty($data['buyer_message']))
                                    <div class="small text-muted">Message: {{ $data['buyer_message'] }}</div>
                                @endif
                            </td>
                            <td>
                                {{ $requestedStart ? \Illuminate\Support\Carbon::parse($requestedStart)->format('M d, Y h:i A') : 'Not specified' }}
                            </td>
                            <td>{{ $notification->created_at?->diffForHumans() }}</td>
                            <td>
                                <span class="badge {{ $notification->read_at ? 'text-bg-secondary' : 'text-bg-primary' }} status-badge">
                                    {{ $notification->read_at ? 'Read' : 'Unread' }}
                                </span>
                            </td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-primary" href="{{ $data['action_url'] ?? route('agent.leads.queue') }}">Open Queue</a>
                                @if(is_null($notification->read_at))
                                    <form method="POST" action="{{ route('agent.notifications.read', $notification) }}" class="d-inline-block">
                                        @csrf @method('PATCH')
                                        <button class="btn btn-sm btn-outline-secondary" type="submit">Mark read</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="table-empty">No notifications yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="card-body">
            {{ $notifications->links() }}
        </div>
    </div>
</x-app-layout>
