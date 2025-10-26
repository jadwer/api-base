<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Traits\HasPermissions;


class AccountBalance extends Model
{
    use HasFactory, HasPermissions;

    protected $table = 'account_balances';
    
    protected $fillable = [
        'company_id', 'account_id', 'fiscal_year', 'fiscal_month', 'opening_balance', 'period_debits', 'period_credits', 'closing_balance', 'metadata'
    ];

    protected $casts = [
        'opening_balance' => 'float',
        'period_debits' => 'float',
        'period_credits' => 'float',
        'closing_balance' => 'float',
        'metadata' => 'array'
    ];

    // Factory
    protected static function newFactory()
    {
        return \Modules\Accounting\Database\Factories\AccountBalanceFactory::new();
    }
}
