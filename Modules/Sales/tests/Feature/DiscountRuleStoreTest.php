<?php

namespace Modules\Sales\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Sales\Models\DiscountRule;

class DiscountRuleStoreTest extends TestCase
{
    protected function getAdminUser(): User
    {
        return User::where('email', 'admin@example.com')->firstOrFail();
    }

    protected function getTechUser(): User
    {
        return User::where('email', 'tech@example.com')->firstOrFail();
    }

    protected function getCustomerUser(): User
    {
        return User::where('email', 'customer@example.com')->firstOrFail();
    }

    public function test_admin_can_create_percentage_discount(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'discount-rules',
            'attributes' => [
                'name' => 'Summer Sale',
                'code' => 'SUMMER2025',
                'discountType' => 'percentage',
                'discountValue' => 15.0,
                'appliesTo' => 'order',
                'isActive' => true,
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('discount-rules')
            ->withData($data)
            ->post('/api/v1/discount-rules');

        $response->assertCreated();
        $this->assertDatabaseHas('discount_rules', [
            'name' => 'Summer Sale',
            'code' => 'SUMMER2025',
            'discount_type' => 'percentage',
            'discount_value' => 15.0,
        ]);
    }

    public function test_admin_can_create_fixed_amount_discount(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'discount-rules',
            'attributes' => [
                'name' => 'Fixed Discount',
                'code' => 'FIXED100',
                'discountType' => 'fixed_amount',
                'discountValue' => 100.0,
                'appliesTo' => 'order',
                'minOrderAmount' => 500.0,
                'isActive' => true,
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('discount-rules')
            ->withData($data)
            ->post('/api/v1/discount-rules');

        $response->assertCreated();
        $this->assertDatabaseHas('discount_rules', [
            'code' => 'FIXED100',
            'discount_type' => 'fixed_amount',
        ]);
    }

    public function test_store_requires_name(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'discount-rules',
            'attributes' => [
                'code' => 'NONAME',
                'discountType' => 'percentage',
                'discountValue' => 10.0,
                'appliesTo' => 'order',
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('discount-rules')
            ->withData($data)
            ->post('/api/v1/discount-rules');

        $response->assertUnprocessable();
    }

    public function test_store_requires_unique_code(): void
    {
        $admin = $this->getAdminUser();

        DiscountRule::factory()->create(['code' => 'EXISTING']);

        $data = [
            'type' => 'discount-rules',
            'attributes' => [
                'name' => 'New Discount',
                'code' => 'EXISTING',
                'discountType' => 'percentage',
                'discountValue' => 10.0,
                'appliesTo' => 'order',
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('discount-rules')
            ->withData($data)
            ->post('/api/v1/discount-rules');

        $response->assertUnprocessable();
    }

    public function test_unauthorized_user_cannot_create_discount_rule(): void
    {
        $data = [
            'type' => 'discount-rules',
            'attributes' => [
                'name' => 'Test Discount',
                'code' => 'TEST123',
                'discountType' => 'percentage',
                'discountValue' => 10.0,
                'appliesTo' => 'order',
            ]
        ];

        $response = $this->jsonApi()
            ->expects('discount-rules')
            ->withData($data)
            ->post('/api/v1/discount-rules');

        $response->assertUnauthorized();
    }

    public function test_user_without_permission_cannot_create_discount_rule(): void
    {
        $customer = $this->getCustomerUser();

        $data = [
            'type' => 'discount-rules',
            'attributes' => [
                'name' => 'Test Discount',
                'code' => 'TEST456',
                'discountType' => 'percentage',
                'discountValue' => 10.0,
                'appliesTo' => 'order',
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('discount-rules')
            ->withData($data)
            ->post('/api/v1/discount-rules');

        $response->assertForbidden();
    }
}
