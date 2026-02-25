<?php

namespace App\Console\Commands\ModuleGeneration\Generators;

use App\Console\Commands\ModuleGeneration\ModuleConfig;
use Illuminate\Support\Facades\File;

class AssignPermissionSeederGenerator
{
    public function generate(ModuleConfig $module): string
    {
        $path = base_path("Modules/{$module->name}/Database/seeders/{$module->name}AssignPermissionsSeeder.php");
        File::ensureDirectoryExists(dirname($path));

        $namespace = "Modules\\{$module->name}\\Database\\Seeders";
        $godQuery = $this->buildGodQuery($module);
        $adminPerms = $this->buildAdminPermissions($module);
        $customerPerms = $this->buildCustomerPermissions($module);

        $content = <<<PHP
<?php

namespace {$namespace};

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class {$module->name}AssignPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // God: ALL permissions
        \$god = Role::where('name', 'god')->first();
        if (\$god) {
            \$permissions = Permission::where(function (\$query) {
{$godQuery}
            })->get();
            \$god->givePermissionTo(\$permissions);
        }

        // Admin: ALL permissions
        \$admin = Role::where('name', 'admin')->first();
        if (\$admin) {
            \$admin->givePermissionTo([
{$adminPerms}
            ]);
        }

        // Tech: ALL permissions (same as admin by default)
        \$tech = Role::where('name', 'tech')->first();
        if (\$tech) {
            \$tech->givePermissionTo([
{$adminPerms}
            ]);
        }

        // Customer: read-only
        \$customer = Role::where('name', 'customer')->first();
        if (\$customer) {
            \$customer->givePermissionTo([
{$customerPerms}
            ]);
        }
    }
}

PHP;

        File::put($path, $content);
        return $path;
    }

    private function buildGodQuery(ModuleConfig $module): string
    {
        $lines = [];
        $first = true;
        foreach ($module->entities as $entity) {
            $prefix = $entity->permissionPrefix;
            $method = $first ? '            $query->where' : "                ->orWhere";
            $lines[] = "{$method}('name', 'like', '{$prefix}.%')";
            $first = false;
        }

        // Add semicolon to last line
        if (!empty($lines)) {
            $lines[count($lines) - 1] .= ';';
        }

        return implode("\n", $lines);
    }

    private function buildAdminPermissions(ModuleConfig $module): string
    {
        $lines = [];
        foreach ($module->entities as $entity) {
            $prefix = $entity->permissionPrefix;
            $lines[] = "                // " . $entity->modelName;
            foreach (['index', 'show', 'store', 'update', 'destroy'] as $action) {
                $lines[] = "                '{$prefix}.{$action}',";
            }
        }
        return implode("\n", $lines);
    }

    private function buildCustomerPermissions(ModuleConfig $module): string
    {
        $lines = [];
        foreach ($module->entities as $entity) {
            $prefix = $entity->permissionPrefix;
            $lines[] = "                '{$prefix}.index',";
            $lines[] = "                '{$prefix}.show',";
        }
        return implode("\n", $lines);
    }
}
