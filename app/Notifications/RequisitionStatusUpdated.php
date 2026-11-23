<?php

namespace App\Notifications;

use App\Models\Requisitions;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to the requester whenever the status of their request changes.
 * Carries the same information as the public tracker: status, quantities and
 * dates, never batch details.
 */
class RequisitionStatusUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Requisitions $requisition)
    {
        $this->afterCommit();
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $requisition = $this->requisition->loadMissing(['items.inventoryItem', 'latestApproval', 'stockIssuances']);
        $status = $requisition->status;

        return (new MailMessage)
            ->subject("Request {$requisition->request_number}: {$status->label()}")
            ->markdown('mail.requisition-status-updated', [
                'requisition' => $requisition,
                'status' => $status,
                'decidedAt' => $requisition->latestApproval?->decided_at,
                'releasedAt' => $requisition->stockIssuances->max('released_at'),
                'trackUrl' => url('/').'?code='.urlencode($requisition->tracking_code),
            ]);
    }
}
