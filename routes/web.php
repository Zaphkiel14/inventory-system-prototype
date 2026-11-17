<?php

use App\Livewire\Configurations\Departments;
use App\Livewire\Configurations\Sections;
use App\Livewire\Configurations\Units;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::get('department', Departments::class)->name('departments.edit');
    Route::get('sections', Sections::class)->name('sections.edit');
    Route::get('units', Units::class)->name('units.edit');
});

require __DIR__.'/settings.php';
