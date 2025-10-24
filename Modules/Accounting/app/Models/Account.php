<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Traits\HasPermissions;


class Account extends Model
{
    use HasFactory, HasPermissions;

    protected $table = 'accounts';
    
    protected $fillable = [
        'company_id', 'code', 'name', 'account_type', 'nature', 'level', 'parent_id', 'currency', 'is_postable', 'is_cash_flow', 'status', 'metadata'
    ];

    protected $casts = [
                'is_postable' => 'boolean',
        'is_cash_flow' => 'boolean',
        'metadata' => 'array'
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function accounts()
    {
        return $this->hasMany(Account::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function journalLines()
    {
        return $this->hasMany(JournalLine::class);
    }

    // Factory
    protected static function newFactory()
    {
        return \Modules\Accounting\Database\Factories\AccountFactory::new();
    }
}
