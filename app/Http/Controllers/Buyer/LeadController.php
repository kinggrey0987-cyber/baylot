<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLeadRequest;
use App\Models\Appointment;
use App\Models\Lead;
use App\Models\LeadClaim;
use App\Models\Listing;
use App\Models\User;
use App\Notifications\NewLeadNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class LeadController extends Controller
{
    public function store(StoreLeadRequest $request, Listing $listing): RedirectResponse
    {
        abort_if($listing->status !== 'available', 404);

        DB::transaction(function () use ($request, $listing): void {
            $requestedAt = $request->filled('requested_at') ? $request->date('requested_at') : null;
            $isSiteVisit = ! is_null($requestedAt);
            $inquiryType = $isSiteVisit ? 'site_visit' : 'inquiry';

            $isExclusive = $listing->listing_type === 'exclusive';
            $assignedAgentId = $isExclusive ? $listing->owner_agent_id : null;

            $lead = Lead::create([
                'listing_id' => $listing->id,
                'buyer_id' => $request->user()->id,
                'message' => $request->string('message')->toString(),
                'status' => $isExclusive ? 'claimed' : 'new',
                           'inquiry_type' => $inquiryType,
            ]);

            if ($isExclusive && $assignedAgentId) {
                LeadClaim::create([
                    'lead_id' => $lead->id,
                    'agent_id' => $assignedAgentId,
                    'claimed_at' => now(),
                ]);
            }

            if ($isSiteVisit) {
                Appointment::create([
                    'lead_id' => $lead->id,
                    'assigned_agent_id' => $assignedAgentId,
                    'requested_start' => $requestedAt,
                    'requested_end' => $requestedAt->copy()->addHour(),
                    'status' => 'pending',
                ]);
            }

            $buyerMessage = $request->string('message')->toString() ?: null;

            $notification = new NewLeadNotification(
                leadId: $lead->id,
                listingTitle: $listing->title,
                projectName: $listing->project?->name,
                buyerName: $request->user()->name,
                requestedStart: $requestedAt,
                buyerMessage: $buyerMessage,
                inquiryType: $inquiryType,
                listingType: $listing->listing_type,
            );

            if ($isExclusive) {
                $ownerAgent = $listing->owner;
                if ($ownerAgent && $ownerAgent->is_active) {
                    Notification::send(collect([$ownerAgent]), $notification);
                }
            } else {
                $projectBrokerId = $listing->project?->broker_id;

                if ($projectBrokerId) {
                    $recipients = User::query()
                        ->where('is_active', true)
                        ->where(function ($query) use ($projectBrokerId): void {
                            $query->where(function ($q) use ($projectBrokerId): void {
                                $q->where('id', $projectBrokerId)->where('role', 'broker');
                            })->orWhere(function ($q) use ($projectBrokerId): void {
                                $q->where('role', 'agent')->where('broker_id', $projectBrokerId);
                            });
                        })
                        ->get();
                } else {
                    $recipients = User::query()
                        ->whereIn('role', ['agent', 'broker'])
                        ->where('is_active', true)
                        ->get();
                }

                if ($recipients->isNotEmpty()) {
                    Notification::send($recipients, $notification);
                }
            }
        });

        $successMessage = $request->filled('requested_at')
            ? 'Site visit request submitted. You can browse other available postings while waiting for confirmation.'
            : 'Your inquiry was sent. You will be contacted by the listing team shortly.';

        return redirect()->route('properties.index')->with('success', $successMessage);
    }
}
