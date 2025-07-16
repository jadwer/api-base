<?php

namespace Modules\PermissionManager\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\PermissionManager\Database\Factories\PermissionFactory;
use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ["name","guard_name"];

    protected static function newFactory(): PermissionFactory
    {
        return PermissionFactory::new();
    }
}
