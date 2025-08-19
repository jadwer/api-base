<?php

namespace Modules\Finance\JsonApi\V1\ARInvoiceReceipts;

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
use Modules\Finance\Models\ARInvoiceReceipt;

class ARInvoiceReceiptSchema extends Schema
{
    public static string $model = ARInvoiceReceipt::class;

    public function fields(): array
    {
        return [
            ID::make(),
            
            Number::make('arInvoiceId'),
            Number::make('arReceiptId'),
            Number::make('amountApplied')->sortable(),
            DateTime::make('appliedAt')->sortable(),
            Number::make('exchangeRateAtApply')->sortable(),
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

        ];
    }

    public function sortables(): array
    {
        return [
            'amount_applied',
            'applied_at',
            'exchange_rate_at_apply',
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
        return "a-r-invoice-receipts";
    }
}