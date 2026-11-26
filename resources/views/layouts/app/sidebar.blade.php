<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    @include('partials.head')
    @fluxAppearance
</head>

<body class="min-h-screen bg-white dark:bg-zinc-800">
    <flux:sidebar sticky collapsible class="border-r border-zinc-200 dark:border-zinc-700 bg-zinc-50  dark:bg-zinc-900">
        <flux:sidebar.header>
            <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate.hover />

            <flux:sidebar.collapse
                class="in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-mr-2" />
        </flux:sidebar.header>

        <flux:sidebar.nav>
            <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')"
                wire:navigate.hover>
                {{ __('Dashboard') }}
            </flux:sidebar.item>

            <flux:sidebar.group expandable icon="archive-box" heading="Inventory" class="grid">

                <flux:sidebar.item :href="route('inventory.stocks')" wire:navigate.hover>
                    Inventory Stock
                </flux:sidebar.item>

                <flux:sidebar.item :href="route('inventory.receiving')" wire:navigate.hover>
                    Receiving
                </flux:sidebar.item>

                <flux:sidebar.item :href="route('inventory.adjustments')" wire:navigate.hover>
                    Adjustments
                </flux:sidebar.item>

                <flux:sidebar.item :href="route('inventory.items')" wire:navigate.hover>
                    Items
                </flux:sidebar.item>

                <flux:sidebar.item :href="route('inventory.categories')" wire:navigate.hover>
                    Categories
                </flux:sidebar.item>

                <flux:sidebar.item :href="route('inventory.suppliers')" wire:navigate.hover>
                    Suppliers
                </flux:sidebar.item>

            </flux:sidebar.group>

            <flux:sidebar.group expandable icon="document-text" heading="Requisitions" class="grid">

                <flux:sidebar.item :href="route('requisitions.requests')" wire:navigate.hover>
                    Requests
                </flux:sidebar.item>

                <flux:sidebar.item :href="route('requisitions.approvals')" wire:navigate.hover>
                    Approvals
                </flux:sidebar.item>

                <flux:sidebar.item :href="route('requisitions.issuances')" wire:navigate.hover>
                    Issuances
                </flux:sidebar.item>
            </flux:sidebar.group>

            <flux:sidebar.group expandable icon="document-text" heading="Property Management" class="grid">

                <flux:sidebar.item  wire:navigate.hover>
                    Property Acknowledgement Receipt
                </flux:sidebar.item>

                <flux:sidebar.item  wire:navigate.hover>
                    Inventory Custodian Slip
                </flux:sidebar.item>

            </flux:sidebar.group>

            <flux:sidebar.group expandable icon="chart-bar" heading="Monitoring" class="grid">

                <flux:sidebar.item :href="route('monitoring.reports')" wire:navigate.hover>
                    Reports
                </flux:sidebar.item>

                <flux:sidebar.item :href="route('monitoring.stock-monitoring')" wire:navigate.hover>
                    Stock Monitoring
                </flux:sidebar.item>

                <flux:sidebar.item :href="route('monitoring.expiration-monitoring')" wire:navigate.hover>
                    Expiration Monitoring
                </flux:sidebar.item>

                <flux:sidebar.item :href="route('monitoring.transaction-history')" wire:navigate.hover>
                    Transaction History
                </flux:sidebar.item>

                <flux:sidebar.item :href="route('monitoring.stock-reconciliation')" wire:navigate.hover>
                    Stock Reconciliation
                </flux:sidebar.item>

                <flux:sidebar.item :href="route('monitoring.audit-logs')" wire:navigate.hover>
                    Audit Logs
                </flux:sidebar.item>

            </flux:sidebar.group>

        </flux:sidebar.nav>

        <flux:spacer />

        <flux:sidebar.nav>
            <flux:sidebar.item icon="cog-6-tooth" :href="route('configurations.storage-locations')" wire:navigate.hover>
                {{ __('Configurations') }}
            </flux:sidebar.item>
        </flux:sidebar.nav>
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