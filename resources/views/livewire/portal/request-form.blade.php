<div>
    @if ($step === 'done')
        {{-- Submitted: hand over the tracking code --}}
        <flux:card class="space-y-6">
            <flux:callout icon="check-circle" color="green">
                <flux:callout.heading>{{ __('Request submitted') }}</flux:callout.heading>
                <flux:callout.text>
                    {{ __('Keep this tracking number. You will need it to check on your request.') }}
                </flux:callout.text>
            </flux:callout>

            <div class="flex flex-col items-center gap-3 text-center" x-data="{ copied: false }">
                <flux:text>{{ __('Your tracking number') }}</flux:text>
                <div class="flex items-center gap-2">
                    <span class="font-mono text-2xl font-semibold tracking-wider text-zinc-800 dark:text-white"
                        data-test="tracking-code">{{ $trackingCode }}</span>
                    <flux:button size="sm" variant="ghost" icon="clipboard" :aria-label="__('Copy tracking number')"
                        x-on:click="navigator.clipboard.writeText(@js($trackingCode)); copied = true; setTimeout(() => copied = false, 2000)" />
                </div>
                <flux:text size="sm" x-show="copied" x-cloak>{{ __('Copied!') }}</flux:text>
                <flux:text size="sm">{{ __('Reference: :number', ['number' => $requestNumber]) }}</flux:text>
                @if ($notifyEmail)
                    <flux:text size="sm" data-test="notify-email">{{ __('Updates will be sent to :email', ['email' => $notifyEmail]) }}</flux:text>
                @endif
            </div>

            <div class="flex flex-col-reverse justify-center gap-2 sm:flex-row">
                <flux:button variant="ghost" icon="plus" wire:click="startOver">{{ __('Submit another') }}</flux:button>
                <flux:button variant="primary" icon="magnifying-glass" wire:click="track">{{ __('Track this request') }}</flux:button>
            </div>
        </flux:card>

    @elseif ($step === 'review')
        {{-- Summary before submitting --}}
        @php
            $areaLabel = $this->areaOptions?->get((int) $form->administrative_area_id) ?? '—';
        @endphp

        <flux:card class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Review your request') }}</flux:heading>
                <flux:text class="mt-1">{{ __('Check everything below. Nothing is sent until you confirm.') }}</flux:text>
            </div>

            <dl class="grid gap-4 sm:grid-cols-2">
                <div>
                    <flux:text size="sm">{{ __('Your name') }}</flux:text>
                    <flux:text variant="strong">{{ $form->requester_name }}</flux:text>
                </div>
                <div>
                    <flux:text size="sm">{{ __('Immediate supervisor') }}</flux:text>
                    <flux:text variant="strong">{{ $form->supervisor_name }}</flux:text>
                </div>
                <div>
                    <flux:text size="sm">{{ __('Designation') }}</flux:text>
                    <flux:text variant="strong">{{ $areaLabel }}</flux:text>
                </div>
                <div>
                    <flux:text size="sm">{{ __('Purpose') }}</flux:text>
                    <flux:text variant="strong">{{ $form->purpose }}</flux:text>
                </div>
                @if (filled($form->requester_email))
                    <div>
                        <flux:text size="sm">{{ __('Email for updates') }}</flux:text>
                        <flux:text variant="strong">{{ $form->requester_email }}</flux:text>
                    </div>
                @endif
            </dl>

            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Item') }}</flux:table.column>
                    <flux:table.column align="end">{{ __('Requested') }}</flux:table.column>
                    <flux:table.column align="end">{{ __('Available') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($form->items as $i => $row)
                        @php
                            $item = $this->inventoryItems->get($row['inventory_item_id']);
                            $available = (int) ($item?->available_qty ?? 0);
                        @endphp
                        <flux:table.row :key="'summary-'.$i">
                            <flux:table.cell variant="strong">{{ $item?->name ?? '—' }}</flux:table.cell>
                            <flux:table.cell align="end" variant="strong">
                                {{ number_format((int) $row['requested_qty']) }} {{ $item?->unit }}
                            </flux:table.cell>
                            <flux:table.cell align="end">
                                {{ number_format($available) }} {{ $item?->unit }}
                                @if ((int) $row['requested_qty'] > $available)
                                    <flux:badge size="sm" color="amber" class="ms-1">{{ __('More than on hand') }}</flux:badge>
                                @endif
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>

            <flux:text size="sm">
                {{ trans_choice(':count item|:count different items', count($form->items), ['count' => count($form->items)]) }}.
                {{ __('An approver decides how much is dispensed, so you may receive less than requested.') }}
            </flux:text>

            <flux:error name="submit" />

            <div class="flex flex-col-reverse justify-end gap-2 sm:flex-row">
                <flux:button variant="ghost" icon="pencil-square" wire:click="edit">{{ __('Back to edit') }}</flux:button>
                <flux:button variant="primary" icon="paper-airplane" wire:click="submit">{{ __('Confirm & submit') }}</flux:button>
            </div>
        </flux:card>

    @else
        {{-- Request details and item lines --}}
        <flux:card>
            <form wire:submit="review" class="space-y-6">
                <div>
                    <flux:heading size="lg">{{ __('New request') }}</flux:heading>
                    <flux:text class="mt-1">
                        {{ __('Tell us who you are and what you need. You can request several items at once.') }}
                    </flux:text>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:input wire:model="form.requester_name" :label="__('Your name')" autocomplete="name" />
                    <flux:input wire:model="form.supervisor_name" :label="__('Immediate supervisor\'s name')" />
                </div>

                <flux:field>
                    <div class="flex items-center gap-2">
                        <flux:label>{{ __('Email') }}</flux:label>
                        <flux:badge size="sm">{{ __('Optional') }}</flux:badge>
                    </div>
                    <flux:input type="email" wire:model="form.requester_email" autocomplete="email"
                        placeholder="you@example.com" />
                    <flux:description>{{ __("We'll email you when the status of this request changes.") }}</flux:description>
                    <flux:error name="form.requester_email" />
                </flux:field>

                @if ($this->areaOptions === null)
                    <flux:callout icon="exclamation-triangle" color="red">
                        <flux:callout.heading>{{ __('Designations could not be loaded') }}</flux:callout.heading>
                        <flux:callout.text>{{ __('Please try again in a few minutes.') }}</flux:callout.text>
                    </flux:callout>
                @else
                    <x-searchable-select wire:model="form.administrative_area_id" :label="__('Designation')"
                        :placeholder="__('Select your designation')"
                        :options="$this->areaOptions" />
                @endif

                <flux:input wire:model="form.purpose" :label="__('Purpose')"
                    :placeholder="__('e.g. Monthly office supplies for the records section')" />

                {{-- Line items --}}
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <flux:heading>{{ __('Items') }}</flux:heading>
                        <flux:button size="sm" variant="ghost" icon="plus" type="button" wire:click="addItem">
                            {{ __('Add item') }}
                        </flux:button>
                    </div>

                    <flux:error name="form.items" :deep="false" />

                    @foreach ($form->items as $i => $row)
                        @php
                            $item = $this->inventoryItems->get($row['inventory_item_id']);
                            $available = (int) ($item?->available_qty ?? 0);
                            $requested = (int) $row['requested_qty'];
                        @endphp

                        <flux:card size="sm" wire:key="request-line-{{ $i }}" class="space-y-3">
                            <div class="flex items-start gap-3">
                                <div class="grid flex-1 gap-4 sm:grid-cols-[1fr_10rem]">
                                    <flux:select wire:model.live="form.items.{{ $i }}.inventory_item_id" :label="__('Item')"
                                        :placeholder="__('Select item')">
                                        @foreach ($this->inventoryItems as $inventoryItem)
                                            <flux:select.option value="{{ $inventoryItem->id }}">
                                                {{ $inventoryItem->name }}
                                                ({{ number_format((int) $inventoryItem->available_qty) }} {{ $inventoryItem->unit }} available)
                                            </flux:select.option>
                                        @endforeach
                                    </flux:select>

                                    <flux:input type="number" min="1"
                                        wire:model.live.debounce.300ms="form.items.{{ $i }}.requested_qty"
                                        label="{{ __('Quantity') }}{{ $item ? ' ('.$item->unit.')' : '' }}" />
                                </div>

                                @if (count($form->items) > 1)
                                    <flux:button size="sm" variant="ghost" color="red" icon="trash" type="button"
                                        class="mt-6" wire:click="removeItem({{ $i }})"
                                        aria-label="{{ __('Remove item :n', ['n' => $i + 1]) }}" />
                                @endif
                            </div>

                            @if ($item)
                                <flux:text size="sm">
                                    {{ number_format($available) }} {{ $item->unit }} {{ __('currently available.') }}
                                    @if ($requested > $available)
                                        <flux:text inline size="sm" color="amber">
                                            {{ __('Requesting more than is on hand — the approver may dispense less.') }}
                                        </flux:text>
                                    @endif
                                </flux:text>
                            @endif
                        </flux:card>
                    @endforeach
                </div>

                <flux:separator variant="subtle" />

                <div class="flex justify-end">
                    <flux:button type="submit" variant="primary" icon:trailing="arrow-right"
                        :disabled="$this->areaOptions === null">
                        {{ __('Review request') }}
                    </flux:button>
                </div>
            </form>
        </flux:card>
    @endif
</div>
