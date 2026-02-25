<?php

namespace App\Console\Commands\ModuleGeneration\Generators;

use App\Console\Commands\ModuleGeneration\ModuleConfig;
use Illuminate\Support\Facades\File;

class RouteServiceProviderGenerator
{
    public function generate(ModuleConfig $module): string
    {
        $path = base_path("Modules/{$module->name}/app/Providers/RouteServiceProvider.php");
        File::ensureDirectoryExists(dirname($path));

        $namespace = "Modules\\{$module->name}\\Providers";

        $content = <<<PHP
<?php

namespace {$namespace};

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    protected string \$name = '{$module->name}';

    public function boot(): void
    {
        parent::boot();
    }

    public function map(): void
    {
        \$this->mapApiRoutes();
        \$this->mapWebRoutes();
        \$this->mapJsonApiRoutes();
    }

    protected function mapWebRoutes(): void
    {
        Route::middleware('web')->group(module_path(\$this->name, '/routes/web.php'));
    }

    protected function mapApiRoutes(): void
    {
        Route::middleware('api')->prefix('api')->name('api.')->group(module_path(\$this->name, '/routes/api.php'));
    }

    protected function mapJsonApiRoutes(): void
    {
        if (file_exists(module_path(\$this->name, '/routes/jsonapi.php'))) {
            Route::middleware('api')->prefix('api')->group(module_path(\$this->name, '/routes/jsonapi.php'));
        }
    }
}

PHP;

        File::put($path, $content);
        return $path;
    }
}
