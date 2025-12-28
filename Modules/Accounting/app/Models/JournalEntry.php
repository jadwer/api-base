<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Traits\HasPermissions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class JournalEntry extends Model
{
    use HasFactory, HasPermissions, LogsActivity;

    /**
     * Activity Log Configuration
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'number', 'status', 'date', 'total_debit', 'total_credit',
                'posted_at', 'approved_at', 'reversal_of_id'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected $table = 'journal_entries';
    
    protected $fillable = [
        'journal_id', 'fiscal_period_id', 'number', 'date', 'accounting_date', 'reference', 'description', 'total_debit', 'total_credit', 'company_id', 'status', 'approved_at', 'approved_by_id', 'posted_at', 'posted_by_id', 'reversal_of_id', 'reversal_reason', 'metadata'
    ];

    protected $casts = [
        'date' => 'date',
        'accounting_date' => 'date',
        'total_debit' => 'float',
        'total_credit' => 'float',
        'approved_at' => 'datetime',
        'posted_at' => 'datetime',
        'metadata' => 'array'
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'draft');
    }

    public function journal()
    {
        return $this->belongsTo(Journal::class);
    }

    public function fiscalPeriod()
    {
        return $this->belongsTo(FiscalPeriod::class);
    }

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function journalLines()
    {
        return $this->hasMany(JournalLine::class);
    }

    // Factory
    protected static function newFactory()
    {
        return \Modules\Accounting\Database\Factories\JournalEntryFactory::new();
    }
}
