<?php

namespace Modules\Sales\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Sales\Models\DiscountRule;

class DiscountRuleShowTest extends TestCase
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

    public function test_admin_can_show_discount_rule(): void
    {
        $admin = $this->getAdminUser();
        $discountRule = DiscountRule::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('discount-rules')
            ->get('/api/v1/discount-rules/' . $discountRule->id);

        $response->assertOk();
        $response->assertJson([
            'data' => [
                'id' => (string) $discountRule->id,
                'type' => 'discount-rules',
            ]
        ]);
    }

    public function test_admin_can_view_computed_attributes(): void
    {
        $admin = $this->getAdminUser();
        $discountRule = DiscountRule::factory()->create([
            'is_active' => true,
            'start_date' => now()->subDay(),
            'end_date' => now()->addMonth(),
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('discount-rules')
            ->get('/api/v1/discount-rules/' . $discountRule->id);

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'attributes' => [
                    'isValid',
                    'isExpired',
                    'usageRemaining',
                ]
            ]
        ]);
    }

    public function test_show_returns_404_for_non_existent_discount_rule(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('discount-rules')
            ->get('/api/v1/discount-rules/99999');

        $response->assertNotFound();
    }

    public function test_unauthorized_user_cannot_show_discount_rule(): void
    {
        $discountRule = DiscountRule::factory()->create();

        $response = $this->jsonApi()
            ->expects('discount-rules')
            ->get('/api/v1/discount-rules/' . $discountRule->id);

        $response->assertUnauthorized();
    }

    public function test_user_without_permission_cannot_show_discount_rule(): void
    {
        $customer = $this->getCustomerUser();
        $discountRule = DiscountRule::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('discount-rules')
            ->get('/api/v1/discount-rules/' . $discountRule->id);

        $response->assertForbidden();
    }
}
