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
