<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Traits\HasPermissions;


class BankAccount extends Model
{
    use HasFactory, HasPermissions;

    protected $table = 'bank_accounts';
    
    protected $fillable = [
        'bank_name', 'account_number', 'clabe', 'currency', 'account_type', 'opening_balance', 'status'
    ];

    protected $casts = [
                'opening_balance' => 'decimal:2'
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }



    // Factory
    protected static function newFactory()
    {
        return \Modules\Finance\Database\Factories\BankAccountFactory::new();
    }
}
