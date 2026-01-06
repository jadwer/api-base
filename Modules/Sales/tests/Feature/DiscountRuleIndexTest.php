<?php

namespace Modules\Sales\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Sales\Models\DiscountRule;

class DiscountRuleIndexTest extends TestCase
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

    public function test_admin_can_list_discount_rules(): void
    {
        $admin = $this->getAdminUser();

        DiscountRule::factory()->count(3)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('discount-rules')
            ->get('/api/v1/discount-rules');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'attributes' => [
                        'name',
                        'code',
                        'discountType',
                        'discountValue',
                        'isActive',
                    ]
                ]
            ]
        ]);
    }

    public function test_admin_can_filter_discount_rules_by_discount_type(): void
    {
        $admin = $this->getAdminUser();

        DiscountRule::factory()->percentage()->create();
        DiscountRule::factory()->percentage()->create();
        DiscountRule::factory()->fixedAmount()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('discount-rules')
            ->filter(['discountType' => 'percentage'])
            ->get('/api/v1/discount-rules');

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
    }

    public function test_admin_can_filter_discount_rules_by_active_status(): void
    {
        $admin = $this->getAdminUser();

        DiscountRule::factory()->create(['is_active' => true]);
        DiscountRule::factory()->inactive()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('discount-rules')
            ->filter(['isActive' => '1'])
            ->get('/api/v1/discount-rules');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_unauthorized_user_cannot_list_discount_rules(): void
    {
        $response = $this->jsonApi()
            ->expects('discount-rules')
            ->get('/api/v1/discount-rules');

        $response->assertUnauthorized();
    }

    public function test_user_without_permission_cannot_list_discount_rules(): void
    {
        $customer = $this->getCustomerUser();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('discount-rules')
            ->get('/api/v1/discount-rules');

        $response->assertForbidden();
    }
}
