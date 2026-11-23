{{-- Header for pages outside the app shell (welcome + auth). Mirrors the app header, with the logo in place of breadcrumbs. --}}
<flux:header sticky {{ $attributes->class('border-b border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800') }}>
    <x-app-logo href="{{ route('home') }}" wire:navigate />

    <flux:spacer />

    <div class="flex items-center gap-2">
        <x-theme-toggle />

        @auth
            <x-header-user-menu />
        @else
            @if (Route::has('login') && ! request()->routeIs('login'))
                <flux:button :href="route('login')" variant="ghost" size="sm" wire:navigate>
                    {{ __('Log in') }}
                </flux:button>
            @endif

            @if (Route::has('register') && ! request()->routeIs('register'))
                <flux:button :href="route('register')" variant="primary" size="sm" wire:navigate>
                    {{ __('Register') }}
                </flux:button>
            @endif
        @endauth
    </div>
</flux:header>
