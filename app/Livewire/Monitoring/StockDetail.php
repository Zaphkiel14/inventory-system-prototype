<?php

namespace App\Livewire\Monitoring;

use App\Models\InventoryBatches;
use App\Models\InventoryItems;
use App\Services\Inventory\StockFigures;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * One Stock Monitoring row's drill-down: batches in issuance order (with the
 * stock reserved for approved requests), balance trend and recent movement.
 *
 * Mounted lazily inside each (hidden) row of the list and loaded the first
 * time the row is opened, so expanding a row never re-renders the list.
 *
 * @property-read array{item: InventoryItems, method: string, batches: array<int, array{batch: InventoryBatches, order: ?int, reserved: int, expired: bool}>, trend: list<array{date: string, balance: int}>, movement: array{received: int, issued: int, adjusted: int}}|null $detail
 */
class StockDetail extends Component
{
    #[Locked]
    public int $itemId;

    /**
     * The drill-down for the item, or null when it isn't an active item.
     * The item carries the same decorated row fields as the list's rows.
     *
     * @return array{item: InventoryItems, method: string, batches: array<int, array{batch: InventoryBatches, order: ?int, reserved: int, expired: bool}>, trend: list<array{date: string, balance: int}>, movement: array{received: int, issued: int, adjusted: int}}|null
     */
    #[Computed]
    public function detail(): ?array
    {
        $item = InventoryItems::query()->where('is_active', true)->find($this->itemId);

        if (! $item) {
            return null;
        }

        $figures = app(StockFigures::class);

        return $figures->detail($figures->decorate($item, $figures->all()[$item->id] ?? null));
    }

    /**
     * Skeleton shown until the row's detail loads.
     *
     * @param  array<string, mixed>  $params
     */
    public function placeholder(array $params = []): View
    {
        return view('livewire.monitoring.placeholders.stock-detail');
    }

    public function render(): View
    {
        return view('livewire.monitoring.stock-detail');
    }
}
