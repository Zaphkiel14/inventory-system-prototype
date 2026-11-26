<?php

use App\Livewire\Configurations\StorageLocations;
use App\Livewire\Dashboard;
use App\Livewire\Inventory\Adjustments;
use App\Livewire\Inventory\Categories;
use App\Livewire\Inventory\Items;
use App\Livewire\Inventory\Receiving;
use App\Livewire\Inventory\Stocks;
use App\Livewire\Inventory\Suppliers;
use App\Livewire\Monitoring\AuditLogs;
use App\Livewire\Monitoring\ExpirationMonitoring;
use App\Livewire\Monitoring\Reports;
use App\Livewire\Monitoring\StockMonitoring;
use App\Livewire\Monitoring\StockReconciliation;
use App\Livewire\Monitoring\TransactionHistory;
use App\Livewire\PublicPortal;
use App\Livewire\Requisitions\Approvals;
use App\Livewire\Requisitions\Issuances;
use App\Livewire\Requisitions\Requests;
use Illuminate\Support\Facades\Route;

// Public welcome page: submit a supply request or track one (no account needed).
Route::livewire('/', PublicPortal::class)->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', Dashboard::class)->name('dashboard');

    // Chart demo page, local development only.
    if (app()->environment('local')) {
        Route::view('charts', 'charts')->name('charts');
    }

    // Configurations
    Route::prefix('configurations')
        ->name('configurations.')
        ->group(function () {
            Route::get('storage-locations', StorageLocations::class)->name('storage-locations');
        });

    // Inventory
    Route::prefix('inventory')
        ->name('inventory.')
        ->group(function () {
            Route::get('stocks', Stocks::class)->name('stocks');
            Route::get('receiving', Receiving::class)->name('receiving');
            Route::get('adjustments', Adjustments::class)->name('adjustments');
            Route::get('items', Items::class)->name('items');
            Route::get('categories', Categories::class)->name('categories');
            Route::get('suppliers', Suppliers::class)->name('suppliers');
        });
    Route::prefix('requisitions')
        ->name('requisitions.')
        ->group(function () {
            Route::get('requests', Requests::class)->name('requests');
            Route::get('approvals', Approvals::class)->name('approvals');
            Route::get('issuances', Issuances::class)->name('issuances');
        });

    Route::prefix('monitoring')
        ->name('monitoring.')
        ->group(function () {
            Route::get('reports', Reports::class)->name('reports');
            Route::get('stock-monitoring', StockMonitoring::class)->name('stock-monitoring');
            Route::get('expiration-monitoring', ExpirationMonitoring::class)->name('expiration-monitoring');
            Route::get('transaction-history', TransactionHistory::class)->name('transaction-history');
            Route::get('stock-reconciliation', StockReconciliation::class)->name('stock-reconciliation');
            Route::get('audit-logs', AuditLogs::class)->name('audit-logs');
        });

    // Route::prefix('property-management')
    //     ->name('property-management')
    //     ->group(function() {
    //         Route::get('property-acknowledgement-receipt')
    //     });
});

require __DIR__.'/settings.php';
