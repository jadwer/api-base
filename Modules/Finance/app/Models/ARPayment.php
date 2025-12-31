<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Traits\HasPermissions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Modules\Accounting\Models\JournalEntry;
use Modules\Accounting\Models\FiscalPeriod;
use Modules\Contacts\Models\Contact;

class ARPayment extends Model
{
    use HasFactory, HasPermissions, LogsActivity;

    /**
     * Activity Log Configuration
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'payment_number', 'status', 'payment_date', 'payment_amount',
                'contact_id', 'applied_amount', 'voided_at'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected $table = 'ar_payments';

    protected $fillable = [
        'payment_number',
        'payment_date',
        'contact_id',
        'fiscal_period_id',
        'bank_account_id',
        'payment_method',
        'currency',
        'payment_amount',
        'applied_amount',
        'unapplied_amount',
        'status',
        'reference',
        'notes',
        'journal_entry_id',
        'voided_at',
        'voided_by_id',
        'void_reason',
        'metadata'
    ];

    protected $casts = [
        'payment_date' => 'date',
        'payment_amount' => 'float',
        'applied_amount' => 'float',
        'unapplied_amount' => 'float',
        'voided_at' => 'datetime',
        'metadata' => 'array'
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', '!=', 'voided');
    }

    public function scopePosted($query)
    {
        return $query->where('status', 'posted');
    }

    // Relationships
    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    // Legacy alias
    public function customer()
    {
        return $this->contact();
    }

    public function fiscalPeriod()
    {
        return $this->belongsTo(FiscalPeriod::class);
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function voidedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'voided_by_id');
    }

    public function applications()
    {
        return $this->hasMany(PaymentApplication::class, 'ar_payment_id');
    }

    // Factory
    protected static function newFactory()
    {
        return \Modules\Finance\Database\Factories\ARPaymentFactory::new();
    }
}
