<?php

namespace App\Livewire\Portal;

use App\Models\Requisitions;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Public status lookup by tracking code. Shows quantities and stage dates
 * only — never batches or other internal stock details.
 */
class TrackRequest extends Component
{
    /** Lookups allowed per IP per minute. */
    public const MAX_LOOKUPS = 20;

    public string $code = '';

    public ?int $requisitionId = null;

    public bool $searched = false;

    #[On('track-request')]
    public function fromSubmission(string $code): void
    {
        $this->code = $code;
        $this->lookup();
    }

    public function lookup(): void
    {
        $this->validate(['code' => ['required', 'string', 'max:32']], attributes: ['code' => 'tracking number']);

        $key = 'track-request:'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, self::MAX_LOOKUPS)) {
            $this->addError('code', 'Too many lookups. Please wait '
                .RateLimiter::availableIn($key).' seconds and try again.');

            return;
        }

        RateLimiter::hit($key, 60);

        $this->code = strtoupper(trim($this->code));
        $this->requisitionId = Requisitions::query()->where('tracking_code', $this->code)->value('id');
        $this->searched = true;

        unset($this->requisition);
    }

    #[Computed]
    public function requisition(): ?Requisitions
    {
        return $this->requisitionId
            ? Requisitions::query()
                ->with(['items.inventoryItem', 'latestApproval', 'stockIssuances'])
                ->find($this->requisitionId)
            : null;
    }

    public function render()
    {
        return view('livewire.portal.track-request');
    }
}
