<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// FI-002: Schedule daily overdue invoice check
Schedule::command('finance:check-overdue')->daily();

// IV-M002: Schedule daily stock reorder alerts check
Schedule::command('inventory:check-reorder-alerts')->daily();

// P47: Update exchange rates from Banxico daily at 13:00 CST
Schedule::command('currency:update-rates')->dailyAt('13:00')->timezone('America/Mexico_City');

// WS9: Refresh SAT catalogs monthly from phpcfdi/resources-sat-catalogs
Schedule::command('sat:sync-catalogs')->monthlyOn(1, '02:30')->timezone('America/Mexico_City');

// Demo mode: weekly database reset every Monday 03:00 (server time).
// Only registered when APP_DEMO_MODE=true; the command itself also refuses
// to run without demo mode, as a second layer of protection.
if (config('app.demo_mode')) {
    Schedule::command('demo:reset --force')->weeklyOn(1, '03:00');
}
