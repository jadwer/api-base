<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Traits\HasPermissions;


class AccountMapping extends Model
{
    use HasFactory, HasPermissions;

    protected $table = 'account_mappings';
    
    protected $fillable = [
        'company_id', 'mapping_type', 'account_id', 'version', 'effective_from', 'effective_to', 'is_active', 'created_by_id', 'notes'
    ];

    protected $casts = [
                'effective_from' => 'date',
        'effective_to' => 'date',
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
        return \Modules\Accounting\Database\Factories\AccountMappingFactory::new();
    }
}
