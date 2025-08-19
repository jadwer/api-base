<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Traits\HasPermissions;
use Modules\Contacts\Models\Contact;

class ARReceipt extends Model
{
    use HasFactory, HasPermissions;

    protected $table = 'ar_receipts';
    
    protected $fillable = [
        'receipt_number', 'ar_invoice_id', 'contact_id', 'receipt_date', 
        'payment_method', 'currency', 'amount', 'applied_amount', 
        'bank_account_id', 'status'
    ];

    protected $casts = [
        'receipt_date' => 'date',
        'amount' => 'decimal:2',
        'applied_amount' => 'decimal:2'
    ];

    protected $attributes = [
        'status' => 'draft',
        'currency' => 'MXN',
        'payment_method' => 'transfer'
    ];

    // Estados F1: draft -> posted
    const STATUS_DRAFT = 'draft';
    const STATUS_POSTED = 'posted';

    // Business Methods
    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isPosted(): bool
    {
        return $this->status === self::STATUS_POSTED;
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
        return $query->where('status', self::STATUS_POSTED);
    }

    // Relationships
    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function arInvoice()
    {
        return $this->belongsTo(ARInvoice::class);
    }

    public function aRInvoiceReceipts()
    {
        return $this->hasMany(ARInvoiceReceipt::class);
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    // Factory
    protected static function newFactory()
    {
        return \Modules\Finance\Database\Factories\ARReceiptFactory::new();
    }
}
