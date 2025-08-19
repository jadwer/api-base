<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Traits\HasPermissions;


class BankStatement extends Model
{
    use HasFactory, HasPermissions;

    protected $table = 'bank_statements';
    
    protected $fillable = [
        'bank_account_id', 'statement_date', 'import_source'
    ];

    protected $casts = [
                'statement_date' => 'date'
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function bankStatementLines()
    {
        return $this->hasMany(BankStatementLine::class);
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    // Factory
    protected static function newFactory()
    {
        return \Modules\Finance\Database\Factories\BankStatementFactory::new();
    }
}
