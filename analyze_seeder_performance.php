#!/usr/bin/env php
<?php

/**
 * Script to analyze seeder performance
 *
 * This script measures how long each seeder takes to run
 * to identify performance bottlenecks in testing
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "\n🔍 ANALYZING SEEDER PERFORMANCE\n";
echo "================================\n\n";

$seeders = [
    'PermissionManager' => \Modules\PermissionManager\Database\Seeders\PermissionManagerDatabaseSeeder::class,
    'Audit' => \Modules\Audit\Database\Seeders\AuditDatabaseSeeder::class,
    'PageBuilder' => \Modules\PageBuilder\Database\Seeders\PageBuilderDatabaseSeeder::class,
    'User' => \Modules\User\Database\Seeders\UserDatabaseSeeder::class,
    'Product' => \Modules\Product\Database\Seeders\ProductDatabaseSeeder::class,
    'Inventory' => \Modules\Inventory\Database\Seeders\InventoryDatabaseSeeder::class,
    'Purchase' => \Modules\Purchase\Database\Seeders\PurchaseDatabaseSeeder::class,
    'Sales' => \Modules\Sales\Database\Seeders\SalesDatabaseSeeder::class,
    'Ecommerce' => \Modules\Ecommerce\Database\Seeders\EcommerceDatabaseSeeder::class,
    'Contacts' => \Modules\Contacts\Database\Seeders\ContactsDatabaseSeeder::class,
    'Accounting' => \Modules\Accounting\Database\Seeders\AccountingDatabaseSeeder::class,
    'Finance' => \Modules\Finance\Database\Seeders\FinanceDatabaseSeeder::class,
    'HR' => \Modules\HR\Database\Seeders\HRDatabaseSeeder::class,
    'Billing' => \Modules\Billing\Database\Seeders\BillingDatabaseSeeder::class,
];

$results = [];
$totalTime = 0;

// First, run migrate:fresh (measure this too)
echo "Running migrate:fresh...\n";
$migrateStart = microtime(true);
Artisan::call('migrate:fresh', ['--force' => true]);
$migrateTime = microtime(true) - $migrateStart;
echo sprintf("✓ Migrations completed in %.2f seconds\n\n", $migrateTime);

// Create system user (required for activity log)
$system = \Modules\User\Models\User::factory()->create([
    'name' => 'System',
    'email' => 'system@audit.local',
    'password' => 'system',
    'status' => 'active',
]);

echo "Analyzing seeders:\n";
echo str_repeat("-", 80) . "\n";

foreach ($seeders as $name => $class) {
    echo sprintf("%-20s", $name . "...");
    flush();

    $start = microtime(true);

    try {
        Artisan::call('db:seed', [
            '--class' => $class,
            '--force' => true
        ]);

        $elapsed = microtime(true) - $start;
        $totalTime += $elapsed;

        $results[$name] = [
            'time' => $elapsed,
            'status' => 'SUCCESS'
        ];

        echo sprintf(" ✓ %.3f seconds\n", $elapsed);

    } catch (\Exception $e) {
        $elapsed = microtime(true) - $start;
        $results[$name] = [
            'time' => $elapsed,
            'status' => 'ERROR',
            'error' => $e->getMessage()
        ];

        echo sprintf(" ✗ FAILED after %.3f seconds\n", $elapsed);
        echo "   Error: " . substr($e->getMessage(), 0, 80) . "...\n";
    }
}

echo str_repeat("-", 80) . "\n";
echo sprintf("Total seeding time: %.2f seconds\n", $totalTime);
echo sprintf("Total time (migrations + seeding): %.2f seconds\n\n", $migrateTime + $totalTime);

// Sort by time (slowest first)
uasort($results, function($a, $b) {
    return $b['time'] <=> $a['time'];
});

echo "\n📊 PERFORMANCE RANKING (Slowest to Fastest)\n";
echo str_repeat("=", 80) . "\n";

$rank = 1;
foreach ($results as $name => $data) {
    $percentage = ($data['time'] / $totalTime) * 100;
    $bar = str_repeat('█', (int)($percentage / 2));

    echo sprintf(
        "#%d  %-20s %7.3fs  [%6.2f%%] %s\n",
        $rank++,
        $name,
        $data['time'],
        $percentage,
        $bar
    );
}

echo str_repeat("=", 80) . "\n";

// Identify slowest seeders
$slowSeeders = array_filter($results, function($data) {
    return $data['time'] > 1.0; // More than 1 second
});

if (!empty($slowSeeders)) {
    echo "\n⚠️  SLOW SEEDERS (>1 second):\n";
    foreach ($slowSeeders as $name => $data) {
        echo sprintf("   - %s: %.3f seconds\n", $name, $data['time']);
    }
}

echo "\n✅ Analysis complete!\n";
