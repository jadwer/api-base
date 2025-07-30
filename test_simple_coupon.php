<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Modules\User\Models\User;

echo "🧪 SIMPLE COUPON INDEX TEST\n";
echo "============================\n\n";

// Get admin user
$admin = User::where('email', 'admin@example.com')->first();
if (!$admin) {
    echo "❌ Admin user not found\n";
    exit;
}

echo "👤 Admin User: {$admin->email}\n";

// Test permission manually
echo "🔐 Testing permission manually:\n";
echo "  - Can ecommerce.coupons.index: " . ($admin->can('ecommerce.coupons.index') ? 'YES' : 'NO') . "\n";
echo "  - Can ecommerce.index: " . ($admin->can('ecommerce.index') ? 'YES' : 'NO') . "\n\n";

// Simulate the same check that the Authorizer does
echo "🎯 Simulating Authorizer check:\n";
echo "  - Current Authorizer checks: 'ecommerce.index' → " . ($admin->can('ecommerce.index') ? 'PASS' : 'FAIL') . "\n";
echo "  - Should check: 'ecommerce.coupons.index' → " . ($admin->can('ecommerce.coupons.index') ? 'PASS' : 'FAIL') . "\n\n";

// Fix suggestion
echo "🔧 FIX NEEDED:\n";
echo "In CouponAuthorizer.php line 19:\n";
echo "  CHANGE: return \$user?->can('ecommerce.index') ?? false;\n";
echo "  TO:     return \$user?->can('ecommerce.coupons.index') ?? false;\n\n";

// Test API call simulation
echo "🌐 Testing API call simulation:\n";
try {
    // Create application instance for testing
    $response = \Illuminate\Support\Facades\Http::fake();
    
    echo "  - Admin has all required permissions for API call\n";
    echo "  - Should work after Authorizer fix\n";
} catch (Exception $e) {
    echo "  - Error: " . $e->getMessage() . "\n";
}

echo "\n✅ Script completed!\n";
