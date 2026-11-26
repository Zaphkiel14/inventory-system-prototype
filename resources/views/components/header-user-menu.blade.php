<flux:dropdown position="bottom" align="end" {{ $attributes }}>
    <flux:profile :name="auth()->user()->name" :initials="auth()->user()->initials()"
        icon:trailing="chevron-down" data-test="header-menu-button" />

    <flux:menu>
        <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
            <flux:avatar :name="auth()->user()->name" :initials="auth()->user()->initials()" />
            <div class="grid flex-1 text-start text-sm leading-tight">
                <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
            </div>
        </div>

        <flux:menu.separator />

        {{-- Switch between the public homepage and the signed-in app --}}
        @if (request()->routeIs('home'))
            <flux:menu.item :href="route('dashboard')" icon="squares-2x2" data-test="menu-dashboard-link"
                wire:navigate>
                {{ __('Dashboard') }}
            </flux:menu.item>
        @else
            <flux:menu.item :href="route('home')" icon="globe-alt" data-test="menu-home-link">
                {{ __('Homepage') }}
            </flux:menu.item>
        @endif

        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
            {{ __('Settings') }}
        </flux:menu.item>

        <flux:menu.separator />

        <form method="POST" action="{{ route('logout') }}" class="w-full">
            @csrf
            <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle"
                class="w-full cursor-pointer" data-test="logout-button">
                {{ __('Log out') }}
            </flux:menu.item>
        </form>
    </flux:menu>
</flux:dropdown>
