<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Traits\HasPermissions;
use Modules\Accounting\Models\JournalEntry;
use Modules\Contacts\Models\Contact;
use Modules\Purchase\Models\PurchaseOrder;

class APInvoice extends Model
{
    use HasFactory, HasPermissions;

    protected $table = 'ap_invoices';

    protected $fillable = [
        'invoice_number', 'invoice_date', 'due_date', 'contact_id', 'purchase_order_id', 'currency', 'subtotal', 'tax_amount', 'total_amount', 'paid_amount', 'status', 'journal_entry_id', 'fiscal_period_id', 'notes', 'metadata', 'is_active',
        'reconciliation_status', 'reconciled_at', 'reconciled_by', 'reconciliation_notes', 'discrepancies'
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'float',
        'tax_amount' => 'float',
        'total_amount' => 'float',
        'paid_amount' => 'float',
        'metadata' => 'array',
        'is_active' => 'boolean',
        'reconciled_at' => 'datetime',
        'discrepancies' => 'array'
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function reconciledBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'reconciled_by');
    }

    // Legacy alias for backward compatibility
    public function supplier()
    {
        return $this->contact();
    }

    // Factory
    protected static function newFactory()
    {
        return \Modules\Finance\Database\Factories\APInvoiceFactory::new();
    }
}
