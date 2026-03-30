<?php

namespace App\Http\Controllers;

use App\Http\Requests\ScheduleLeadVisitFromConversationRequest;
use App\Http\Requests\StoreLeadConversationMessageRequest;
use App\Models\Appointment;
use App\Models\Lead;
use App\Models\LeadClaim;
use App\Models\LeadMessage;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class LeadConversationController extends Controller
{
    public function show(Lead $lead): View
    {
        Gate::authorize('view', $lead);

        $lead->load([
            'listing.project',
            'listing.owner',
            'buyer',
            'claim.agent',
            'appointment.assignedAgent',
            'messages.sender',
        ]);

        $actor = auth()->user();
        $this->ensureConversationAccess($lead, $actor);

        return view('leads.conversation.show', [
            'lead' => $lead,
            'timeline' => $this->buildTimeline($lead),
            'hasBuyerMessage' => $this->leadHasBuyerMessage($lead),
            'requiresManualClaimBeforeReply' => $this->requiresManualClaimBeforeReply($lead, $actor),
        ]);
    }

    public function storeMessage(StoreLeadConversationMessageRequest $request, Lead $lead): RedirectResponse
    {
        Gate::authorize('view', $lead);

        DB::transaction(function () use ($request, $lead): void {
            $lockedLead = Lead::with(['listing.project', 'buyer', 'claim', 'appointment'])
                ->lockForUpdate()
                ->findOrFail($lead->id);

            $actor = $request->user();
            $this->ensureConversationAccess($lockedLead, $actor);

            if ($actor->isAgent()) {
                $lockedLead = $this->claimSharedLeadIfNeeded($lockedLead, $actor);
            }

            LeadMessage::create([
                'lead_id' => $lockedLead->id,
                'sender_id' => $actor->id,
                'message' => $request->string('message')->toString(),
            ]);
        });

        return back()->with('success', 'Message sent.');
    }

    public function scheduleVisit(ScheduleLeadVisitFromConversationRequest $request, Lead $lead): RedirectResponse
    {
        Gate::authorize('view', $lead);

        DB::transaction(function () use ($request, $lead): void {
            $lockedLead = Lead::with(['listing.project', 'buyer', 'claim', 'appointment'])
                ->lockForUpdate()
                ->findOrFail($lead->id);

            $actor = $request->user();
            $this->ensureConversationAccess($lockedLead, $actor);

            abort_unless($actor->isBuyer(), 403, 'Only buyers can set the site visit schedule.');

            $requestedAt = $request->date('requested_at');
            $appointment = $lockedLead->appointment;
            $assignedAgentId = $appointment?->assigned_agent_id
                ?? $lockedLead->claim?->agent_id
                ?? ($lockedLead->listing->listing_type === 'exclusive' ? $lockedLead->listing->owner_agent_id : null);

            if ($appointment) {
                $appointment->update([
                    'assigned_agent_id' => $assignedAgentId,
                    'requested_start' => $requestedAt,
                    'requested_end' => $requestedAt?->copy()->addHour(),
                    'status' => 'pending',
                ]);
            } else {
                Appointment::create([
                    'lead_id' => $lockedLead->id,
                    'assigned_agent_id' => $assignedAgentId,
                    'requested_start' => $requestedAt,
                    'requested_end' => $requestedAt?->copy()->addHour(),
                    'status' => 'pending',
                ]);
            }

            $note = trim($request->string('note')->toString());
            $message = 'Buyer requested a site visit on '.$requestedAt?->format('M d, Y h:i A').'.';

            if ($note !== '') {
                $message .= "\n\n".$note;
            }

            LeadMessage::create([
                'lead_id' => $lockedLead->id,
                'sender_id' => $actor->id,
                'message' => $message,
            ]);
        });

        return back()->with('success', 'Site visit schedule updated.');
    }

    private function ensureConversationAccess(Lead $lead, User $actor): void
    {
        if ($actor->isBuyer()) {
            abort_if($lead->buyer_id !== $actor->id, 403);

            return;
        }

        abort_unless($actor->isAgent(), 403);

        $lead->loadMissing(['listing.project', 'claim']);
        $listing = $lead->listing;

        if ($listing->listing_type === 'exclusive') {
            abort_if($listing->owner_agent_id !== $actor->id, 403);

            return;
        }

        $projectBrokerId = $listing->project?->broker_id;
        if (! is_null($projectBrokerId)) {
            abort_if(is_null($actor->broker_id) || $actor->broker_id !== $projectBrokerId, 403);
        }

        if ($lead->claim && $lead->claim->agent_id !== $actor->id) {
            abort(403, 'This conversation is already assigned to another agent.');
        }
    }

    private function claimSharedLeadIfNeeded(Lead $lead, User $agent): Lead
    {
        if ($lead->listing->listing_type !== 'shared') {
            return $lead;
        }

        if ($lead->claim) {
            abort_if($lead->claim->agent_id !== $agent->id, 403, 'This conversation is already assigned to another agent.');

            return $lead;
        }

        if (! $this->leadHasBuyerMessage($lead)) {
            abort(422, 'This site-visit-only request must be claimed from Lead Queue before replying.');
        }

        LeadClaim::create([
            'lead_id' => $lead->id,
            'agent_id' => $agent->id,
            'claimed_at' => now(),
        ]);

        $lead->update(['status' => 'claimed']);

        if ($lead->appointment && is_null($lead->appointment->assigned_agent_id)) {
            $lead->appointment->update(['assigned_agent_id' => $agent->id]);
        }

        return $lead->refresh()->loadMissing(['claim', 'appointment']);
    }

    private function requiresManualClaimBeforeReply(Lead $lead, User $actor): bool
    {
        if (! $actor->isAgent()) {
            return false;
        }

        return $lead->listing->listing_type === 'shared'
            && ! $lead->claim
            && ! $this->leadHasBuyerMessage($lead);
    }

    private function leadHasBuyerMessage(Lead $lead): bool
    {
        if (filled($lead->message)) {
            return true;
        }

        return $lead->messages()
            ->where('sender_id', $lead->buyer_id)
            ->exists();
    }

    private function buildTimeline(Lead $lead): Collection
    {
        $timeline = $lead->messages
            ->map(function (LeadMessage $message): array {
                return [
                    'sender_id' => $message->sender_id,
                    'sender_name' => $message->sender?->name ?? 'Unknown user',
                    'message' => $message->message,
                    'created_at' => $message->created_at,
                ];
            });

        $hasStoredInitialMessage = $timeline->contains(function (array $item) use ($lead): bool {
            return $item['sender_id'] === $lead->buyer_id
                && trim($item['message']) === trim((string) $lead->message);
        });

        if (filled($lead->message) && ! $hasStoredInitialMessage) {
            $timeline->push([
                'sender_id' => $lead->buyer_id,
                'sender_name' => $lead->buyer?->name ?? 'Buyer',
                'message' => $lead->message,
                'created_at' => $lead->created_at,
            ]);
        }

        return $timeline->sortBy('created_at')->values();
    }
}
