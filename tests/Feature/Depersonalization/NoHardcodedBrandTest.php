<?php

namespace Tests\Feature\Depersonalization;

use Tests\TestCase;

/**
 * Hard guard against tenant-specific brand strings creeping back into the
 * api-base template. Run as part of the regular suite so any merge that
 * accidentally reintroduces "Labor Wasser" / "laborwasser" / "LWM" fails CI.
 *
 * Allowed exceptions are the deprecated alias commands under
 * app/Console/Commands/DeprecatedAliases/Lwm*Alias.php that intentionally
 * keep the old `lwm:` artisan signature for one-release backward compat.
 *
 * If you need to allow another file, extend $allowedFiles below with a
 * justification comment.
 */
class NoHardcodedBrandTest extends TestCase
{
    public function test_no_lwm_strings_in_modules_or_app(): void
    {
        $patterns = ['Labor Wasser', 'laborwasser', 'LWM'];

        // Cover every directory a tenant string could realistically land in.
        // Rationale: the original scope (Modules/ + app/) missed real hits in
        // database/seeders/ and database/migrations-scripts/ in Fase 1 because
        // they were never scanned. Seeders and resources/views are runtime
        // outputs visible to the customer, so they must stay clean too.
        $directories = [
            base_path('Modules'),
            base_path('app'),
            base_path('database'),
            base_path('resources'),
            base_path('routes'),
            base_path('config'),
            base_path('bootstrap'),
        ];

        $allowedFiles = [
            base_path('app/Console/Commands/DeprecatedAliases/LwmVerifyMigrationAlias.php'),
            base_path('app/Console/Commands/DeprecatedAliases/LwmVerifyFilesAlias.php'),
            base_path('app/Console/Commands/DeprecatedAliases/LwmImportProductionAlias.php'),
        ];

        // Allowed file extensions (skip binary assets, lockfiles, etc.).
        $allowedExtensions = ['php', 'blade.php', 'json', 'md', 'sql', 'env', 'txt'];

        $hits = [];

        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if (!$file->isFile()) {
                    continue;
                }
                $ext = $file->getExtension();
                if (!in_array($ext, ['php', 'md', 'json', 'yml', 'yaml', 'env', 'txt', 'gitkeep'], true)) {
                    continue;
                }

                $path = $file->getPathname();

                if (in_array($path, $allowedFiles, true)) {
                    continue;
                }

                $contents = file_get_contents($path);
                if ($contents === false) {
                    continue;
                }

                foreach ($patterns as $pattern) {
                    if (stripos($contents, $pattern) !== false) {
                        $hits[] = "$path contains '$pattern'";
                    }
                }
            }
        }

        $this->assertEmpty(
            $hits,
            "Brand hardcodes found in template code:\n" . implode("\n", $hits)
        );
    }
}
