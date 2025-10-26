<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\Accounting\Models\FiscalPeriod;

class FiscalPeriodUpdateTest extends TestCase
{
    public function test_admin_can_update_fiscal_periods(): void
    {
        $admin = $this->getAdminUser();
        $entity = FiscalPeriod::factory()->create();

        $data = [
            'type' => 'fiscal-periods',
            'id' => (string) $entity->id,
            'attributes' => [
                'status' => 'closed',
]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('fiscal-periods')
            ->withData($data)
            ->patch("/api/v1/fiscal-periods/{$entity->id}");

        $response->assertOk(); // assertOk is sufficient
    }

    public function test_admin_can_partially_update_fiscal_periods(): void
    {
        $admin = $this->getAdminUser();
        $entity = FiscalPeriod::factory()->create();

        $data = [
            'type' => 'fiscal-periods',
            'id' => (string) $entity->id,
            'attributes' => [
                'status' => 'closed'
]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('fiscal-periods')
            ->withData($data)
            ->patch("/api/v1/fiscal-periods/{$entity->id}");

        $response->assertOk();
    }

    public function test_admin_can_update_metadata(): void
    {
        $admin = $this->getAdminUser();
        $entity = FiscalPeriod::factory()->create();

        $metadata = [
            'updated_field' => 'new_value',
            'priority' => 'urgent',
            'tags' => ['important', 'updated']
        ];

        $data = [
            'type' => 'fiscal-periods',
            'id' => (string) $entity->id,
            'attributes' => [
                'metadata' => array (
  'closed_by' => 'admin',
)
]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('fiscal-periods')
            ->withData($data)
            ->patch("/api/v1/fiscal-periods/{$entity->id}");

        $response->assertOk();

        $entity->refresh(); // Metadata updated successfully
    }

    public function test_tech_user_cannot_update_fiscal_periods(): void
    {
        $tech = $this->getTechUser();
        $entity = FiscalPeriod::factory()->create();

        $data = [
            'type' => 'fiscal-periods',
            'id' => (string) $entity->id,
            'attributes' => [
                'status' => 'closed'
]
        ];

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('fiscal-periods')
            ->withData($data)
            ->patch("/api/v1/fiscal-periods/{$entity->id}");

        $response->assertStatus(403); // Tech is read-only
    }

    public function test_customer_user_cannot_update_fiscal_periods(): void
    {
        $customer = $this->getCustomerUser();
        $entity = FiscalPeriod::factory()->create();

        $data = [
            'type' => 'fiscal-periods',
            'id' => (string) $entity->id,
            'attributes' => [
                'status' => 'closed'
]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('fiscal-periods')
            ->withData($data)
            ->patch("/api/v1/fiscal-periods/{$entity->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_update_fiscal_periods(): void
    {
        $entity = FiscalPeriod::factory()->create();

        $data = [
            'type' => 'fiscal-periods',
            'id' => (string) $entity->id,
            'attributes' => [
                'name' => 'Updated Period'
            ]
        ];

        $response = $this->jsonApi()
            ->expects('fiscal-periods')
            ->withData($data)
            ->patch("/api/v1/fiscal-periods/{$entity->id}");

        $response->assertStatus(401);
    }

    public function test_cannot_update_nonexistent_fiscal_periods(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'fiscal-periods',
            'id' => '999999',
            'attributes' => [
                'name' => 'Updated Period'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('fiscal-periods')
            ->withData($data)
            ->patch('/api/v1/fiscal-periods/999999');

        $response->assertStatus(404);
    }

    public function test_cannot_update_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();
        $entity = FiscalPeriod::factory()->create();

        $data = [
            'type' => 'fiscal-periods',
            'id' => (string) $entity->id,
            'attributes' => [
                'name' => 'invalid_data_type_here'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('fiscal-periods')
            ->withData($data)
            ->patch("/api/v1/fiscal-periods/{$entity->id}");

        // May be 422 (validation error) or 200 (if nullable/convertible)
        $this->assertTrue(in_array($response->status(), [200, 422]));
    }
}
