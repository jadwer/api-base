<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\TelescopeServiceProvider::class,
    LaravelJsonApi\Laravel\ServiceProvider::class,

    Modules\User\Providers\UserServiceProvider::class,
    Modules\Audit\Providers\AuditServiceProvider::class,
    Modules\PageBuilder\Providers\PageBuilderServiceProvider::class,
];
