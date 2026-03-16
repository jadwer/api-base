<?php

namespace Modules\MailerManager\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;
use LaravelJsonApi\Laravel\Facades\JsonApiRoute;
use LaravelJsonApi\Laravel\Routing\ResourceRegistrar;

class RouteServiceProvider extends ServiceProvider
{
    protected string $name = 'MailerManager';

    public function boot(): void
    {
        parent::boot();
    }

    public function map(): void
    {
        $this->mapApiRoutes();
        $this->mapJsonApiRoutes();
    }

    protected function mapApiRoutes(): void
    {
        Route::middleware('api')->prefix('api')->name('api.')->group(module_path($this->name, '/routes/api.php'));
    }

    protected function mapJsonApiRoutes(): void
    {
        JsonApiRoute::server('v1')
            ->prefix('api/v1')
            ->resources(function (ResourceRegistrar $server) {
                $server->resource('email-templates', \Modules\MailerManager\Http\Controllers\Api\V1\EmailTemplateController::class);
                $server->resource('system-emails', \Modules\MailerManager\Http\Controllers\Api\V1\SystemEmailController::class);
            });
    }
}
