@php
    $isFollowUp = $followUpOf !== null;
    $root = $isFollowUp ? $this->followUpRoot : null;
    $isPartial = ! $isFollowUp && $form->status === \App\Enums\ReceiptStatus::Partial->value;
    $optionalErrorKeys = fn ($i) => [
        "form.items.$i.batch_number",
        "form.items.$i.lot_number",
        "form.items.$i.manufactured_date",
        "form.items.$i.expiration_date",
    ];
@endphp

<flux:modal name="create-receiving" class="w-[1100px] max-w-[95vw]">
    <div class="space-y-6">

        <div class="pr-8">
            @if ($isFollowUp)
                <flux:heading size="lg">Receive Remaining Delivery</flux:heading>
                <div class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1">
                    <flux:text>{{ $root?->supplier?->name ?? '—' }}</flux:text>
                    <flux:text inline>&middot;</flux:text>
                    <flux:text>PO {{ $root?->purchase_order_number ?? '—' }}</flux:text>
                    @if ($root?->is_donation)
                        <flux:badge size="sm" color="sky">Donation</flux:badge>
                    @endif
                    <flux:text inline>&middot;</flux:text>
                    <flux:text>Follow-up to <span class="font-mono">{{ $root?->reference_number }}</span></flux:text>
                </div>
            @else
                <flux:heading size="lg">Receive Stock</flux:heading>
                <flux:text class="mt-1">
                    Record a delivery and the batches that came with it.
                </flux:text>
            @endif
        </div>

        <form wire:submit="save" class="space-y-6">

            @error('form.parent_receipt_id')
                <flux:callout variant="danger" icon="exclamation-triangle">
                    <flux:callout.text>{{ $message }}</flux:callout.text>
                </flux:callout>
            @enderror

            {{-- Receipt details --}}
            @if ($isFollowUp)
                @error('form.supplier_id')
                    <flux:text class="text-red-500">{{ $message }}</flux:text>
                @enderror

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <flux:input wire:model="form.reference_number" label="Reference Number"
                        placeholder="Delivery receipt / invoice no." />
                    <x-datetime-picker wire:model="form.received_date" label="Received Date & Time" />
                    <x-datetime-picker wire:model="form.delivery_date" label="Delivery Date & Time" />
                </div>
            @else
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <flux:radio.group wire:model.live="form.is_donation" label="Receipt type" variant="segmented">
                        <flux:radio value="0" label="Purchase" />
                        <flux:radio value="1" label="Donation" />
                    </flux:radio.group>

                    <flux:radio.group wire:model.live="form.status" label="Delivery" variant="segmented">
                        <flux:radio :value="\App\Enums\ReceiptStatus::Complete->value" label="Complete" />
                        <flux:radio :value="\App\Enums\ReceiptStatus::Partial->value" label="Partial" />
                    </flux:radio.group>
                </div>
                @error('form.is_donation')
                    <flux:text class="text-red-500">{{ $message }}</flux:text>
                @enderror

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <flux:select wire:model="form.supplier_id" label="Supplier" placeholder="Select supplier">
                        @foreach ($this->suppliers as $supplier)
                            <flux:select.option value="{{ $supplier->id }}">{{ $supplier->name }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:input wire:model="form.reference_number" label="Reference Number"
                        placeholder="Delivery receipt / invoice no." />

                    <flux:input wire:model="form.purchase_order_number" label="PO Number (optional)" />

                    <x-datetime-picker wire:model="form.received_date" label="Received Date & Time" />
                    <x-datetime-picker wire:model="form.delivery_date" label="Delivery Date & Time" />
                </div>

                @if ($form->is_donation)
                    <flux:text size="sm">Any supplier can donate. Unit cost is still recorded like a normal delivery.</flux:text>
                @endif
            @endif

            @error('form.items')
                <flux:text class="text-red-500">{{ $message }}</flux:text>
            @enderror

            @if ($isFollowUp)
                {{-- Follow-up: one table of what is outstanding and what arrives now --}}
                <div class="space-y-3">
                    <flux:heading>Items</flux:heading>

                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>Item</flux:table.column>
                            <flux:table.column align="end">Ordered</flux:table.column>
                            <flux:table.column align="end">Received so far</flux:table.column>
                            <flux:table.column align="end">Outstanding</flux:table.column>
                            <flux:table.column align="end">Receiving now</flux:table.column>
                            <flux:table.column align="end">After this delivery</flux:table.column>
                        </flux:table.columns>

                        <flux:table.rows>
                            @foreach ($this->followUpRows as $row)
                                @php
                                    $i = $row['index'];
                                    $item = $row['item'];
                                    $detailsOpen = $errors->hasAny($optionalErrorKeys($i));
                                @endphp

                                <flux:table.row :key="'follow-up-row-'.$i">
                                    <flux:table.cell>
                                        <flux:text variant="strong" class="font-medium">{{ $item?->name ?? '—' }}</flux:text>
                                        <flux:text size="sm">{{ $item?->item_code }}</flux:text>
                                        <flux:error name="form.items.{{ $i }}.inventory_item_id" />
                                    </flux:table.cell>
                                    <flux:table.cell align="end" class="tabular-nums">{{ number_format($row['ordered']) }}</flux:table.cell>
                                    <flux:table.cell align="end" class="tabular-nums">{{ number_format($row['received']) }}</flux:table.cell>
                                    <flux:table.cell align="end" class="tabular-nums">{{ number_format($row['outstanding']) }}</flux:table.cell>
                                    <flux:table.cell align="end">
                                        <div class="ms-auto w-28">
                                            <flux:input type="number" min="0" size="sm"
                                                wire:model.live.debounce.300ms="form.items.{{ $i }}.quantity"
                                                aria-label="Receiving now for {{ $item?->name }}{{ $item ? ' ('.$item->unit.')' : '' }}" />
                                        </div>
                                    </flux:table.cell>
                                    <flux:table.cell align="end" class="tabular-nums">
                                        @if ($row['remaining_after'] === 0)
                                            <flux:badge size="sm" color="green">Complete</flux:badge>
                                        @else
                                            {{ number_format($row['remaining_after']) }}
                                            <flux:text inline size="sm">{{ $item?->unit }}</flux:text>
                                        @endif
                                    </flux:table.cell>
                                </flux:table.row>

                                <flux:table.row :key="'follow-up-details-'.$i" class="!border-t-0">
                                    <flux:table.cell colspan="6" class="!pt-0">
                                        <div x-data="{ open: @js($detailsOpen) }" class="space-y-3">
                                            <div class="grid grid-cols-1 items-end gap-3 sm:grid-cols-2 lg:grid-cols-4">
                                                <flux:select size="sm" wire:model="form.items.{{ $i }}.storage_location_id"
                                                    label="Storage Location" placeholder="Select location">
                                                    @foreach ($this->storageLocations as $location)
                                                        <flux:select.option value="{{ $location->id }}">{{ $location->name }}</flux:select.option>
                                                    @endforeach
                                                </flux:select>

                                                <flux:input size="sm" type="number" min="0" step="0.01"
                                                    wire:model="form.items.{{ $i }}.unit_cost" label="Unit Cost" />

                                                @if ($item?->requires_expiry)
                                                    <x-date-picker wire:model="form.items.{{ $i }}.expiration_date"
                                                        label="Expiration" align="end" clearable />
                                                @endif

                                                <div>
                                                    <flux:button size="sm" variant="subtle" type="button"
                                                        icon="chevron-down" x-on:click="open = ! open"
                                                        x-bind:aria-expanded="open"
                                                        aria-controls="follow-up-optional-{{ $i }}">
                                                        Optional details
                                                    </flux:button>
                                                </div>
                                            </div>

                                            <div id="follow-up-optional-{{ $i }}" x-show="open" x-cloak
                                                class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                                                <flux:input size="sm" wire:model="form.items.{{ $i }}.batch_number"
                                                    label="Batch No. (optional)" placeholder="Auto-generated if blank" />
                                                <flux:input size="sm" wire:model="form.items.{{ $i }}.lot_number"
                                                    label="Lot No. (optional)" placeholder="Auto-generated if blank" />
                                                <x-date-picker wire:model="form.items.{{ $i }}.manufactured_date"
                                                    label="Manufactured" clearable />
                                                @unless ($item?->requires_expiry)
                                                    <x-date-picker wire:model="form.items.{{ $i }}.expiration_date"
                                                        label="Expiration (optional)" :disabled="$item !== null"
                                                        align="end" clearable />
                                                @endunless
                                            </div>
                                        </div>
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                </div>

                {{-- Previous deliveries --}}
                @if ($timeline = $this->followUpTimeline)
                    <details class="group rounded-lg border border-zinc-200 dark:border-zinc-700">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-2 px-4 py-3 text-sm font-medium text-zinc-800 dark:text-white [&::-webkit-details-marker]:hidden">
                            Previous deliveries ({{ count($timeline['deliveries']) }})
                            <flux:icon name="chevron-down" variant="micro" class="text-zinc-400 transition-transform group-open:rotate-180" />
                        </summary>
                        <ol class="space-y-2 border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">
                            @foreach ($timeline['deliveries'] as $delivery)
                                <li wire:key="previous-delivery-{{ $delivery['id'] }}" class="space-y-0.5">
                                    <div class="flex flex-wrap items-center gap-x-2">
                                        <span class="font-mono text-sm font-medium text-zinc-800 dark:text-white">{{ $delivery['reference_number'] }}</span>
                                        <flux:text size="sm" inline>{{ $delivery['received_date']->format('M d, Y h:i A') }}</flux:text>
                                    </div>
                                    <flux:text size="sm" class="tabular-nums">
                                        @if (count($delivery['items']))
                                            {{ collect($delivery['items'])->map(fn ($d) => number_format($d['qty']).' '.$d['unit'].' '.$d['name'])->implode(', ') }}
                                        @else
                                            Received nothing
                                        @endif
                                    </flux:text>
                                </li>
                            @endforeach
                        </ol>
                    </details>
                @endif
            @else
                {{-- New receipt: one card per line --}}
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <flux:heading>Batches</flux:heading>
                        <flux:button size="sm" variant="ghost" icon="plus" type="button" wire:click="addItem">
                            Add line
                        </flux:button>
                    </div>

                    @if ($isPartial)
                        <flux:text size="sm">Enter what was ordered and what arrived now. Lines can receive 0.</flux:text>
                    @endif

                    @foreach ($form->items as $i => $row)
                        @php
                            $item = $this->inventoryItems->get($row['inventory_item_id']);
                            $detailsOpen = $errors->hasAny($optionalErrorKeys($i));
                        @endphp

                        <flux:card size="sm" wire:key="receipt-line-{{ $i }}" class="space-y-4"
                            x-data="{ open: @js($detailsOpen) }">

                            <div class="flex items-center justify-between">
                                <flux:text class="font-medium">Line {{ $i + 1 }}</flux:text>
                                @if (count($form->items) > 1)
                                    <flux:button size="sm" variant="ghost" icon="trash" type="button"
                                        class="text-red-500" wire:click="removeItem({{ $i }})"
                                        aria-label="Remove line {{ $i + 1 }}" />
                                @endif
                            </div>

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                                <flux:select wire:model.live="form.items.{{ $i }}.inventory_item_id" label="Item"
                                    placeholder="Select item">
                                    @foreach ($this->inventoryItems as $inventoryItem)
                                        <flux:select.option value="{{ $inventoryItem->id }}">
                                            {{ $inventoryItem->item_code }} — {{ $inventoryItem->name }}
                                        </flux:select.option>
                                    @endforeach
                                </flux:select>

                                <flux:select wire:model="form.items.{{ $i }}.storage_location_id"
                                    label="Storage Location" placeholder="Select location">
                                    @foreach ($this->storageLocations as $location)
                                        <flux:select.option value="{{ $location->id }}">{{ $location->name }}</flux:select.option>
                                    @endforeach
                                </flux:select>

                                @if ($isPartial)
                                    <flux:input type="number" min="0" wire:model="form.items.{{ $i }}.ordered_qty"
                                        label="Ordered Qty{{ $item ? ' ('.$item->unit.')' : '' }}" />
                                @endif

                                <flux:input type="number" min="{{ $isPartial ? 0 : 1 }}"
                                    wire:model="form.items.{{ $i }}.quantity"
                                    label="{{ $isPartial ? 'Received Now' : 'Quantity' }}{{ $item ? ' ('.$item->unit.')' : '' }}" />

                                <flux:input type="number" min="0" step="0.01"
                                    wire:model="form.items.{{ $i }}.unit_cost" label="Unit Cost" />

                                @if ($item?->requires_expiry)
                                    <x-date-picker wire:model="form.items.{{ $i }}.expiration_date"
                                        label="Expiration" align="end" clearable />
                                @endif
                            </div>

                            <div>
                                <flux:button size="sm" variant="subtle" type="button" icon="chevron-down"
                                    x-on:click="open = ! open" x-bind:aria-expanded="open"
                                    aria-controls="receipt-optional-{{ $i }}">
                                    Optional details
                                </flux:button>
                            </div>

                            <div id="receipt-optional-{{ $i }}" x-show="open" x-cloak
                                class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                                <flux:input wire:model="form.items.{{ $i }}.batch_number" label="Batch No. (optional)"
                                    placeholder="Auto-generated if blank" />

                                <flux:input wire:model="form.items.{{ $i }}.lot_number" label="Lot No. (optional)"
                                    placeholder="Auto-generated if blank" />

                                <x-date-picker wire:model="form.items.{{ $i }}.manufactured_date"
                                    label="Manufactured" clearable />

                                @unless ($item?->requires_expiry)
                                    <x-date-picker wire:model="form.items.{{ $i }}.expiration_date"
                                        label="Expiration (optional)" :disabled="$item !== null" align="end" clearable />
                                @endunless
                            </div>
                        </flux:card>
                    @endforeach
                </div>
            @endif

            <flux:textarea wire:model="form.remarks" label="Remarks" rows="2" />

            {{-- Attachments --}}
            <div class="space-y-3">
                @if ($isFollowUp)
                    <flux:input type="file" wire:model="attachments" label="Add more files (optional)" multiple
                        description="Files from {{ $root?->reference_number }} are reused for this delivery. Add any new delivery receipt or invoice." />
                @else
                    <flux:input type="file" wire:model="attachments" label="Attachments (required)" multiple
                        description="At least one file: delivery receipt, invoice or deed of donation." />
                @endif

                @error('attachments')
                    <flux:text class="text-red-500">{{ $message }}</flux:text>
                @enderror
                @error('attachments.*')
                    <flux:text class="text-red-500">{{ $message }}</flux:text>
                @enderror
            </div>

            <div class="flex justify-end gap-2 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                <flux:modal.close>
                    <flux:button variant="ghost" type="button">Cancel</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="primary" wire:loading.attr="disabled"
                    wire:target="save,attachments">
                    <span wire:loading.remove wire:target="save">Save Receipt</span>
                    <span wire:loading wire:target="save">Saving...</span>
                </flux:button>
            </div>
        </form>
    </div>
</flux:modal>
