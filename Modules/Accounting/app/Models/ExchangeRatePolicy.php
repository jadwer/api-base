<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Traits\HasPermissions;


class ExchangeRatePolicy extends Model
{
    use HasFactory, HasPermissions;

    protected $table = 'exchange_rate_policies';
    
    protected $fillable = [
        'company_id', 'currency', 'source', 'scope', 'max_age_days', 'tolerance_percentage', 'require_approval_over', 'is_active'
    ];

    protected $casts = [
                'tolerance_percentage' => 'decimal:2',
        'require_approval_over' => 'decimal:2',
        'is_active' => 'boolean'
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }



    // Factory
    protected static function newFactory()
    {
        return \Modules\Accounting\Database\Factories\ExchangeRatePolicyFactory::new();
    }
}
