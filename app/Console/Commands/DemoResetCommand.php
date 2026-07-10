<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

/**
 * DemoResetCommand
 *
 * Wipes and reseeds the demo database. Intended ONLY for public demo
 * instances (marca blanca) with a disposable database. Guarded by
 * APP_DEMO_MODE: the command refuses to run when demo mode is off,
 * regardless of --force, so it can never wipe a real tenant.
 *
 * Pipeline:
 *   1. migrate:fresh --force
 *   2. CleanDatabaseSeeder      (roles, users, essential catalogs)
 *   3. DemoAppSettingsSeeder    (Demo Company branding placeholders)
 *   4. DemoWorkflowSeeder       (persona users, products, contacts, quote/order flows)
 */
class DemoResetCommand extends Command
{
    protected $signature = 'demo:reset {--force : Skip the interactive confirmation}';

    protected $description = 'Wipe and reseed the demo database (requires APP_DEMO_MODE=true)';

    public function handle(): int
    {
        // ABSOLUTE GUARD: never run outside demo mode. --force does NOT bypass this.
        if (config('app.demo_mode') !== true) {
            $this->error('demo:reset refused: APP_DEMO_MODE is not enabled');

            return self::FAILURE;
        }

        if (!$this->option('force') && !$this->confirm('This will WIPE the database and reseed demo data. Continue?')) {
            $this->info('demo:reset aborted by user.');

            return self::SUCCESS;
        }

        $startedAt = now();
        $this->logStep('demo:reset started');

        $steps = [
            'migrate:fresh' => fn () => Artisan::call('migrate:fresh', ['--force' => true], $this->output),
            'CleanDatabaseSeeder' => fn () => Artisan::call('db:seed', [
                '--class' => \Database\Seeders\CleanDatabaseSeeder::class,
                '--force' => true,
            ], $this->output),
            'DemoAppSettingsSeeder' => fn () => Artisan::call('db:seed', [
                '--class' => \Database\Seeders\DemoAppSettingsSeeder::class,
                '--force' => true,
            ], $this->output),
            'DemoWorkflowSeeder' => fn () => Artisan::call('db:seed', [
                '--class' => \Database\Seeders\DemoWorkflowSeeder::class,
                '--force' => true,
            ], $this->output),
        ];

        foreach ($steps as $name => $step) {
            $stepStart = microtime(true);
            $this->logStep("running {$name}...");

            $exitCode = $step();

            $elapsed = round(microtime(true) - $stepStart, 2);

            if ($exitCode !== 0) {
                $this->error("demo:reset failed at step '{$name}' (exit {$exitCode}, {$elapsed}s)");
                Log::error('demo:reset failed', ['step' => $name, 'exit_code' => $exitCode]);

                return self::FAILURE;
            }

            $this->logStep("{$name} done ({$elapsed}s)");
        }

        $totalSeconds = round($startedAt->diffInSeconds(now()), 1);
        $this->logStep("demo:reset completed in {$totalSeconds}s");
        Log::info('demo:reset completed', [
            'started_at' => $startedAt->toIso8601String(),
            'finished_at' => now()->toIso8601String(),
            'duration_seconds' => $totalSeconds,
        ]);

        return self::SUCCESS;
    }

    private function logStep(string $message): void
    {
        $this->info('[' . now()->format('Y-m-d H:i:s') . '] ' . $message);
    }
}
