<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Traits\HasPermissions;


class FiscalPeriod extends Model
{
    use HasFactory, HasPermissions;

    protected $table = 'fiscal_periods';
    
    protected $fillable = [
        'name', 'year', 'month', 'start_date', 'end_date', 'status', 'closed_at', 'closed_by_id', 'closing_entry_id', 'metadata'
    ];

    protected $casts = [
                'start_date' => 'date',
        'end_date' => 'date',
        'closed_at' => 'datetime',
        'metadata' => 'array'
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'open');
    }

    public function journalEntries()
    {
        return $this->hasMany(JournalEntry::class);
    }

    // Factory
    protected static function newFactory()
    {
        return \Modules\Accounting\Database\Factories\FiscalPeriodFactory::new();
    }
}
