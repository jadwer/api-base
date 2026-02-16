<?php

namespace Modules\AppConfig\Providers;

use Illuminate\Support\ServiceProvider;
use Nwidart\Modules\Traits\PathNamespace;

class AppConfigServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'AppConfig';

    protected string $nameLower = 'appconfig';

    public function boot(): void
    {
        $this->registerCommands();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));
    }

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }

    protected function registerCommands(): void
    {
        // $this->commands([]);
    }
}
