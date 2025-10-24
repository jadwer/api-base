<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Traits\HasPermissions;


class IdempotencyKey extends Model
{
    use HasFactory, HasPermissions;

    protected $table = 'idempotency_keys';
    
    protected $fillable = [
        'company_id', 'user_id', 'endpoint', 'idempotency_key', 'request_hash', 'response_data', 'status', 'expires_at', 'metadata'
    ];

    protected $casts = [
        'response_data' => 'array',
        'expires_at' => 'datetime',
        'metadata' => 'array'
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }



    // Factory
    protected static function newFactory()
    {
        return \Modules\Accounting\Database\Factories\IdempotencyKeyFactory::new();
    }
}
