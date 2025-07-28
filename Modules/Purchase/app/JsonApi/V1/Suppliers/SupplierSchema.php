<?php

namespace Modules\Purchase\JsonApi\V1\Suppliers;

use LaravelJsonApi\Eloquent\Fields\Boolean;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Fields\Relations\HasMany;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\WhereLike;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;
use Modules\Purchase\Models\Supplier;

class SupplierSchema extends Schema
{
    /**
     * The model the schema corresponds to.
     */
    public static string $model = Supplier::class;

    /**
     * The maximum depth of include paths.
     */
    protected int $maxDepth = 3;

    /**
     * Get the resource fields.
     */
    public function fields(): array
    {
        return [
            ID::make(),
            
            // Campos básicos
            Str::make('name')->sortable(),
            Str::make('email')->sortable(),
            Str::make('phone'),
            Str::make('address'),
            Str::make('rfc'),
            Boolean::make('isActive', 'is_active')->sortable(),
            
            // Relaciones
            HasMany::make('purchaseOrders')
                   ->type('purchase-orders'),
            
            // Timestamps
            DateTime::make('createdAt', 'created_at')->readOnly()->sortable(),
            DateTime::make('updatedAt', 'updated_at')->readOnly()->sortable(),
        ];
    }

    /**
     * Get the resource filters.
     */
    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            Where::make('name'),
            Where::make('email'),
            Where::make('rfc'),
            Where::make('isActive', 'is_active')->asBoolean(),
        ];
    }

    /**
     * Get the resource include paths.
     */
    public function includePaths(): array
    {
        return [
            'purchaseOrders',
        ];
    }

    /**
     * Get the resource paginator.
     */
    public function pagination(): ?PagePagination
    {
        return PagePagination::make();
    }

    /**
     * Get the JSON:API resource type.
     */
    public static function type(): string
    {
        return 'suppliers';
    }
}
