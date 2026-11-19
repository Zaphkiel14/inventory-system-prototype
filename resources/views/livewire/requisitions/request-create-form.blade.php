<flux:modal name="create-request" class="w-[820px] max-w-[95vw]">
    <div class="space-y-6">

        <div>
            <flux:heading size="lg">New Request</flux:heading>
            <flux:text class="mt-1">
                List what you need. An approver decides how much is dispensed, then the items are released at issuance.
            </flux:text>
        </div>

        <form wire:submit="save" class="space-y-6">

            <flux:input wire:model="form.purpose" label="Purpose"
                placeholder="e.g. Monthly office supplies for the records section" />

            {{-- Line items --}}
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <flux:heading>Items</flux:heading>
                    <flux:button size="sm" variant="ghost" icon="plus" type="button" wire:click="addItem">
                        Add line
                    </flux:button>
                </div>

                @error('form.items')
                    <flux:text class="text-red-500">{{ $message }}</flux:text>
                @enderror

                @foreach ($form->items as $i => $row)
                    @php
                        $item = $this->inventoryItems->get($row['inventory_item_id']);
                        $available = (int) ($item?->available_qty ?? 0);
                        $requested = (int) $row['requested_qty'];
                    @endphp

                    <flux:card size="sm" wire:key="request-line-{{ $i }}" class="space-y-3">
                        <div class="flex items-start gap-3">
                            <div class="grid flex-1 gap-4 sm:grid-cols-[1fr_10rem]">
                                <flux:select wire:model.live="form.items.{{ $i }}.inventory_item_id" label="Item"
                                    placeholder="Select item">
                                    @foreach ($this->inventoryItems as $inventoryItem)
                                        <flux:select.option value="{{ $inventoryItem->id }}">
                                            {{ $inventoryItem->item_code }} — {{ $inventoryItem->name }}
                                            ({{ number_format((int) $inventoryItem->available_qty) }} {{ $inventoryItem->unit }} available)
                                        </flux:select.option>
                                    @endforeach
                                </flux:select>

                                <flux:input type="number" min="1"
                                    wire:model.live.debounce.300ms="form.items.{{ $i }}.requested_qty"
                                    label="Quantity{{ $item ? ' ('.$item->unit.')' : '' }}" />
                            </div>

                            @if (count($form->items) > 1)
                                <flux:button size="sm" variant="ghost" icon="trash" type="button"
                                    class="mt-6 text-red-500" wire:click="removeItem({{ $i }})"
                                    aria-label="Remove line {{ $i + 1 }}" />
                            @endif
                        </div>

                        @if ($item)
                            <flux:text size="sm">
                                {{ number_format($available) }} {{ $item->unit }} currently available.
                                @if ($requested > $available)
                                    <flux:text inline size="sm" color="amber">
                                        Requesting more than is on hand — the approver may dispense less.
                                    </flux:text>
                                @endif
                            </flux:text>
                        @endif
                    </flux:card>
                @endforeach
            </div>

            <flux:separator variant="subtle" />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost" type="button">Cancel</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="save">
                    <span wire:loading.remove wire:target="save">Submit Request</span>
                    <span wire:loading wire:target="save">Submitting...</span>
                </flux:button>
            </div>
        </form>
    </div>
</flux:modal>
