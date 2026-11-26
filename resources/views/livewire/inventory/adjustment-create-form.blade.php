<flux:modal name="create-adjustment" class="w-[720px] max-w-[95vw]">
    <div class="space-y-6">

        <div>
            <flux:heading size="lg">New Stock Adjustment</flux:heading>
            <flux:text class="mt-1">
                Adjustments are permanent. To fix a mistake, record another adjustment.
            </flux:text>
        </div>

        <form wire:submit="save" class="space-y-6">

            <div class="grid items-start gap-4 sm:grid-cols-2">
                <flux:select wire:model.live="form.type" label="Adjustment Type" placeholder="Select type">
                    @foreach (\App\Enums\AdjustmentType::cases() as $type)
                        <flux:select.option value="{{ $type->value }}">{{ $type->label() }}</flux:select.option>
                    @endforeach
                </flux:select>

                @php $batchOptions = $this->batches; @endphp

                <div class="flex flex-col gap-2">
                    <flux:select wire:model.live="form.batch_id" label="Batch" placeholder="Select batch"
                        :disabled="$batchOptions->isEmpty()">
                        @foreach ($batchOptions as $batch)
                            <flux:select.option value="{{ $batch->id }}" wire:key="batch-option-{{ $batch->id }}">
                                {{ $batch->batch_number ?? 'No batch no.' }} — {{ $batch->inventoryItem?->name }}
                                ({{ number_format($batch->remaining_qty) }} left)
                            </flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:input size="sm" icon="magnifying-glass" placeholder="Search batch, lot or item…"
                        aria-label="Search batches" wire:model.live.debounce.300ms="batchSearch" />

                    @if ($batchOptions->isEmpty())
                        <flux:text size="sm">No batches match.</flux:text>
                    @elseif ($this->hasMoreBatches)
                        <flux:text size="sm">Showing the 50 newest matches — refine your search.</flux:text>
                    @endif
                </div>
            </div>

            @if ($this->selectedBatch)
                <flux:callout icon="cube" variant="secondary">
                    <flux:callout.heading>{{ $this->selectedBatch->inventoryItem?->name }}</flux:callout.heading>
                    <flux:callout.text>
                        Batch {{ $this->selectedBatch->batch_number ?? '—' }}
                        · Lot {{ $this->selectedBatch->lot_number ?? '—' }}
                        · {{ number_format($this->selectedBatch->remaining_qty) }} of
                        {{ number_format($this->selectedBatch->received_qty) }}
                        {{ $this->selectedBatch->inventoryItem?->unit }} remaining
                        @if ($this->selectedBatch->expiration_date)
                            · Expires {{ $this->selectedBatch->expiration_date->format('M d, Y') }}
                        @endif
                    </flux:callout.text>
                </flux:callout>
            @endif

            <flux:input type="number" min="0" wire:model.live.debounce.300ms="form.quantity"
                :label="$form->adjustmentType()?->quantityLabel() ?? 'Quantity'" />

            {{-- Previous / change / new preview --}}
            @if ($this->preview)
                @php [$previous, $change, $new] = $this->preview; @endphp

                <div class="grid grid-cols-3 gap-3">
                    <flux:card size="sm">
                        <flux:text size="sm">Previous Qty</flux:text>
                        <flux:heading size="lg">{{ number_format($previous) }}</flux:heading>
                    </flux:card>
                    <flux:card size="sm">
                        <flux:text size="sm">Change</flux:text>
                        <flux:heading size="lg">
                            <flux:text inline size="lg" :color="$change < 0 ? 'red' : ($change > 0 ? 'green' : null)">
                                {{ $change > 0 ? '+' : '' }}{{ number_format($change) }}
                            </flux:text>
                        </flux:heading>
                    </flux:card>
                    <flux:card size="sm">
                        <flux:text size="sm">New Qty</flux:text>
                        <flux:heading size="lg">
                            <flux:text inline size="lg" :color="$new < 0 ? 'red' : null" :variant="$new < 0 ? null : 'strong'">
                                {{ number_format($new) }}
                            </flux:text>
                        </flux:heading>
                    </flux:card>
                </div>
            @endif

            <flux:textarea wire:model="form.reason" label="Reason" rows="3"
                placeholder="Describe what happened, e.g. carton crushed during unloading" />

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="form.requested_by" label="Requested By" />
                <flux:input wire:model="form.approved_by" label="Approved By" placeholder="Name of approver" />
            </div>

            <flux:separator variant="subtle" />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost" type="button">Cancel</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="save">
                    <span wire:loading.remove wire:target="save">Record Adjustment</span>
                    <span wire:loading wire:target="save">Saving...</span>
                </flux:button>
            </div>
        </form>
    </div>
</flux:modal>
