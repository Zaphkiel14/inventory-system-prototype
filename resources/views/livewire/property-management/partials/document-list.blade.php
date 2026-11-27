{{--
Shared list for the ICS and PAR pages (PropertyDocumentList components).
Expects: $isIcs (bool), $heading, $intro, $numberLabel.
--}}
@php
    $columnCount = $isIcs ? 10 : 9;
    $renewalSoon = today()->addDays(\App\Livewire\PropertyManagement\PropertyDocumentList::RENEWAL_WINDOW_DAYS);
@endphp

<flux:card variant="default">
    <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
        <div>
            <flux:heading size="xl" level="2">{{ $heading }}</flux:heading>
            <flux:text class="mt-1">{{ $intro }}</flux:text>
        </div>

        <div class="flex flex-wrap gap-2">
            @if ($isIcs)
                @foreach ($this->icsClassOptions as $class => $label)
                    <flux:button size="sm" icon="plus" :variant="$loop->first ? 'primary' : 'filled'"
                        x-on:click="$dispatch('create-property-document', { type: 'ics', icsClass: @js($class) })">
                        New {{ strtoupper($class) }} ICS
                    </flux:button>
                @endforeach
            @else
                <flux:button size="sm" icon="plus" variant="primary"
                    x-on:click="$dispatch('create-property-document', { type: 'par', icsClass: null })">
                    New PAR
                </flux:button>
            @endif
        </div>
    </div>

    @if (session('property-documents-drafted'))
        <flux:callout icon="document-check" color="sky" class="mb-4">
            <flux:callout.text>{{ session('property-documents-drafted') }}</flux:callout.text>
        </flux:callout>
    @endif

    {{-- Toolbar --}}
    <div class="mb-4 flex flex-col gap-2 md:flex-row md:items-center">
        <div class="md:flex-1">
            <flux:input icon="magnifying-glass" size="sm"
                placeholder="Search {{ $numberLabel }}, property no., serial, item, accountable person, office, RIS no., fund cluster"
                wire:model.live.debounce.500ms="search" />
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <flux:select size="sm" class="md:w-44" wire:model.live="status">
                <flux:select.option value="">All statuses</flux:select.option>
                @foreach ($this->statusOptions as $value => $label)
                    <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:switch wire:model.live="renewalDue" label="Renewal due within 30 days" align="left" />
        </div>
    </div>

    @if ($issuance)
        <div class="mb-4">
            <flux:badge color="sky" size="sm">
                From issuance #{{ $issuance }}
                <flux:badge.close wire:click="clearIssuance" aria-label="Show all documents" />
            </flux:badge>
        </div>
    @endif

    <flux:table :paginate="$this->documents" class="transition-opacity"
        wire:loading.delay.class="opacity-60"
        wire:target="search,status,renewalDue,sort,clearIssuance,gotoPage,nextPage,previousPage,setPage">
        <flux:table.columns>
            <flux:table.column sortable :sorted="$sortBy === 'document_number'" :direction="$sortDirection"
                wire:click="sort('document_number')">
                {{ $numberLabel }}
            </flux:table.column>
            @if ($isIcs)
                <flux:table.column>Class</flux:table.column>
            @endif
            <flux:table.column sortable :sorted="$sortBy === 'document_date'" :direction="$sortDirection"
                wire:click="sort('document_date')">
                Date
            </flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'to_name'" :direction="$sortDirection"
                wire:click="sort('to_name')">
                Accountable Person
            </flux:table.column>
            <flux:table.column>Source</flux:table.column>
            <flux:table.column align="end">Lines</flux:table.column>
            <flux:table.column align="end">Total</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'status'" :direction="$sortDirection"
                wire:click="sort('status')">
                Status
            </flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'renewal_due_on'" :direction="$sortDirection"
                wire:click="sort('renewal_due_on')">
                Renewal Due
            </flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->documents as $document)
                @php $class = $document->ics_class; @endphp

                <flux:table.row :key="$document->id">
                    <flux:table.cell variant="strong" class="whitespace-nowrap font-mono font-medium">
                        @if (filled($document->document_number))
                            {{ $document->document_number }}
                        @else
                            <flux:text inline class="font-sans italic">Draft #{{ $document->id }}</flux:text>
                        @endif
                    </flux:table.cell>

                    @if ($isIcs)
                        <flux:table.cell>
                            @if ($class)
                                <flux:badge size="sm" :color="$class->color()">{{ strtoupper($class->value) }}</flux:badge>
                            @endif
                        </flux:table.cell>
                    @endif

                    <flux:table.cell class="whitespace-nowrap">{{ $document->document_date?->format('M d, Y') }}</flux:table.cell>

                    <flux:table.cell>
                        {{ $document->to_name ?: '—' }}
                        @if (filled($document->to_position))
                            <flux:text size="sm" class="max-w-56 truncate" :title="$document->to_position">{{ $document->to_position }}</flux:text>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell>
                        @if ($document->requisition)
                            RIS {{ $document->requisition->ris_number ?: $document->requisition->request_number }}
                            @if (filled($document->requisition->office))
                                <flux:text size="sm" class="max-w-48 truncate" :title="$document->requisition->office">{{ $document->requisition->office }}</flux:text>
                            @endif
                        @else
                            <flux:text inline size="sm">Hand-encoded</flux:text>
                        @endif
                        @if (filled($document->fund_cluster))
                            <flux:text size="sm" class="max-w-48 truncate" :title="$document->fund_cluster">{{ $document->fund_cluster }}</flux:text>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell align="end">{{ number_format((int) $document->lines_count) }}</flux:table.cell>
                    <flux:table.cell align="end" class="whitespace-nowrap tabular-nums">₱{{ number_format((float) $document->lines_total, 2) }}</flux:table.cell>

                    <flux:table.cell>
                        @if ($document->status)
                            <flux:badge size="sm" :color="$document->status->color()">{{ $document->status->label() }}</flux:badge>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell class="whitespace-nowrap">
                        @if ($document->renewal_due_on)
                            @php $dueSoon = $document->status === \App\Enums\PropertyDocumentStatus::Active && $document->renewal_due_on->lte($renewalSoon); @endphp
                            <flux:text inline :color="$dueSoon ? 'amber' : null" class="{{ $dueSoon ? 'font-medium' : '' }}">
                                {{ $document->renewal_due_on->format('M d, Y') }}
                            </flux:text>
                        @else
                            <flux:text inline>—</flux:text>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell align="end" class="whitespace-nowrap">
                        @if (filled($document->document_number))
                            <flux:tooltip content="Print {{ $document->document_number }}">
                                <flux:button size="xs" variant="ghost" icon="printer" target="_blank"
                                    :href="route('property-management.documents.pdf', $document)"
                                    aria-label="Print {{ $document->document_number }}" />
                            </flux:tooltip>
                        @endif

                        <flux:button size="xs" variant="ghost" icon="pencil-square"
                            x-on:click="$dispatch('edit-property-document', { id: {{ $document->id }} })">
                            {{ $document->isFinalized() ? 'Open' : 'Edit' }}
                        </flux:button>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="{{ $columnCount }}">
                        <div class="flex flex-col items-center justify-center gap-2 py-8 text-center">
                            <flux:icon name="document-text" variant="outline" class="size-8 text-zinc-400" />
                            <flux:heading size="sm">No {{ $numberLabel === 'ICS No.' ? 'custodian slips' : 'acknowledgment receipts' }} found</flux:heading>
                            <flux:text size="sm">
                                Issue property items, then create the {{ $isIcs ? 'ICS' : 'PAR' }} from the Issuances page, or encode one by hand.
                            </flux:text>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <livewire:property-management.property-document-editor />
</flux:card>
