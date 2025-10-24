<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Traits\HasPermissions;
use Modules\Accounting\Models\JournalEntry;
use Modules\Contacts\Models\Contact;

class ARInvoice extends Model
{
    use HasFactory, HasPermissions;

    protected $table = 'ar_invoices';
    
    protected $fillable = [
        'invoice_number', 'invoice_date', 'due_date', 'customer_id', 'currency', 'subtotal', 'tax_amount', 'total_amount', 'paid_amount', 'status', 'journal_entry_id', 'notes', 'metadata', 'is_active'
    ];

    protected $casts = [
                'invoice_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'metadata' => 'array',
        'is_active' => 'boolean'
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }


    public function customer()
    {
        return $this->belongsTo(Contact::class, 'customer_id');
    }

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function paymentApplications()
    {
        return $this->hasMany(PaymentApplication::class);
    }

    // Factory
    protected static function newFactory()
    {
        return \Modules\Finance\Database\Factories\ARInvoiceFactory::new();
    }
}
