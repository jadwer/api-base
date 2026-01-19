<?php

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Inventory\Models\Warehouse;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * SA-M006: Remission Model
 *
 * A remission (remisión) is a delivery document that accompanies merchandise.
 * It's NOT a fiscal document but serves as proof of delivery and inventory control.
 *
 * @property int $id
 * @property int $sales_order_id
 * @property int|null $shipment_id
 * @property int|null $warehouse_id
 * @property string $remission_number
 * @property string $status
 * @property \Carbon\Carbon $remission_date
 * @property \Carbon\Carbon|null $delivery_date
 * @property string|null $delivered_by
 * @property string|null $received_by
 * @property string|null $delivery_notes
 * @property array|null $shipping_address
 * @property string|null $pdf_path
 * @property \Carbon\Carbon|null $pdf_generated_at
 * @property string|null $internal_notes
 * @property array|null $metadata
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Remission extends Model
{
    use HasFactory, LogsActivity;

    protected $guarded = [];

    protected $casts = [
        'id' => 'integer',
        'sales_order_id' => 'integer',
        'shipment_id' => 'integer',
        'warehouse_id' => 'integer',
        'remission_date' => 'date',
        'delivery_date' => 'date',
        'delivered_at' => 'datetime',
        'printed_at' => 'datetime',
        'shipping_address' => 'array',
        'pdf_generated_at' => 'datetime',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'draft',
    ];

    /**
     * Activity Log Configuration
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'remission_number', 'status', 'remission_date',
                'delivery_date', 'delivered_by', 'received_by'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // Relationships

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(RemissionItem::class);
    }

    // Scopes

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopePrinted($query)
    {
        return $query->where('status', 'printed');
    }

    public function scopeDelivered($query)
    {
        return $query->where('status', 'delivered');
    }

    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['cancelled']);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeForOrder($query, int $orderId)
    {
        return $query->where('sales_order_id', $orderId);
    }

    // Business Logic

    /**
     * Generate unique remission number using FolioSequence
     */
    public static function generateRemissionNumber(): string
    {
        return FolioSequence::getNextFolio('remission');
    }

    /**
     * Check if remission can be edited
     */
    public function isEditable(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Check if remission can be printed/reprinted
     */
    public function canBePrinted(): bool
    {
        return in_array($this->status, ['draft', 'printed']) && $this->items()->exists();
    }

    /**
     * Check if remission can be marked as delivered
     */
    public function canBeDelivered(): bool
    {
        return in_array($this->status, ['printed']);
    }

    /**
     * Mark as printed (after PDF generation)
     */
    public function markAsPrinted(): bool
    {
        if (!$this->canBePrinted()) {
            return false;
        }

        $this->update([
            'status' => 'printed',
            'printed_at' => now(),
        ]);

        return true;
    }

    /**
     * Mark as delivered
     */
    public function markAsDelivered(?string $receivedBy = null, ?string $notes = null): bool
    {
        if (!$this->canBeDelivered()) {
            return false;
        }

        $updateData = [
            'status' => 'delivered',
            'delivery_date' => now(),
            'delivered_at' => now(),
        ];

        if ($receivedBy) {
            $updateData['received_by'] = $receivedBy;
        }

        if ($notes) {
            $updateData['delivery_notes'] = $this->delivery_notes
                ? $this->delivery_notes . "\n" . $notes
                : $notes;
        }

        $this->update($updateData);

        return true;
    }

    /**
     * Cancel remission
     */
    public function cancel(): bool
    {
        if ($this->status === 'delivered') {
            return false;
        }

        $this->update(['status' => 'cancelled']);

        return true;
    }

    // Computed Attributes

    public function getTotalItemsAttribute(): int
    {
        return $this->items->count();
    }

    public function getTotalQuantityAttribute(): float
    {
        return $this->items->sum('quantity');
    }

    /**
     * Get effective shipping address (remission address or fallback to order)
     */
    public function getEffectiveShippingAddressAttribute(): ?array
    {
        return $this->shipping_address ?? $this->salesOrder?->shipping_address;
    }

    // Factory

    protected static function newFactory()
    {
        return \Modules\Sales\Database\Factories\RemissionFactory::new();
    }

    // Boot

    protected static function booted()
    {
        static::creating(function (Remission $remission) {
            if (empty($remission->remission_number)) {
                $remission->remission_number = self::generateRemissionNumber();
            }
            if (empty($remission->remission_date)) {
                $remission->remission_date = now();
            }
        });
    }
}
