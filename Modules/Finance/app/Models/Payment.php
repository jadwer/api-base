<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Traits\HasPermissions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Modules\Accounting\Models\JournalEntry;
use Modules\Contacts\Models\Contact;

class Payment extends Model
{
    use HasFactory, HasPermissions, LogsActivity;

    /**
     * Activity Log Configuration
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'payment_number', 'status', 'payment_date', 'contact_id',
                'amount', 'applied_amount', 'unapplied_amount', 'payment_method_id'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected $table = 'payments';
    
    protected $fillable = [
        'payment_number', 'payment_date', 'contact_id', 'bank_account_id', 'payment_method_id', 'amount', 'currency', 'applied_amount', 'unapplied_amount', 'status', 'journal_entry_id', 'reference', 'notes', 'metadata', 'is_active'
    ];

    protected $casts = [
                'payment_date' => 'date',
        'amount' => 'float',
        'applied_amount' => 'float',
        'unapplied_amount' => 'float',
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

    // Legacy alias for backward compatibility
    public function customer()
    {
        return $this->contact();
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
