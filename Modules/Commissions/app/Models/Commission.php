<?php

namespace Modules\Commissions\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Contacts\Models\Contact;
use Modules\Finance\Models\ARInvoice;
use Modules\Sales\Models\SalesOrder;
use Modules\User\Models\User;

/**
 * Commission
 *
 * One row per (sales_order_id, user_id). Lifecycle:
 * pending -> earned (AR invoice fully paid) -> paid (payout)
 * pending/earned -> cancelled (order cancelled)
 *
 * commission_pct is frozen at row creation time: changing the salesperson's
 * or contact's percentage afterwards does NOT recalculate existing rows.
 */
class Commission extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_EARNED = 'earned';
    public const STATUS_PAID = 'paid';
    public const STATUS_CANCELLED = 'cancelled';

    protected $table = 'commissions';

    protected $fillable = [
        'sales_order_id',
        'ar_invoice_id',
        'user_id',
        'contact_id',
        'base_amount',
        'commission_pct',
        'commission_amount',
        'status',
        'earned_at',
        'paid_at',
        'payment_reference',
    ];

    protected $casts = [
        'sales_order_id' => 'integer',
        'ar_invoice_id' => 'integer',
        'user_id' => 'integer',
        'contact_id' => 'integer',
        'base_amount' => 'float',
        'commission_pct' => 'float',
        'commission_amount' => 'float',
        'earned_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    // Relationships

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function arInvoice(): BelongsTo
    {
        return $this->belongsTo(ARInvoice::class, 'ar_invoice_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    // Scopes

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeEarnedBetween($query, string $start, string $end)
    {
        return $query->whereBetween('earned_at', [$start, $end]);
    }

    // Helpers

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isEarned(): bool
    {
        return $this->status === self::STATUS_EARNED;
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    // Factory

    protected static function newFactory()
    {
        return \Modules\Commissions\Database\Factories\CommissionFactory::new();
    }
}
