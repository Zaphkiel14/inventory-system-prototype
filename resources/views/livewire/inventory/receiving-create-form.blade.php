<flux:modal name="create-receiving" class="w-[1100px] max-w-[95vw]">
    <div class="space-y-6">

        <div>
            <flux:heading size="lg">Receive Stock</flux:heading>
            <flux:text class="mt-1">
                Record a delivery and the batches that came with it.
            </flux:text>
        </div>

        <form wire:submit="save" class="space-y-6">

            {{-- Receipt details --}}
            <div class="grid grid-cols-2 gap-4">
                <flux:select wire:model="form.supplier_id" label="Supplier" placeholder="Select supplier">
                    @foreach ($this->suppliers as $supplier)
                        <flux:select.option value="{{ $supplier->id }}">{{ $supplier->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:input wire:model="form.reference_number" label="Reference Number"
                    placeholder="Delivery receipt / invoice no." />

                <flux:input wire:model="form.purchase_order_number" label="PO Number (optional)" />

                <flux:select wire:model="form.status" label="Delivery Status">
                    <flux:select.option value="complete_delivery">Fully Delivered</flux:select.option>
                    <flux:select.option value="partial_delivery">Partial Delivery</flux:select.option>
                </flux:select>

                <x-datetime-picker wire:model="form.received_date" label="Received Date & Time" />
                <x-datetime-picker wire:model="form.delivery_date" label="Delivery Date & Time" />
            </div>

            {{-- Line items --}}
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <flux:heading>Batches</flux:heading>
                    <flux:button size="sm" variant="ghost" icon="plus" type="button" wire:click="addItem">
                        Add line
                    </flux:button>
                </div>

                @error('form.items')
                    <flux:text class="text-red-500">{{ $message }}</flux:text>
                @enderror

                @foreach ($form->items as $i => $row)
                    @php $item = $this->inventoryItems->get($row['inventory_item_id']); @endphp

                    <flux:card size="sm" wire:key="receipt-line-{{ $i }}" class="space-y-4">

                        <div class="flex items-center justify-between">
                            <flux:text class="font-medium">Line {{ $i + 1 }}</flux:text>
                            @if (count($form->items) > 1)
                                <flux:button size="xs" variant="ghost" icon="trash" type="button"
                                    class="text-red-500" wire:click="removeItem({{ $i }})" />
                            @endif
                        </div>

                        <div class="grid grid-cols-4 gap-4">
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

                            <flux:input wire:model="form.items.{{ $i }}.batch_number" label="Batch No. (optional)"
                                placeholder="Auto-generated if blank" />

                            <flux:input wire:model="form.items.{{ $i }}.lot_number" label="Lot No. (optional)"
                                placeholder="Auto-generated if blank" />
                        </div>

                        <div class="grid grid-cols-4 gap-4">
                            <flux:input type="number" min="1" wire:model="form.items.{{ $i }}.quantity"
                                label="Quantity{{ $item ? ' ('.$item->unit.')' : '' }}" />

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

            <flux:input type="file" wire:model="attachments" label="Attachments" multiple />
            @error('attachments.*')
                <flux:text class="text-red-500">{{ $message }}</flux:text>
            @enderror

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