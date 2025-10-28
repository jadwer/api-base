<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * AnalyzeHeavyQueries Command
 *
 * Analyzes database query patterns to identify heavy operations.
 *
 * Usage:
 *   php artisan analyze:queries
 *   php artisan analyze:queries --sample=1000
 */
class AnalyzeHeavyQueries extends Command
{
    protected $signature = 'analyze:queries
                            {--sample=500 : Number of sample operations to run}
                            {--verbose : Show detailed query information}';

    protected $description = 'Analyze database query patterns and identify heavy operations';

    public function handle(): int
    {
        $this->info('╔════════════════════════════════════════════════════╗');
        $this->info('║  Database Query Analysis                          ║');
        $this->info('╚════════════════════════════════════════════════════╝');
        $this->newLine();

        $sample = (int) $this->option('sample');

        // Test various query patterns
        $this->testQueryPatterns($sample);

        // Analyze query performance
        $this->analyzeQueryPerformance();

        // Show recommendations
        $this->showRecommendations();

        return 0;
    }

    /**
     * Test various query patterns
     */
    protected function testQueryPatterns(int $sample): void
    {
        $this->info('🔍 Testing Query Patterns...');
        $this->newLine();

        $patterns = [];

        // Test 1: Simple select
        $start = microtime(true);
        DB::enableQueryLog();

        for ($i = 0; $i < min($sample, 100); $i++) {
            if (class_exists(\Modules\Finance\Models\ARInvoice::class)) {
                \Modules\Finance\Models\ARInvoice::take(10)->get();
            }
        }

        $queries = DB::getQueryLog();
        $duration = (microtime(true) - $start) * 1000;

        $patterns[] = [
            'Pattern' => 'Simple SELECT',
            'Queries' => count($queries),
            'Duration' => number_format($duration, 2) . 'ms',
            'Avg per Query' => count($queries) > 0 ? number_format($duration / count($queries), 2) . 'ms' : 'N/A',
        ];

        DB::disableQueryLog();

        // Test 2: With relationships (eager loading)
        $start = microtime(true);
        DB::enableQueryLog();

        for ($i = 0; $i < min($sample, 50); $i++) {
            if (class_exists(\Modules\Finance\Models\ARInvoice::class)) {
                \Modules\Finance\Models\ARInvoice::with(['contact'])->take(10)->get();
            }
        }

        $queries = DB::getQueryLog();
        $duration = (microtime(true) - $start) * 1000;

        $patterns[] = [
            'Pattern' => 'SELECT with Eager Loading',
            'Queries' => count($queries),
            'Duration' => number_format($duration, 2) . 'ms',
            'Avg per Query' => count($queries) > 0 ? number_format($duration / count($queries), 2) . 'ms' : 'N/A',
        ];

        DB::disableQueryLog();

        // Test 3: Filtered queries
        $start = microtime(true);
        DB::enableQueryLog();

        for ($i = 0; $i < min($sample, 50); $i++) {
            if (class_exists(\Modules\Finance\Models\ARInvoice::class)) {
                \Modules\Finance\Models\ARInvoice::where('status', 'unpaid')->take(10)->get();
            }
        }

        $queries = DB::getQueryLog();
        $duration = (microtime(true) - $start) * 1000;

        $patterns[] = [
            'Pattern' => 'SELECT with WHERE',
            'Queries' => count($queries),
            'Duration' => number_format($duration, 2) . 'ms',
            'Avg per Query' => count($queries) > 0 ? number_format($duration / count($queries), 2) . 'ms' : 'N/A',
        ];

        DB::disableQueryLog();

        $this->table(['Pattern', 'Queries', 'Duration', 'Avg per Query'], $patterns);
        $this->newLine();
    }

    /**
     * Analyze query performance
     */
    protected function analyzeQueryPerformance(): void
    {
        $this->info('📊 Query Performance Analysis:');
        $this->newLine();

        // Enable query log
        DB::enableQueryLog();

        // Perform a series of typical operations
        $operations = 0;

        if (class_exists(\Modules\Finance\Models\ARInvoice::class)) {
            \Modules\Finance\Models\ARInvoice::with(['contact', 'salesOrder'])->take(5)->get();
            $operations++;
        }

        if (class_exists(\Modules\Accounting\Models\FiscalPeriod::class)) {
            \Modules\Accounting\Models\FiscalPeriod::where('status', 'open')->get();
            $operations++;
        }

        if (class_exists(\Modules\Contacts\Models\Contact::class)) {
            \Modules\Contacts\Models\Contact::where('is_customer', true)->take(10)->get();
            $operations++;
        }

        // Get query log
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $totalTime = array_sum(array_column($queries, 'time'));
        $avgTime = count($queries) > 0 ? $totalTime / count($queries) : 0;
        $slowQueries = array_filter($queries, fn($q) => $q['time'] > 100); // > 100ms

        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Operations', $operations],
                ['Total Queries', count($queries)],
                ['Queries per Operation', count($queries) > 0 && $operations > 0 ? number_format(count($queries) / $operations, 2) : 'N/A'],
                ['Total Time', number_format($totalTime, 2) . 'ms'],
                ['Average Query Time', number_format($avgTime, 2) . 'ms'],
                ['Slow Queries (>100ms)', count($slowQueries)],
            ]
        );

        $this->newLine();

        // Show slow queries if verbose
        if ($this->option('verbose') && count($slowQueries) > 0) {
            $this->warn('Slow Queries Detected:');
            foreach ($slowQueries as $query) {
                $this->line('  SQL: ' . $query['query']);
                $this->line('  Time: ' . number_format($query['time'], 2) . 'ms');
                $this->newLine();
            }
        }

        // Warnings
        if (count($queries) / max($operations, 1) > 10) {
            $this->warn('⚠️  High query count per operation (N+1 query issue?)');
        }

        if (count($slowQueries) > 0) {
            $this->warn('⚠️  Slow queries detected - consider adding indexes');
        }

        $this->newLine();
    }

    /**
     * Show optimization recommendations
     */
    protected function showRecommendations(): void
    {
        $this->info('💡 Optimization Recommendations:');
        $this->newLine();

        $recommendations = [
            '1. Eager Loading' => 'Use with() to prevent N+1 queries',
            '2. Indexing' => 'Verify Stage 2 indexes are applied (150+ indexes)',
            '3. Select Specific Columns' => 'Use select() instead of get() when possible',
            '4. Pagination' => 'Always paginate large result sets',
            '5. Caching' => 'Use cache for frequently accessed data',
            '6. Query Optimization' => 'Review slow queries (>100ms)',
            '7. Connection Pooling' => 'Configure database connection pool',
            '8. Read Replicas' => 'Consider read replicas for heavy read traffic',
        ];

        foreach ($recommendations as $title => $description) {
            $this->line("<fg=green>$title:</> $description");
        }

        $this->newLine();

        $this->info('Tools for deeper analysis:');
        $this->line('  • Laravel Debugbar: composer require barryvdh/laravel-debugbar --dev');
        $this->line('  • Laravel Telescope: composer require laravel/telescope --dev');
        $this->line('  • MySQL Slow Query Log: Enable in my.cnf');
        $this->newLine();
    }
}
