<section class="w-full">
    @include('partials.configuration-heading')

    <flux:heading level="2" class="sr-only">{{ __('Department settings') }}</flux:heading>

    <x-configurations.layout :heading="__('Department')" :subheading=" __('Update Department information settings')">

    </x-configurations.layout>
</section>