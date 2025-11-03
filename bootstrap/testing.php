<?php

/*
|--------------------------------------------------------------------------
| Testing Environment Bootstrap
|--------------------------------------------------------------------------
|
| Lightweight bootstrap for fast individual tests.
| Database setup is handled externally by test suite runner script.
|
| Strategy:
| - Individual tests: Fast, no DB setup overhead
| - Full suite: Run via script that sets up DB once before all tests
|
| Tests use DatabaseTransactions trait to rollback changes after each test.
|
*/

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/app.php';

// Boot the application
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// NO migrate:fresh here - handled by suite runner script
// This allows individual tests to run quickly without DB setup overhead
