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
     * Status constants
     */
    const STATUS_DRAFT = 'draft';
    const STATUS_APPROVED = 'approved';
    const STATUS_POSTED = 'posted';
    const STATUS_REVERSED = 'reversed';

    const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_APPROVED,
        self::STATUS_POSTED,
        self::STATUS_REVERSED,
    ];

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
        'journal_id', 'fiscal_period_id', 'number', 'date', 'accounting_date', 'reference', 'description', 'total_debit', 'total_credit', 'company_id', 'status', 'approved_at', 'approved_by_id', 'posted_at', 'posted_by_id',
        // Reversal fields
        'reversal_of_id', 'reversal_reason', 'is_reversal', 'reverses_entry_id',
        // Source tracking
        'source_type', 'source_id',
        'metadata'
    ];

    protected $casts = [
        'date' => 'date',
        'accounting_date' => 'date',
        'total_debit' => 'float',
        'total_credit' => 'float',
        'approved_at' => 'datetime',
        'posted_at' => 'datetime',
        'metadata' => 'array',
        'is_reversal' => 'boolean',
        'reverses_entry_id' => 'integer',
        'source_id' => 'integer'
    ];

    // Scopes
    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopePosted($query)
    {
        return $query->where('status', self::STATUS_POSTED);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [self::STATUS_DRAFT, self::STATUS_APPROVED, self::STATUS_POSTED]);
    }

    /**
     * Paquete A (auditoria 10 pasos): buscador del listado. El FE ya mandaba
     * filter[search] pero el Schema no lo declaraba y el backend respondia 400.
     */
    public function scopeSearch($query, string $term)
    {
        $term = trim($term);

        return $query->where(function ($q) use ($term) {
            $q->where('number', 'like', "%{$term}%")
                ->orWhere('reference', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%");
        });
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

    // Alias for journalLines (for test compatibility)
    public function lines()
    {
        return $this->journalLines();
    }

    /**
     * The entry that this entry reverses.
     */
    public function reversesEntry()
    {
        return $this->belongsTo(JournalEntry::class, 'reverses_entry_id');
    }

    /**
     * The entry that reverses this entry.
     */
    public function reversedByEntry()
    {
        return $this->hasOne(JournalEntry::class, 'reverses_entry_id');
    }

    /**
     * Legacy alias for backwards compatibility.
     */
    public function reversalOf()
    {
        return $this->belongsTo(JournalEntry::class, 'reversal_of_id');
    }

    /**
     * Get the source model (polymorphic).
     */
    public function source()
    {
        return $this->morphTo();
    }

    // Factory
    protected static function newFactory()
    {
        return \Modules\Accounting\Database\Factories\JournalEntryFactory::new();
    }
}
