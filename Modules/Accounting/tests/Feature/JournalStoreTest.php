<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\Accounting\Models\Journal;

class JournalStoreTest extends TestCase
{
    public function test_admin_can_create_Journal(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'journals',
            'attributes' => [
                'code' => 'GEN',
                'name' => 'General Journal',
                'type' => 'general'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journals')
            ->withData($data)
            ->post('/api/v1/journals');

        $response->assertCreated();

        $this->assertDatabaseHas('journals', [
            'code' => 'GEN',
            'name' => 'General Journal',
            'type' => 'general'
        ]);
    }

    public function test_admin_can_create_Journal_with_minimal_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'journals',
            'attributes' => [
                'code' => 'MIN',
                'name' => 'Minimal'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journals')
            ->withData($data)
            ->post('/api/v1/journals');

        $response->assertCreated();
    }

    public function test_customer_user_cannot_create_Journal(): void
    {
        $customer = $this->getCustomerUser();

        $data = [
            'type' => 'journals',
            'attributes' => [
                'code' => 'HACK'
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('journals')
            ->withData($data)
            ->post('/api/v1/journals');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_create_Journal(): void
    {
        $data = [
            'type' => 'journals',
            'attributes' => [
                'code' => 'HACK'
            ]
        ];

        $response = $this->jsonApi()
            ->expects('journals')
            ->withData($data)
            ->post('/api/v1/journals');

        $response->assertStatus(401);
    }

    public function test_cannot_create_Journal_without_required_fields(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'journals',
            'attributes' => [
                // Missing required fields
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journals')
            ->withData($data)
            ->post('/api/v1/journals');

        $response->assertStatus(422);
    }

    public function test_cannot_create_Journal_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'journals',
            'attributes' => [
                'code' => '',
                'name' => ''
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journals')
            ->withData($data)
            ->post('/api/v1/journals');

        $response->assertStatus(422);
    }
}
