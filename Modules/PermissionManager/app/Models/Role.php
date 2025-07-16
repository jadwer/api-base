<?php

namespace Modules\PermissionManager\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\PermissionManager\Database\Factories\RoleFactory;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ["name", "description", "guard_name"];

    protected static function newFactory(): RoleFactory
    {
        return RoleFactory::new();
    }
}
