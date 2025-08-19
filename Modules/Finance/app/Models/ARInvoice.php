<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Traits\HasPermissions;
use Modules\Contacts\Models\Contact;
use Illuminate\Validation\ValidationException;

class ARInvoice extends Model
{
    use HasFactory, HasPermissions;

    protected $table = 'ar_invoices';
    
    protected $fillable = [
        'contact_id', 'invoice_number', 'invoice_date', 'due_date', 'currency', 'exchange_rate', 'subtotal', 'tax_total', 'total', 'status'
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'exchange_rate' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'total' => 'decimal:2'
    ];

    protected $attributes = [
        'status' => 'draft',
        'currency' => 'MXN',
        'subtotal' => 0.00,
        'tax_total' => 0.00
    ];

    // Estados F1: draft -> posted -> partially_paid/paid
    const STATUS_DRAFT = 'draft';
    const STATUS_POSTED = 'posted';
    const STATUS_PARTIALLY_PAID = 'partially_paid';
    const STATUS_PAID = 'paid';

    protected static function boot()
    {
        parent::boot();
        
        // Validar unicidad (contact_id, invoice_number)
        static::saving(function ($invoice) {
            $existing = static::where('contact_id', $invoice->contact_id)
                ->where('invoice_number', $invoice->invoice_number)
                ->where('id', '!=', $invoice->id)
                ->exists();
                
            if ($existing) {
                throw ValidationException::withMessages([
                    'invoice_number' => 'Invoice number must be unique per customer.'
                ]);
            }
        });
    }

    // Business Methods
    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isPosted(): bool
    {
        return in_array($this->status, [
            self::STATUS_POSTED, 
            self::STATUS_PARTIALLY_PAID, 
            self::STATUS_PAID
        ]);
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopePosted($query)
    {
        return $query->whereIn('status', [
            self::STATUS_POSTED, 
            self::STATUS_PARTIALLY_PAID, 
            self::STATUS_PAID
        ]);
    }

    // Relationships
    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function aRInvoiceLines()
    {
        return $this->hasMany(ARInvoiceLine::class);
    }

    public function aRInvoiceReceipts()
    {
        return $this->hasMany(ARInvoiceReceipt::class);
    }

    // Factory
    protected static function newFactory()
    {
        return \Modules\Finance\Database\Factories\ARInvoiceFactory::new();
    }
}
