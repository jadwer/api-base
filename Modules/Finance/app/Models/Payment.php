<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Traits\HasPermissions;
use Modules\Accounting\Models\JournalEntry;
use Modules\Sales\Models\Customer;

class Payment extends Model
{
    use HasFactory, HasPermissions;

    protected $table = 'payments';
    
    protected $fillable = [
        'payment_number', 'payment_date', 'customer_id', 'bank_account_id', 'payment_method_id', 'amount', 'currency', 'applied_amount', 'unapplied_amount', 'status', 'journal_entry_id', 'reference', 'notes', 'metadata', 'is_active'
    ];

    protected $casts = [
                'payment_date' => 'date',
        'amount' => 'decimal:2',
        'applied_amount' => 'decimal:2',
        'unapplied_amount' => 'decimal:2',
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
        return $this->belongsTo(Customer::class);
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
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
        return \Modules\Finance\Database\Factories\PaymentFactory::new();
    }
}
