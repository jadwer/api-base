<?php

namespace Modules\Ecommerce\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Ecommerce\Models\Coupon;

class CouponUpdateTest extends TestCase
{
    private function getAdminUser(): User
    {
        return User::where('email', 'admin@example.com')->firstOrFail();
    }

    private function getTechUser(): User
    {
        return User::where('email', 'tech@example.com')->firstOrFail();
    }

    private function getCustomerUser(): User
    {
        return User::where('email', 'customer@example.com')->firstOrFail();
    }

    public function test_admin_can_update_Coupon(): void
    {
        $admin = $this->getAdminUser();
        $coupon = Coupon::factory()->create();

        $data = [
            'type' => 'coupons',
            'id' => (string) $coupon->id,
            'attributes' => [
                'name' => 'Updated Coupon',
                'description' => 'Updated description',
                'isActive' => false
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('coupons')
            ->withData($data)
            ->patch("/api/v1/coupons/{$coupon->id}");

        $response->assertOk();
        
        $this->assertDatabaseHas('coupons', [
            'id' => $coupon->id,
            'name' => 'Updated Coupon',
            'description' => 'Updated description',
            'is_active' => false
        ]);
    }

    public function test_admin_can_partially_update_Coupon(): void
    {
        $admin = $this->getAdminUser();
        $coupon = Coupon::factory()->create([
            'name' => 'Original Name',
            'description' => 'Original Description'
        ]);

        $data = [
            'type' => 'coupons',
            'id' => (string) $coupon->id,
            'attributes' => [
                'name' => 'Partially Updated Name'
                // description should remain unchanged
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('coupons')
            ->withData($data)
            ->patch("/api/v1/coupons/{$coupon->id}");

        $response->assertOk();
        
        $this->assertDatabaseHas('coupons', [
            'id' => $coupon->id,
            'name' => 'Partially Updated Name',
            'description' => 'Original Description'
        ]);
    }

    public function test_admin_can_update_Coupon_arrays(): void
    {
        $admin = $this->getAdminUser();
        $coupon = Coupon::factory()->create();

        $customerIds = [1, 2, 3];
        $productIds = [4, 5, 6];

        $data = [
            'type' => 'coupons',
            'id' => (string) $coupon->id,
            'attributes' => [
                'customerIds' => $customerIds,
                'productIds' => $productIds
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('coupons')
            ->withData($data)
            ->patch("/api/v1/coupons/{$coupon->id}");

        $response->assertOk();
        
        $coupon->refresh();
        $this->assertEquals([1, 2, 3], $coupon->customer_ids);
        $this->assertEquals([4, 5, 6], $coupon->product_ids);
    }

    public function test_customer_user_cannot_update_Coupon(): void
    {
        $customer = $this->getCustomerUser();
        $coupon = Coupon::factory()->create();

        $data = [
            'type' => 'coupons',
            'id' => (string) $coupon->id,
            'attributes' => [
                'name' => 'Unauthorized Update'
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('coupons')
            ->withData($data)
            ->patch("/api/v1/coupons/{$coupon->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_update_Coupon(): void
    {
        $coupon = Coupon::factory()->create();

        $data = [
            'type' => 'coupons',
            'id' => (string) $coupon->id,
            'attributes' => [
                'name' => 'Guest Update'
            ]
        ];

        $response = $this->jsonApi()
            ->expects('coupons')
            ->withData($data)
            ->patch("/api/v1/coupons/{$coupon->id}");

        $response->assertStatus(401);
    }

    public function test_cannot_update_nonexistent_Coupon(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'coupons',
            'id' => '999999',
            'attributes' => [
                'name' => 'Nonexistent Update'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('coupons')
            ->withData($data)
            ->patch('/api/v1/coupons/999999');

        $response->assertStatus(404);
    }

    public function test_cannot_update_Coupon_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();
        $coupon = Coupon::factory()->create();

        $data = [
            'type' => 'coupons',
            'id' => (string) $coupon->id,
            'attributes' => [
                'name' => '', // Empty name
                'isActive' => 'invalid_boolean'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('coupons')
            ->withData($data)
            ->patch("/api/v1/coupons/{$coupon->id}");

        $response->assertStatus(422);
    }
}
