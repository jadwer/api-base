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
        'company_id', 'currency', 'source', 'scope', 'max_age_days', 'tolerance_percentage', 'require_approval_over', 'is_active', 'metadata'
    ];

    protected $casts = [
                'tolerance_percentage' => 'float',
        'require_approval_over' => 'float',
        'is_active' => 'boolean',
        'metadata' => 'array'
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
