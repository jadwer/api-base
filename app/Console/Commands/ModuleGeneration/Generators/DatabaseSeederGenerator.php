<?php

namespace App\Console\Commands\ModuleGeneration\Generators;

use App\Console\Commands\ModuleGeneration\ModuleConfig;
use Illuminate\Support\Facades\File;

class DatabaseSeederGenerator
{
    public function generate(ModuleConfig $module): string
    {
        $path = base_path("Modules/{$module->name}/Database/seeders/{$module->name}DatabaseSeeder.php");
        File::ensureDirectoryExists(dirname($path));

        $namespace = "Modules\\{$module->name}\\Database\\Seeders";

        $content = <<<PHP
<?php

namespace {$namespace};

use Illuminate\Database\Seeder;

class {$module->name}DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        \$this->call([
            {$module->name}PermissionSeeder::class,
            {$module->name}AssignPermissionsSeeder::class,
        ]);
    }
}

PHP;

        File::put($path, $content);
        return $path;
    }
}
