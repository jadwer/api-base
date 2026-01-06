<?php

namespace Modules\Sales\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Sales\Models\DiscountRule;

class DiscountRuleUpdateTest extends TestCase
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

    public function test_admin_can_update_discount_rule(): void
    {
        $admin = $this->getAdminUser();
        $discountRule = DiscountRule::factory()->create([
            'name' => 'Original Name',
            'discount_value' => 10.0,
        ]);

        $data = [
            'type' => 'discount-rules',
            'id' => (string) $discountRule->id,
            'attributes' => [
                'name' => 'Updated Name',
                'discountValue' => 25.0,
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('discount-rules')
            ->withData($data)
            ->patch('/api/v1/discount-rules/' . $discountRule->id);

        $response->assertOk();
        $this->assertDatabaseHas('discount_rules', [
            'id' => $discountRule->id,
            'name' => 'Updated Name',
            'discount_value' => 25.0,
        ]);
    }

    public function test_admin_can_deactivate_discount_rule(): void
    {
        $admin = $this->getAdminUser();
        $discountRule = DiscountRule::factory()->create([
            'is_active' => true,
        ]);

        $data = [
            'type' => 'discount-rules',
            'id' => (string) $discountRule->id,
            'attributes' => [
                'isActive' => false,
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('discount-rules')
            ->withData($data)
            ->patch('/api/v1/discount-rules/' . $discountRule->id);

        $response->assertOk();
        $this->assertDatabaseHas('discount_rules', [
            'id' => $discountRule->id,
            'is_active' => false,
        ]);
    }

    public function test_admin_can_update_date_range(): void
    {
        $admin = $this->getAdminUser();
        $discountRule = DiscountRule::factory()->create();

        $data = [
            'type' => 'discount-rules',
            'id' => (string) $discountRule->id,
            'attributes' => [
                'startDate' => now()->toDateTimeString(),
                'endDate' => now()->addMonths(2)->toDateTimeString(),
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('discount-rules')
            ->withData($data)
            ->patch('/api/v1/discount-rules/' . $discountRule->id);

        $response->assertOk();
    }

    public function test_update_validates_discount_type(): void
    {
        $admin = $this->getAdminUser();
        $discountRule = DiscountRule::factory()->create();

        $data = [
            'type' => 'discount-rules',
            'id' => (string) $discountRule->id,
            'attributes' => [
                'discountType' => 'invalid_type',
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('discount-rules')
            ->withData($data)
            ->patch('/api/v1/discount-rules/' . $discountRule->id);

        $response->assertUnprocessable();
    }

    public function test_unauthorized_user_cannot_update_discount_rule(): void
    {
        $discountRule = DiscountRule::factory()->create();

        $data = [
            'type' => 'discount-rules',
            'id' => (string) $discountRule->id,
            'attributes' => [
                'name' => 'Updated Name',
            ]
        ];

        $response = $this->jsonApi()
            ->expects('discount-rules')
            ->withData($data)
            ->patch('/api/v1/discount-rules/' . $discountRule->id);

        $response->assertUnauthorized();
    }

    public function test_user_without_permission_cannot_update_discount_rule(): void
    {
        $customer = $this->getCustomerUser();
        $discountRule = DiscountRule::factory()->create();

        $data = [
            'type' => 'discount-rules',
            'id' => (string) $discountRule->id,
            'attributes' => [
                'name' => 'Updated Name',
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('discount-rules')
            ->withData($data)
            ->patch('/api/v1/discount-rules/' . $discountRule->id);

        $response->assertForbidden();
    }
}
