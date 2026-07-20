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
 * @property string $order_type
 * @property string|null $customer_po_number
 * @property string|null $customer_po_path
 * @property string|null $payment_method
 * @property int|null $credit_days
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
                'total_amount', 'discount_total', 'invoicing_status', 'financial_status',
                'payment_status',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected $fillable = [
        'contact_id', 'assigned_to', 'quote_id', 'order_number', 'status', 'order_date',
        'approved_at', 'delivered_at', 'subtotal', 'tax_amount', 'total_amount',
        'discount_total', 'currency', 'exchange_rate_used',
        'order_type', 'customer_po_number', 'customer_po_path', 'payment_method', 'credit_days',
        'notes', 'metadata', 'shipping_address', 'billing_address',
        'ar_invoice_id', 'invoicing_status', 'financial_status', 'invoicing_notes',
        'order_source', 'checkout_session_id',
        'payment_status', 'paid_at',
    ];

    protected $casts = [
        'id' => 'integer',
        'contact_id' => 'integer',
        'assigned_to' => 'integer',
        'quote_id' => 'integer',
        'order_date' => 'date',
        'approved_at' => 'datetime',
        'delivered_at' => 'datetime',
        'subtotal' => 'float',
        'tax_amount' => 'float',
        'total_amount' => 'float',
        'discount_total' => 'float',
        'exchange_rate_used' => 'float',
        'order_type' => 'string',
        'customer_po_number' => 'string',
        'customer_po_path' => 'string',
        'payment_method' => 'string',
        'credit_days' => 'integer',
        'metadata' => 'array',
        'shipping_address' => 'array',
        'billing_address' => 'array',
        'ar_invoice_id' => 'integer',
        'invoicing_status' => 'string',
        'financial_status' => 'string',
        'payment_status' => 'string',
        'paid_at' => 'datetime',
        'order_source' => 'string',
        'checkout_session_id' => 'integer',
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

    /**
     * Nota cliente #11: pedidos "por surtir".
     *
     * fulfillment_status es un accessor calculado (no columna), asi que no se
     * puede filtrar por el en SQL. Aproximamos "por surtir" por el status de la
     * orden: abiertas = todas menos las cerradas (delivered, completed,
     * cancelled, returned, refunded). El enum de sales_orders admite 10 estados
     * (draft, pending, confirmed, processing, shipped, delivered, completed,
     * cancelled, returned, refunded).
     *
     * Se acepta valor booleano para poder exponerlo como filtro de un solo
     * parametro (filter[pending_fulfillment]=1). Cualquier valor "falsy"
     * (0, false, "false", "") no aplica el filtro.
     */
    public function scopePendingFulfillment($query, $value = true)
    {
        $closedStatuses = ['delivered', 'completed', 'cancelled', 'returned', 'refunded'];

        $enabled = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        $enabled = $enabled ?? true;

        if (!$enabled) {
            return $query;
        }

        return $query->whereNotIn('status', $closedStatuses);
    }

    /**
     * Filter orders by contact email (for customer portal)
     */
    public function scopeForContactEmail($query, string $email)
    {
        return $query->whereHas('contact', function ($q) use ($email) {
            $q->where('email', $email);
        });
    }

    /**
     * Paquete A (auditoria 10 pasos): buscador del listado. El FE ya mandaba
     * filter[search] pero el Schema no lo declaraba y el backend respondia 400.
     */
    public function scopeSearch($query, string $term)
    {
        $term = trim($term);

        return $query->where(function ($q) use ($term) {
            $q->where('order_number', 'like', "%{$term}%")
                ->orWhere('customer_po_number', 'like', "%{$term}%")
                ->orWhereHas('contact', function ($c) use ($term) {
                    $c->where('name', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%");
                });
        });
    }

    // Relaciones
    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class, 'quote_id');
    }

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
