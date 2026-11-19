@php $crumbs = \App\Support\Breadcrumbs::current(); @endphp

<flux:breadcrumbs {{ $attributes }}>
    @foreach ($crumbs as $crumb)
        @if ($crumb['pages'])
            {{-- Section crumb: dropdown of its pages for quick navigation --}}
            <flux:breadcrumbs.item>
                <flux:dropdown position="bottom" align="start">
                    <flux:button variant="ghost" size="sm" icon:trailing="chevron-down" class="-mx-2">
                        {{ $crumb['label'] }}
                    </flux:button>

                    <flux:menu>
                        @foreach ($crumb['pages'] as $page)
                            <flux:menu.item :href="$page['url']" wire:navigate
                                :icon="$page['current'] ? 'check' : null">
                                {{ $page['label'] }}
                            </flux:menu.item>
                        @endforeach
                    </flux:menu>
                </flux:dropdown>
            </flux:breadcrumbs.item>
        @elseif ($crumb['url'])
            <flux:breadcrumbs.item :href="$crumb['url']" :icon="$crumb['icon']" wire:navigate>
                {{ $crumb['label'] }}
            </flux:breadcrumbs.item>
        @elseif ($crumb['icon'] && $loop->first && $loop->last)
            <flux:breadcrumbs.item>
                <span class="flex items-center gap-1.5">
                    <flux:icon :icon="$crumb['icon']" variant="mini" />
                    {{ $crumb['label'] }}
                </span>
            </flux:breadcrumbs.item>
        @else
            <flux:breadcrumbs.item>{{ $crumb['label'] }}</flux:breadcrumbs.item>
        @endif
    @endforeach
</flux:breadcrumbs>
