<?php

namespace App\Notifications;

use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewLeadNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $leadId,
        private readonly string $listingTitle,
        private readonly ?string $projectName,
        private readonly string $buyerName,
        private readonly ?CarbonInterface $requestedStart,
        private readonly ?string $buyerMessage,
        private readonly string $inquiryType = 'site_visit',
        private readonly string $listingType = 'shared',
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject($this->getSubject())
            ->view('emails.new-lead', [
                'recipientName' => $notifiable->name,
                'listingTitle' => $this->listingTitle,
                'projectName' => $this->projectName,
                'buyerName' => $this->buyerName,
                'requestedStart' => $this->requestedStart,
                'buyerMessage' => $this->buyerMessage,
                'inquiryType' => $this->inquiryType,
                'listingType' => $this->listingType,
                'actionUrl' => $this->resolveActionUrl($notifiable),
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->getTitle(),
            'lead_id' => $this->leadId,
            'listing_title' => $this->listingTitle,
            'project_name' => $this->projectName,
            'buyer_name' => $this->buyerName,
            'requested_start' => $this->requestedStart?->toIso8601String(),
            'buyer_message' => $this->buyerMessage,
            'inquiry_type' => $this->inquiryType,
            'listing_type' => $this->listingType,
            'action_url' => $this->resolveActionUrl($notifiable),
        ];
    }

    private function getTitle(): string
    {
        if ($this->inquiryType === 'inquiry') {
            return $this->listingType === 'exclusive'
                ? 'New inquiry on your exclusive listing'
                : 'New buyer inquiry on a shared listing';
        }

        return $this->listingType === 'exclusive'
            ? 'New site visit request on your listing'
            : 'New shared lead is ready to claim';
    }

    private function getSubject(): string
    {
        return $this->getTitle().' — '.$this->listingTitle;
    }

    private function resolveActionUrl(object $notifiable): string
    {
        if (method_exists($notifiable, 'isBroker') && $notifiable->isBroker()) {
            return route('broker.dashboard');
        }

        return route('agent.leads.queue');
    }
}
