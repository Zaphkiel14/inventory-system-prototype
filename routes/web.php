<?php

use App\Livewire\Configurations\Departments;
use App\Livewire\Configurations\Sections;
use App\Livewire\Configurations\StorageLocations;
use App\Livewire\Configurations\Units;
use App\Livewire\Inventory\Categories;
use App\Livewire\Inventory\Items;
use App\Livewire\Inventory\Receiving;
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
            Route::get('receiving', Receiving::class)->name('receiving');
            Route::get('items', Items::class)->name('items');
            Route::get('categories', Categories::class)->name('categories');
        });

    // Route::get('department', Departments::class)->name('departments.edit');
    // Route::get('sections', Sections::class)->name('sections.edit');
    // Route::get('units', Units::class)->name('units.edit');
    // Route::get('storage-locations', StorageLocations::class)->name('storage-locations');
    // Route::get('inventory/items', Items::class)->name('inventory.items');
    // Route::get('inventory/categories', Categories::class)->name('inventory.categories');
});

require __DIR__.'/settings.php';
