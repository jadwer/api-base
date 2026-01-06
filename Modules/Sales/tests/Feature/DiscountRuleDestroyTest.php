<?php

namespace Modules\Sales\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Sales\Models\DiscountRule;

class DiscountRuleDestroyTest extends TestCase
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

    public function test_admin_can_delete_discount_rule(): void
    {
        $admin = $this->getAdminUser();
        $discountRule = DiscountRule::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('discount-rules')
            ->delete('/api/v1/discount-rules/' . $discountRule->id);

        $response->assertNoContent();
        $this->assertDatabaseMissing('discount_rules', [
            'id' => $discountRule->id,
        ]);
    }

    public function test_admin_can_delete_inactive_discount_rule(): void
    {
        $admin = $this->getAdminUser();
        $discountRule = DiscountRule::factory()->inactive()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('discount-rules')
            ->delete('/api/v1/discount-rules/' . $discountRule->id);

        $response->assertNoContent();
        $this->assertDatabaseMissing('discount_rules', [
            'id' => $discountRule->id,
        ]);
    }

    public function test_delete_returns_404_for_nonexistent_discount_rule(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('discount-rules')
            ->delete('/api/v1/discount-rules/99999');

        $response->assertNotFound();
    }

    public function test_unauthorized_user_cannot_delete_discount_rule(): void
    {
        $discountRule = DiscountRule::factory()->create();

        $response = $this->jsonApi()
            ->expects('discount-rules')
            ->delete('/api/v1/discount-rules/' . $discountRule->id);

        $response->assertUnauthorized();
    }

    public function test_user_without_permission_cannot_delete_discount_rule(): void
    {
        $customer = $this->getCustomerUser();
        $discountRule = DiscountRule::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('discount-rules')
            ->delete('/api/v1/discount-rules/' . $discountRule->id);

        $response->assertForbidden();
    }
}
