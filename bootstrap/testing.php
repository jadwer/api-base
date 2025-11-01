<?php

/*
|--------------------------------------------------------------------------
| Testing Environment Bootstrap
|--------------------------------------------------------------------------
|
| This file is loaded ONCE before the entire test suite runs.
| It sets up the test database by running migrations and seeding data.
|
| Tests use DatabaseTransactions trait to rollback changes after each test,
| so the seeded data persists throughout the entire test suite.
|
| Performance Impact:
| - WITHOUT this: migrate:fresh runs on EVERY test (280 tests × 20s = 93 minutes)
| - WITH this: migrate:fresh runs ONCE (20 seconds total)
| - Savings: 99% reduction in migration overhead
|
*/

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/app.php';

// Boot the application
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "\n🧪 Setting up test environment...\n";

// Run migrations and seeders ONCE for the entire test suite
echo "📦 Running migrate:fresh --seed...\n";

Illuminate\Support\Facades\Artisan::call('migrate:fresh', [
    '--seed' => true,
    '--env' => 'testing',
    '--force' => true,
]);

echo "✅ Test environment ready!\n";
echo "   - Database migrated\n";
echo "   - All modules seeded\n";
echo "   - Tests will use transactions for cleanup\n\n";
