<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Modules\User\Models\User;
use Modules\Ecommerce\Models\Coupon;

echo "🧪 DEBUGGING COUPON API RESPONSE\n";
echo "==================================\n\n";

// Get a sample coupon to see its actual structure
$coupon = Coupon::first();
if ($coupon) {
    echo "📋 Sample Coupon attributes in database:\n";
    foreach ($coupon->getAttributes() as $key => $value) {
        echo "  - {$key}: " . (is_null($value) ? 'null' : gettype($value)) . "\n";
    }
    echo "\n";
}

// Now make a simple HTTP request to see API response
echo "🌐 Making API request...\n";

try {
    // Use Guzzle to make the request
    $client = new \GuzzleHttp\Client();
    
    // Get admin token first (simplified)
    $admin = User::where('email', 'admin@example.com')->first();
    $token = $admin->createToken('test')->plainTextToken;
    
    $response = $client->get('http://localhost:8000/api/v1/coupons', [
        'headers' => [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/vnd.api+json',
        ]
    ]);
    
    $data = json_decode($response->getBody(), true);
    
    if (isset($data['data']) && count($data['data']) > 0) {
        echo "✅ API Response received\n";
        echo "📋 First item attributes:\n";
        $firstItem = $data['data'][0];
        if (isset($firstItem['attributes'])) {
            foreach ($firstItem['attributes'] as $key => $value) {
                echo "  - {$key}\n";
            }
        }
    } else {
        echo "❌ No data in response\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "💡 Try running: php artisan serve\n";
}
