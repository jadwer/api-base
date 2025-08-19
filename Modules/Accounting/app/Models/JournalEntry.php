<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Traits\HasPermissions;
use Illuminate\Validation\ValidationException;

class JournalEntry extends Model
{
    use HasFactory, HasPermissions;

    protected $table = 'journal_entries';
    
    protected $fillable = [
        'journal_id', 'period_id', 'number', 'date', 'currency', 'exchange_rate', 'reference', 'description', 'status', 'approved_by_id', 'posted_by_id', 'posted_at', 'reversal_of_id', 'source_type', 'source_id'
    ];

    protected $casts = [
        'date' => 'date',
        'exchange_rate' => 'decimal:2',
        'posted_at' => 'datetime'
    ];

    protected $attributes = [
        'status' => 'draft',
        'currency' => 'MXN'
    ];

    // Estados simples F1: draft -> posted
    const STATUS_DRAFT = 'draft';
    const STATUS_POSTED = 'posted';

    protected static function boot()
    {
        parent::boot();
        
        // Bloquear edición si está posteado
        static::updating(function ($entry) {
            if ($entry->getOriginal('status') === self::STATUS_POSTED) {
                throw ValidationException::withMessages([
                    'status' => 'Cannot modify posted journal entry.'
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
        return $this->status === self::STATUS_POSTED;
    }

    public function getTotalDebit(): float
    {
        return $this->journalLines()->sum('debit');
    }

    public function getTotalCredit(): float
    {
        return $this->journalLines()->sum('credit');
    }

    public function isBalanced(): bool
    {
        return abs($this->getTotalDebit() - $this->getTotalCredit()) < 0.01;
    }

    public function hasPostableAccounts(): bool
    {
        return $this->journalLines()
            ->join('accounts', 'journal_lines.account_id', '=', 'accounts.id')
            ->where('accounts.is_postable', false)
            ->count() === 0;
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

    public function journalLines()
    {
        return $this->hasMany(JournalLine::class);
    }

    public function journal()
    {
        return $this->belongsTo(Journal::class);
    }

    public function fiscalPeriod()
    {
        return $this->belongsTo(FiscalPeriod::class);
    }

    // Factory
    protected static function newFactory()
    {
        return \Modules\Accounting\Database\Factories\JournalEntryFactory::new();
    }
}
