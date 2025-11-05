<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Sales\Models\SalesOrder;
use Modules\Finance\Models\ARInvoice;

class PaymentTransaction extends Model
{
    use HasFactory;

    protected $table = 'payment_transactions';

    protected $fillable = [
        'checkout_session_id',
        'sales_order_id',
        'ar_invoice_id',
        'transaction_id',
        'gateway',
        'payment_method',
        'status',
        'amount',
        'currency',
        'gateway_response',
        'error_message',
        'processed_at',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'float',
        'gateway_response' => 'array',
        'processed_at' => 'datetime',
        'metadata' => 'array',
    ];

    // Calculated Attributes
    protected $appends = [
        'is_successful',
        'is_failed',
        'is_refunded',
    ];

    public function getIsSuccessfulAttribute(): bool
    {
        return in_array($this->status, ['authorized', 'captured']);
    }

    public function getIsFailedAttribute(): bool
    {
        return $this->status === 'failed';
    }

    public function getIsRefundedAttribute(): bool
    {
        return $this->status === 'refunded';
    }

    // Business Logic Methods
    public function canBeRefunded(): bool
    {
        return $this->status === 'captured' && $this->amount > 0;
    }

    public function canBeCaptured(): bool
    {
        return $this->status === 'authorized';
    }

    public function markAsAuthorized(): void
    {
        $this->update([
            'status' => 'authorized',
            'processed_at' => now(),
        ]);
    }

    public function markAsCaptured(): void
    {
        $this->update([
            'status' => 'captured',
            'processed_at' => now(),
        ]);
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'processed_at' => now(),
        ]);
    }

    public function markAsRefunded(): void
    {
        $this->update([
            'status' => 'refunded',
            'processed_at' => now(),
        ]);
    }

    // Scopes
    public function scopeSuccessful($query)
    {
        return $query->whereIn('status', ['authorized', 'captured']);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeByGateway($query, string $gateway)
    {
        return $query->where('gateway', $gateway);
    }

    public function scopeForCheckoutSession($query, int $sessionId)
    {
        return $query->where('checkout_session_id', $sessionId);
    }

    // Relationships
    public function checkoutSession()
    {
        return $this->belongsTo(CheckoutSession::class);
    }

    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function arInvoice()
    {
        return $this->belongsTo(ARInvoice::class);
    }

    // Factory
    protected static function newFactory()
    {
        return \Modules\Ecommerce\Database\Factories\PaymentTransactionFactory::new();
    }
}
