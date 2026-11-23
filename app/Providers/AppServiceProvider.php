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

        // Demo mode hard lock: force the log mail driver AFTER the database
        // mailer config so it always wins. Even if a prospect reaches the
        // Mailer Manager SMTP screen or the .env is misconfigured, no email
        // ever leaves a demo instance.
        if (config('app.demo_mode')) {
            config(['mail.default' => 'log']);
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
        // La llave es `ip_address` (la que lee AuditSchema) y se fija `event` para
        // que el filtro por evento encuentre las sesiones (PLA-17, PLA-18).
        $context = fn (array $extra = []) => array_merge([
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ], $extra);

        Event::listen(Login::class, function (Login $event) use ($context) {
            activity()
                ->causedBy($event->user)
                ->event('login')
                ->withProperties($context(['guard' => $event->guard]))
                ->log('login');
        });

        Event::listen(Logout::class, function (Logout $event) use ($context) {
            if ($event->user) {
                activity()
                    ->causedBy($event->user)
                    ->event('logout')
                    ->withProperties($context(['guard' => $event->guard]))
                    ->log('logout');
            }
        });

        Event::listen(Failed::class, function (Failed $event) use ($context) {
            activity()
                ->event('login_failed')
                ->withProperties($context([
                    'credentials' => ['email' => $event->credentials['email'] ?? 'unknown'],
                    'guard' => $event->guard,
                ]))
                ->log('login_failed');
        });

        // Los cambios de modelo (LogsActivity) no guardaban desde donde se hicieron.
        // Se engancha al modelo configurado (los eventos de Eloquent van por clase).
        $activityModel = config('activitylog.activity_model', \Spatie\Activitylog\Models\Activity::class);
        $activityModel::creating(function ($activity) {
            if (app()->runningInConsole() && ! app()->runningUnitTests()) {
                return;
            }
            $properties = collect($activity->properties ?? []);
            if (! $properties->has('ip_address') && request()->ip()) {
                $activity->properties = $properties->merge([
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]);
            }
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
