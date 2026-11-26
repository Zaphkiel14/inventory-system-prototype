<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    @include('partials.head')
</head>

<body class="min-h-screen bg-white dark:bg-zinc-800">
    <flux:sidebar sticky collapsible class="border-r border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900">
        {{-- Persist the sidebar's contents rather than the <flux:sidebar> shell: Flux places the shell in the
             page grid with direct-child selectors, and a fresh shell also closes the mobile drawer on navigate.
             Active items stay in sync through Livewire's data-current tracking on wire:navigate links, so no
             item may set :current (that opts it out of the tracking). --}}
        @persist('sidebar')
        <flux:sidebar.header>
            <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />

            <flux:sidebar.collapse
                class="in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-mr-2" />
        </flux:sidebar.header>

        <flux:sidebar.nav>
            <flux:sidebar.item icon="home" :href="route('dashboard')" wire:navigate>
                {{ __('Dashboard') }}
            </flux:sidebar.item>

            <flux:sidebar.group expandable icon="archive-box" heading="Inventory" class="grid">

                <flux:sidebar.item :href="route('inventory.stocks')" wire:navigate>
                    Inventory Stock
                </flux:sidebar.item>

                <flux:sidebar.item :href="route('inventory.receiving')" wire:navigate>
                    Receiving
                </flux:sidebar.item>

                <flux:sidebar.item :href="route('inventory.adjustments')" wire:navigate>
                    Adjustments
                </flux:sidebar.item>

                <flux:sidebar.item :href="route('inventory.items')" wire:navigate>
                    Items
                </flux:sidebar.item>

                <flux:sidebar.item :href="route('inventory.categories')" wire:navigate>
                    Categories
                </flux:sidebar.item>

                <flux:sidebar.item :href="route('inventory.suppliers')" wire:navigate>
                    Suppliers
                </flux:sidebar.item>

            </flux:sidebar.group>

            <flux:sidebar.group expandable icon="document-text" heading="Requisitions" class="grid">

                <flux:sidebar.item :href="route('requisitions.requests')" wire:navigate>
                    Requests
                </flux:sidebar.item>

                <flux:sidebar.item :href="route('requisitions.approvals')" wire:navigate>
                    Approvals
                </flux:sidebar.item>

                <flux:sidebar.item :href="route('requisitions.issuances')" wire:navigate>
                    Issuances
                </flux:sidebar.item>
            </flux:sidebar.group>

            <flux:sidebar.group expandable icon="document-text" heading="Property Management" class="grid">

                <flux:sidebar.item>
                    Property Acknowledgement Receipt
                </flux:sidebar.item>

                <flux:sidebar.item>
                    Inventory Custodian Slip
                </flux:sidebar.item>

            </flux:sidebar.group>

            <flux:sidebar.group expandable icon="chart-bar" heading="Monitoring" class="grid">

                <flux:sidebar.item :href="route('monitoring.reports')" wire:navigate>
                    Reports
                </flux:sidebar.item>

                <flux:sidebar.item :href="route('monitoring.stock-monitoring')" wire:navigate>
                    Stock Monitoring
                </flux:sidebar.item>

                <flux:sidebar.item :href="route('monitoring.expiration-monitoring')" wire:navigate>
                    Expiration Monitoring
                </flux:sidebar.item>

                <flux:sidebar.item :href="route('monitoring.transaction-history')" wire:navigate>
                    Transaction History
                </flux:sidebar.item>

                <flux:sidebar.item :href="route('monitoring.stock-reconciliation')" wire:navigate>
                    Stock Reconciliation
                </flux:sidebar.item>

                <flux:sidebar.item :href="route('monitoring.audit-logs')" wire:navigate>
                    Audit Logs
                </flux:sidebar.item>

            </flux:sidebar.group>

        </flux:sidebar.nav>

        <flux:spacer />

        <flux:sidebar.nav>
            <flux:sidebar.item icon="cog-6-tooth" :href="route('configurations.storage-locations')" wire:navigate>
                {{ __('Configurations') }}
            </flux:sidebar.item>
        </flux:sidebar.nav>
        @endpersist
    </flux:sidebar>

    {{-- Secondary header: breadcrumbs, theme toggle and profile menu --}}
    <flux:header sticky class="border-b border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

        <x-app-breadcrumbs class="min-w-0 max-sm:hidden" />

        <flux:spacer />

        <div class="flex items-center gap-2">
            <x-theme-toggle />
            <x-header-user-menu />
        </div>
    </flux:header>

    {{ $slot }}

    @persist('toast')
    <flux:toast.group>
        <flux:toast />
    </flux:toast.group>
    @endpersist

    @fluxScripts
</body>

</html>
