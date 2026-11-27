<flux:card variant="default" x-data="{ expanded: {} }">
    @php
        $hasFilters = $search !== '' || $action !== '' || $auditableType !== '' || $from !== '' || $to !== '' || $user !== '';
        $display = function ($value): string {
            if ($value === null || $value === '') {
                return '—';
            }
            if (is_bool($value)) {
                return $value ? 'true' : 'false';
            }
            if (is_array($value)) {
                return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
            }

            return (string) $value;
        };
    @endphp

    <div class="mb-4">
        <flux:heading size="xl" level="2">Audit Logs</flux:heading>
        <flux:text class="mt-1">
            Who changed what and when, with the reason and the old and new values. Covers ICS, PAR and RIS records.
        </flux:text>
    </div>

    {{-- Filters --}}
    <div class="mb-4 space-y-2">
        <div class="flex flex-col gap-2 md:flex-row md:items-center">
            <div class="md:flex-1">
                <flux:input icon="magnifying-glass" size="sm" placeholder="Search user, document / property / RIS no., reason"
                    wire:model.live.debounce.500ms="search" />
            </div>

            <flux:select size="sm" class="md:w-44" wire:model.live="action" aria-label="Action">
                <flux:select.option value="">All actions</flux:select.option>
                @foreach ($this->actionOptions as $value => $label)
                    <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select size="sm" class="md:w-36" wire:model.live="auditableType" aria-label="Record type">
                <flux:select.option value="">All records</flux:select.option>
                @foreach ($this->typeOptions as $value => $label)
                    <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        <div class="flex flex-col gap-2 md:flex-row md:items-center">
            <x-date-picker wire:model.live="from" size="sm" aria-label="From" placeholder="From" clearable class="md:w-44" />
            <x-date-picker wire:model.live="to" size="sm" aria-label="To" placeholder="To" clearable align="end" class="md:w-44" />

            <flux:select size="sm" class="md:w-48" wire:model.live="user" aria-label="User">
                <flux:select.option value="">All users</flux:select.option>
                @foreach ($this->users as $id => $name)
                    <flux:select.option value="{{ $id }}">{{ $name }}</flux:select.option>
                @endforeach
            </flux:select>

            @if ($hasFilters)
                <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="clearFilters">Clear filters</flux:button>
            @endif
        </div>
    </div>

    <flux:table :paginate="$this->logs" class="transition-opacity"
        wire:loading.delay.class="opacity-60"
        wire:target="search,action,auditableType,from,to,user,clearFilters,gotoPage,nextPage,previousPage,setPage">
        <flux:table.columns>
            <flux:table.column>When</flux:table.column>
            <flux:table.column>User</flux:table.column>
            <flux:table.column>Action</flux:table.column>
            <flux:table.column>Record</flux:table.column>
            <flux:table.column>Reason</flux:table.column>
            <flux:table.column align="end">Changes</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->logs as $log)
                @php $changes = $log->changes(); @endphp

                <flux:table.row :key="'log-'.$log->id">
                    <flux:table.cell class="whitespace-nowrap">
                        {{ $log->created_at?->format('M d, Y') }}
                        <flux:text size="sm">{{ $log->created_at?->format('h:i A') }}</flux:text>
                    </flux:table.cell>

                    <flux:table.cell>{{ $log->user?->name ?? 'System' }}</flux:table.cell>

                    <flux:table.cell>
                        @if ($log->action)
                            <flux:badge size="sm" :color="$log->action->color()">{{ $log->action->label() }}</flux:badge>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell variant="strong" class="max-w-72">
                        <span class="block truncate" title="{{ $log->recordLabel() }}">{{ $log->recordLabel() }}</span>
                    </flux:table.cell>

                    <flux:table.cell class="max-w-72">
                        @if (filled($log->reason))
                            <flux:text class="line-clamp-2" :title="$log->reason">{{ $log->reason }}</flux:text>
                        @else
                            <flux:text inline>—</flux:text>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell align="end">
                        @if ($changes !== [])
                            <flux:button size="xs" variant="ghost"
                                x-on:click="expanded[{{ $log->id }}] = ! expanded[{{ $log->id }}]"
                                x-bind:aria-expanded="!! expanded[{{ $log->id }}]"
                                aria-controls="log-changes-{{ $log->id }}">
                                {{ count($changes) }} {{ \Illuminate\Support\Str::plural('field', count($changes)) }}
                                <flux:icon name="chevron-down" variant="micro" class="transition-transform"
                                    x-bind:class="expanded[{{ $log->id }}] && 'rotate-180'" />
                            </flux:button>
                        @else
                            <flux:text inline size="sm">—</flux:text>
                        @endif
                    </flux:table.cell>
                </flux:table.row>

                @if ($changes !== [])
                    <flux:table.row :key="'log-changes-'.$log->id" id="log-changes-{{ $log->id }}"
                        x-cloak x-show="expanded[{{ $log->id }}]">
                        <flux:table.cell colspan="6" class="bg-zinc-50 dark:bg-white/5">
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="text-start text-zinc-500 dark:text-zinc-400">
                                            <th class="w-48 py-1 pe-4 text-start font-medium">Field</th>
                                            <th class="py-1 pe-4 text-start font-medium">Old</th>
                                            <th class="py-1 text-start font-medium">New</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($changes as $field => [$old, $new])
                                            <tr class="border-t border-zinc-200 dark:border-white/10">
                                                <td class="py-1 pe-4 font-mono text-xs text-zinc-600 dark:text-zinc-300">{{ $field }}</td>
                                                <td class="py-1 pe-4 break-all text-zinc-500 line-through decoration-zinc-400 dark:text-zinc-400">{{ $display($old) }}</td>
                                                <td class="py-1 break-all font-medium text-zinc-800 dark:text-white">{{ $display($new) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endif
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="6">
                        <div class="flex flex-col items-center justify-center gap-2 py-8 text-center">
                            <flux:icon name="clipboard-document-list" variant="outline" class="size-8 text-zinc-400" />
                            <flux:heading size="sm">No audit entries</flux:heading>
                            <flux:text size="sm">
                                {{ $hasFilters ? 'Try a different search or filter.' : 'Changes to ICS, PAR and RIS records will appear here.' }}
                            </flux:text>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</flux:card>
