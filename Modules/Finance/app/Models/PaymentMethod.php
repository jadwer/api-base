<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Traits\HasPermissions;


/**
 * @property int $id
 * @property string $code Clave SAT de forma de pago (c_FormaPago)
 * @property string $name
 */
class PaymentMethod extends Model
{
    use HasFactory, HasPermissions;

    protected $table = 'payment_methods';
    
    protected $fillable = [
        'code', 'name', 'type', 'requires_reference', 'is_active'
    ];

    protected $casts = [
                'requires_reference' => 'boolean',
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
        return \Modules\Finance\Database\Factories\PaymentMethodFactory::new();
    }
}
