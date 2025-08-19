<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Traits\HasPermissions;


class ExchangeRate extends Model
{
    use HasFactory, HasPermissions;

    protected $table = 'exchange_rates';
    
    protected $fillable = [
        'base_currency', 'quote_currency', 'rate_date', 'rate'
    ];

    protected $casts = [
                'rate_date' => 'date',
        'rate' => 'decimal:2'
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }



    // Factory
    protected static function newFactory()
    {
        return \Modules\Accounting\Database\Factories\ExchangeRateFactory::new();
    }
}
