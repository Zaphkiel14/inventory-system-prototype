{{-- Placeholder for the deferred Approvals component. Mirrors livewire/requisitions/approvals.blade.php. --}}
<flux:card variant="default">
    <span class="sr-only" role="status">Loading approvals…</span>

    <flux:skeleton.group animate="pulse">
        <div class="mb-4">
            <div class="flex flex-wrap items-center gap-2">
                <flux:heading size="xl" level="2">Requisition Approvals</flux:heading>
                <flux:skeleton class="h-5 w-20 rounded-md" aria-hidden="true" />
            </div>
            <flux:text class="mt-1">
                Review requests and decide how much of each item may be dispensed. Approved quantities move to issuance.
            </flux:text>
        </div>

        <div class="mb-4 flex flex-col gap-2 md:flex-row md:items-center" aria-hidden="true">
            <div class="md:flex-1">
                <flux:skeleton class="h-8 w-full rounded-lg" />
            </div>

            <flux:skeleton class="h-8 w-full rounded-lg md:w-48" />
        </div>

        <x-skeleton.table-rows :rows="10" :columns="10" />
    </flux:skeleton.group>
</flux:card>
