<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/**
 * ProfileMemoryUsage Command
 *
 * Analyzes memory usage patterns in the application.
 *
 * Usage:
 *   php artisan profile:memory
 *   php artisan profile:memory --detailed
 *   php artisan profile:memory --check-leaks
 */
class ProfileMemoryUsage extends Command
{
    protected $signature = 'profile:memory
                            {--detailed : Show detailed memory breakdown}
                            {--check-leaks : Test for memory leaks}
                            {--iterations=100 : Number of iterations for leak testing}';

    protected $description = 'Profile application memory usage and detect potential leaks';

    public function handle(): int
    {
        $this->info('╔════════════════════════════════════════════════════╗');
        $this->info('║  Memory & Resource Profiling                      ║');
        $this->info('╚════════════════════════════════════════════════════╝');
        $this->newLine();

        // Show current memory status
        $this->showMemoryStatus();

        if ($this->option('check-leaks')) {
            $this->checkMemoryLeaks();
        }

        if ($this->option('detailed')) {
            $this->showDetailedAnalysis();
        }

        $this->newLine();
        $this->info('✅ Profiling complete');

        return 0;
    }

    /**
     * Show current memory status
     */
    protected function showMemoryStatus(): void
    {
        $this->info('📊 Current Memory Status:');
        $this->newLine();

        $current = memory_get_usage(true);
        $peak = memory_get_peak_usage(true);
        $limit = $this->getMemoryLimit();

        $this->table(
            ['Metric', 'Value', 'Percentage'],
            [
                ['Current Usage', $this->formatBytes($current), $this->getPercentage($current, $limit)],
                ['Peak Usage', $this->formatBytes($peak), $this->getPercentage($peak, $limit)],
                ['Memory Limit', $this->formatBytes($limit), '100%'],
                ['Available', $this->formatBytes($limit - $current), $this->getPercentage($limit - $current, $limit)],
            ]
        );

        // Warning if using > 80% memory
        if ($current / $limit > 0.8) {
            $this->warn('⚠️  High memory usage detected (>80%)');
        }

        $this->newLine();
    }

    /**
     * Check for memory leaks
     */
    protected function checkMemoryLeaks(): void
    {
        $this->info('🔍 Testing for Memory Leaks...');
        $this->newLine();

        $iterations = (int) $this->option('iterations');
        $memoryReadings = [];

        $this->output->progressStart($iterations);

        for ($i = 0; $i < $iterations; $i++) {
            // Perform typical operations
            $this->performTypicalOperations();

            // Record memory
            $memoryReadings[] = memory_get_usage(true);

            $this->output->progressAdvance();

            // Small delay to allow cleanup
            usleep(10000); // 10ms
        }

        $this->output->progressFinish();
        $this->newLine();

        // Analyze memory trend
        $this->analyzeMemoryTrend($memoryReadings);
    }

    /**
     * Perform typical operations to test for leaks
     */
    protected function performTypicalOperations(): void
    {
        // Simulate typical API operations

        // 1. Database queries
        if (class_exists(\Modules\Finance\Models\ARInvoice::class)) {
            \Modules\Finance\Models\ARInvoice::take(10)->get();
        }

        // 2. Cache operations
        Cache::put('test_key_' . rand(), 'test_value', 10);
        Cache::get('test_key_' . rand());

        // 3. Collection operations
        $collection = collect(range(1, 100))->map(fn($i) => ['id' => $i, 'value' => rand()]);
        $collection->filter(fn($item) => $item['value'] > 500);

        // 4. JSON encoding
        json_encode($collection->toArray());

        // Force garbage collection
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
    }

    /**
     * Analyze memory trend for leaks
     */
    protected function analyzeMemoryTrend(array $readings): void
    {
        $first = array_slice($readings, 0, 10);
        $last = array_slice($readings, -10);

        $firstAvg = array_sum($first) / count($first);
        $lastAvg = array_sum($last) / count($last);

        $growth = $lastAvg - $firstAvg;
        $growthPercent = ($growth / $firstAvg) * 100;

        $this->info('Memory Leak Analysis:');
        $this->table(
            ['Metric', 'Value'],
            [
                ['First 10 iterations (avg)', $this->formatBytes($firstAvg)],
                ['Last 10 iterations (avg)', $this->formatBytes($lastAvg)],
                ['Memory Growth', $this->formatBytes($growth)],
                ['Growth Rate', number_format($growthPercent, 2) . '%'],
            ]
        );

        if ($growthPercent > 10) {
            $this->error('⚠️  Potential memory leak detected (>10% growth)');
            $this->warn('   Consider:');
            $this->warn('   - Checking for unclosed database connections');
            $this->warn('   - Reviewing event listener cleanup');
            $this->warn('   - Examining large collection operations');
        } elseif ($growthPercent > 5) {
            $this->warn('⚠️  Moderate memory growth (5-10%)');
            $this->info('   Memory growth is within acceptable range');
        } else {
            $this->info('✅ No significant memory leak detected');
        }

        $this->newLine();
    }

