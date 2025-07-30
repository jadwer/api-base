<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Modules\User\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

$admin = User::where('email', 'admin@example.com')->first();

if (!$admin) {
    echo "❌ Admin user not found\n";
    exit;
}

echo "👤 Admin User: {$admin->email}\n";
echo "🎭 Roles: " . $admin->getRoleNames()->implode(', ') . "\n";

echo "\n� DEBUGGING GUARDS:\n";

// Check admin user's guard_name
echo "User guard_name: " . ($admin->guard_name ?? 'default') . "\n";

// Check roles with different guards
$adminRoleWeb = Role::where('name', 'admin')->where('guard_name', 'web')->first();
$adminRoleApi = Role::where('name', 'admin')->where('guard_name', 'api')->first();

echo "Admin role (web guard): " . ($adminRoleWeb ? 'EXISTS' : 'NOT FOUND') . "\n";
echo "Admin role (api guard): " . ($adminRoleApi ? 'EXISTS' : 'NOT FOUND') . "\n";

// Check permissions with different guards
$ecommercePermWeb = Permission::where('name', 'ecommerce.coupons.index')->where('guard_name', 'web')->first();
$ecommercePermApi = Permission::where('name', 'ecommerce.coupons.index')->where('guard_name', 'api')->first();

echo "Ecommerce permission (web guard): " . ($ecommercePermWeb ? 'EXISTS' : 'NOT FOUND') . "\n";
echo "Ecommerce permission (api guard): " . ($ecommercePermApi ? 'EXISTS' : 'NOT FOUND') . "\n";

// Test specific permission with API guard
if ($adminRoleApi) {
    echo "\n🧪 Testing with API GUARD:\n";
    echo "Admin role (api) has ecommerce.coupons.index: " . ($adminRoleApi->hasPermissionTo('ecommerce.coupons.index', 'api') ? 'Yes' : 'No') . "\n";
}

// Check what guard the user actually uses for auth
echo "\n👤 User's auth guard context:\n";
echo "Default guard: " . config('auth.defaults.guard') . "\n";
echo "Auth guards: " . implode(', ', array_keys(config('auth.guards'))) . "\n";
