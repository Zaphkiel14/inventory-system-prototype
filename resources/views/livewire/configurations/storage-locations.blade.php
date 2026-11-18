<section class="w-full">
    @include('partials.configuration-heading')

    <flux:heading level="2" class="sr-only">{{ __('Storage Location') }}</flux:heading>

    <x-configurations.layout :heading="__('Storage Locations')"
        :subheading=" __('Add/Update storage locations for easy logging of items')">
        
    </x-configurations.layout>
</section>