<?php

namespace Tests\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class FastTestCommand extends Command
{
    protected $signature = 'test:fast {pattern?} {--parallel=4}';
    protected $description = 'Run tests with optimizations for speed';

    public function handle()
    {
        $pattern = $this->argument('pattern') ?: '';
        $parallel = $this->option('parallel');
        
        $this->info('🚀 Running Fast Tests...');
        
        // Configurar environment para tests rápidos
        $env = [
            'APP_ENV' => 'testing',
            'DB_CONNECTION' => 'sqlite',
            'DB_DATABASE' => ':memory:',
            'LOG_LEVEL' => 'emergency',
            'CACHE_STORE' => 'array',
            'SESSION_DRIVER' => 'array',
            'QUEUE_CONNECTION' => 'sync',
            'MAIL_MAILER' => 'array',
        ];
        
        $command = [
            'vendor/bin/phpunit',
            '--configuration=phpunit.xml',
            '--stop-on-failure',
            '--no-coverage'
        ];
        
        if ($pattern) {
            $command[] = "--filter={$pattern}";
        }
        
        if ($parallel > 1) {
            $command[] = "--parallel={$parallel}";
        }
        
        $process = new Process($command, base_path(), $env);
        $process->setTimeout(300); // 5 minutos max
        
        $process->run(function ($type, $buffer) {
            echo $buffer;
        });
        
        return $process->getExitCode();
    }
}