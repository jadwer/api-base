<?php

namespace Modules\Finance\JsonApi\V1\APPayments;

use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Boolean;
use LaravelJsonApi\Eloquent\Fields\ArrayHash;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Relations\HasMany;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;
use Modules\Finance\Models\APPayment;

class APPaymentSchema extends Schema
{
    public static string $model = APPayment::class;

    public function fields(): array
    {
        return [
            ID::make(),
            
            Number::make('contactId'),
            DateTime::make('paymentDate')->sortable(),
            Str::make('paymentMethod')->sortable(),
            Str::make('currency')->sortable(),
            Number::make('amount')->sortable(),
            Number::make('bankAccountId'),
            Str::make('status')->sortable(),
            // Metadata
            ArrayHash::make('metadata'),
            
            // Timestamps
            DateTime::make('createdAt')->sortable()->readOnly(),
            DateTime::make('updatedAt')->sortable()->readOnly(),

        ];
    }

    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            \LaravelJsonApi\Eloquent\Filters\Where::make('payment_method'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('currency'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('status'),
        ];
    }

    public function sortables(): array
    {
        return [
            'payment_date',
            'payment_method',
            'currency',
            'amount',
            'status',
            'created_at',
            'updated_at',
        ];
    }

    public function includePaths(): array
    {
        return [

        ];
    }

    public function pagination(): ?Paginator
    {
        return PagePagination::make();
    }

    public static function type(): string
    {
        return "a-p-payments";
    }
}