    /**
     * Show detailed memory analysis
     */
    protected function showDetailedAnalysis(): void
    {
        $this->info('📋 Detailed Memory Analysis:');
        $this->newLine();

        // OPcache status
        $this->showOpcacheStatus();

        // Database connections
        $this->showDatabaseConnections();

        // Cache statistics
        $this->showCacheStatistics();

        // Loaded classes
        $this->showLoadedClasses();
    }

    /**
     * Show OPcache status
     */
    protected function showOpcacheStatus(): void
    {
        if (function_exists('opcache_get_status')) {
            $status = opcache_get_status();

            if ($status) {
                $this->info('OPcache Status:');
                $this->table(
                    ['Metric', 'Value'],
                    [
                        ['Enabled', $status['opcache_enabled'] ? 'Yes' : 'No'],
                        ['Memory Usage', $this->formatBytes($status['memory_usage']['used_memory'])],
                        ['Free Memory', $this->formatBytes($status['memory_usage']['free_memory'])],
                        ['Cached Scripts', $status['opcache_statistics']['num_cached_scripts']],
                        ['Hit Rate', number_format($status['opcache_statistics']['opcache_hit_rate'], 2) . '%'],
                    ]
                );
                $this->newLine();
            }
        } else {
            $this->warn('OPcache is not available');
            $this->newLine();
        }
    }

    /**
     * Show database connection info
     */
    protected function showDatabaseConnections(): void
    {
        try {
            $this->info('Database Connections:');

            // Get connection info
            $pdo = DB::connection()->getPdo();
            $driver = DB::connection()->getDriverName();

            $this->table(
                ['Metric', 'Value'],
                [
                    ['Driver', $driver],
                    ['Active Connection', 'Yes'],
                    ['Database', DB::connection()->getDatabaseName()],
                ]
            );

            // Try to get connection count (MySQL only)
            if ($driver === 'mysql') {
                try {
                    $processes = DB::select('SHOW PROCESSLIST');
                    $this->info('Active MySQL Processes: ' . count($processes));
                } catch (\Exception $e) {
                    // Silently fail if no permission
                }
            }

            $this->newLine();
        } catch (\Exception $e) {
            $this->warn('Could not retrieve database connection info');
            $this->newLine();
        }
    }

    /**
     * Show cache statistics
     */
    protected function showCacheStatistics(): void
    {
        $this->info('Cache Configuration:');

        $driver = config('cache.default');

        $this->table(
            ['Metric', 'Value'],
            [
                ['Driver', $driver],
                ['Prefix', config('cache.prefix', 'none')],
            ]
        );

        // Test cache read/write
        $testKey = 'memory_profile_test';
        $testValue = 'test_' . time();

        Cache::put($testKey, $testValue, 60);
        $retrieved = Cache::get($testKey);

        if ($retrieved === $testValue) {
            $this->info('✅ Cache read/write working correctly');
        } else {
            $this->error('❌ Cache read/write failed');
        }

        Cache::forget($testKey);
        $this->newLine();
    }

    /**
     * Show loaded classes count
     */
    protected function showLoadedClasses(): void
    {
        $classes = get_declared_classes();
        $interfaces = get_declared_interfaces();
        $traits = get_declared_traits();

        $this->info('Loaded Components:');
        $this->table(
            ['Type', 'Count'],
            [
                ['Classes', count($classes)],
                ['Interfaces', count($interfaces)],
                ['Traits', count($traits)],
                ['Total', count($classes) + count($interfaces) + count($traits)],
            ]
        );

        $this->newLine();
    }

    /**
     * Format bytes to human readable
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Get memory limit in bytes
     */
    protected function getMemoryLimit(): int
    {
        $limit = ini_get('memory_limit');

        if ($limit == -1) {
            return PHP_INT_MAX;
        }

        $limit = trim($limit);
        $last = strtolower($limit[strlen($limit) - 1]);
        $value = (int) $limit;

        switch ($last) {
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
        }

        return $value;
    }

    /**
     * Get percentage
     */
    protected function getPercentage(int $value, int $total): string
    {
        if ($total == 0) {
            return '0%';
        }

        return number_format(($value / $total) * 100, 2) . '%';
    }
}
