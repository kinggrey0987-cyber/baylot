<x-app-layout>
    @php
        $isSharedLead = $lead->listing->listing_type === 'shared';

        $leadStatusClass = match ($lead->status) {
            'claimed' => 'text-bg-success',
            'new' => 'text-bg-warning',
            'closed' => 'text-bg-dark',
            'cancelled' => 'text-bg-danger',
            default => 'text-bg-secondary',
        };

        $appointmentStatusClass = match ($lead->appointment?->status) {
            'approved', 'completed' => 'text-bg-success',
            'pending', 'rescheduled' => 'text-bg-warning',
            'cancelled', 'rejected' => 'text-bg-danger',
            default => 'text-bg-secondary',
        };

        $canAgentConfirmSchedule = auth()->user()->isAgent()
            && $lead->appointment
            && $lead->appointment->assigned_agent_id === auth()->id()
            && in_array($lead->appointment->status, ['pending', 'rescheduled'], true);
    @endphp

    <div class="app-section-head d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h1 class="app-section-title">Lead Conversation</h1>
            <p class="app-section-subtitle">{{ $lead->listing->title }} - {{ $lead->buyer->name }}</p>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="badge {{ $leadStatusClass }} status-badge">{{ ucfirst($lead->status) }}</span>
            @if($lead->claim)
                <span class="badge text-bg-info status-badge">Handled by {{ $lead->claim->agent?->name ?? 'Agent' }}</span>
            @else
                <span class="badge text-bg-secondary status-badge">Unclaimed</span>
            @endif
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-lg-8">
            <div class="card module-card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span>Messages</span>
                    @if(auth()->user()->isAgent() && $isSharedLead && ! $lead->claim && $hasBuyerMessage)
                        <span class="small text-muted">Your first reply will auto-claim this lead.</span>
                    @endif
                </div>

                <div class="card-body" style="max-height: 520px; overflow-y: auto;">
                    @forelse($timeline as $entry)
                        @php
                            $isMine = $entry['sender_id'] === auth()->id();
                            $bubbleClass = $isMine ? 'bg-primary text-white' : 'bg-light border';
                            $metaClass = $isMine ? 'text-white-50' : 'text-muted';
                        @endphp

                        <div class="d-flex {{ $isMine ? 'justify-content-end' : 'justify-content-start' }} mb-3">
                            <div class="rounded-3 p-3 {{ $bubbleClass }}" style="max-width: 85%;">
                                <div class="small {{ $metaClass }} fw-semibold mb-1">
                                    {{ $entry['sender_name'] }} - {{ $entry['created_at']?->format('M d, Y h:i A') }}
                                </div>
                                <div>{!! nl2br(e($entry['message'])) !!}</div>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted mb-0">No messages yet.</p>
                    @endforelse
                </div>
            </div>

            <div class="card module-card mt-3">
                <div class="card-header">Send Message</div>
                <div class="card-body">
                    @if($requiresManualClaimBeforeReply)
                        <div class="alert alert-warning mb-0">
                            This lead has no buyer message yet and is currently site-visit-only. Claim it from Lead Queue first before replying.
                        </div>
                    @else
                        <form method="POST" action="{{ route('leads.conversation.messages.store', $lead) }}" class="row g-2">
                            @csrf
                            <div class="col-12">
                                <textarea class="form-control" name="message" rows="4" maxlength="2000" required placeholder="Type your message or answer here...">{{ old('message') }}</textarea>
                            </div>
                            <div class="col-12 d-flex justify-content-end">
                                <button class="btn btn-primary" type="submit">Send Message</button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="card module-card">
                <div class="card-header">Site Visit Scheduler</div>
                <div class="card-body">
                    @if(auth()->user()->isBuyer())
                        @if($lead->appointment)
                            <div class="mb-3">
                                <div class="small text-muted">Current Schedule</div>
                                <div class="fw-semibold">{{ $lead->appointment->requested_start?->format('M d, Y h:i A') }}</div>
                                <div class="small mt-1">
                                    Status:
                                    <span class="badge {{ $appointmentStatusClass }} status-badge">{{ ucfirst($lead->appointment->status) }}</span>
                                </div>
                            </div>
                        @else
                            <p class="text-muted small">No site visit schedule yet.</p>
                        @endif

                        <form id="conversationScheduleForm" method="POST" action="{{ route('leads.conversation.site-visit.store', $lead) }}" class="row g-2">
                            @csrf
                            <div class="col-12">
                                <label class="form-label">Preferred Date &amp; Time</label>
                                <input id="conversationRequestedAt" class="form-control" type="datetime-local" name="requested_at" value="{{ old('requested_at') }}" step="900" required>
                                <div class="form-text">Working hours: 8:00 AM to 5:00 PM (last start time: 4:00 PM).</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Note (optional)</label>
                                <textarea class="form-control" name="note" rows="3" maxlength="500" placeholder="Add context for this schedule request">{{ old('note') }}</textarea>
                            </div>
                            <div class="col-12 d-grid">
                                <button class="btn btn-outline-primary" type="submit">Save Schedule</button>
                            </div>
                        </form>
                    @else
                        @if($lead->appointment)
                            <div class="mb-3">
                                <div class="small text-muted">Buyer-selected Schedule</div>
                                <div class="fw-semibold">{{ $lead->appointment->requested_start?->format('M d, Y h:i A') }}</div>
                                <div class="small mt-1">
                                    Status:
                                    <span class="badge {{ $appointmentStatusClass }} status-badge">{{ ucfirst($lead->appointment->status) }}</span>
                                </div>
                            </div>

                            @if($canAgentConfirmSchedule)
                                <form method="POST" action="{{ route('agent.appointments.update', $lead->appointment) }}" class="d-grid">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="status" value="approved">
                                    <button class="btn btn-outline-success" type="submit">Confirm Schedule</button>
                                </form>
                            @else
                                <div class="small text-muted">Use the Appointments module for status updates.</div>
                            @endif
                        @else
                            <p class="text-muted small mb-0">Waiting for buyer to set a schedule.</p>
                        @endif
                    @endif
                </div>
            </div>

            <div class="card module-card mt-3">
                <div class="card-header">Lead Details</div>
                <div class="card-body small">
                    <div class="mb-2">
                        <div class="text-muted">Inquiry Type</div>
                        <div>{{ $lead->inquiry_type === 'inquiry' ? 'Inquiry' : 'Site Visit' }}</div>
                    </div>
                    <div class="mb-2">
                        <div class="text-muted">Buyer</div>
                        <div>{{ $lead->buyer->name }}</div>
                    </div>
                    <div class="mb-3">
                        <div class="text-muted">Listing</div>
                        <div>{{ $lead->listing->title }}</div>
                    </div>
                    <a class="btn btn-sm btn-outline-secondary w-100" href="{{ route('listings.show', $lead->listing) }}">View Listing</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const scheduleForm = document.getElementById('conversationScheduleForm');
            const scheduleInput = document.getElementById('conversationRequestedAt');

            if (!scheduleForm || !scheduleInput) {
                return;
            }

            const validateWorkingHours = () => {
                if (!scheduleInput.value) {
                    scheduleInput.setCustomValidity('');
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

            scheduleInput.addEventListener('change', validateWorkingHours);

            scheduleForm.addEventListener('submit', (event) => {
                if (!validateWorkingHours()) {
                    event.preventDefault();
                    scheduleInput.reportValidity();
                }
            });
        });
    </script>
</x-app-layout>
