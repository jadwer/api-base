<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Traits\HasPermissions;


class AuditLog extends Model
{
    use HasFactory, HasPermissions;

    protected $table = 'audit_logs';
    
    protected $fillable = [
        'company_id', 'model_type', 'model_id', 'action', 'user_id', 'changes', 'ip_address', 'user_agent', 'session_id', 'payload_hash', 'requires_retention', 'retention_until', 'metadata'
    ];

    protected $casts = [
        'changes' => 'array',
        'requires_retention' => 'boolean',
        'retention_until' => 'datetime',
        'metadata' => 'array'
    ];

    // Factory
    protected static function newFactory()
    {
        return \Modules\Accounting\Database\Factories\AuditLogFactory::new();
    }
}
