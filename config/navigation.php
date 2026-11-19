<?php

/*
|--------------------------------------------------------------------------
| Navigation Map
|--------------------------------------------------------------------------
|
| Drives the breadcrumbs in the app header. Each section lists its pages as
| route name => label. The section crumb becomes a dropdown of these pages
| for quick navigation. Add new pages here when you add new routes.
|
*/

return [

    'home' => ['route' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'home'],

    'sections' => [

        'inventory' => [
            'label' => 'Inventory',
            'pages' => [
                'inventory.stocks' => 'Inventory Stock',
                'inventory.receiving' => 'Receiving',
                'inventory.adjustments' => 'Adjustments',
                'inventory.items' => 'Items',
                'inventory.categories' => 'Categories',
            ],
        ],

        'requisitions' => [
            'label' => 'Requisitions',
            'pages' => [
                'requisitions.requests' => 'Requests',
                'requisitions.approvals' => 'Approvals',
                'requisitions.issuances' => 'Issuances',
            ],
        ],

        'monitoring' => [
            'label' => 'Monitoring',
            'pages' => [
                'monitoring.reports' => 'Reports',
                'monitoring.stock-monitoring' => 'Stock Monitoring',
                'monitoring.expiration-monitoring' => 'Expiration Monitoring',
                'monitoring.transaction-history' => 'Transaction History',
                'monitoring.audit-logs' => 'Audit Logs',
            ],
        ],

        'configurations' => [
            'label' => 'Configurations',
            'pages' => [
                'configurations.departments' => 'Departments',
                'configurations.sections' => 'Sections',
                'configurations.units' => 'Units',
                'configurations.storage-locations' => 'Storage Locations',
            ],
        ],

        'settings' => [
            'label' => 'Settings',
            'pages' => [
                'profile.edit' => 'Profile',
                'appearance.edit' => 'Appearance',
                'security.edit' => 'Security',
            ],
        ],

    ],

];
