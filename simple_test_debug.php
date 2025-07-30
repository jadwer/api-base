<?php

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Ecommerce\Models\Coupon;

class SimpleCouponTest extends TestCase
{
    public function test_simple_coupon_count()
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        
        Coupon::factory()->count(2)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->get('/api/v1/coupons');

        $response->assertOk(); // 1 assertion
        $this->assertCount(2, $response->json('data')); // 1 assertion
        
        // Total esperado: 2 assertions
    }
}

// Run the simple test
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🧪 Creando test simple...\n";
echo "Este test debería tener solo 2 assertions\n";
echo "Comparado con 237 del test completo con assertJsonStructure\n";
