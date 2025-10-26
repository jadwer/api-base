<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Traits\HasPermissions;


class PaymentApplication extends Model
{
    use HasFactory, HasPermissions;

    protected $table = 'payment_applications';
    
    protected $fillable = [
        'payment_id', 'ar_invoice_id', 'amount', 'application_date', 'notes', 'metadata', 'is_active'
    ];

    protected $casts = [
        'amount' => 'float',
        'application_date' => 'date',
        'metadata' => 'array',
        'is_active' => 'boolean'
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }


    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function aRInvoice()
    {
        return $this->belongsTo(ARInvoice::class);
    }

    // Factory
    protected static function newFactory()
    {
        return \Modules\Finance\Database\Factories\PaymentApplicationFactory::new();
    }
}
