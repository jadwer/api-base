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
        'debit' => 'float',
        'credit' => 'float',
        'metadata' => 'array'
    ];

    /** @return \Illuminate\Database\Eloquent\Relations\BelongsTo<JournalEntry, $this> */
    public function journalEntry(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    /** @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Account, $this> */
    public function account(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    // Factory
    protected static function newFactory()
    {
        return \Modules\Accounting\Database\Factories\JournalLineFactory::new();
    }
}
