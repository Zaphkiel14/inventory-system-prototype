<div class="flex items-start max-md:flex-col">
    <div class="me-10 w-full pb-4 md:w-[220px]">
        <flux:navlist aria-label="{{ __('Settings') }}">
            <flux:navlist.item :href="route('configurations.storage-locations')" wire:navigate>{{ __('Storage Location') }}</flux:navlist.item>
            <flux:navlist.item :href="route('configurations.property-settings')" wire:navigate>{{ __('Property Settings') }}</flux:navlist.item>
            <flux:navlist.item :href="route('configurations.sticker-profiles')" wire:navigate>{{ __('Sticker Profiles') }}</flux:navlist.item>
        </flux:navlist>
    </div>

    <flux:separator class="md:hidden" />

    <div class="flex-1 self-stretch max-md:pt-6">
        <flux:heading>{{ $heading ?? '' }}</flux:heading>
        <flux:subheading>{{ $subheading ?? '' }}</flux:subheading>

        <div class="mt-5 w-full ">
            {{ $slot }}
        </div>
    </div>
</div>