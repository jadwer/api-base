<?php

namespace Modules\SystemHealth\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Spatie\Activitylog\Models\Activity;

class SystemHealthService
{
    /**
     * Get complete system health status
     */
    public function getHealthStatus(): array
    {
        return [
            'status' => $this->getOverallStatus(),
            'timestamp' => now()->toIso8601String(),
            'environment' => app()->environment(),
            'checks' => [
                'database' => $this->checkDatabase(),
                'cache' => $this->checkCache(),
                'queue' => $this->checkQueue(),
                'storage' => $this->checkStorage(),
            ],
            'metrics' => [
                'database' => $this->getDatabaseMetrics(),
                'application' => $this->getApplicationMetrics(),
                'errors' => $this->getRecentErrors(),
            ],
        ];
    }

    /**
     * Get overall system status
     */
    protected function getOverallStatus(): string
    {
        $checks = [
            $this->checkDatabase()['status'],
            $this->checkCache()['status'],
            $this->checkStorage()['status'],
        ];

        if (in_array('critical', $checks)) {
            return 'critical';
        }

        if (in_array('warning', $checks)) {
            return 'warning';
        }

        return 'healthy';
    }

    /**
     * Check database connection and performance
     */
    public function checkDatabase(): array
    {
        try {
            $start = microtime(true);
            DB::select('SELECT 1');
            $responseTime = round((microtime(true) - $start) * 1000, 2);

            return [
                'status' => $responseTime < 100 ? 'healthy' : ($responseTime < 500 ? 'warning' : 'critical'),
                'message' => 'Database connection successful',
                'responseTimeMs' => $responseTime,
            ];
        } catch (\Exception $e) {
            Log::error('Database health check failed', ['error' => $e->getMessage()]);
            return [
                'status' => 'critical',
                'message' => 'Database connection failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check cache connection
     */
    public function checkCache(): array
    {
        try {
            $key = 'health_check_' . uniqid();
            $start = microtime(true);

            Cache::put($key, 'test', 10);
            $value = Cache::get($key);
            Cache::forget($key);

            $responseTime = round((microtime(true) - $start) * 1000, 2);

            if ($value !== 'test') {
                return [
                    'status' => 'critical',
                    'message' => 'Cache read/write failed',
                ];
            }

            return [
                'status' => 'healthy',
                'message' => 'Cache is working',
                'driver' => config('cache.default'),
                'responseTimeMs' => $responseTime,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'warning',
                'message' => 'Cache check failed',
                'driver' => config('cache.default'),
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check queue status
     */
    public function checkQueue(): array
    {
        try {
            $driver = config('queue.default');
            $failedJobs = 0;

            // Check failed jobs table if it exists
            if (Schema::hasTable('failed_jobs')) {
                $failedJobs = DB::table('failed_jobs')->count();
            }

            // Check jobs table if using database driver
            $pendingJobs = 0;
            if ($driver === 'database' && Schema::hasTable('jobs')) {
                $pendingJobs = DB::table('jobs')->count();
            }

            $status = 'healthy';
            if ($failedJobs > 10) {
                $status = 'critical';
            } elseif ($failedJobs > 0) {
                $status = 'warning';
            }

            return [
                'status' => $status,
                'message' => $failedJobs > 0 ? "Found {$failedJobs} failed jobs" : 'Queue is healthy',
                'driver' => $driver,
                'pendingJobs' => $pendingJobs,
                'failedJobs' => $failedJobs,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'warning',
                'message' => 'Queue check failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check storage/disk space
     */
    public function checkStorage(): array
    {
        try {
            $storagePath = storage_path();
            $totalSpace = disk_total_space($storagePath);
            $freeSpace = disk_free_space($storagePath);
            $usedSpace = $totalSpace - $freeSpace;
            $usedPercent = round(($usedSpace / $totalSpace) * 100, 2);

            $status = 'healthy';
            if ($usedPercent >= 95) {
                $status = 'critical';
            } elseif ($usedPercent >= 85) {
                $status = 'warning';
            }

            return [
                'status' => $status,
                'message' => "Disk usage: {$usedPercent}%",
                'totalGb' => round($totalSpace / 1024 / 1024 / 1024, 2),
                'usedGb' => round($usedSpace / 1024 / 1024 / 1024, 2),
                'freeGb' => round($freeSpace / 1024 / 1024 / 1024, 2),
                'usedPercent' => $usedPercent,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'warning',
                'message' => 'Storage check failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get database metrics (table sizes, row counts)
     */
    public function getDatabaseMetrics(): array
    {
        try {
            $driver = config('database.default');
            $database = config("database.connections.{$driver}.database");

            // Different queries for MySQL vs SQLite
            if ($driver === 'mysql') {
                $tables = DB::select("
                    SELECT
                        table_name as `name`,
                        table_rows as `rowCount`,
                        ROUND((data_length + index_length) / 1024 / 1024, 2) as `sizeMb`
                    FROM information_schema.tables
                    WHERE table_schema = ?
                    ORDER BY (data_length + index_length) DESC
                    LIMIT 15
                ", [$database]);

                $totalSize = DB::selectOne("
                    SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as `totalMb`
                    FROM information_schema.tables
                    WHERE table_schema = ?
                ", [$database]);

                return [
                    'driver' => $driver,
                    'database' => $database,
                    'totalSizeMb' => $totalSize->totalMb ?? 0,
                    'topTables' => array_map(fn($t) => [
                        'name' => $t->name,
                        'rows' => (int) $t->rowCount,
                        'sizeMb' => (float) $t->sizeMb,
                    ], $tables),
                ];
            }

            // SQLite fallback
            $tables = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
            $tableData = [];
            foreach (array_slice($tables, 0, 15) as $table) {
                $count = DB::table($table->name)->count();
                $tableData[] = [
                    'name' => $table->name,
                    'rows' => $count,
                    'sizeMb' => null,
                ];
            }

            return [
                'driver' => $driver,
                'database' => $database,
                'totalSizeMb' => null,
                'topTables' => $tableData,
            ];
        } catch (\Exception $e) {
            return [
                'driver' => config('database.default'),
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get application metrics
     */
    public function getApplicationMetrics(): array
    {
        try {
            // Count records in key tables
            $metrics = [
                'users' => Schema::hasTable('users') ? DB::table('users')->count() : 0,
                'products' => Schema::hasTable('products') ? DB::table('products')->count() : 0,
                'salesOrders' => Schema::hasTable('sales_orders') ? DB::table('sales_orders')->count() : 0,
                'purchaseOrders' => Schema::hasTable('purchase_orders') ? DB::table('purchase_orders')->count() : 0,
                'contacts' => Schema::hasTable('contacts') ? DB::table('contacts')->count() : 0,
            ];

            // Activity log metrics (last 24 hours)
            if (Schema::hasTable('activity_log')) {
                $metrics['activityLast24h'] = Activity::where('created_at', '>=', now()->subDay())->count();
                $metrics['totalActivityLogs'] = Activity::count();
            }

            return $metrics;
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Get recent errors from Telescope or logs
     */
    public function getRecentErrors(): array
    {
        try {
            $errors = [];

            // Check Telescope exceptions if available
            if (Schema::hasTable('telescope_entries')) {
                $telescopeErrors = DB::table('telescope_entries')
                    ->where('type', 'exception')
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
                    ->get(['uuid', 'created_at', 'content']);

                foreach ($telescopeErrors as $error) {
                    $content = json_decode($error->content, true);
                    $errors[] = [
                        'id' => $error->uuid,
                        'timestamp' => $error->created_at,
                        'class' => $content['class'] ?? 'Unknown',
                        'message' => isset($content['message']) ? substr($content['message'], 0, 200) : null,
                        'file' => $content['file'] ?? null,
                        'line' => $content['line'] ?? null,
                    ];
                }
            }

            // Count errors by type (last 24 hours)
            $errorCounts = [];
            if (Schema::hasTable('telescope_entries')) {
                $counts = DB::table('telescope_entries')
                    ->select('type', DB::raw('COUNT(*) as count'))
                    ->where('created_at', '>=', now()->subDay())
                    ->groupBy('type')
                    ->get();

                foreach ($counts as $count) {
                    $errorCounts[$count->type] = $count->count;
                }
            }

            return [
                'recentExceptions' => $errors,
                'last24hCounts' => $errorCounts,
                'totalExceptionsLast24h' => $errorCounts['exception'] ?? 0,
            ];
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage(),
                'recentExceptions' => [],
            ];
        }
    }

    /**
     * Get a simple health check (for uptime monitoring)
     */
    public function getSimpleHealthCheck(): array
    {
        $dbCheck = $this->checkDatabase();

        return [
            'status' => $dbCheck['status'] === 'healthy' ? 'ok' : 'error',
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
