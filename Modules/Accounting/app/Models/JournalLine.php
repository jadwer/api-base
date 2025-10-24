<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Traits\HasPermissions;


class JournalLine extends Model
{
    use HasFactory, HasPermissions;

    protected $table = 'journal_lines';
    
    protected $fillable = [
        'journal_entry_id', 'account_id', 'contact_id', 'debit', 'credit', 'description', 'reference', 'metadata'
    ];

    protected $casts = [
                'debit' => 'decimal:2',
        'credit' => 'decimal:2',
        'metadata' => 'array'
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    // Factory
    protected static function newFactory()
    {
        return \Modules\Accounting\Database\Factories\JournalLineFactory::new();
    }
}
