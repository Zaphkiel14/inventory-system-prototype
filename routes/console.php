<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Batches expire without any stock movement, so recheck every item daily.
Schedule::command('inventory:check-stock-status')->dailyAt('06:00');

// Telescope's commands exist only while it is enabled; keep a day of entries.
if (config('telescope.enabled')) {
    Schedule::command('telescope:prune --hours=24')->daily();
}
