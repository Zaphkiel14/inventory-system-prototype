{{-- Placeholder for the deferred TransactionHistory component. Mirrors livewire/monitoring/transaction-history.blade.php. --}}
<div class="flex flex-col gap-6">
    <span class="sr-only" role="status">Loading transaction history…</span>

    {{-- Header --}}
    <flux:card variant="default">
        <flux:skeleton.group animate="pulse" class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <flux:heading size="xl" level="2">Transaction History</flux:heading>
                <div class="mt-1 flex flex-wrap items-center gap-x-1">
                    <flux:text>Every receipt, issuance and adjustment in the inventory ledger from</flux:text>
                    <flux:skeleton.line class="w-48" aria-hidden="true" />
                </div>
            </div>

            <flux:skeleton class="h-8 w-28 rounded-lg" aria-hidden="true" />
        </flux:skeleton.group>
    </flux:card>

    {{-- Totals --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach (['Entries', 'Units in', 'Units out', 'Net change'] as $label)
            <flux:card size="sm" class="flex flex-col gap-2">
                <flux:skeleton.group animate="pulse" class="flex flex-col gap-2">
                    <div class="flex items-center justify-between gap-2">
                        <flux:text size="sm">{{ $label }}</flux:text>
                        <flux:skeleton class="size-4 rounded" aria-hidden="true" />
                    </div>
                    <flux:skeleton class="h-8 w-16" aria-hidden="true" />
                </flux:skeleton.group>
            </flux:card>
        @endforeach
    </div>

    {{-- Ledger --}}
    <flux:card>
        <flux:skeleton.group animate="pulse">
            <div class="mb-4 flex flex-col gap-2 lg:flex-row lg:items-center" aria-hidden="true">
                <div class="lg:flex-1">
                    <flux:skeleton class="h-8 w-full rounded-lg" />
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <flux:skeleton class="h-8 w-full rounded-lg sm:w-40" />
                    <flux:skeleton class="h-8 w-full rounded-lg sm:w-36" />
                    <flux:skeleton class="h-8 w-full rounded-lg sm:w-32" />
                    <flux:skeleton class="h-8 w-full rounded-lg sm:w-44" />
                    <flux:skeleton class="size-8 rounded-lg" />
                </div>
            </div>

            <x-skeleton.table-rows :rows="10" :columns="7" />
        </flux:skeleton.group>
    </flux:card>
</div>
