<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Traits\HasPermissions;
use Modules\Accounting\Models\JournalEntry;
// use Modules\Purchase\Models\Supplier; // TODO: Uncomment when Supplier model is implemented

class APInvoice extends Model
{
    use HasFactory, HasPermissions;

    protected $table = 'ap_invoices';
    
    protected $fillable = [
        'invoice_number', 'invoice_date', 'due_date', 'supplier_id', 'currency', 'subtotal', 'tax_amount', 'total_amount', 'paid_amount', 'status', 'journal_entry_id', 'notes', 'metadata', 'is_active'
    ];

    protected $casts = [
                'invoice_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'float',
        'tax_amount' => 'float',
        'total_amount' => 'float',
        'paid_amount' => 'float',
        'metadata' => 'array',
        'is_active' => 'boolean'
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // TODO: Uncomment when Supplier model is implemented
    // public function supplier()
    // {
    //     return $this->belongsTo(Supplier::class);
    // }

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class);
    }

    // Factory
    protected static function newFactory()
    {
        return \Modules\Finance\Database\Factories\APInvoiceFactory::new();
    }
}
