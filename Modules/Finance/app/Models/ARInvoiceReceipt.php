<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Traits\HasPermissions;


class ARInvoiceReceipt extends Model
{
    use HasFactory, HasPermissions;

    protected $table = 'ar_invoice_receipts';
    
    protected $fillable = [
        'ar_invoice_id', 'ar_receipt_id', 'amount_applied', 'applied_at', 'exchange_rate_at_apply'
    ];

    protected $casts = [
                'amount_applied' => 'decimal:2',
        'applied_at' => 'date',
        'exchange_rate_at_apply' => 'decimal:2'
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }



    // Factory
    protected static function newFactory()
    {
        return \Modules\Finance\Database\Factories\ARInvoiceReceiptFactory::new();
    }
}
