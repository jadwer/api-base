<?php

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Sales\Database\Factories\SalesOrderFactory;

class SalesOrder extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'customer_id',
        'order_number',
        'status',
        'order_date',
        'approved_at',
        'delivered_at',
        'total_amount',
        'discount_total',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'order_date' => 'date',
        'approved_at' => 'datetime',
        'delivered_at' => 'datetime',
        'total_amount' => 'decimal:2',
        'discount_total' => 'decimal:2',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(SalesOrderItem::class);
    }

    protected static function newFactory()
    {
        return \Modules\Sales\Database\Factories\SalesOrderFactory::new();
    }
}
