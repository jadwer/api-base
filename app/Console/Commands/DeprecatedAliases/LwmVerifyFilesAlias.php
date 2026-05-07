<?php

namespace App\Console\Commands\DeprecatedAliases;

use App\Console\Commands\VerifyProductFiles;
use Illuminate\Support\Facades\Log;

/**
 * Deprecated alias for the renamed `system:verify-files` command.
 * See LwmVerifyMigrationAlias for context. Removed in next major release.
 */
class LwmVerifyFilesAlias extends VerifyProductFiles
{
    protected $signature = 'lwm:verify-files {--fix : Attempt to fix missing files}';

    protected $description = 'DEPRECATED. Use system:verify-files. This alias will be removed in the next major release.';

    public function handle(): int
    {
        $this->warn('The lwm:verify-files command is deprecated. Use system:verify-files instead.');
        Log::warning('Deprecated artisan command invoked: lwm:verify-files. Use system:verify-files.');

        return parent::handle();
    }
}
