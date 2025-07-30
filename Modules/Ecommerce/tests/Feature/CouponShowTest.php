<?php

namespace Modules\Ecommerce\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Ecommerce\Models\Coupon;

class CouponShowTest extends TestCase
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

    public function test_admin_can_view_Coupon(): void
    {
        $admin = $this->getAdminUser();
        $coupon = Coupon::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('coupons')
            ->get("/api/v1/coupons/{$coupon->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                        'code',
                        'name',
                        'description',
                        'type',
                        'value',
                        'minAmount',
                        'maxAmount',
                        'maxUses',
                        'usedCount',
                        'startsAt',
                        'expiresAt',
                        'isActive',
                        'customerIds',
                        'productIds',
                        'categoryIds',
                    'createdAt',
                    'updatedAt'
                ]
            ]
        ]);
    }

    public function test_admin_can_view_Coupon_with_specific_data(): void
    {
        $admin = $this->getAdminUser();
        
        $coupon = Coupon::factory()->create(['code' => 'TEST123', 'name' => 'Test Name', 'description' => 'test description', 'type' => 'test string', 'value' => 99.99, 'min_amount' => 99.99, 'max_amount' => 99.99, 'max_uses' => 100, 'used_count' => 100, 'starts_at' => now(), 'expires_at' => now(), 'is_active' => true, 'customer_ids' => 'test value', 'product_ids' => 'test value', 'category_ids' => 'test value']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('coupons')
            ->get("/api/v1/coupons/{$coupon->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                        'code',
                        'name',
                        'description',
                        'type',
                        'value',
                        'minAmount',
                        'maxAmount',
                        'maxUses',
                        'usedCount',
                        'startsAt',
                        'expiresAt',
                        'isActive',
                        'customerIds',
                        'productIds',
                        'categoryIds',
                    'createdAt',
                    'updatedAt'
                ]
            ]
        ]);
    }

    public function test_tech_user_can_view_Coupon_with_permission(): void
    {
        $tech = $this->getTechUser();
        $coupon = Coupon::factory()->create();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('coupons')
            ->get("/api/v1/coupons/{$coupon->id}");

        $response->assertOk();
    }

    public function test_customer_user_cannot_view_Coupon(): void
    {
        $customer = $this->getCustomerUser();
        $coupon = Coupon::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('coupons')
            ->get("/api/v1/coupons/{$coupon->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_view_Coupon(): void
    {
        $coupon = Coupon::factory()->create();

        $response = $this->jsonApi()
            ->expects('coupons')
            ->get("/api/v1/coupons/{$coupon->id}");

        $response->assertStatus(401);
    }

    public function test_returns_404_for_nonexistent_Coupon(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('coupons')
            ->get('/api/v1/coupons/999999');

        $response->assertStatus(404);
    }

    public function test_response_includes_timestamps(): void
    {
        $admin = $this->getAdminUser();
        $coupon = Coupon::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('coupons')
            ->get("/api/v1/coupons/{$coupon->id}");

        $response->assertOk();
        
        $this->assertNotNull($response->json('data.attributes.createdAt'));
        $this->assertNotNull($response->json('data.attributes.updatedAt'));
    }
}
