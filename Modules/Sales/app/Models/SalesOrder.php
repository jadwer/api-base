<?php

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Contacts\Models\Contact;
use Modules\User\Models\User;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * @property int $id
 * @property int $contact_id
 * @property string $order_number
 * @property string $status
 * @property \Carbon\Carbon $order_date
 * @property \Carbon\Carbon|null $approved_at
 * @property \Carbon\Carbon|null $delivered_at
 * @property float $subtotal
 * @property float $tax_amount
 * @property float $total_amount
 * @property float|null $discount_total
 * @property string|null $notes
 * @property array|null $metadata
 * @property int|null $ar_invoice_id
 * @property string $invoicing_status
 * @property string $financial_status
 * @property string|null $invoicing_notes
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class SalesOrder extends Model
{
    use HasFactory, LogsActivity;

    /**
     * Activity Log Configuration
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'order_number', 'status', 'order_date', 'contact_id',
                'total_amount', 'discount_total', 'invoicing_status', 'financial_status'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected $guarded = [];

    protected $casts = [
        'id' => 'integer',
        'contact_id' => 'integer',
        'assigned_to' => 'integer',
        'order_date' => 'date',
        'approved_at' => 'datetime',
        'delivered_at' => 'datetime',
        'subtotal' => 'float',
        'tax_amount' => 'float',
        'total_amount' => 'float',
        'discount_total' => 'float',
        'metadata' => 'array',
        'shipping_address' => 'array',
        'billing_address' => 'array',
        'ar_invoice_id' => 'integer',
        'invoicing_status' => 'string',
        'financial_status' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Scopes útiles
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['cancelled']);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByContact($query, int $contactId)
    {
        return $query->where('contact_id', $contactId);
    }

    // Relaciones
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }
    
    // Backward compatibility alias
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }

    /**
     * Phase 13: User/Employee assigned to this order (seller/salesperson).
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesOrderItem::class);
    }

    public function arInvoices(): HasMany
    {
        return $this->hasMany(\Modules\Finance\Models\ARInvoice::class, 'sales_order_id');
    }

    /**
     * SA-M001: Shipments for this order.
     */
    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    /**
     * SA-M002: Backorders for this order.
     */
    public function backorders(): HasMany
    {
        return $this->hasMany(Backorder::class);
    }

    /**
     * SA-M006: Remissions (delivery notes) for this order.
     */
    public function remissions(): HasMany
    {
        return $this->hasMany(Remission::class);
    }

    /**
     * SA-M001: Get fulfillment status based on item statuses.
     */
    public function getFulfillmentStatusAttribute(): string
    {
        $items = $this->items;

        if ($items->isEmpty()) {
            return 'pending';
        }

        $allDelivered = $items->every(fn($item) => $item->fulfillment_status === 'delivered');
        if ($allDelivered) {
            return 'delivered';
        }

        $allShipped = $items->every(fn($item) => in_array($item->fulfillment_status, ['shipped', 'delivered']));
        if ($allShipped) {
            return 'shipped';
        }

        $anyShipped = $items->contains(fn($item) => in_array($item->fulfillment_status, ['partially_shipped', 'shipped', 'delivered']));
        if ($anyShipped) {
            return 'partially_shipped';
        }

        return 'pending';
    }

    /**
     * SA-M001: Check if order is fully shipped.
     */
    public function isFullyShipped(): bool
    {
        return $this->items->every(fn($item) => $item->shipped_quantity >= $item->quantity);
    }

    /**
     * SA-M001: Check if order has any shipments.
     */
    public function hasShipments(): bool
    {
        return $this->shipments()->exists();
    }

    /**
     * SA-M001: Get remaining quantity to ship for all items.
     */
    public function getRemainingToShipAttribute(): float
    {
        return $this->items->sum(fn($item) => max(0, $item->quantity - $item->shipped_quantity));
    }

    // Factory
    protected static function newFactory()
    {
        return \Modules\Sales\Database\Factories\SalesOrderFactory::new();
    }
}
