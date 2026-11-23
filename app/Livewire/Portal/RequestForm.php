<?php

namespace App\Livewire\Portal;

use App\Livewire\Forms\RequisitionsForm;
use App\Models\InventoryItems;
use App\Services\AdministrativeAreas\AdministrativeAreaService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Throwable;

/**
 * Public request form: fill in details and items, review a summary, then
 * submit and receive a tracking code.
 */
class RequestForm extends Component
{
    /** Submissions allowed per IP per minute. */
    public const MAX_SUBMISSIONS = 5;

    public RequisitionsForm $form;

    /** 'form' → 'review' → 'done' */
    public string $step = 'form';

    public ?string $trackingCode = null;

    public ?string $requestNumber = null;

    /** Email the requester asked to be notified at, kept for the done step. */
    public ?string $notifyEmail = null;

    public function mount(): void
    {
        $this->form->initialize();
    }

    #[Computed]
    public function inventoryItems(): Collection
    {
        return InventoryItems::query()
            ->where('is_active', true)
            ->withAvailableQty()
            ->orderBy('name')
            ->get()
            ->keyBy('id');
    }

    /**
     * Designation choices from the administrative-areas API, or null when the
     * API is unreachable (the form then shows an error and can't be submitted).
     *
     * @return Collection<int, string>|null
     */
    #[Computed]
    public function areaOptions(): ?Collection
    {
        try {
            return app(AdministrativeAreaService::class)->options();
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }

    /**
     * The same designations grouped by parent for the select's option groups.
     * Only rendered once areaOptions loaded, so an empty list on failure is fine.
     *
     * @return Collection<int, array{label: string, options: Collection<int, string>}>
     */
    #[Computed]
    public function areaGroups(): Collection
    {
        try {
            return app(AdministrativeAreaService::class)->groupedOptions();
        } catch (Throwable $e) {
            report($e);

            return collect();
        }
    }

    public function addItem(): void
    {
        $this->form->addItem();
    }

    public function removeItem(int $index): void
    {
        if (count($this->form->items) > 1) {
            $this->form->removeItem($index);
        }
    }

    public function review(): void
    {
        $this->form->validate();

        $this->step = 'review';
    }

    public function edit(): void
    {
        $this->step = 'form';
    }

    public function submit(): void
    {
        $key = 'public-request:'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, self::MAX_SUBMISSIONS)) {
            $this->addError('submit', 'Too many requests submitted. Please wait '
                .RateLimiter::availableIn($key).' seconds and try again.');

            return;
        }

        try {
            $requisition = $this->form->store();
        } catch (ValidationException $e) {
            // Something changed since the review (e.g. an item was deactivated).
            $this->step = 'form';

            throw $e;
        }

        RateLimiter::hit($key, 60);

        $this->trackingCode = $requisition->tracking_code;
        $this->requestNumber = $requisition->request_number;
        $this->notifyEmail = filled($this->form->requester_email) ? $this->form->requester_email : null;
        $this->step = 'done';

        $this->form->initialize();
        unset($this->inventoryItems);
    }

    public function track(): void
    {
        $this->dispatch('track-request', code: $this->trackingCode);
    }

    public function startOver(): void
    {
        $this->reset('trackingCode', 'requestNumber', 'notifyEmail');
        $this->resetErrorBag();
        $this->step = 'form';
    }

    public function render()
    {
        return view('livewire.portal.request-form');
    }
}
