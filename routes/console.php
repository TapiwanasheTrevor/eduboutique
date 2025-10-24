<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Jobs\SyncProductsFromOdoo;
use App\Jobs\SyncStockLevelsFromOdoo;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Odoo Sync Scheduled Tasks
Schedule::job(new SyncProductsFromOdoo())
    ->everyThirtyMinutes()
    ->name('sync-products-from-odoo')
    ->onOneServer()
    ->withoutOverlapping();

Schedule::job(new SyncStockLevelsFromOdoo())
    ->everyFifteenMinutes()
    ->name('sync-stock-levels-from-odoo')
    ->onOneServer()
    ->withoutOverlapping();

// Cleanup old sync logs (keep 30 days)
Schedule::command('model:prune', ['--model' => 'App\\Models\\OdooSyncLog'])
    ->daily()
    ->at('02:00')
    ->name('cleanup-old-sync-logs');
