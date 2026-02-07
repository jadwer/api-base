<?php

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Contacts\Models\Contact;
use Modules\Ecommerce\Models\ShoppingCart;
use Modules\Purchase\Models\PurchaseOrder;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * @property int $id
 * @property int $contact_id
 * @property int|null $shopping_cart_id
 * @property int|null $sales_order_id
 * @property int|null $purchase_order_id
 * @property string $quote_number
 * @property string $status
 * @property \Carbon\Carbon $quote_date
 * @property \Carbon\Carbon|null $valid_until
 * @property string|null $estimated_eta
 * @property float $subtotal_amount
 * @property float $discount_amount
 * @property float $tax_amount
 * @property float $total_amount
 * @property string $currency
 * @property string|null $notes
 * @property string|null $internal_notes
 * @property string|null $terms_and_conditions
 * @property array|null $shipping_address
 * @property array|null $billing_address
 * @property array|null $metadata
 * @property \Carbon\Carbon|null $sent_at
 * @property \Carbon\Carbon|null $accepted_at
 * @property \Carbon\Carbon|null $rejected_at
 * @property \Carbon\Carbon|null $converted_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Quote extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'contact_id', 'shopping_cart_id', 'sales_order_id', 'purchase_order_id',
        'quote_number', 'status', 'quote_date', 'valid_until', 'estimated_eta',
        'subtotal_amount', 'discount_amount', 'tax_amount', 'total_amount', 'currency',
        'notes', 'internal_notes', 'terms_and_conditions',
        'shipping_address', 'billing_address', 'metadata',
        'sent_at', 'accepted_at', 'rejected_at', 'converted_at',
    ];

    protected $casts = [
        'id' => 'integer',
        'contact_id' => 'integer',
        'shopping_cart_id' => 'integer',
        'sales_order_id' => 'integer',
        'purchase_order_id' => 'integer',
        'quote_date' => 'date',
        'valid_until' => 'date',
        'subtotal_amount' => 'float',
        'discount_amount' => 'float',
        'tax_amount' => 'float',
        'total_amount' => 'float',
        'shipping_address' => 'array',
        'billing_address' => 'array',
        'metadata' => 'array',
        'sent_at' => 'datetime',
        'accepted_at' => 'datetime',
        'rejected_at' => 'datetime',
        'converted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Activity Log Configuration
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'quote_number', 'status', 'quote_date', 'contact_id',
                'total_amount', 'discount_amount', 'estimated_eta'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // Scopes
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', ['draft', 'sent']);
    }

    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['cancelled', 'rejected', 'expired', 'converted']);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByContact($query, int $contactId)
    {
        return $query->where('contact_id', $contactId);
    }

    /**
     * Filter quotes by contact email (for customer portal)
     */
    public function scopeForContactEmail($query, string $email)
    {
        return $query->whereHas('contact', function ($q) use ($email) {
            $q->where('email', $email);
        });
    }

    public function scopeExpiringSoon($query, int $days = 7)
    {
        return $query->where('status', 'sent')
            ->whereNotNull('valid_until')
            ->where('valid_until', '>', now())              // Not already expired
            ->where('valid_until', '<=', now()->addDays($days)); // Within X days
    }

    // Relationships
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function shoppingCart(): BelongsTo
    {
        return $this->belongsTo(ShoppingCart::class);
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuoteItem::class);
    }

    // Computed Attributes
    public function getIsExpiredAttribute(): bool
    {
        return $this->valid_until && $this->valid_until->isPast();
    }

    public function getCanBeSentAttribute(): bool
    {
        return $this->status === 'draft' && $this->items()->count() > 0;
    }

    public function getCanBeConvertedAttribute(): bool
    {
        return in_array($this->status, ['accepted']) && !$this->sales_order_id;
    }

    public function getCanGeneratePurchaseOrderAttribute(): bool
    {
        // Can generate PO when quote is accepted but not yet has a PO
        return in_array($this->status, ['accepted', 'converted']) && !$this->purchase_order_id;
    }

    public function getHasPurchaseOrderAttribute(): bool
    {
        return !is_null($this->purchase_order_id);
    }

    public function getItemsCountAttribute(): int
    {
        return $this->items()->count();
    }

    public function getTotalQuantityAttribute(): float
    {
        return $this->items()->sum('quantity');
    }

    // Business Logic Methods
    public function recalculateTotals(): void
    {
        // Calculate subtotal as sum of (quantity * quoted_price) for all items
        $subtotalBeforeDiscount = $this->items()
            ->selectRaw('SUM(quantity * quoted_price) as subtotal')
            ->value('subtotal') ?? 0;

        $discountAmount = $this->items()->sum('discount_amount') ?? 0;
        $taxAmount = $this->items()->sum('tax_amount') ?? 0;

        // Subtotal after discount
        $subtotalAfterDiscount = $subtotalBeforeDiscount - $discountAmount;

        $this->update([
            'subtotal_amount' => $subtotalAfterDiscount, // Subtotal after discounts, before tax
            'discount_amount' => $discountAmount,
            'tax_amount' => $taxAmount,
            'total_amount' => $subtotalAfterDiscount + $taxAmount,
        ]);
    }

    public function markAsSent(): bool
    {
        if ($this->status !== 'draft') {
            return false;
        }

        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        return true;
    }

    public function markAsAccepted(): bool
    {
        if ($this->status !== 'sent') {
            return false;
        }

        $this->update([
            'status' => 'accepted',
            'accepted_at' => now(),
        ]);

        return true;
    }

    public function markAsRejected(): bool
    {
        if (!in_array($this->status, ['sent', 'draft'])) {
            return false;
        }

        $this->update([
            'status' => 'rejected',
            'rejected_at' => now(),
        ]);

        return true;
    }

    public function markAsExpired(): bool
    {
        if ($this->status !== 'sent') {
            return false;
        }

        $this->update([
            'status' => 'expired',
        ]);

        return true;
    }

    public function cancel(): bool
    {
        if (in_array($this->status, ['converted', 'cancelled'])) {
            return false;
        }

        $this->update([
            'status' => 'cancelled',
        ]);

        return true;
    }

    /**
     * Generate unique quote number using FolioSequence
     *
     * Format is configurable via folio_sequences table.
     * Default: COT-26000001 (prefix + year short + sequence)
     */
    public static function generateQuoteNumber(): string
    {
        return FolioSequence::getNextFolio('quote');
    }

    // Factory
    protected static function newFactory()
    {
        return \Modules\Sales\Database\Factories\QuoteFactory::new();
    }
}
