{{-- Placeholder for the deferred StorageLocations component. Mirrors livewire/configurations/storage-locations.blade.php. --}}
<section class="w-full">
    <span class="sr-only" role="status">Loading storage locations…</span>

    @include('partials.configuration-heading')

    <x-configurations.layout :heading="__('Storage Locations')"
        :subheading="__('Add/Update storage locations for easy logging of items')">

        <flux:skeleton.group animate="pulse">
            <div class="mb-4 flex items-center justify-between gap-2" aria-hidden="true">
                <flux:skeleton class="h-8 w-full max-w-64 rounded-lg" />
                <flux:skeleton class="h-8 w-44 shrink-0 rounded-lg" />
            </div>

            <x-skeleton.table-rows :rows="10" :columns="4" />

            <div class="mt-6 flex items-center justify-between gap-4" aria-hidden="true">
                <flux:skeleton.line class="w-40" />
                <flux:skeleton class="h-8 w-48 rounded-lg" />
            </div>
        </flux:skeleton.group>
    </x-configurations.layout>
</section>
