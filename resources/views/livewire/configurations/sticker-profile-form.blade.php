<div>
    <flux:modal name="sticker-profile-form" class="w-[1000px] max-w-[95vw]">
        @php
            $fits = $this->layoutFits;
            $slots = $this->previewSlots;
            $pageW = max(1.0, (float) $form->page_width_mm);
            $pageH = max(1.0, (float) $form->page_height_mm);
            $pct = fn (float $value, float $of): string => round($value / $of * 100, 4).'%';

            $mmField = fn (string $field, string $label, ?string $description = null) => compact('field', 'label', 'description');
            $sections = [
                'Page' => [
                    $mmField('page_width_mm', 'Width'),
                    $mmField('page_height_mm', 'Height'),
                ],
                'Tag' => [
                    $mmField('tag_width_mm', 'Width'),
                    $mmField('tag_height_mm', 'Height'),
                ],
                'Margins and gaps' => [
                    $mmField('margin_top_mm', 'Top margin', 'Page edge to the first row.'),
                    $mmField('margin_left_mm', 'Left margin', 'Page edge to the first column.'),
                    $mmField('gap_x_mm', 'Gap between columns'),
                    $mmField('gap_y_mm', 'Gap between rows'),
                ],
            ];
        @endphp

        <div class="space-y-6">
            <div class="pr-8">
                <flux:heading size="lg">{{ $form->profile ? 'Edit sticker profile' : 'New sticker profile' }}</flux:heading>
                <flux:text class="mt-1">All measurements are in millimetres. The preview updates as you type.</flux:text>
            </div>

            @error('form.layout')
                <flux:callout icon="exclamation-triangle" color="red">
                    <flux:callout.text>{{ $message }}</flux:callout.text>
                </flux:callout>
            @enderror

            <form wire:submit="save" class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_18rem]">
                <div class="space-y-5">
                    <flux:input wire:model="form.name" label="Name" placeholder="e.g. A4 · 2 × 5" required />

                    <div class="grid grid-cols-2 gap-4">
                        <flux:input wire:model.live.debounce.400ms="form.columns" type="number" min="1" max="20" label="Columns" />
                        <flux:input wire:model.live.debounce.400ms="form.rows" type="number" min="1" max="20" label="Rows" />
                    </div>

                    @foreach ($sections as $legend => $fields)
                        <flux:fieldset>
                            <flux:legend>{{ $legend }}</flux:legend>
                            <div class="grid gap-4 sm:grid-cols-2">
                                @foreach ($fields as $input)
                                    <flux:field>
                                        <flux:label>{{ $input['label'] }}</flux:label>
                                        <flux:input.group>
                                            <flux:input wire:model.live.debounce.400ms="form.{{ $input['field'] }}" inputmode="decimal" placeholder="0.00" />
                                            <flux:input.group.suffix>mm</flux:input.group.suffix>
                                        </flux:input.group>
                                        @if ($input['description'])
                                            <flux:description>{{ $input['description'] }}</flux:description>
                                        @endif
                                        <flux:error name="form.{{ $input['field'] }}" />
                                    </flux:field>
                                @endforeach
                            </div>
                        </flux:fieldset>
                    @endforeach

                    <flux:fieldset>
                        <flux:legend>Calibration offsets</flux:legend>
                        <flux:description>
                            Print the calibration page, measure how far the outlines are from the sticker edges, and enter that here.
                            Positive X moves right, positive Y moves down (−20 to 20 mm).
                        </flux:description>
                        <div class="mt-3 grid gap-4 sm:grid-cols-2">
                            @foreach (['offset_x_mm' => 'X offset', 'offset_y_mm' => 'Y offset'] as $field => $label)
                                <flux:field>
                                    <flux:label>{{ $label }}</flux:label>
                                    <flux:input.group>
                                        <flux:input wire:model="form.{{ $field }}" inputmode="decimal" placeholder="0.00" />
                                        <flux:input.group.suffix>mm</flux:input.group.suffix>
                                    </flux:input.group>
                                    <flux:error name="form.{{ $field }}" />
                                </flux:field>
                            @endforeach
                        </div>
                    </flux:fieldset>
                </div>

                {{-- Live preview, scaled to the page --}}
                <div class="space-y-3 lg:sticky lg:top-0 lg:self-start">
                    <div class="flex items-center justify-between">
                        <flux:heading size="sm">Preview</flux:heading>
                        @if ($fits)
                            <flux:badge size="sm" color="green" icon="check">Fits</flux:badge>
                        @else
                            <flux:badge size="sm" color="red" icon="exclamation-triangle">Doesn't fit</flux:badge>
                        @endif
                    </div>

                    <div class="mx-auto w-full max-w-64">
                        <div @class([
                                'relative w-full overflow-hidden rounded-sm bg-white shadow-sm ring-1',
                                'ring-zinc-300 dark:ring-white/20' => $fits,
                                'ring-2 ring-red-500' => ! $fits,
                            ])
                            style="aspect-ratio: {{ $pageW }} / {{ $pageH }};"
                            role="img"
                            aria-label="{{ count($slots) }} tags on a {{ number_format($pageW, 1) }} by {{ number_format($pageH, 1) }} mm page">
                            @foreach ($slots as $slot)
                                @php $outside = $slot['x_mm'] + $slot['width_mm'] > $pageW + 0.01 || $slot['y_mm'] + $slot['height_mm'] > $pageH + 0.01; @endphp
                                <div wire:key="preview-slot-{{ $slot['index'] }}"
                                    @class([
                                        'absolute flex items-center justify-center rounded-[1px] border text-[9px] tabular-nums',
                                        'border-zinc-400 bg-zinc-100 text-zinc-500' => ! $outside,
                                        'border-red-500 bg-red-50 text-red-600' => $outside,
                                    ])
                                    style="left: {{ $pct($slot['x_mm'], $pageW) }}; top: {{ $pct($slot['y_mm'], $pageH) }}; width: {{ $pct($slot['width_mm'], $pageW) }}; height: {{ $pct($slot['height_mm'], $pageH) }};">
                                    {{ $slot['index'] }}
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <flux:text size="sm" class="text-center">
                        @if ($slots === [])
                            Enter columns and rows to see the layout.
                        @else
                            {{ count($slots) }} tags per sheet ·
                            {{ number_format($pageW, 1) }} × {{ number_format($pageH, 1) }} mm
                        @endif
                    </flux:text>

                    @unless ($fits)
                        <flux:text size="sm" color="red">
                            The margins, tags and gaps add up to more than the page. Reduce them or the number of columns or rows.
                        </flux:text>
                    @endunless
                </div>

                <div class="flex justify-end gap-2 border-t border-zinc-200 pt-4 lg:col-span-2 dark:border-zinc-700">
                    <flux:modal.close>
                        <flux:button variant="ghost" type="button">Cancel</flux:button>
                    </flux:modal.close>

                    <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="save">
                        <span wire:loading.remove wire:target="save">Save Profile</span>
                        <span wire:loading wire:target="save">Saving...</span>
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>
</div>
