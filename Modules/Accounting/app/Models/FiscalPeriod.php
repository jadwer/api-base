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
        'name', 'start_date', 'end_date', 'status', 'allow_backpost'
    ];

    protected $casts = [
                'start_date' => 'date',
        'end_date' => 'date',
        'allow_backpost' => 'boolean'
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }



    // Factory
    protected static function newFactory()
    {
        return \Modules\Accounting\Database\Factories\FiscalPeriodFactory::new();
    }
}
