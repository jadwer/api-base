<?php

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Contacts\Models\Contact;

/**
 * @property int $id
 * @property int $contact_id
 * @property string $order_number
 * @property string $status
 * @property \Carbon\Carbon $order_date
 * @property \Carbon\Carbon|null $approved_at
 * @property \Carbon\Carbon|null $delivered_at
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
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'id' => 'integer',
        'contact_id' => 'integer',
        'order_date' => 'date',
        'approved_at' => 'datetime',
        'delivered_at' => 'datetime',
        'total_amount' => 'float',
        'discount_total' => 'float',
        'metadata' => 'array',
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

    public function items(): HasMany
    {
        return $this->hasMany(SalesOrderItem::class);
    }

    // Factory
    protected static function newFactory()
    {
        return \Modules\Sales\Database\Factories\SalesOrderFactory::new();
    }
}
