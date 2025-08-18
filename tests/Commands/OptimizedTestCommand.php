<?php

namespace Tests\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class OptimizedTestCommand extends Command
{
    protected $signature = 'test:optimized {pattern?} {--parallel=} {--fast}';
    protected $description = 'Run tests with maximum optimizations';

    public function handle()
    {
        $pattern = $this->argument('pattern') ?: '';
        $parallel = $this->option('parallel') ?: $this->getOptimalParallelism();
        $fast = $this->option('fast');
        
        $this->info("🚀 Running Optimized Tests with {$parallel} parallel processes...");
        
        $env = [
            'APP_ENV' => 'testing',
            'DB_CONNECTION' => 'sqlite',
            'DB_DATABASE' => ':memory:',
            'LOG_LEVEL' => 'emergency',
            'CACHE_STORE' => 'array',
            'SESSION_DRIVER' => 'array',
            'QUEUE_CONNECTION' => 'sync',
            'MAIL_MAILER' => 'array',
            'PULSE_ENABLED' => 'false',
            'TELESCOPE_ENABLED' => 'false',
        ];
        
        $command = [
            'vendor/bin/phpunit',
            '--configuration=phpunit.xml',
            '--no-coverage',
            '--stop-on-failure',
        ];
        
        if ($fast) {
            $command[] = '--no-logging';
            $command[] = '--no-interaction';
        }
        
        if ($pattern) {
            $command[] = "--filter={$pattern}";
        }
        
        if ($parallel > 1) {
            $command[] = "--parallel={$parallel}";
        }
        
        $process = new Process($command, base_path(), $env);
        $process->setTimeout(600); // 10 minutes max
        
        $startTime = microtime(true);
        
        $process->run(function ($type, $buffer) {
            echo $buffer;
        });
        
        $duration = microtime(true) - $startTime;
        
        if ($process->getExitCode() === 0) {
            $this->info("✅ Tests completed successfully in " . round($duration, 2) . " seconds!");
        } else {
            $this->error("❌ Tests failed in " . round($duration, 2) . " seconds");
        }
        
        return $process->getExitCode();
    }
    
    private function getOptimalParallelism(): int
    {
        // Detectar número de cores disponibles
        $cores = (int) shell_exec('nproc 2>/dev/null') ?: 4;
        
        // Usar 75% de los cores para dejar recursos al sistema
        return max(1, (int) floor($cores * 0.75));
    }
}