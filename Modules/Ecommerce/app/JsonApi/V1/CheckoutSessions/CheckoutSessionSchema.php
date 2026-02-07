<?php

namespace Modules\Ecommerce\JsonApi\V1\CheckoutSessions;

use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\ArrayHash;
use LaravelJsonApi\Eloquent\Fields\Boolean;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Relations\HasMany;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;
use Modules\Ecommerce\Models\CheckoutSession;

class CheckoutSessionSchema extends Schema
{
    /**
     * The model the schema corresponds to.
     *
     * @var string
     */
    public static string $model = CheckoutSession::class;

    /**
     * Get the resource fields.
     *
     * @return array
     */
    public function fields(): array
    {
        return [
            ID::make(),

            // Foreign Keys
            Number::make('shoppingCartId', 'shopping_cart_id'),
            Number::make('userId', 'user_id'),
            Number::make('shippingMethodId', 'shipping_method_id'),

            // Session Status
            Str::make('status')->sortable(),
            Str::make('step'),

            // Contact Information
            Str::make('contactEmail', 'contact_email'),
            Str::make('contactPhone', 'contact_phone'),

            // Addresses (JSON)
            ArrayHash::make('billingAddress', 'billing_address'),
            ArrayHash::make('shippingAddress', 'shipping_address'),

            // Payment Information
            Str::make('paymentMethod', 'payment_method'),
            Str::make('paymentIntentId', 'payment_intent_id'),

            // Amounts
            Number::make('subtotalAmount', 'subtotal_amount'),
            Number::make('shippingAmount', 'shipping_amount'),
            Number::make('taxAmount', 'tax_amount'),
            Number::make('discountAmount', 'discount_amount'),
            Number::make('totalAmount', 'total_amount'),
            Str::make('currency'),

            // Metadata
            Str::make('notes'),
            ArrayHash::make('metadata'),

            // Timestamps
            DateTime::make('expiresAt', 'expires_at'),
            DateTime::make('completedAt', 'completed_at'),
            DateTime::make('createdAt', 'created_at')->sortable()->readOnly(),
            DateTime::make('updatedAt', 'updated_at')->sortable()->readOnly(),

            // Calculated Fields (appended attributes)
            Boolean::make('isExpired', 'is_expired')->readOnly(),
            Boolean::make('canProceedToPayment', 'can_proceed_to_payment')->readOnly(),
            Number::make('timeRemaining', 'time_remaining')->readOnly(),

            // Relationships
            BelongsTo::make('shoppingCart')->type('shopping-carts')->readOnly(),
            BelongsTo::make('user')->type('users')->readOnly(),
            BelongsTo::make('shippingMethod')->type('shipping-methods')->readOnly(),
            HasMany::make('inventoryReservations')->type('inventory-reservations')->readOnly(),
            HasMany::make('paymentTransactions')->type('payment-transactions')->readOnly(),
        ];
    }

    /**
     * Get the resource filters.
     *
     * @return array
     */
    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            Where::make('status'),
            Where::make('step'),
            Where::make('userId', 'user_id'),
            Where::make('shoppingCartId', 'shopping_cart_id'),
        ];
    }

    public function includePaths(): iterable
    {
        return [
            'shoppingCart',
            'user',
            'shippingMethod',
            'inventoryReservations',
            'paymentTransactions',
        ];
    }

    /**
     * Get the resource paginator.
     *
     * @return Paginator|null
     */
    public function pagination(): ?Paginator
    {
        return PagePagination::make();
    }
}
