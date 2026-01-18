<?php

namespace Modules\Ecommerce\Tests\Feature;

use Tests\TestCase;
use Modules\Ecommerce\Models\Coupon;

class CouponValidateTest extends TestCase
{
    public function test_can_validate_active_coupon(): void
    {
        $admin = $this->getAdminUser();

        $coupon = Coupon::factory()->create([
            'code' => 'VALID10',
            'name' => '10% Discount',
            'type' => 'percentage',
            'value' => 10,
            'is_active' => true,
            'max_uses' => 100,
            'used_count' => 0,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/coupons/validate/VALID10');

        $response->assertOk();
        $response->assertJson([
            'valid' => true,
        ]);
        $response->assertJsonStructure([
            'valid',
            'data' => [
                'type',
                'id',
                'attributes' => [
                    'code',
                    'name',
                    'couponType',
                    'value',
                ]
            ]
        ]);
    }

    public function test_validate_does_not_increment_used_count(): void
    {
        $admin = $this->getAdminUser();

        $coupon = Coupon::factory()->create([
            'code' => 'NOINCREMENT',
            'is_active' => true,
            'used_count' => 5,
        ]);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/coupons/validate/NOINCREMENT');

        // used_count should NOT change after validation
        $this->assertEquals(5, $coupon->refresh()->used_count);
    }

    public function test_invalid_coupon_code_returns_error(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/coupons/validate/NONEXISTENT');

        $response->assertStatus(404);
        $response->assertJson([
            'valid' => false,
            'error' => 'Coupon not found',
        ]);
    }

    public function test_inactive_coupon_returns_error(): void
    {
        $admin = $this->getAdminUser();

        Coupon::factory()->create([
            'code' => 'INACTIVE',
            'is_active' => false,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/coupons/validate/INACTIVE');

        $response->assertStatus(400);
        $response->assertJson([
            'valid' => false,
            'error' => 'Coupon is inactive',
        ]);
    }

    public function test_expired_coupon_returns_error(): void
    {
        $admin = $this->getAdminUser();

        Coupon::factory()->create([
            'code' => 'EXPIRED',
            'is_active' => true,
            'expires_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/coupons/validate/EXPIRED');

        $response->assertStatus(400);
        $response->assertJson([
            'valid' => false,
            'error' => 'Coupon has expired',
        ]);
    }

    public function test_coupon_not_yet_active_returns_error(): void
    {
        $admin = $this->getAdminUser();

        Coupon::factory()->create([
            'code' => 'FUTURE',
            'is_active' => true,
            'starts_at' => now()->addDay(),
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/coupons/validate/FUTURE');

        $response->assertStatus(400);
        $response->assertJson([
            'valid' => false,
            'error' => 'Coupon is not yet active',
        ]);
    }

    public function test_coupon_usage_limit_reached_returns_error(): void
    {
        $admin = $this->getAdminUser();

        Coupon::factory()->create([
            'code' => 'MAXED',
            'is_active' => true,
            'max_uses' => 10,
            'used_count' => 10,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/coupons/validate/MAXED');

        $response->assertStatus(400);
        $response->assertJson([
            'valid' => false,
            'error' => 'Coupon usage limit reached',
        ]);
    }

    public function test_validate_returns_coupon_details(): void
    {
        $admin = $this->getAdminUser();

        Coupon::factory()->create([
            'code' => 'DETAILS',
            'name' => 'Special Discount',
            'description' => 'A special discount for testing',
            'type' => 'fixed_amount',
            'value' => 50,
            'min_amount' => 100,
            'max_amount' => 200,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/coupons/validate/DETAILS');

        $response->assertOk();
        $response->assertJson([
            'valid' => true,
            'data' => [
                'attributes' => [
                    'code' => 'DETAILS',
                    'name' => 'Special Discount',
                    'couponType' => 'fixed_amount',
                    'value' => 50,
                    'minAmount' => 100,
                    'maxAmount' => 200,
                ]
            ]
        ]);
    }

    public function test_coupon_with_no_expiry_is_valid(): void
    {
        $admin = $this->getAdminUser();

        Coupon::factory()->create([
            'code' => 'NOEXPIRY',
            'is_active' => true,
            'expires_at' => null,
            'starts_at' => null,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/coupons/validate/NOEXPIRY');

        $response->assertOk();
        $response->assertJson(['valid' => true]);
    }

    public function test_coupon_with_unlimited_uses_is_valid(): void
    {
        $admin = $this->getAdminUser();

        Coupon::factory()->create([
            'code' => 'UNLIMITED',
            'is_active' => true,
            'max_uses' => null,
            'used_count' => 1000,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/coupons/validate/UNLIMITED');

        $response->assertOk();
        $response->assertJson(['valid' => true]);
    }

    public function test_validate_is_case_sensitive(): void
    {
        $admin = $this->getAdminUser();

        Coupon::factory()->create([
            'code' => 'UPPERCASE',
            'is_active' => true,
        ]);

        // Lowercase should not find it
        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/coupons/validate/uppercase');

        $response->assertStatus(404);
    }

    public function test_customer_can_validate_coupon(): void
    {
        $customer = $this->getCustomerUser();

        Coupon::factory()->create([
            'code' => 'CUSTOMER',
            'is_active' => true,
        ]);

        $response = $this->actingAs($customer, 'sanctum')
            ->getJson('/api/v1/coupons/validate/CUSTOMER');

        $response->assertOk();
        $response->assertJson(['valid' => true]);
    }

    public function test_guest_cannot_validate_coupon(): void
    {
        Coupon::factory()->create([
            'code' => 'GUESTTEST',
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/v1/coupons/validate/GUESTTEST');

        $response->assertStatus(401);
    }
}
