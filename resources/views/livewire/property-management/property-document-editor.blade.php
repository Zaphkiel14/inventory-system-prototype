<div>
    <flux:modal name="property-document-editor" class="w-[1200px] max-w-[95vw]" wire:close="close">
        @if ($form->type !== '')
            @php
                $isIcs = $form->type === \App\Enums\PropertyDocumentType::Ics->value;
                $finalized = $this->isFinalized;
                $document = $this->document;
                $classes = $this->lineClasses;
                $icsClass = \App\Enums\PropertyClass::tryFrom($form->ics_class);
                $typeLabel = $isIcs ? 'ICS' : 'PAR';
                $numberExample = $isIcs ? (($icsClass?->value === 'sphv' ? 'SPHV' : 'SPLV').'-2026-10-062') : '2026-01-003';
                $fromHeading = $isIcs ? 'Received from' : 'Issued by';
            @endphp

            <div class="space-y-6"
                x-data="{
                    peso(value) {
                        const amount = parseFloat(value);
                        return Number.isFinite(amount)
                            ? '₱' + amount.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
                            : '—';
                    },
                    lineTotal(line) {
                        const qty = parseFloat(line?.quantity);
                        const cost = parseFloat(line?.unit_cost);
                        return Number.isFinite(qty) && Number.isFinite(cost) ? qty * cost : null;
                    },
                    grandTotal() {
                        return Object.values($wire.form.lines ?? {}).reduce((sum, line) => sum + (this.lineTotal(line) ?? 0), 0);
                    },
                }">

                {{-- Header --}}
                <div class="pr-8">
                    <div class="flex flex-wrap items-center gap-2">
                        <flux:heading size="lg">
                            @if (filled($document?->document_number))
                                {{ $typeLabel }} {{ $document->document_number }}
                            @elseif ($document)
                                Draft {{ $typeLabel }} #{{ $document->id }}
                            @else
                                New {{ $isIcs && $icsClass ? strtoupper($icsClass->value).' ' : '' }}{{ $typeLabel }}
                            @endif
                        </flux:heading>

                        @if ($isIcs && $icsClass)
                            <flux:badge size="sm" :color="$icsClass->color()">{{ $icsClass->label() }}</flux:badge>
                        @endif

                        @if ($document?->status)
                            <flux:badge size="sm" :color="$document->status->color()">{{ $document->status->label() }}</flux:badge>
                        @endif
                    </div>

                    <flux:text class="mt-1">
                        @if ($finalized)
                            Finalized{{ $document->finalized_at ? ' on '.$document->finalized_at->format('M d, Y') : '' }}.
                            Corrections need a reason and are written to the audit log.
                        @else
                            {{ $isIcs ? 'Inventory Custodian Slip' : 'Property Acknowledgment Receipt' }}.
                            Drafts can be changed freely until they're finalized.
                        @endif
                    </flux:text>
                </div>

                @error('form.document_id')
                    <flux:callout icon="exclamation-triangle" color="red">
                        <flux:callout.text>{{ $message }}</flux:callout.text>
                    </flux:callout>
                @enderror

                {{-- Document --}}
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <flux:input wire:model.blur="form.document_number" label="{{ $typeLabel }} No."
                        class:input="font-mono" placeholder="{{ $numberExample }}"
                        description="Example: {{ $numberExample }}" />

                    <flux:input wire:model="form.fund_cluster" label="Fund Cluster"
                        placeholder="e.g. 05 - Internally Generated Funds" />

                    <x-date-picker wire:model="form.document_date" label="Date" />

                    <flux:input wire:model="form.iar_number" label="IAR No." badge="Optional" />
                </div>

                @if ($form->patternMismatch())
                    <div class="space-y-3">
                        <flux:callout icon="exclamation-triangle" color="amber">
                            <flux:callout.heading>{{ $typeLabel }} No. doesn't match the usual pattern</flux:callout.heading>
                            <flux:callout.text>
                                Check the number. To save it anyway, say why below. The reason is written to the audit log.
                            </flux:callout.text>
                        </flux:callout>

                        <flux:textarea wire:model="form.pattern_override_reason" label="Override reason" rows="2"
                            placeholder="Why this number is correct as typed" />
                    </div>
                @endif

                {{-- Signatories --}}
                <div class="grid gap-4 md:grid-cols-2">
                    <flux:fieldset class="rounded-lg border border-zinc-200 p-4 dark:border-white/10">
                        <flux:legend>{{ $fromHeading }}</flux:legend>
                        <div class="space-y-3">
                            <flux:input wire:model="form.from_name" label="Name" />
                            <div class="grid gap-3 sm:grid-cols-2">
                                <flux:input wire:model="form.from_position" label="Position/Office" />
                                <x-date-picker wire:model="form.from_date" label="Date" clearable />
                            </div>
                        </div>
                    </flux:fieldset>

                    <flux:fieldset class="rounded-lg border border-zinc-200 p-4 dark:border-white/10">
                        <flux:legend>Received by</flux:legend>
                        <flux:description>The accountable person.</flux:description>
                        <div class="mt-3 space-y-3">
                            <flux:input wire:model="form.to_name" label="Name" />
                            <div class="grid gap-3 sm:grid-cols-2">
                                <flux:input wire:model="form.to_position" label="Position/Office" />
                                <x-date-picker wire:model="form.to_date" label="Date" clearable />
                            </div>
                        </div>
                    </flux:fieldset>
                </div>

                {{-- Lines --}}
                <div class="space-y-3">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <flux:heading>Items</flux:heading>
                        <flux:text>
                            Total
                            <span class="font-semibold tabular-nums text-zinc-800 dark:text-white" x-text="peso(grandTotal())"></span>
                        </flux:text>
                    </div>

                    <flux:error name="form.lines" :deep="false" />

                    @foreach ($form->lines as $i => $line)
                        @php
                            $fromIssuance = filled($line['issued_property_unit_id'] ?? null);
                            $lineClass = \App\Enums\PropertyClass::tryFrom((string) ($classes[$i] ?? ''));
                        @endphp

                        <div wire:key="document-line-{{ $line['id'] ?? 'new' }}-{{ $i }}"
                            class="space-y-3 rounded-lg border border-zinc-200 p-4 dark:border-white/10">
                            <div class="flex flex-wrap items-center gap-2">
                                <flux:text variant="strong" class="font-medium">Line {{ $loop->iteration }}</flux:text>

                                @if ($lineClass)
                                    <flux:badge size="sm" :color="$lineClass->color()">{{ $lineClass->label() }}</flux:badge>
                                @endif

                                @if ($fromIssuance)
                                    <flux:badge size="sm" color="zinc" icon="truck">From issuance</flux:badge>
                                @endif

                                <div class="ms-auto flex items-center gap-3">
                                    <flux:text size="sm">
                                        Line total
                                        <span class="font-medium tabular-nums text-zinc-800 dark:text-white"
                                            x-text="peso(lineTotal($wire.form.lines[{{ $i }}]))"></span>
                                    </flux:text>

                                    @if (! $finalized && ! $fromIssuance)
                                        <flux:button size="xs" variant="ghost" icon="trash" class="text-red-500 hover:text-red-600"
                                            wire:click="removeLine({{ $i }})" aria-label="Remove line {{ $loop->iteration }}" />
                                    @endif
                                </div>
                            </div>

                            @error('form.lines.'.$i)
                                <flux:callout icon="exclamation-triangle" color="red">
                                    <flux:callout.text>{{ $message }}</flux:callout.text>
                                </flux:callout>
                            @enderror

                            <div class="grid gap-3 md:grid-cols-2">
                                <flux:input wire:model="form.lines.{{ $i }}.item_name" label="Item" class:input="font-semibold"
                                    placeholder="e.g. ELECTRIC FAN" />
                                <flux:input wire:model="form.lines.{{ $i }}.item_type" label="Type / description"
                                    placeholder="e.g. Industrial, Ground Type" />
                            </div>

                            <div class="grid gap-3 sm:grid-cols-3">
                                <flux:input wire:model="form.lines.{{ $i }}.brand" label="Brand" />
                                <flux:input wire:model="form.lines.{{ $i }}.model" label="Model" />
                                <flux:input wire:model="form.lines.{{ $i }}.serial_number" label="Serial No." class:input="font-mono" />
                            </div>

                            <div class="grid gap-3 sm:grid-cols-2 {{ $isIcs ? 'lg:grid-cols-6' : 'lg:grid-cols-5' }}">
                                <flux:input wire:model.live.debounce.400ms="form.lines.{{ $i }}.quantity" type="number" min="1"
                                    label="Quantity" :readonly="$fromIssuance" />
                                <flux:input wire:model="form.lines.{{ $i }}.unit" label="Unit" placeholder="unit" :readonly="$fromIssuance" />

                                <flux:field>
                                    <flux:label>Unit cost</flux:label>
                                    <flux:input.group>
                                        <flux:input.group.prefix>₱</flux:input.group.prefix>
                                        <flux:input wire:model.live.debounce.400ms="form.lines.{{ $i }}.unit_cost" inputmode="decimal"
                                            placeholder="0.00" :readonly="$fromIssuance" />
                                    </flux:input.group>
                                    <flux:error name="form.lines.{{ $i }}.unit_cost" />
                                </flux:field>

                                <flux:input wire:model="form.lines.{{ $i }}.property_number"
                                    label="{{ $isIcs ? 'Inventory Item No.' : 'Property Number' }}" class:input="font-mono" />

                                @if ($isIcs)
                                    <flux:input wire:model="form.lines.{{ $i }}.estimated_useful_life_years" type="number" min="1"
                                        label="Useful life (years)" />
                                @endif

                                <x-date-picker wire:model="form.lines.{{ $i }}.date_acquired" label="Date acquired" clearable />
                            </div>
                        </div>
                    @endforeach

                    @unless ($finalized)
                        <flux:button size="sm" variant="filled" icon="plus" wire:click="addLine">Add line</flux:button>
                    @endunless
                </div>

                {{-- Finalized: correction reason and status --}}
                @if ($finalized)
                    <flux:separator variant="subtle" />

                    <flux:textarea wire:model="form.change_reason" label="Reason for this correction" rows="2"
                        description="Needed to save changes to a finalized document. Written to the audit log." />

                    @if ($this->statusOptions !== [])
                        <flux:fieldset x-data="{ status: '', reason: '', busy: false }"
                            class="rounded-lg border border-zinc-200 p-4 dark:border-white/10">
                            <flux:legend>Change status</flux:legend>
                            <flux:description>Mark the property returned, transferred, renewed or disposed. The reason is logged.</flux:description>

                            <div class="mt-3 grid gap-3 md:grid-cols-[14rem_minmax(0,1fr)_auto] md:items-end">
                                <flux:select x-model="status" label="New status">
                                    <flux:select.option value="">Choose...</flux:select.option>
                                    @foreach ($this->statusOptions as $value => $label)
                                        <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                                    @endforeach
                                </flux:select>

                                <flux:input x-model="reason" label="Reason" placeholder="At least 5 characters" />

                                <flux:button variant="filled" icon="arrow-path"
                                    x-bind:disabled="busy || status === '' || reason.trim().length < 5"
                                    x-on:click="busy = true; $wire.changeStatus(status, reason).finally(() => busy = false)">
                                    Change status
                                </flux:button>
                            </div>

                            <flux:error name="form.status" />
                        </flux:fieldset>
                    @endif
                @endif

                <flux:separator variant="subtle" />

                {{-- Footer --}}
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        @if ($document && ! $finalized)
                            <flux:button variant="ghost" icon="trash" class="text-red-500 hover:text-red-600"
                                wire:click="deleteDraft" wire:loading.attr="disabled" wire:target="deleteDraft"
                                wire:confirm="Delete this draft {{ $typeLabel }}? Its issued units go back to waiting for a document.">
                                Delete draft
                            </flux:button>
                        @endif

                        @if ($finalized && filled($document?->document_number))
                            <flux:button variant="ghost" icon="printer" target="_blank"
                                :href="route('property-management.documents.pdf', $document)">
                                Print
                            </flux:button>
                        @endif
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <flux:modal.close>
                            <flux:button variant="ghost">{{ $finalized ? 'Close' : 'Cancel' }}</flux:button>
                        </flux:modal.close>

                        @if ($finalized)
                            <flux:button variant="primary" icon="check" wire:click="save"
                                wire:loading.attr="disabled" wire:target="save">Save correction</flux:button>
                        @else
                            <flux:button variant="filled" wire:click="save"
                                wire:loading.attr="disabled" wire:target="save">Save draft</flux:button>
                            <flux:modal.trigger name="confirm-finalize-document">
                                <flux:button variant="primary" icon="lock-closed">Finalize</flux:button>
                            </flux:modal.trigger>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </flux:modal>

    {{-- Finalize confirmation (opens on top of the editor) --}}
    <flux:modal name="confirm-finalize-document" class="w-[520px] max-w-[95vw]">
        @if ($form->type !== '')
            @php
                $isIcs = $form->type === \App\Enums\PropertyDocumentType::Ics->value;
                $tagging = \App\Support\PropertySettings::icsTagging();
            @endphp

            <div class="space-y-6">
                <div class="pr-8">
                    <flux:heading size="lg">Finalize this {{ $isIcs ? 'ICS' : 'PAR' }}?</flux:heading>
                    <flux:text class="mt-1">The document becomes the active custody record.</flux:text>
                </div>

                <ul class="list-disc space-y-1 ps-5 text-sm text-zinc-600 dark:text-zinc-300">
                    <li>Lines can no longer be added or removed.</li>
                    <li>Any later correction needs a reason and is written to the audit log.</li>
                    <li>Status becomes Active, and renewal falls due {{ \App\Support\PropertySettings::renewalYears() }} years after the document date.</li>
                    <li>
                        @if (! $isIcs)
                            Every line gets a property tag in the print queue.
                        @else
                            Property tags: {{ $tagging->label() }}.
                        @endif
                    </li>
                </ul>

                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button variant="ghost">Go back</flux:button>
                    </flux:modal.close>

                    <flux:button variant="primary" icon="lock-closed" wire:click="finalize"
                        wire:loading.attr="disabled" wire:target="finalize"
                        x-on:click="$flux.modal('confirm-finalize-document').close()">
                        Finalize
                    </flux:button>
                </div>
            </div>
        @endif
    </flux:modal>
</div>
