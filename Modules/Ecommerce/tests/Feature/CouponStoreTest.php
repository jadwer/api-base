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
                'code' => 'TEST123',
                'name' => 'Test Name',
                'description' => 'test description',
                'type' => 'test string',
                'value' => 99.99,
                'minAmount' => 99.99,
                'maxAmount' => 99.99,
                'maxUses' => 100,
                'usedCount' => 100,
                'startsAt' => '2024-01-01',
                'expiresAt' => '2024-01-01',
                'isActive' => true,
                'customerIds' => 'test value',
                'productIds' => 'test value',
                'categoryIds' => 'test value'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('coupons')
            ->withData($data)
            ->post('/api/v1/coupons');

        $response->assertCreated();
        
        $this->assertDatabaseHas('coupons', ['code' => 'TEST123', 'name' => 'Test Name', 'description' => 'test description', 'type' => 'test string', 'value' => 99.99, 'min_amount' => 99.99, 'max_amount' => 99.99, 'max_uses' => 100, 'used_count' => 100, 'starts_at' => 'test value', 'expires_at' => 'test value', 'is_active' => true, 'customer_ids' => 'test value', 'product_ids' => 'test value', 'category_ids' => 'test value']);
    }

    public function test_admin_can_create_Coupon_with_minimal_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'coupons',
            'attributes' => [
                'code' => 'TEST123',
                'name' => 'Test Name',
                'value' => 99.99,
                'usedCount' => 100,
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
                'name' => 'Unauthorized Coupon',
                'is_active' => true
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
                'name' => 'Guest Coupon',
                'is_active' => true
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
        $this->assertJsonApiValidationErrors(['/data/attributes/name'], $response);
    }

    public function test_cannot_create_Coupon_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'coupons',
            'attributes' => [
                'name' => '', // Empty name
                'is_active' => 'not_boolean' // Invalid boolean
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
