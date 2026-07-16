<?php

namespace Modules\Billing\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class BillingServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'Billing';

    protected string $nameLower = 'billing';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerCommands();
        $this->registerCommandSchedules();
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->name, 'Database/migrations'));

        // Validate external service configurations on boot (Phase 9 - Technical Debt)
        $this->validateServiceConfigurations();
    }

    /**
     * Validate external service configurations.
     * Logs warnings for missing configurations to help with early detection.
     */
    protected function validateServiceConfigurations(): void
    {
        // Skip validation during console commands (migrations, tests, etc.)
        if ($this->app->runningInConsole()) {
            return;
        }

        // Validate Stripe configuration
        $stripeKey = config('services.stripe.secret');
        if (empty($stripeKey)) {
            Log::warning('Billing: Stripe API key not configured. Payment processing will not work.', [
                'config_key' => 'services.stripe.secret',
            ]);
        }

        $stripeWebhookSecret = config('services.stripe.webhook_secret');
        if (empty($stripeWebhookSecret)) {
            Log::warning('Billing: Stripe webhook secret not configured. Webhook verification will fail.', [
                'config_key' => 'services.stripe.webhook_secret',
            ]);
        }

        // Validate SW PAC configuration
        $pacConfig = config('billing.sw_pac');
        if (!empty($pacConfig['enabled'])) {
            $hasToken = !empty($pacConfig['token']);
            $hasCredentials = !empty($pacConfig['user']) && !empty($pacConfig['password']);

            if (!$hasToken && !$hasCredentials) {
                Log::warning('Billing: SW PAC is enabled but no credentials configured. CFDI stamping will fail.', [
                    'config_key' => 'billing.sw_pac',
                    'has_token' => $hasToken,
                    'has_user' => !empty($pacConfig['user']),
                ]);
            }

            if (empty($pacConfig['url'])) {
                Log::warning('Billing: SW PAC URL not configured.', [
                    'config_key' => 'billing.sw_pac.url',
                ]);
            }
        }
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);

        // Register StripeService as singleton
        $this->app->singleton(\Modules\Billing\Services\StripeService::class, function ($app) {
            return new \Modules\Billing\Services\StripeService();
        });

        // Register CFDI services as singletons
        $this->app->singleton(\Modules\Billing\Services\CFDI\CFDIXMLGenerator::class, function ($app) {
            return new \Modules\Billing\Services\CFDI\CFDIXMLGenerator();
        });

        $this->app->singleton(\Modules\Billing\Services\CFDI\CFDIPDFGenerator::class, function ($app) {
            return new \Modules\Billing\Services\CFDI\CFDIPDFGenerator();
        });

        // Register CFDI Automation Service as singleton
        $this->app->singleton(\Modules\Billing\Services\CFDIAutomationService::class, function ($app) {
            return new \Modules\Billing\Services\CFDIAutomationService();
        });

        // Register PAC services as singletons
        $this->app->singleton(\Modules\Billing\Services\PAC\SWPacService::class, function ($app) {
            return new \Modules\Billing\Services\PAC\SWPacService();
        });

        $this->app->singleton(\Modules\Billing\Services\CFDI\CFDIStampingService::class, function ($app) {
            return new \Modules\Billing\Services\CFDI\CFDIStampingService(
                $app->make(\Modules\Billing\Services\PAC\SWPacService::class),
                $app->make(\Modules\Billing\Services\CFDI\CFDIXMLGenerator::class),
                $app->make(\Modules\Billing\Services\CFDI\CFDIPDFGenerator::class)
            );
        });
    }

    /**
     * Register commands in the format of Command::class
     */
    protected function registerCommands(): void
    {
        $this->commands([
            \Modules\Billing\Console\UploadCsdToPacCommand::class,
        ]);
    }

    /**
     * Register command Schedules.
     */
    protected function registerCommandSchedules(): void
    {
        // $this->app->booted(function () {
        //     $schedule = $this->app->make(Schedule::class);
        //     $schedule->command('inspire')->hourly();
        // });
    }

    /**
     * Register translations.
     */
    public function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/'.$this->nameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->nameLower);
            $this->loadJsonTranslationsFrom($langPath);
        } else {
            $this->loadTranslationsFrom(module_path($this->name, 'lang'), $this->nameLower);
            $this->loadJsonTranslationsFrom(module_path($this->name, 'lang'));
        }
    }

    /**
     * Register config.
     */
    protected function registerConfig(): void
    {
        $configPath = module_path($this->name, config('modules.paths.generator.config.path'));

        if (is_dir($configPath)) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($configPath));

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $config = str_replace($configPath.DIRECTORY_SEPARATOR, '', $file->getPathname());
                    $config_key = str_replace([DIRECTORY_SEPARATOR, '.php'], ['.', ''], $config);
                    $segments = explode('.', $this->nameLower.'.'.$config_key);

                    // Remove duplicated adjacent segments
                    $normalized = [];
                    foreach ($segments as $segment) {
                        if (end($normalized) !== $segment) {
                            $normalized[] = $segment;
                        }
                    }

                    $key = ($config === 'config.php') ? $this->nameLower : implode('.', $normalized);

                    $this->publishes([$file->getPathname() => config_path($config)], 'config');
                    $this->merge_config_from($file->getPathname(), $key);
                }
            }
        }
    }

    /**
     * Merge config from the given path recursively.
     */
    protected function merge_config_from(string $path, string $key): void
    {
        $existing = config($key, []);
        $module_config = require $path;

        config([$key => array_replace_recursive($existing, $module_config)]);
    }

    /**
     * Register views.
     */
    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/'.$this->nameLower);
        $sourcePath = module_path($this->name, 'resources/views');

        $this->publishes([$sourcePath => $viewPath], ['views', $this->nameLower.'-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->nameLower);

        Blade::componentNamespace(config('modules.namespace').'\\' . $this->name . '\\View\\Components', $this->nameLower);
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [];
    }

    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (config('view.paths') as $path) {
            if (is_dir($path.'/modules/'.$this->nameLower)) {
                $paths[] = $path.'/modules/'.$this->nameLower;
            }
        }

        return $paths;
    }
}
