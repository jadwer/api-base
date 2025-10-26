<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Traits\HasPermissions;
use Modules\Accounting\Models\Account;

class BankAccount extends Model
{
    use HasFactory, HasPermissions;

    protected $table = 'bank_accounts';
    
    protected $fillable = [
        'account_number', 'account_name', 'bank_name', 'currency', 'gl_account_id', 'current_balance', 'opening_balance', 'status', 'metadata', 'is_active'
    ];

    protected $casts = [
                'current_balance' => 'float',
        'opening_balance' => 'float',
        'metadata' => 'array',
        'is_active' => 'boolean'
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }


    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    // Factory
    protected static function newFactory()
    {
        return \Modules\Finance\Database\Factories\BankAccountFactory::new();
    }
}
