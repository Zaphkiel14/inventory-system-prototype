<div class="space-y-6">
    <flux:card>
        <form wire:submit="lookup" class="space-y-4">
            <div>
                <flux:heading size="lg">{{ __('Track a request') }}</flux:heading>
                <flux:text class="mt-1">{{ __('Enter the tracking number you received when you submitted your request.') }}</flux:text>
            </div>

            <div class="flex flex-col gap-2 sm:flex-row sm:items-start">
                <div class="flex-1">
                    <flux:input wire:model="code" :placeholder="__('e.g. TRK-7F3K9Q2M')" icon="hashtag"
                        class="font-mono uppercase" :aria-label="__('Tracking number')" />
                </div>
                <flux:button type="submit" variant="primary" icon="magnifying-glass">{{ __('Track') }}</flux:button>
            </div>
        </form>
    </flux:card>

    @if ($searched && ! $this->requisition)
        <flux:callout icon="question-mark-circle" variant="secondary">
            <flux:callout.heading>{{ __('No request found for that tracking number') }}</flux:callout.heading>
            <flux:callout.text>{{ __('Check the number and try again.') }}</flux:callout.text>
        </flux:callout>
    @endif

    @if ($request = $this->requisition)
        <flux:card class="space-y-6">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <flux:heading size="lg" class="font-mono">{{ $request->tracking_code }}</flux:heading>
                    <flux:text class="mt-1">
                        {{ __('Submitted :date', ['date' => $request->submitted_at?->format('M d, Y h:i A')]) }}
                    </flux:text>
                </div>

                @if ($request->status)
                    <flux:badge :color="$request->status->color()" data-test="tracking-status">{{ $request->status->label() }}</flux:badge>
                @endif
            </div>

            <dl class="grid gap-4 sm:grid-cols-3">
                <div>
                    <flux:text size="sm">{{ __('Requested by') }}</flux:text>
                    <flux:text variant="strong">{{ $request->requester_name }}</flux:text>
                </div>
                <div class="sm:col-span-2">
                    <flux:text size="sm">{{ __('Designation') }}</flux:text>
                    <flux:text variant="strong">{{ $request->designation ?? '—' }}</flux:text>
                </div>
            </dl>

            {{-- Progress through the stages --}}
            @php
                $decision = $request->latestApproval;
                $lastIssuance = $request->stockIssuances->sortByDesc('released_at')->first();
                $steps = [
                    ['label' => __('Submitted'), 'done' => true, 'date' => $request->submitted_at],
                    ['label' => $decision?->decision?->label() ?? __('Awaiting approval'), 'done' => (bool) $decision, 'date' => $decision?->decided_at],
                    ['label' => __('Released'), 'done' => (bool) $lastIssuance, 'date' => $lastIssuance?->released_at],
                ];
            @endphp

            <ol class="grid gap-3 sm:grid-cols-3">
                @foreach ($steps as $step)
                    <li class="flex items-start gap-2">
                        @if ($step['done'])
                            <flux:icon.check-circle variant="mini" class="mt-0.5 size-5 shrink-0 text-(--color-accent-content)" />
                        @else
                            <flux:icon.clock variant="mini" class="mt-0.5 size-5 shrink-0 text-zinc-400" />
                        @endif
                        <div>
                            <flux:text variant="strong">{{ $step['label'] }}</flux:text>
                            @if ($step['date'])
                                <flux:text size="sm">{{ $step['date']->format('M d, Y h:i A') }}</flux:text>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ol>

            @if ($decision?->remarks)
                <flux:callout icon="chat-bubble-left-ellipsis" variant="secondary">
                    <flux:callout.heading>{{ __('Approver remarks') }}</flux:callout.heading>
                    <flux:callout.text>{{ $decision->remarks }}</flux:callout.text>
                </flux:callout>
            @endif

            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Item') }}</flux:table.column>
                    <flux:table.column align="end">{{ __('Requested') }}</flux:table.column>
                    <flux:table.column align="end">{{ __('Approved') }}</flux:table.column>
                    <flux:table.column align="end">{{ __('Released') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($request->items as $line)
                        <flux:table.row :key="'track-line-'.$line->id">
                            <flux:table.cell variant="strong">
                                {{ $line->inventoryItem?->name ?? '—' }}
                                <flux:text size="sm">{{ $line->inventoryItem?->unit }}</flux:text>
                            </flux:table.cell>
                            <flux:table.cell align="end">{{ number_format($line->requested_qty) }}</flux:table.cell>
                            <flux:table.cell align="end">
                                @if ($line->approved_qty === null)
                                    <flux:text inline size="sm">{{ __('Pending') }}</flux:text>
                                @else
                                    {{ number_format($line->approved_qty) }}
                                @endif
                            </flux:table.cell>
                            <flux:table.cell align="end">{{ number_format($line->released_qty) }}</flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </flux:card>
    @endif
</div>
