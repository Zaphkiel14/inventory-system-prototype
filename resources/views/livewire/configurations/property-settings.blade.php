<section class="w-full">
    @include('partials.configuration-heading')

    <flux:heading level="2" class="sr-only">{{ __('Property Settings') }}</flux:heading>

    <x-configurations.layout :heading="__('Property Settings')"
        :subheading="__('Entity details, classification thresholds and numbering rules for RIS, ICS and PAR forms')">

        <form wire:submit="save" class="max-w-2xl space-y-8">

            {{-- Entity --}}
            <flux:fieldset>
                <flux:legend>Entity</flux:legend>
                <flux:description>Printed on the letterhead of every property form.</flux:description>

                <div class="mt-4 space-y-4">
                    <flux:input wire:model="form.entity_name" label="Entity name" required />

                    <flux:textarea wire:model="form.entity_address" label="Entity address" rows="2" required />
                </div>
            </flux:fieldset>

            <flux:separator variant="subtle" />

            {{-- Classification --}}
            <flux:fieldset>
                <flux:legend>Classification thresholds</flux:legend>
                <flux:description>
                    Unit cost decides the class: SPLV up to the SPLV threshold, PPE from the PPE threshold up,
                    and SPHV in between.
                </flux:description>

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <flux:field>
                        <flux:label>SPLV threshold</flux:label>
                        <flux:input.group>
                            <flux:input.group.prefix>₱</flux:input.group.prefix>
                            <flux:input wire:model="form.splv_threshold" inputmode="decimal" placeholder="5000.00"
                                required />
                        </flux:input.group>
                        <flux:description>Unit cost at or below this is SPLV.</flux:description>
                        <flux:error name="form.splv_threshold" />
                    </flux:field>

                    <flux:field>
                        <flux:label>PPE threshold</flux:label>
                        <flux:input.group>
                            <flux:input.group.prefix>₱</flux:input.group.prefix>
                            <flux:input wire:model="form.ppe_threshold" inputmode="decimal" placeholder="50000.00"
                                required />
                        </flux:input.group>
                        <flux:description>Unit cost at or above this is PPE.</flux:description>
                        <flux:error name="form.ppe_threshold" />
                    </flux:field>
                </div>
            </flux:fieldset>

            <flux:separator variant="subtle" />

            {{-- Documents --}}
            <flux:fieldset>
                <flux:legend>Documents and tags</flux:legend>

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <flux:select wire:model="form.ics_tagging" label="ICS tagging"
                        description="Which ICS items get a property tag. PAR items are always tagged.">
                        @foreach ($this->icsTaggingOptions as $value => $label)
                            <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model="form.paper_size" label="Paper size"
                        description="Used for RIS, ICS and PAR printouts.">
                        @foreach ($this->paperSizeOptions as $value => $label)
                            <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:input wire:model="form.renewal_years" type="number" min="1" max="50"
                        label="Renewal period (years)"
                        description="ICS and PAR are due for renewal this long after the document date." required />
                </div>
            </flux:fieldset>

            <flux:separator variant="subtle" />

            {{-- Number patterns --}}
            <flux:fieldset>
                <flux:legend>Control number patterns</flux:legend>
                <flux:description>
                    Regular expressions without delimiters. A number that doesn't match can still be saved with
                    an override reason.
                </flux:description>

                <div class="mt-4 space-y-4">
                    <flux:input wire:model="form.ris_pattern" label="RIS number" class:input="font-mono"
                        description="Example: 26102455" required />

                    <flux:input wire:model="form.ics_pattern" label="ICS number" class:input="font-mono"
                        description="Example: SPLV-2026-10-062" required />

                    <flux:input wire:model="form.par_pattern" label="PAR number" class:input="font-mono"
                        description="Example: 2026-01-003" required />
                </div>
            </flux:fieldset>

            <div class="flex justify-end border-t border-zinc-200 pt-4 dark:border-zinc-700">
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="save">
                    <span wire:loading.remove wire:target="save">Save Settings</span>
                    <span wire:loading wire:target="save">Saving...</span>
                </flux:button>
            </div>
        </form>
    </x-configurations.layout>
</section>
