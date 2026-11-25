<?php

namespace App\Livewire\Inventory;

use App\Models\StockReceipts;
use Flux\Flux;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class Receiving extends Component
{
    use WithPagination;

    public string $search = '';

    public int $perPage = 10;

    #[On('stock-received')]
    public function refreshReceipts(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /** Receipt shown in the read-only details modal. */
    public ?int $viewingId = null;

    public function view(int $receiptId): void
    {
        $this->viewingId = $receiptId;
        unset($this->viewing, $this->viewingAttachments);

        Flux::modal('view-receipt')->show();
    }

    #[Computed]
    public function viewing(): ?StockReceipts
    {
        if ($this->viewingId === null) {
            return null;
        }

        return StockReceipts::query()
            ->with([
                'supplier',
                'receiver',
                'attachments',
                'inventoryBatches' => fn ($q) => $q->orderBy('id'),
                'inventoryBatches.inventoryItem',
                'inventoryBatches.storageLocation',
            ])
            ->find($this->viewingId);
    }

    /**
     * The viewed receipt's files, ready for display.
     *
     * @return list<array{id: int, name: string, url: ?string, image: bool, pdf: bool, size: ?int}>
     */
    #[Computed]
    public function viewingAttachments(): array
    {
        $disk = Storage::disk('public');

        return ($this->viewing->attachments ?? collect())
            ->map(function ($attachment) use ($disk) {
                $exists = $disk->exists($attachment->path);
                $extension = strtolower(pathinfo($attachment->path, PATHINFO_EXTENSION));

                return [
                    'id' => $attachment->id,
                    'name' => basename($attachment->path),
                    'url' => $exists ? $disk->url($attachment->path) : null,
                    'image' => in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true),
                    'pdf' => $extension === 'pdf',
                    'size' => $exists ? $disk->size($attachment->path) : null,
                ];
            })
            ->values()
            ->all();
    }

    #[Computed]
    public function receipts()
    {
        return StockReceipts::query()
            ->with(['supplier', 'receiver'])
            ->withCount('inventoryBatches')
            ->withSum('inventoryBatches', 'received_qty')
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('reference_number', 'like', "%{$this->search}%")
                        ->orWhere('purchase_order_number', 'like', "%{$this->search}%")
                        ->orWhereHas('supplier', fn ($q) => $q->where('name', 'like', "%{$this->search}%"));
                });
            })
            ->latest('received_date')
            ->latest('id')
            ->paginate($this->perPage);
    }

    public function render()
    {
        return view('livewire.inventory.receiving');
    }
}
