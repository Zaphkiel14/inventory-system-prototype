<?php

namespace App\Notifications;

use App\Models\Requisitions;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Confirmation sent to the requester right after they submit a request.
 */
class RequisitionReceived extends Notification implements ShouldQueue
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
        $requisition = $this->requisition;

        return (new MailMessage)
            ->subject("Request {$requisition->request_number} received")
            ->markdown('mail.requisition-received', [
                'requisition' => $requisition,
                'trackUrl' => url('/').'?code='.urlencode($requisition->tracking_code),
            ]);
    }
}
