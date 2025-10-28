<?php

namespace App\Observers;

use App\Http\Middleware\CacheJsonApiResponse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * CacheInvalidationObserver
 *
 * Automatically invalidates JSON:API response cache when models are created, updated, or deleted.
 *
 * Usage:
 *   In your model:
 *   protected static function boot()
 *   {
 *       parent::boot();
 *       static::observe(CacheInvalidationObserver::class);
 *   }
 *
 * Or register globally in AppServiceProvider:
 *   ARInvoice::observe(CacheInvalidationObserver::class);
 */
class CacheInvalidationObserver
{
    /**
     * Handle the Model "created" event.
     */
    public function created(Model $model): void
    {
        $this->invalidateCache($model);
    }

    /**
     * Handle the Model "updated" event.
     */
    public function updated(Model $model): void
    {
        $this->invalidateCache($model);
    }

    /**
     * Handle the Model "deleted" event.
     */
    public function deleted(Model $model): void
    {
        $this->invalidateCache($model);
    }

    /**
     * Handle the Model "restored" event.
     */
    public function restored(Model $model): void
    {
        $this->invalidateCache($model);
    }

    /**
     * Invalidate cache for this model's resource type
     */
    private function invalidateCache(Model $model): void
    {
        $resourceType = $this->getResourceType($model);

        if ($resourceType) {
            CacheJsonApiResponse::invalidate($resourceType);

            // Also invalidate related resource types if defined
            $relatedTypes = $this->getRelatedResourceTypes($model);
            foreach ($relatedTypes as $relatedType) {
                CacheJsonApiResponse::invalidate($relatedType);
            }
        }
    }

    /**
     * Get JSON:API resource type from model class name
     */
    private function getResourceType(Model $model): string
    {
        // Get class name without namespace
        $className = class_basename($model);

        // Convert to kebab-case plural
        // ARInvoice -> ar-invoices
        // Contact -> contacts
        return Str::kebab(Str::plural($className));
    }

    /**
     * Get related resource types that should also be invalidated
     *
     * This prevents stale data when relationships are included
     */
    private function getRelatedResourceTypes(Model $model): array
    {
        // Define relationships that should trigger cache invalidation
        $relationships = [
            'Modules\\Finance\\Models\\ARInvoice' => ['contacts', 'sales-orders', 'journal-entries', 'payment-applications'],
            'Modules\\Finance\\Models\\APInvoice' => ['contacts', 'purchase-orders', 'journal-entries', 'ap-payment-applications'],
            'Modules\\Finance\\Models\\Payment' => ['ar-invoices', 'contacts', 'bank-accounts'],
            'Modules\\Finance\\Models\\APPayment' => ['ap-invoices', 'contacts', 'bank-accounts'],
            'Modules\\Sales\\Models\\SalesOrder' => ['contacts', 'sales-order-items', 'ar-invoices'],
            'Modules\\Purchase\\Models\\PurchaseOrder' => ['contacts', 'purchase-order-items', 'ap-invoices'],
            'Modules\\Accounting\\Models\\JournalEntry' => ['fiscal-periods', 'journal-lines', 'accounts'],
            'Modules\\Accounting\\Models\\FiscalPeriod' => ['journal-entries'],
            'Modules\\Inventory\\Models\\InventoryMovement' => ['products', 'warehouses', 'stock'],
            'Modules\\Inventory\\Models\\Stock' => ['products', 'warehouses'],
            'Modules\\Contacts\\Models\\Contact' => [
                'ar-invoices',
                'ap-invoices',
                'sales-orders',
                'purchase-orders',
                'contact-people',
                'contact-addresses',
                'contact-documents',
            ],
        ];

        $modelClass = get_class($model);

        return $relationships[$modelClass] ?? [];
    }
}
