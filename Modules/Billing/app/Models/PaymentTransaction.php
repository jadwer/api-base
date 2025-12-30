<?php

namespace Modules\Billing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Billing\Database\Factories\PaymentTransactionFactory;
use Modules\Ecommerce\Models\CheckoutSession;
use Modules\Sales\Models\SalesOrder;
use Modules\Finance\Models\ARInvoice;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class PaymentTransaction extends Model
{
    use HasFactory, LogsActivity;

    /**
     * Activity Log Configuration
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'amount', 'gateway', 'payment_method', 'error_message', 'captured_at', 'failed_at', 'refunded_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected $fillable = [
        'checkout_session_id',
        'sales_order_id',
        'ar_invoice_id',
        'gateway',
        'payment_intent_id',
        'transaction_id',
        'client_secret',
        'amount',
        'currency',
        'status',
        'payment_method',
        'card_brand',
        'card_last4',
        'gateway_response',
        'error_message',
        'authorized_at',
        'captured_at',
        'failed_at',
        'refunded_at',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'float',
        'gateway_response' => 'array',
        'metadata' => 'array',
        'authorized_at' => 'datetime',
        'captured_at' => 'datetime',
        'failed_at' => 'datetime',
        'refunded_at' => 'datetime',
    ];

    /**
     * Relationship to CheckoutSession.
     */
    public function checkoutSession(): BelongsTo
    {
        return $this->belongsTo(CheckoutSession::class);
    }

    /**
     * Relationship to SalesOrder.
     */
    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    /**
     * Relationship to ARInvoice.
     */
    public function arInvoice(): BelongsTo
    {
        return $this->belongsTo(ARInvoice::class);
    }

    /**
     * Scope to filter only pending transactions.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to filter only captured transactions.
     */
    public function scopeCaptured($query)
    {
        return $query->where('status', 'captured');
    }

    /**
     * Scope to filter only failed transactions.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope to filter by gateway.
     */
    public function scopeByGateway($query, string $gateway)
    {
        return $query->where('gateway', $gateway);
    }

    /**
     * Check if transaction is successful.
     */
    public function isSuccessful(): bool
    {
        return $this->status === 'captured';
    }

    /**
     * Check if transaction is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if transaction has failed.
     */
    public function hasFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return PaymentTransactionFactory::new();
    }
}
