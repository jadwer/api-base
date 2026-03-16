<?php

namespace App\Providers;

use App\Observers\CacheInvalidationObserver;
use App\Services\MailConfigService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Failed;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Configure mail from AppConfig database settings
        if (!app()->runningUnitTests()) {
            MailConfigService::configureMailer();
        }

        // Register cache invalidation observers for critical models
        $this->registerCacheInvalidation();

        // Register authentication event listeners for audit logging
        $this->registerAuthenticationLogging();
    }

    /**
     * Register authentication event listeners for audit trail
     */
    private function registerAuthenticationLogging(): void
    {
        // Log successful logins
        Event::listen(Login::class, function (Login $event) {
            activity()
                ->causedBy($event->user)
                ->withProperties([
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'guard' => $event->guard,
                ])
                ->log('login');
        });

        // Log logouts
        Event::listen(Logout::class, function (Logout $event) {
            if ($event->user) {
                activity()
                    ->causedBy($event->user)
                    ->withProperties([
                        'ip' => request()->ip(),
                        'guard' => $event->guard,
                    ])
                    ->log('logout');
            }
        });

        // Log failed login attempts
        Event::listen(Failed::class, function (Failed $event) {
            activity()
                ->withProperties([
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'credentials' => [
                        'email' => $event->credentials['email'] ?? 'unknown',
                    ],
                    'guard' => $event->guard,
                ])
                ->log('login_failed');
        });
    }

    /**
     * Register cache invalidation observers
     */
    private function registerCacheInvalidation(): void
    {
        $models = [
            // Finance Module
            \Modules\Finance\Models\ARInvoice::class,
            \Modules\Finance\Models\APInvoice::class,
            \Modules\Finance\Models\Payment::class,
            \Modules\Finance\Models\PaymentApplication::class,
            \Modules\Finance\Models\BankAccount::class,
            \Modules\Finance\Models\BankTransaction::class,

            // Accounting Module
            \Modules\Accounting\Models\Account::class,
            \Modules\Accounting\Models\FiscalPeriod::class,
            \Modules\Accounting\Models\JournalEntry::class,
            \Modules\Accounting\Models\JournalLine::class,

            // Sales & Purchase
            \Modules\Sales\Models\SalesOrder::class,
            \Modules\Purchase\Models\PurchaseOrder::class,

            // Contacts
            \Modules\Contacts\Models\Contact::class,

            // Inventory
            \Modules\Inventory\Models\Product::class,
            \Modules\Inventory\Models\Stock::class,
            \Modules\Inventory\Models\InventoryMovement::class,
        ];

        foreach ($models as $model) {
            if (class_exists($model)) {
                $model::observe(CacheInvalidationObserver::class);
            }
        }
    }
}
