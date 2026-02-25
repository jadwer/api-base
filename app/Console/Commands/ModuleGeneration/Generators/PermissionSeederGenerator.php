<?php

namespace App\Console\Commands\ModuleGeneration\Generators;

use App\Console\Commands\ModuleGeneration\ModuleConfig;
use Illuminate\Support\Facades\File;

class PermissionSeederGenerator
{
    public function generate(ModuleConfig $module): string
    {
        $path = base_path("Modules/{$module->name}/Database/seeders/{$module->name}PermissionSeeder.php");
        File::ensureDirectoryExists(dirname($path));

        $namespace = "Modules\\{$module->name}\\Database\\Seeders";
        $permissions = $this->buildPermissionsList($module);

        $content = <<<PHP
<?php

namespace {$namespace};

use Illuminate\Database\Seeder;
use App\Database\Seeders\Concerns\BulkPermissions;

class {$module->name}PermissionSeeder extends Seeder
{
    use BulkPermissions;

    public function run(): void
    {
        \$permissions = [
{$permissions}
        ];

        \$this->bulkCreatePermissions(\$permissions);
    }
}

PHP;

        File::put($path, $content);
        return $path;
    }

    private function buildPermissionsList(ModuleConfig $module): string
    {
        $lines = [];
        foreach ($module->entities as $entity) {
            $prefix = $entity->permissionPrefix;
            $lines[] = "            '{$prefix}.index', '{$prefix}.show', '{$prefix}.store', '{$prefix}.update', '{$prefix}.destroy',";
        }
        return implode("\n", $lines);
    }
}
