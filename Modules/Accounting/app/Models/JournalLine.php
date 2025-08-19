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
        'journal_entry_id', 'account_id', 'debit', 'credit', 'base_amount', 'cost_center_id', 'partner_id', 'memo'
    ];

    protected $casts = [
                'debit' => 'decimal:2',
        'credit' => 'decimal:2',
        'base_amount' => 'decimal:2'
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
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
