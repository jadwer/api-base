<?php

namespace Modules\Ecommerce\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Ecommerce\Models\Coupon;

class CouponStoreTest extends TestCase
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

    public function test_admin_can_create_Coupon(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'coupons',
            'attributes' => [
                'code' => 'SAVE10',
                'name' => 'Save 10 Percent',
                'description' => '10% off on all items',
                'couponType' => 'percentage',
                'value' => 10,
                'minAmount' => 50.00,
                'maxAmount' => 100.00,
                'maxUses' => 100,
                'usedCount' => 0,
                'startsAt' => '2025-01-01T00:00:00Z',
                'expiresAt' => '2025-12-31T23:59:59Z',
                'isActive' => true,
                'customerIds' => [1, 2, 3],
                'productIds' => [4, 5, 6],
                'categoryIds' => [7, 8, 9]
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('coupons')
            ->withData($data)
            ->post('/api/v1/coupons');

        $response->assertCreated();
        
        $this->assertDatabaseHas('coupons', [
            'code' => 'SAVE10',
            'name' => 'Save 10 Percent',
            'description' => '10% off on all items',
            'type' => 'percentage',
            'value' => 10,
            'min_amount' => 50.00,
            'max_amount' => 100.00,
            'max_uses' => 100,
            'used_count' => 0,
            'is_active' => true
        ]);
    }

    public function test_admin_can_create_Coupon_with_minimal_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'coupons',
            'attributes' => [
                'code' => 'MINIMAL',
                'name' => 'Minimal Coupon',
                'couponType' => 'fixed_amount',
                'value' => 5.00,
                'usedCount' => 0,
                'isActive' => true
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('coupons')
            ->withData($data)
            ->post('/api/v1/coupons');

        $response->assertCreated();
    }

    public function test_customer_user_cannot_create_Coupon(): void
    {
        $customer = $this->getCustomerUser();

        $data = [
            'type' => 'coupons',
            'attributes' => [
                'code' => 'UNAUTHORIZED',
                'name' => 'Unauthorized Coupon',
                'couponType' => 'percentage',
                'value' => 10,
                'usedCount' => 0,
                'isActive' => true
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('coupons')
            ->withData($data)
            ->post('/api/v1/coupons');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_create_Coupon(): void
    {
        $data = [
            'type' => 'coupons',
            'attributes' => [
                'code' => 'GUEST',
                'name' => 'Guest Coupon',
                'couponType' => 'percentage',
                'value' => 15,
                'usedCount' => 0,
                'isActive' => true
            ]
        ];

        $response = $this->jsonApi()
            ->expects('coupons')
            ->withData($data)
            ->post('/api/v1/coupons');

        $response->assertStatus(401);
    }

    public function test_cannot_create_Coupon_without_required_fields(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'coupons',
            'attributes' => [
                'description' => 'Missing name'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('coupons')
            ->withData($data)
            ->post('/api/v1/coupons');

        $response->assertStatus(422);
    }

    public function test_cannot_create_Coupon_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'coupons',
            'attributes' => [
                'code' => '',
                'name' => '', // Empty name
                'couponType' => '',
                'value' => -5, // Negative value
                'usedCount' => 0,
                'isActive' => 'not_boolean' // Invalid boolean
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('coupons')
            ->withData($data)
            ->post('/api/v1/coupons');

        $response->assertStatus(422);
    }
}
