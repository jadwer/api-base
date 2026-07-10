<?php

namespace Modules\Commissions\JsonApi\V1\Commissions;

use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;
use Modules\Commissions\Models\Commission;

class CommissionSchema extends Schema
{
    public static string $model = Commission::class;

    public function fields(): array
    {
        return [
            ID::make(),

            Number::make('salesOrderId', 'sales_order_id')->sortable()->readOnly(),
            Number::make('arInvoiceId', 'ar_invoice_id')->sortable()->readOnly(),
            Number::make('userId', 'user_id')->sortable()->readOnly(),
            Number::make('contactId', 'contact_id')->sortable()->readOnly(),
            Number::make('baseAmount', 'base_amount')->sortable()->readOnly(),
            Number::make('commissionPct', 'commission_pct')->sortable()->readOnly(),
            Number::make('commissionAmount', 'commission_amount')->sortable()->readOnly(),
            Str::make('status')->sortable()->readOnly(),
            DateTime::make('earnedAt', 'earned_at')->sortable()->readOnly(),
            DateTime::make('paidAt', 'paid_at')->sortable()->readOnly(),
            Str::make('paymentReference', 'payment_reference')->readOnly(),

            DateTime::make('createdAt')->sortable()->readOnly(),
            DateTime::make('updatedAt')->sortable()->readOnly(),

            BelongsTo::make('salesOrder', 'salesOrder')->type('sales-orders')->readOnly(),
            BelongsTo::make('arInvoice', 'arInvoice')->type('ar-invoices')->readOnly(),
            BelongsTo::make('user', 'user')->type('users')->readOnly(),
            BelongsTo::make('contact', 'contact')->type('contacts')->readOnly(),
        ];
    }

    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            Where::make('status'),
            Where::make('userId', 'user_id'),
            Where::make('contactId', 'contact_id'),
            Where::make('salesOrderId', 'sales_order_id'),
        ];
    }

    public function includePaths(): array
    {
        return [
            'salesOrder',
            'arInvoice',
            'user',
            'contact',
        ];
    }

    public function pagination(): ?Paginator
    {
        return PagePagination::make();
    }

    public static function type(): string
    {
        return 'commissions';
    }
}
