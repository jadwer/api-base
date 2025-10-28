<?php

namespace App\Providers;

use App\Observers\CacheInvalidationObserver;
use Illuminate\Support\ServiceProvider;

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
        // Register cache invalidation observers for critical models
        $this->registerCacheInvalidation();
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
