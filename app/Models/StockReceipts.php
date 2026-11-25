<?php

namespace App\Models;

use App\Enums\ReceiptStatus;
use Carbon\CarbonInterface;
use Database\Factories\StockReceiptsFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int $supplier_id
 * @property string|null $purchase_order_number
 * @property string $received_by
 * @property string $reference_number
 * @property CarbonInterface $received_date
 * @property CarbonInterface $delivery_date
 * @property ReceiptStatus $status
 * @property string|null $remarks
 * @property int|null $parent_receipt_id
 * @property bool $is_donation
 * @property CarbonInterface|null $completed_at
 * @property string|null $closed_reason
 * @property-read Suppliers $supplier
 * @property-read User|null $receiver
 * @property-read StockReceipts|null $parent
 * @property-read Collection<int, StockReceipts> $followUps
 * @property-read Collection<int, StockReceiptLines> $lines
 * @property-read Collection<int, InventoryBatches> $inventoryBatches
 * @property-read Collection<int, Attachments> $attachments
 */
class StockReceipts extends Model
{
    /** @use HasFactory<StockReceiptsFactory> */
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'purchase_order_number',
        'received_by',
        'reference_number',
        'received_date',
        'delivery_date',
        'status',
        'remarks',
        'parent_receipt_id',
        'is_donation',
        'completed_at',
        'closed_reason',
    ];

    protected function casts(): array
    {
        return [
            'received_date' => 'datetime',
            'delivery_date' => 'datetime',
            'status' => ReceiptStatus::class,
            'is_donation' => 'boolean',
            'completed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Suppliers, $this> */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Suppliers::class, 'supplier_id');
    }

    /** @return BelongsTo<User, $this> */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    /** @return HasMany<InventoryBatches, $this> */
    public function inventoryBatches(): HasMany
    {
        return $this->hasMany(InventoryBatches::class, 'stock_receipt_id');
    }

    /** @return MorphMany<Attachments, $this> */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachments::class, 'attachable');
    }

    /** @return BelongsTo<StockReceipts, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_receipt_id');
    }

    /** @return HasMany<StockReceipts, $this> */
    public function followUps(): HasMany
    {
        return $this->hasMany(self::class, 'parent_receipt_id');
    }

    /** @return HasMany<StockReceiptLines, $this> */
    public function lines(): HasMany
    {
        return $this->hasMany(StockReceiptLines::class, 'stock_receipt_id');
    }

    public function isFollowUp(): bool
    {
        return $this->parent_receipt_id !== null;
    }

    /** The original receipt of this delivery chain (itself when it is the original). */
    public function root(): self
    {
        return $this->parent ?? $this;
    }

    /** Whether this receipt's delivery chain is still an open partial (can receive the remaining stock). */
    public function isChainOpen(): bool
    {
        return $this->root()->status === ReceiptStatus::Partial;
    }

    /** @param  Builder<StockReceipts>  $query */
    public function scopeRoots(Builder $query): void
    {
        $query->whereNull('parent_receipt_id');
    }

    /** @param  Builder<StockReceipts>  $query */
    public function scopeOpenPartial(Builder $query): void
    {
        $query->whereNull('parent_receipt_id')->where('status', ReceiptStatus::Partial);
    }

    /** @param  Builder<StockReceipts>  $query */
    public function scopeDonations(Builder $query): void
    {
        $query->where('is_donation', true);
    }

    /**
     * Turn attachments into display arrays (shared by every view that lists files).
     *
     * @param  iterable<Attachments>  $attachments
     * @return list<array{id: int, name: string, url: ?string, image: bool, pdf: bool, size: ?int}>
     */
    public static function attachmentFiles(iterable $attachments): array
    {
        $disk = Storage::disk('public');
        $files = [];

        foreach ($attachments as $attachment) {
            $exists = $disk->exists($attachment->path);
            $extension = strtolower(pathinfo($attachment->path, PATHINFO_EXTENSION));

            $files[] = [
                'id' => $attachment->id,
                'name' => basename($attachment->path),
                'url' => $exists ? $disk->url($attachment->path) : null,
                'image' => in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true),
                'pdf' => $extension === 'pdf',
                'size' => $exists ? $disk->size($attachment->path) : null,
            ];
        }

        return $files;
    }
}
