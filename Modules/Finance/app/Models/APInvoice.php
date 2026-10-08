<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Traits\HasPermissions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Modules\Accounting\Models\JournalEntry;
use Modules\Contacts\Models\Contact;
use Modules\Purchase\Models\PurchaseOrder;

class APInvoice extends Model
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
                'contact_id', 'total_amount', 'paid_amount', 'reconciliation_status'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected $table = 'ap_invoices';

    protected $fillable = [
        'invoice_number', 'invoice_date', 'due_date', 'contact_id', 'purchase_order_id', 'currency', 'subtotal', 'tax_amount', 'total_amount', 'paid_amount', 'status', 'journal_entry_id', 'fiscal_period_id', 'notes', 'metadata', 'is_active',
        'reconciliation_status', 'reconciled_at', 'reconciled_by', 'reconciliation_notes', 'discrepancies',
        // Void/Replacement fields
        'voided_at', 'voided_by_id', 'void_reason', 'replaces_invoice_id'
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
        'discrepancies' => 'array',
        // Void fields
        'voided_at' => 'datetime',
        'voided_by_id' => 'integer',
        'replaces_invoice_id' => 'integer'
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Paquete A (auditoria 10 pasos): buscador del listado. El FE ya mandaba
     * filter[search] pero el Schema no lo declaraba y el backend respondia 400.
     */
    public function scopeSearch($query, string $term)
    {
        $term = trim($term);

        return $query->where(function ($q) use ($term) {
            $q->where('invoice_number', 'like', "%{$term}%")
                ->orWhereHas('contact', function ($c) use ($term) {
                    $c->where('name', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%");
                });
        });
    }

    /** @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Contact, $this> */
    public function contact(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }

    /** @return \Illuminate\Database\Eloquent\Relations\BelongsTo<PurchaseOrder, $this> */
    public function purchaseOrder(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    /** @return \Illuminate\Database\Eloquent\Relations\BelongsTo<JournalEntry, $this> */
    public function journalEntry(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    /** @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, $this> */
    public function reconciledBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'reconciled_by');
    }

    /** @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, $this> */
    public function voidedBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'voided_by_id');
    }

    /** @return \Illuminate\Database\Eloquent\Relations\BelongsTo<APInvoice, $this> */
    public function replacesInvoice(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(APInvoice::class, 'replaces_invoice_id');
    }

    /** @return \Illuminate\Database\Eloquent\Relations\HasOne<APInvoice, $this> */
    public function replacementInvoice(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(APInvoice::class, 'replaces_invoice_id');
    }

    // Legacy alias for backward compatibility
    /** @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Contact, $this> */
    public function supplier(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->contact();
    }

    // Factory
    protected static function newFactory()
    {
        return \Modules\Finance\Database\Factories\APInvoiceFactory::new();
    }
}
