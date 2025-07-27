<?php

use Modules\PermissionManager\Models\Role;
use Modules\PermissionManager\Models\Permission;

$admin = Role::where('name', 'admin')->first();
echo "Admin role found: " . ($admin ? "Yes (ID: {$admin->id})" : "No") . "\n";

$permission = Permission::where('name', 'warehouses.index')->first();
echo "warehouses.index permission found: " . ($permission ? "Yes (ID: {$permission->id})" : "No") . "\n";

if ($admin && $permission) {
    $hasPermission = $admin->hasPermissionTo($permission);
    echo "Admin has warehouses.index: " . ($hasPermission ? "Yes" : "No") . "\n";
    
    if (!$hasPermission) {
        $admin->givePermissionTo($permission);
        echo "Permission granted!\n";
        
        // Verify again
        $hasPermissionNow = $admin->hasPermissionTo($permission);
        echo "Admin has warehouses.index now: " . ($hasPermissionNow ? "Yes" : "No") . "\n";
    }
}
