<?php

namespace Modules\Ecommerce\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Ecommerce\Models\Coupon;

class CouponIndexTest extends TestCase
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

    public function test_admin_can_list_Coupons(): void
    {
        $admin = $this->getAdminUser();
        
        Coupon::factory()->count(3)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('coupons')
            ->get('/api/v1/coupons');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'attributes' => [
                        'code',
                        'name',
                        'description',
                        'couponType',
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
                    ]
                ]
            ]
        ]);
    }

    public function test_admin_can_sort_Coupons_by_name(): void
    {
        $admin = $this->getAdminUser();
        
        Coupon::factory()->create(['name' => 'B Coupon']);
        Coupon::factory()->create(['name' => 'A Coupon']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('coupons')
            ->get('/api/v1/coupons?sort=name');

        $response->assertOk();
    }

    public function test_admin_can_filter_Coupons_by_id(): void
    {
        $admin = $this->getAdminUser();
        
        $coupon1 = Coupon::factory()->create();
        $coupon2 = Coupon::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('coupons')
            ->get("/api/v1/coupons?filter[id][]={$coupon1->id}");

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_tech_user_can_list_Coupons_with_permission(): void
    {
        $tech = $this->getTechUser();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('coupons')
            ->get('/api/v1/coupons');

        $response->assertOk();
    }

    public function test_customer_user_cannot_list_Coupons(): void
    {
        $customer = $this->getCustomerUser();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('coupons')
            ->get('/api/v1/coupons');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_list_Coupons(): void
    {
        $response = $this->jsonApi()
            ->expects('coupons')
            ->get('/api/v1/coupons');

        $response->assertStatus(401);
    }

    public function test_can_paginate_Coupons(): void
    {
        $admin = $this->getAdminUser();
        
        Coupon::factory()->count(25)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('coupons')
            ->get('/api/v1/coupons?page[size]=10');

        $response->assertOk();
        $this->assertCount(10, $response->json('data'));
        $response->assertJsonStructure(['links', 'meta']);
    }
}
