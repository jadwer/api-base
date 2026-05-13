<?php

// Base providers list (always registered).
$providers = [
    App\Providers\AppServiceProvider::class,
    App\Providers\CustomerServiceProvider::class,
    LaravelJsonApi\Laravel\ServiceProvider::class,

    Modules\User\Providers\UserServiceProvider::class,
    Modules\Audit\Providers\AuditServiceProvider::class,
    Modules\PageBuilder\Providers\PageBuilderServiceProvider::class,
    Modules\PermissionManager\Providers\PermissionManagerServiceProvider::class,
    Modules\Product\Providers\ProductServiceProvider::class,
    Modules\Inventory\Providers\InventoryServiceProvider::class,
];

// Telescope is dev-only (require-dev). Only register the local provider when
// the parent class actually exists in vendor/. This lets `composer install
// --no-dev` work in production without crashing at boot.
if (class_exists(\Laravel\Telescope\TelescopeApplicationServiceProvider::class)) {
    $providers[] = App\Providers\TelescopeServiceProvider::class;
}

return $providers;
