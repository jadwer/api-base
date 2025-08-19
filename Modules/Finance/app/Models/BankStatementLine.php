<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Traits\HasPermissions;


class BankStatementLine extends Model
{
    use HasFactory, HasPermissions;

    protected $table = 'bank_statement_lines';
    
    protected $fillable = [
        'bank_statement_id', 'txn_date', 'amount', 'counterparty', 'reference', 'fitid', 'status'
    ];

    protected $casts = [
                'txn_date' => 'date',
        'amount' => 'decimal:2'
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }



    // Factory
    protected static function newFactory()
    {
        return \Modules\Finance\Database\Factories\BankStatementLineFactory::new();
    }
}
