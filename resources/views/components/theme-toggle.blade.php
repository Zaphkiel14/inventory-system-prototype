{{-- Light / dark switch. Uses Flux's appearance store, so it stays in sync with Settings > Appearance. --}}
<flux:tooltip :content="__('Toggle light / dark mode')" position="bottom">
    <flux:button {{ $attributes }} x-data x-on:click="$flux.dark = ! $flux.dark" variant="subtle" square
        :aria-label="__('Toggle light / dark mode')">
        <flux:icon.moon variant="mini" x-show="! $flux.dark" />
        <flux:icon.sun variant="mini" x-show="$flux.dark" x-cloak />
    </flux:button>
</flux:tooltip>
