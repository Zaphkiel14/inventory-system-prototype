<?php

use App\Livewire\Configurations\Departments;
use App\Livewire\Configurations\Sections;
use App\Livewire\Configurations\StorageLocations;
use App\Livewire\Configurations\Units;
use App\Livewire\Inventory\Adjustments;
use App\Livewire\Inventory\Categories;
use App\Livewire\Inventory\Items;
use App\Livewire\Inventory\Receiving;
use App\Livewire\Inventory\Stocks;
use App\Livewire\Monitoring\AuditLogs;
use App\Livewire\Monitoring\ExpirationMonitoring;
use App\Livewire\Monitoring\Reports;
use App\Livewire\Monitoring\TransactionHistory;
use App\Livewire\Requisitions\Approvals;
use App\Livewire\Requisitions\Issuances;
use App\Livewire\Requisitions\Requests;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    // Configurations
    Route::prefix('configurations')
        ->name('configurations.')
        ->group(function () {
            Route::get('departments', Departments::class)->name('departments');
            Route::get('sections', Sections::class)->name('sections');
            Route::get('units', Units::class)->name('units');
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
            Route::get('expiration-monitoring', ExpirationMonitoring::class)->name('expiration-monitoring');
            Route::get('transaction-history', TransactionHistory::class)->name('transaction-history');
            Route::get('audit-logs', AuditLogs::class)->name('audit-logs');
        });
});

require __DIR__.'/settings.php';
