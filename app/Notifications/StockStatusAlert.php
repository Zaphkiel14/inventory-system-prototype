<?php

namespace App\Notifications;

use App\Enums\StockStatus;
use App\Models\InventoryItems;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

/**
 * Sent to opted-in users when one action (an issuance, receipt, adjustment or
 * the daily check) makes one or more items drop into Reorder, Critical or Out
 * of stock. Lists every item that got worse.
 */
class StockStatusAlert extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Plain rows rather than models, so the queued job carries exactly what
     * was true when the alert was raised.
     *
     * @var array<int, array{item_code: string, name: string, unit: string|null, previous_status: string, previous_label: string, status: string, status_label: string, available_qty: int, minimum_stock: int, reorder_level: int}>
     */
    public array $items;

    /**
     * @param  Collection<int, InventoryItems>  $items  items with `previous_status` and `stock_status` set
     */
    public function __construct(Collection $items)
    {
        $this->items = $items->map(function (InventoryItems $item): array {
            /** @var StockStatus $previous */
            $previous = $item->getAttribute('previous_status');
            /** @var StockStatus $status */
            $status = $item->getAttribute('stock_status');

            return [
                'item_code' => (string) $item->item_code,
                'name' => (string) $item->name,
                'unit' => $item->unit,
                'previous_status' => $previous->value,
                'previous_label' => $previous->label(),
                'status' => $status->value,
                'status_label' => $status->label(),
                'available_qty' => (int) $item->getAttribute('available_qty'),
                'minimum_stock' => (int) $item->minimum_stock,
                'reorder_level' => (int) $item->reorder_level,
            ];
        })->values()->all();

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
        return (new MailMessage)
            ->subject($this->subject())
            ->markdown('mail.stock-status-alert', [
                'items' => $this->items,
                'monitoringUrl' => route('monitoring.stock-monitoring'),
            ]);
    }

    public function subject(): string
    {
        if (count($this->items) === 1) {
            $item = $this->items[0];

            return "Stock alert: {$item['name']} is {$item['status_label']}";
        }

        return 'Stock alert: '.count($this->items).' items need attention';
    }
}
