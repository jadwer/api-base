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
        'code', 'name', 'account_type', 'level', 'parent_id', 'currency', 'is_postable', 'status', 'metadata'
    ];

    protected $casts = [
        'is_postable' => 'boolean',
        'metadata' => 'array'
    ];

    protected $attributes = [
        'is_postable' => true,
        'status' => 'active'
    ];

    // Account Types for basic implementation
    const TYPE_ASSET = 'asset';
    const TYPE_LIABILITY = 'liability';
    const TYPE_EQUITY = 'equity';
    const TYPE_REVENUE = 'revenue';
    const TYPE_EXPENSE = 'expense';

    // Business Methods
    public function isPostable(): bool
    {
        return $this->is_postable === true;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopePostable($query)
    {
        return $query->where('is_postable', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('account_type', $type);
    }

    // Relationships
    public function parent()
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Account::class, 'parent_id');
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
