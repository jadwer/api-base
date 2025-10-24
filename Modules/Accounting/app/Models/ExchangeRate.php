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
        'from_currency', 'to_currency', 'rate', 'effective_date', 'source', 'status', 'metadata'
    ];

    protected $casts = [
                'rate' => 'decimal:2',
        'effective_date' => 'date',
        'metadata' => 'array'
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }



    // Factory
    protected static function newFactory()
    {
        return \Modules\Accounting\Database\Factories\ExchangeRateFactory::new();
    }
}
