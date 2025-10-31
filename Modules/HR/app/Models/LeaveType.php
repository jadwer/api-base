<?php

namespace Modules\HR\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\HR\Database\Factories\LeaveTypeFactory;

class LeaveType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'days_allowed',
        'requires_approval',
        'paid',
        'active',
    ];

    protected $casts = [
        'days_allowed' => 'integer',
        'requires_approval' => 'boolean',
        'paid' => 'boolean',
        'active' => 'boolean',
    ];

    /**
     * Leaves using this type.
     */
    public function leaves(): HasMany
    {
        return $this->hasMany(Leave::class);
    }

    /**
     * Scope to filter active leave types.
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope to filter inactive leave types.
     */
    public function scopeInactive($query)
    {
        return $query->where('active', false);
    }

    /**
     * Scope to filter paid leave types.
     */
    public function scopePaid($query)
    {
        return $query->where('paid', true);
    }

    /**
     * Scope to filter unpaid leave types.
     */
    public function scopeUnpaid($query)
    {
        return $query->where('paid', false);
    }

    /**
     * Scope to filter leave types requiring approval.
     */
    public function scopeRequiresApproval($query)
    {
        return $query->where('requires_approval', true);
    }

    /**
     * Scope to filter by code.
     */
    public function scopeByCode($query, string $code)
    {
        return $query->where('code', $code);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return LeaveTypeFactory::new();
    }
}
