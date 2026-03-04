<?php

namespace App\Console\Commands;

use App\Services\BanxicoExchangeRateProvider;
use Illuminate\Console\Command;
use Modules\AppConfig\Models\AppSetting;

class UpdateExchangeRatesCommand extends Command
{
    protected $signature = 'currency:update-rates {--force : Update even if auto-update is disabled}';

    protected $description = 'Update currency exchange rates from Banxico API';

    public function handle(BanxicoExchangeRateProvider $provider): int
    {
        $autoUpdate = AppSetting::getBoolean('currency.auto_update_rates', true);
        $source = AppSetting::get('currency.exchange_rate_source', 'banxico');

        if (!$autoUpdate && !$this->option('force')) {
            $this->info('Auto-update is disabled. Use --force to override.');
            return self::SUCCESS;
        }

        if ($source !== 'banxico' && !$this->option('force')) {
            $this->info("Exchange rate source is '{$source}', not 'banxico'. Use --force to override.");
            return self::SUCCESS;
        }

        $this->info('Fetching exchange rates from Banxico...');

        $results = $provider->updateAllRates();

        foreach ($results as $code => $result) {
            $this->line("  {$code}: {$result}");
        }

        $failures = collect($results)->filter(fn ($r) => str_contains($r, 'failed'))->count();

        if ($failures > 0) {
            $this->warn("{$failures} rate(s) failed to update.");
            return self::FAILURE;
        }

        $this->info('Exchange rates updated successfully.');
        return self::SUCCESS;
    }
}
