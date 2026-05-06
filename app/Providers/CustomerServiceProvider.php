<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * CustomerServiceProvider
 *
 * Stub provider for client-specific overrides in the WordPress-style template.
 *
 * In api-base (the upstream template), this class is intentionally empty.
 *
 * In a client repo (e.g. clients/laborwasser/api), override this file to register
 * client-specific bindings without modifying any other template file. Examples:
 *
 *     public function register(): void
 *     {
 *         // Override request validation for Product:
 *         $this->app->bind(
 *             \Modules\Product\JsonApi\V1\Products\ProductRequest::class,
 *             \Clients\Laborwasser\Modules\Product\ProductRequestCustom::class,
 *         );
 *
 *         // Override a service:
 *         $this->app->bind(
 *             \App\Services\MailConfigService::class,
 *             \Clients\Laborwasser\Services\MailConfigServiceCustom::class,
 *         );
 *     }
 *
 *     public function boot(): void
 *     {
 *         // Register custom event listeners, observers, etc. here.
 *     }
 *
 * Why this lives in app/Providers/ (the template) and not in the client repo:
 * keeping the registration here means an upstream merge does not need any extra
 * step in bootstrap/providers.php — the client only edits this single file.
 */
class CustomerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Intentionally empty in the template. Clients override this file.
    }

    public function boot(): void
    {
        // Intentionally empty in the template. Clients override this file.
    }
}
