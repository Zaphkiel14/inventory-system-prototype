@php
    $isFollowUp = $followUpOf !== null;
    $root = $isFollowUp ? $this->followUpRoot : null;
    $followLines = $isFollowUp ? $this->followUpLines : collect();
    $isPartial = ! $isFollowUp && $form->status === 'partial_delivery';
    $qtyLabel = ($isPartial || $isFollowUp) ? 'Received Now' : 'Quantity';
    $lineItems = $isFollowUp
        ? $followLines->pluck('item')->filter()
        : $this->inventoryItems;
@endphp

<flux:modal name="create-receiving" class="w-[1100px] max-w-[95vw]">
    <div class="space-y-6">

        <div class="pr-8">
            @if ($isFollowUp)
                <flux:heading size="lg">Receive Remaining Delivery</flux:heading>
                <flux:text class="mt-1">
                    Follow-up to {{ $root?->reference_number }} &middot; {{ $root?->supplier?->name }}
                </flux:text>
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
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                @if ($isFollowUp)
                    <div class="min-w-0">
                        <flux:text size="sm">Supplier</flux:text>
                        <div class="flex flex-wrap items-center gap-2">
                            <flux:text variant="strong">{{ $root?->supplier?->name ?? '—' }}</flux:text>
                            @if ($root?->is_donation)
                                <flux:badge size="sm" color="sky">Donation</flux:badge>
                            @endif
                        </div>
                    </div>

                    <div class="min-w-0">
                        <flux:text size="sm">PO Number</flux:text>
                        <flux:text variant="strong">{{ $root?->purchase_order_number ?? '—' }}</flux:text>
                    </div>

                    <flux:input wire:model="form.reference_number" label="Reference Number"
                        placeholder="Delivery receipt / invoice no." />
                    @error('form.supplier_id')
                        <flux:text class="text-red-500">{{ $message }}</flux:text>
                    @enderror
                @else
                    <flux:select wire:model="form.supplier_id" label="Supplier" placeholder="Select supplier">
                        @foreach ($this->suppliers as $supplier)
                            <flux:select.option value="{{ $supplier->id }}">{{ $supplier->name }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:input wire:model="form.reference_number" label="Reference Number"
                        placeholder="Delivery receipt / invoice no." />

                    <flux:input wire:model="form.purchase_order_number" label="PO Number (optional)" />

                    <flux:select wire:model.live="form.status" label="Delivery Status">
                        <flux:select.option value="complete_delivery">Fully Delivered</flux:select.option>
                        <flux:select.option value="partial_delivery">Partial Delivery</flux:select.option>
                    </flux:select>
                @endif

                <x-datetime-picker wire:model="form.received_date" label="Received Date & Time" />
                <x-datetime-picker wire:model="form.delivery_date" label="Delivery Date & Time" />
            </div>

            @unless ($isFollowUp)
                <flux:checkbox wire:model="form.is_donation" label="This delivery is a donation"
                    description="Any supplier can donate. Unit cost is still recorded like a normal delivery." />
                @error('form.is_donation')
                    <flux:text class="text-red-500">{{ $message }}</flux:text>
                @enderror
            @endunless

            {{-- Follow-up progress --}}
            @if ($isFollowUp && ($timeline = $this->followUpTimeline))
                <x-receiving.delivery-timeline :timeline="$timeline" mode="preview" />
            @endif

            {{-- Line items --}}
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

                @error('form.items')
                    <flux:text class="text-red-500">{{ $message }}</flux:text>
                @enderror

                @foreach ($form->items as $i => $row)
                    @php
                        $item = $this->inventoryItems->get($row['inventory_item_id']);
                        $outstanding = $isFollowUp
                            ? $followLines->first(fn ($l) => ($l['item']?->id) == $row['inventory_item_id'])['outstanding'] ?? null
                            : null;
                    @endphp

                    <flux:card size="sm" wire:key="receipt-line-{{ $i }}" class="space-y-4">

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
                                @foreach ($lineItems as $inventoryItem)
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

                            <flux:input wire:model="form.items.{{ $i }}.batch_number" label="Batch No. (optional)"
                                placeholder="Auto-generated if blank" />

                            <flux:input wire:model="form.items.{{ $i }}.lot_number" label="Lot No. (optional)"
                                placeholder="Auto-generated if blank" />
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            @if ($isPartial)
                                <flux:input type="number" min="0" wire:model="form.items.{{ $i }}.ordered_qty"
                                    label="Ordered Qty{{ $item ? ' ('.$item->unit.')' : '' }}" />
                            @endif

                            @if ($isFollowUp && $outstanding !== null)
                                <flux:input type="number" min="0" wire:model.live.debounce.300ms="form.items.{{ $i }}.quantity"
                                    label="{{ $qtyLabel }}{{ $item ? ' ('.$item->unit.')' : '' }}"
                                    description="Outstanding: {{ number_format($outstanding) }}" />
                            @else
                                <flux:input type="number" min="{{ ($isPartial || $isFollowUp) ? 0 : 1 }}"
                                    wire:model="form.items.{{ $i }}.quantity"
                                    label="{{ $qtyLabel }}{{ $item ? ' ('.$item->unit.')' : '' }}" />
                            @endif

                            <flux:input type="number" min="0" step="0.01"
                                wire:model="form.items.{{ $i }}.unit_cost" label="Unit Cost" />

                            <x-date-picker wire:model="form.items.{{ $i }}.manufactured_date"
                                label="Manufactured" clearable />

                            <x-date-picker wire:model="form.items.{{ $i }}.expiration_date"
                                label="Expiration{{ $item?->requires_expiry ? '' : ' (optional)' }}"
                                :disabled="$item && ! $item->requires_expiry" align="end" clearable />
                        </div>
                    </flux:card>
                @endforeach
            </div>

            <flux:textarea wire:model="form.remarks" label="Remarks" />

            {{-- Attachments --}}
            <div class="space-y-3">
                @if ($isFollowUp)
                    <flux:input type="file" wire:model="attachments" label="Add more files (optional)" multiple />
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
