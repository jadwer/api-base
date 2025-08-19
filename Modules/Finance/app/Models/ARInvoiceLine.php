<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Traits\HasPermissions;


class ARInvoiceLine extends Model
{
    use HasFactory, HasPermissions;

    protected $table = 'ar_invoice_lines';
    
    protected $fillable = [
        'ar_invoice_id', 'description', 'quantity', 'unit_price', 'discount', 'line_total'
    ];

    protected $casts = [
                'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'discount' => 'decimal:2',
        'line_total' => 'decimal:2'
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }



    // Factory
    protected static function newFactory()
    {
        return \Modules\Finance\Database\Factories\ARInvoiceLineFactory::new();
    }
}
