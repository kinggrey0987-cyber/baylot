<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Carbon\CarbonInterface;

class NewSharedLeadNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $leadId,
        private readonly string $listingTitle,
        private readonly ?string $projectName,
        private readonly string $buyerName,
        private readonly ?CarbonInterface $requestedStart,
        private readonly ?string $buyerMessage,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'New shared lead is ready to claim',
            'lead_id' => $this->leadId,
            'listing_title' => $this->listingTitle,
            'project_name' => $this->projectName,
            'buyer_name' => $this->buyerName,
            'requested_start' => $this->requestedStart?->toIso8601String(),
            'buyer_message' => $this->buyerMessage,
            'action_url' => route('agent.leads.queue'),
        ];
    }
}
