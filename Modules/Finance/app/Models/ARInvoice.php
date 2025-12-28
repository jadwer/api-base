<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Traits\HasPermissions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Modules\Accounting\Models\JournalEntry;
use Modules\Contacts\Models\Contact;
use Modules\Sales\Models\SalesOrder;

class ARInvoice extends Model
{
    use HasFactory, HasPermissions, LogsActivity;

    /**
     * Activity Log Configuration
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'invoice_number', 'status', 'invoice_date', 'due_date',
                'contact_id', 'total_amount', 'paid_amount', 'sales_order_id'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected $table = 'ar_invoices';

    protected $fillable = [
        'invoice_number', 'invoice_date', 'due_date', 'contact_id', 'sales_order_id', 'currency', 'subtotal', 'tax_amount', 'total_amount', 'paid_amount', 'paid_date', 'status', 'journal_entry_id', 'fiscal_period_id', 'notes', 'metadata', 'is_active'
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'paid_date' => 'date',
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


    public function contact()
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }

    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id');
    }

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function paymentApplications()
    {
        return $this->hasMany(PaymentApplication::class);
    }

    // Legacy alias for backward compatibility
    public function customer()
    {
        return $this->contact();
    }

    // Factory
    protected static function newFactory()
    {
        return \Modules\Finance\Database\Factories\ARInvoiceFactory::new();
    }
}
