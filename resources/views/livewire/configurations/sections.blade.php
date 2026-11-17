<section class="w-full">
    @include('partials.configuration-heading')

    <flux:heading level="2" class="sr-only">{{ __('Sections settings') }}</flux:heading>

    <x-configurations.layout :heading="__('Sections')" :subheading=" __('Update Sections information settings')">

    </x-configurations.layout>
</section>