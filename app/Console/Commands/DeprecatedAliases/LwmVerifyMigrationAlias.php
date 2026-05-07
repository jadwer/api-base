<?php

namespace App\Console\Commands\DeprecatedAliases;

use App\Console\Commands\VerifyMigrationIntegrity;
use Illuminate\Support\Facades\Log;

/**
 * Deprecated alias for the renamed `system:verify-migration` command.
 *
 * Kept for one release cycle so external scripts and CI jobs that still
 * call `php artisan lwm:verify-migration` keep working. Logs a deprecation
 * warning on every invocation so adopters know to migrate.
 *
 * Removed in the next major release of the api-base template.
 */
class LwmVerifyMigrationAlias extends VerifyMigrationIntegrity
{
    protected $signature = 'lwm:verify-migration {--fix : Attempt to fix issues found}';

    protected $description = 'DEPRECATED. Use system:verify-migration. This alias will be removed in the next major release.';

    public function handle(): int
    {
        $this->warn('The lwm:verify-migration command is deprecated. Use system:verify-migration instead.');
        Log::warning('Deprecated artisan command invoked: lwm:verify-migration. Use system:verify-migration.');

        return parent::handle();
    }
}
