<?php

namespace App\Console\Commands\DeprecatedAliases;

use App\Console\Commands\ImportProductionData;
use Illuminate\Support\Facades\Log;

/**
 * Deprecated alias for the renamed `system:import-production` command.
 * See LwmVerifyMigrationAlias for context. Removed in next major release.
 */
class LwmImportProductionAlias extends ImportProductionData
{
    protected $signature = 'lwm:import-production
                            {--source= : Path to SQL dump file}
                            {--dry-run : Show what would be imported without making changes}
                            {--skip-products : Skip importing products}
                            {--skip-users : Skip importing users}';

    protected $description = 'DEPRECATED. Use system:import-production. This alias will be removed in the next major release.';

    public function handle(): int
    {
        $this->warn('The lwm:import-production command is deprecated. Use system:import-production instead.');
        Log::warning('Deprecated artisan command invoked: lwm:import-production. Use system:import-production.');

        return parent::handle();
    }
}
