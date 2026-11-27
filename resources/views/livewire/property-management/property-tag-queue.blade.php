<flux:card variant="default"
    x-data="{ pdfUrl: null }"
    x-on:open-pdf.window="pdfUrl = $event.detail.url; window.open($event.detail.url, '_blank')">
    @php
        $summary = $this->selectedSummary;
        $grid = $this->profileGrid;
        $columns = max(1, (int) $grid['columns']);
        $perSheet = $columns * max(0, (int) $grid['rows']);
        $selectedIds = array_map('intval', $selected);
        $hasFilters = $search !== '' || $status !== 'pending' || $documentType !== '' || $fundCluster !== '' || $office !== '' || $from !== '' || $to !== '';
    @endphp

    <div class="mb-4">
        <flux:heading size="xl" level="2">Property Tags</flux:heading>
        <flux:text class="mt-1">
            Tags from finalized ICS and PAR documents. Select tags, pick the sticker sheet and the first free slot, then print and confirm which came out right.
        </flux:text>
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
        {{-- Queue --}}
        <div class="min-w-0 space-y-4">
            <div class="space-y-2">
                <div class="flex flex-col gap-2 md:flex-row md:items-center">
                    <div class="md:flex-1">
                        <flux:input icon="magnifying-glass" size="sm" placeholder="Search property no., item, serial, accountable person, document no."
                            wire:model.live.debounce.500ms="search" />
                    </div>

                    <flux:select size="sm" class="md:w-36" wire:model.live="status" aria-label="Status">
                        <flux:select.option value="">All statuses</flux:select.option>
                        @foreach (\App\Enums\PropertyTagStatus::cases() as $option)
                            <flux:select.option value="{{ $option->value }}">{{ $option->label() }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select size="sm" class="md:w-32" wire:model.live="documentType" aria-label="Document type">
                        <flux:select.option value="">ICS and PAR</flux:select.option>
                        <flux:select.option value="ics">ICS</flux:select.option>
                        <flux:select.option value="par">PAR</flux:select.option>
                    </flux:select>
                </div>

                <div class="flex flex-col gap-2 md:flex-row md:items-center">
                    <flux:input size="sm" class="md:w-48" placeholder="Fund cluster" wire:model.live.debounce.500ms="fundCluster" aria-label="Fund cluster" />
                    <flux:input size="sm" class="md:w-48" placeholder="Office" wire:model.live.debounce.500ms="office" aria-label="Office" />
                    <x-date-picker wire:model.live="from" size="sm" aria-label="Document date from" placeholder="From" clearable class="md:w-40" />
                    <x-date-picker wire:model.live="to" size="sm" aria-label="Document date to" placeholder="To" clearable align="end" class="md:w-40" />
                </div>
            </div>

            {{-- Selection bar --}}
            <div class="flex flex-wrap items-center gap-2">
                <flux:text size="sm">
                    <span class="font-medium text-zinc-800 dark:text-white">{{ number_format(count($selectedIds)) }}</span> selected
                </flux:text>
                <flux:button size="xs" variant="ghost" wire:click="selectPage">Select page</flux:button>
                <flux:button size="xs" variant="ghost" wire:click="selectAllMatching">Select all matching</flux:button>
                @if ($selectedIds !== [])
                    <flux:button size="xs" variant="ghost" icon="x-mark" wire:click="clearSelection">Clear selection</flux:button>
                @endif
            </div>

            <flux:error name="selected" />

            <flux:table :paginate="$this->tags" class="transition-opacity"
                wire:loading.delay.class="opacity-60"
                wire:target="search,status,documentType,fundCluster,office,from,to,gotoPage,nextPage,previousPage,setPage">
                <flux:table.columns>
                    <flux:table.column class="w-8"><span class="sr-only">Select</span></flux:table.column>
                    <flux:table.column>Property No.</flux:table.column>
                    <flux:table.column>Item</flux:table.column>
                    <flux:table.column>Accountable Person</flux:table.column>
                    <flux:table.column>Document</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column align="end">Prints</flux:table.column>
                    <flux:table.column>Last Printed</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($this->tags as $tag)
                        @php $content = $tag->content ?? []; @endphp

                        <flux:table.row :key="'tag-'.$tag->id">
                            <flux:table.cell>
                                <flux:checkbox wire:model.live="selected" value="{{ $tag->id }}"
                                    aria-label="Select tag {{ $content['property_number'] ?? $tag->id }}" />
                            </flux:table.cell>

                            <flux:table.cell variant="strong" class="whitespace-nowrap font-mono text-xs">
                                {{ $content['property_number'] ?: '—' }}
                            </flux:table.cell>

                            <flux:table.cell class="max-w-64">
                                <flux:text variant="strong" class="truncate font-medium" :title="$content['item'] ?? ''">{{ $content['item'] ?? '—' }}</flux:text>
                                @if (filled($content['model'] ?? null) || filled($content['serial_number'] ?? null))
                                    <flux:text size="sm" class="truncate">
                                        {{ collect([$content['model'] ?? null, filled($content['serial_number'] ?? null) ? 'S/N '.$content['serial_number'] : null])->filter()->implode(' · ') }}
                                    </flux:text>
                                @endif
                            </flux:table.cell>

                            <flux:table.cell>{{ $content['accountable_person'] ?: '—' }}</flux:table.cell>

                            <flux:table.cell class="whitespace-nowrap">{{ $content['document_label'] ?? '—' }}</flux:table.cell>

                            <flux:table.cell>
                                @if ($tag->status)
                                    <flux:badge size="sm" :color="$tag->status->color()">{{ $tag->status->label() }}</flux:badge>
                                @endif
                            </flux:table.cell>

                            <flux:table.cell align="end">{{ number_format((int) $tag->print_count) }}</flux:table.cell>

                            <flux:table.cell class="whitespace-nowrap">
                                {{ $tag->last_printed_at?->format('M d, Y') ?? '—' }}
                                @if ($tag->last_printed_at)
                                    <flux:text size="sm">{{ $tag->last_printed_at->format('h:i A') }}</flux:text>
                                @endif
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="8">
                                <div class="flex flex-col items-center justify-center gap-2 py-8 text-center">
                                    <flux:icon name="tag" variant="outline" class="size-8 text-zinc-400" />
                                    <flux:heading size="sm">{{ $hasFilters ? 'No tags found' : 'No tags waiting to print' }}</flux:heading>
                                    <flux:text size="sm">
                                        {{ $hasFilters ? 'Try a different search or filter.' : 'Finalizing an ICS or PAR adds its tags here.' }}
                                    </flux:text>
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>

        {{-- Print panel --}}
        <div class="space-y-5 xl:sticky xl:top-4 xl:self-start">
            <flux:card size="sm" class="space-y-5">
                <flux:heading>Print</flux:heading>

                <flux:field>
                    <div class="flex items-center justify-between">
                        <flux:label>Sticker sheet</flux:label>
                        <flux:link :href="route('configurations.sticker-profiles')" wire:navigate.hover class="text-sm">Manage</flux:link>
                    </div>
                    <flux:select wire:model.live="profileId">
                        @foreach ($this->profiles as $id => $name)
                            <flux:select.option value="{{ $id }}">{{ $name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="profileId" />
                </flux:field>

                {{-- Start slot: the sheet grid, click the first free sticker --}}
                <flux:field>
                    <flux:label>Start at slot {{ $startSlot }}</flux:label>
                    <flux:description>Click the first unused sticker on the sheet.</flux:description>

                    @if ($perSheet > 0)
                        <div class="mt-2 grid gap-1 rounded-lg border border-zinc-200 bg-zinc-50 p-2 dark:border-white/10 dark:bg-white/5"
                            style="grid-template-columns: repeat({{ $columns }}, minmax(0, 1fr));"
                            role="radiogroup" aria-label="Start slot">
                            @for ($slot = 1; $slot <= $perSheet; $slot++)
                                <flux:button size="xs" wire:key="slot-{{ $slot }}"
                                    :variant="$slot === $startSlot ? 'primary' : ($slot < $startSlot ? 'subtle' : 'filled')"
                                    class="w-full tabular-nums {{ $slot < $startSlot ? 'line-through opacity-60' : '' }}"
                                    wire:click="$set('startSlot', {{ $slot }})"
                                    role="radio" aria-checked="{{ $slot === $startSlot ? 'true' : 'false' }}"
                                    aria-label="Slot {{ $slot }}{{ $slot < $startSlot ? ' (already used)' : '' }}">
                                    {{ $slot }}
                                </flux:button>
                            @endfor
                        </div>
                    @endif
                    <flux:error name="startSlot" />
                </flux:field>

                {{-- Summary --}}
                <div class="grid grid-cols-3 gap-2 text-center">
                    <div class="rounded-lg bg-zinc-50 p-2 dark:bg-white/5">
                        <div class="text-lg font-semibold tabular-nums text-zinc-800 dark:text-white">{{ number_format($summary['count']) }}</div>
                        <flux:text size="sm">tags</flux:text>
                    </div>
                    <div class="rounded-lg bg-zinc-50 p-2 dark:bg-white/5">
                        <div class="text-lg font-semibold tabular-nums {{ $summary['printed'] > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-zinc-800 dark:text-white' }}">{{ number_format($summary['printed']) }}</div>
                        <flux:text size="sm">printed before</flux:text>
                    </div>
                    <div class="rounded-lg bg-zinc-50 p-2 dark:bg-white/5">
                        <div class="text-lg font-semibold tabular-nums text-zinc-800 dark:text-white">{{ number_format($summary['sheets']) }}</div>
                        <flux:text size="sm">{{ $summary['sheets'] === 1 ? 'sheet' : 'sheets' }}</flux:text>
                    </div>
                </div>

                @if ($summary['printed'] > 0)
                    <flux:textarea wire:model="reprintReason" label="Reprint reason" rows="2"
                        description="{{ $summary['printed'] }} of the selected tags were printed before. Say why they're being reprinted; it's logged." />
                @endif

                <flux:button variant="primary" icon="printer" class="w-full" wire:click="preview"
                    wire:loading.attr="disabled" wire:target="preview"
                    :disabled="$summary['count'] === 0">
                    Preview &amp; print
                </flux:button>

                <template x-if="pdfUrl">
                    <flux:text size="sm">
                        Didn't open? <a x-bind:href="pdfUrl" target="_blank" rel="noopener" class="font-medium underline">Open the PDF</a>.
                    </flux:text>
                </template>

                <flux:separator variant="subtle" />

                <div class="space-y-2">
                    <flux:label>Blank stickers</flux:label>
                    <div class="flex items-start gap-2">
                        <flux:input type="number" min="1" max="200" wire:model="blankCount" class="w-24" aria-label="Number of blank stickers" />
                        <flux:button variant="filled" icon="document" wire:click="printBlank"
                            wire:loading.attr="disabled" wire:target="printBlank">Print blank stickers</flux:button>
                    </div>
                    <flux:error name="blankCount" />
                </div>

                <flux:button variant="ghost" icon="viewfinder-circle" class="w-full" wire:click="printCalibration"
                    wire:loading.attr="disabled" wire:target="printCalibration">
                    Calibration page
                </flux:button>
            </flux:card>
        </div>
    </div>

    {{-- Did these print correctly? Opens while a preview is awaiting confirmation. --}}
    <div x-effect="$wire.awaitingConfirmation?.length ? $flux.modal('confirm-tags-printed').show() : $flux.modal('confirm-tags-printed').close()"></div>

    <flux:modal name="confirm-tags-printed" class="w-[560px] max-w-[95vw]" :dismissible="false" :closable="false">
        @if ($awaitingConfirmation !== [])
            @php $awaiting = $this->awaitingTags; @endphp

            <div class="space-y-5" x-data="{ printed: @js(array_map('strval', $awaitingConfirmation)) }">
                <div class="pr-8">
                    <flux:heading size="lg">Did these tags print correctly?</flux:heading>
                    <flux:text class="mt-1">
                        Untick any that came out wrong. Ticked tags are marked Printed; unticked tags stay Pending so you can print them again.
                    </flux:text>
                </div>

                <flux:error name="awaitingConfirmation" />

                <div class="max-h-80 overflow-y-auto rounded-lg border border-zinc-800/10 p-3 dark:border-white/10">
                    <flux:checkbox.group x-model="printed" aria-label="Tags that printed correctly">
                        @foreach ($awaiting as $awaitingTag)
                            <flux:checkbox wire:key="awaiting-{{ $awaitingTag->id }}" value="{{ $awaitingTag->id }}"
                                label="{{ ($awaitingTag->content['property_number'] ?? null) ?: '—' }}"
                                description="{{ $awaitingTag->content['item'] ?? '' }}" />
                        @endforeach
                    </flux:checkbox.group>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-2">
                    <flux:text size="sm"><span x-text="printed.length"></span> of {{ count($awaiting) }} ticked</flux:text>

                    <div class="flex gap-2">
                        <flux:button variant="ghost" wire:click="cancelConfirmation">Not now</flux:button>
                        <flux:button variant="primary" icon="check" x-on:click="$wire.confirmPrinted(printed)"
                            wire:loading.attr="disabled" wire:target="confirmPrinted,cancelConfirmation">Confirm printed</flux:button>
                    </div>
                </div>
            </div>
        @endif
    </flux:modal>
</flux:card>
