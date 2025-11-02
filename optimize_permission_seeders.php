#!/usr/bin/env php
<?php

/**
 * Script to automatically optimize all permission seeders
 * Converts firstOrCreate() calls to bulk inserts
 */

$files = [
    'Modules/Ecommerce/Database/seeders/PermissionsSeeder.php',
    'Modules/HR/Database/seeders/PermissionsSeeder.php',
    'Modules/Billing/Database/seeders/PermissionsSeeder.php',
];

foreach ($files as $file) {
    if (!file_exists($file)) {
        echo "⚠️  File not found: $file\n";
        continue;
    }

    echo "Processing: $file\n";

    $content = file_get_contents($file);

    // Extract all permission names
    preg_match_all("/'name' => '([^']+)'/", $content, $matches);
    $permissions = array_unique($matches[1]);

    if (empty($permissions)) {
        echo "  ⚠️  No permissions found\n";
        continue;
    }

    echo "  Found " . count($permissions) . " permissions\n";

    // Generate optimized code
    $namespace = preg_match('/namespace ([^;]+);/', $content, $nsMatch) ? $nsMatch[1] : '';

    $permissionsArray = '';
    foreach ($permissions as $perm) {
        $permissionsArray .= "            '$perm',\n";
    }

    $optimized = <<<PHP
<?php

namespace $namespace;

use Illuminate\Database\Seeder;
use App\Database\Seeders\Concerns\BulkPermissions;

class PermissionsSeeder extends Seeder
{
    use BulkPermissions;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \$this->command->info('🔐 Seeding permissions...');

        \$permissions = [
$permissionsArray        ];

        \$this->bulkCreatePermissions(\$permissions);

        \$this->command->info('✅ Permissions seeded successfully!');
    }
}

PHP;

    // Backup original
    $backup = $file . '.backup';
    copy($file, $backup);
    echo "  ✅ Backup created: $backup\n";

    // Write optimized version
    file_put_contents($file, $optimized);
    echo "  ✅ Optimized version written\n";
}

echo "\n✅ Optimization complete!\n";
echo "To restore backups, run: find Modules -name '*.backup' -exec sh -c 'mv \"\$1\" \"\${1%.backup}\"' _ {} \\;\n";
