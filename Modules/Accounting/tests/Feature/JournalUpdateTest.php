<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Accounting\Models\Journal;

class JournalUpdateTest extends TestCase
{



    public function test_admin_can_update_Journal(): void
    {
        $admin = $this->getAdminUser();
        $journal = Journal::factory()->create();

        $data = [
            'type' => 'journals',
            'id' => (string) $journal->id,
            'attributes' => [
                'name' => 'Updated Journal',
                'description' => 'Updated description',
                'is_active' => false
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journals')
            ->withData($data)
            ->patch("/api/v1/journals/{$journal->id}");

        $response->assertOk();
        
        $this->assertDatabaseHas('journals', [
            'id' => $journal->id,
            'name' => 'Updated Journal',
            'description' => 'Updated description',
            'is_active' => false
        ]);
    }

    public function test_admin_can_partially_update_Journal(): void
    {
        $admin = $this->getAdminUser();
        $journal = Journal::factory()->create([
            'name' => 'Original Name',
            'description' => 'Original Description'
        ]);

        $data = [
            'type' => 'journals',
            'id' => (string) $journal->id,
            'attributes' => [
                'name' => 'Partially Updated Name'
                // description should remain unchanged
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journals')
            ->withData($data)
            ->patch("/api/v1/journals/{$journal->id}");

        $response->assertOk();
        
        $this->assertDatabaseHas('journals', [
            'id' => $journal->id,
            'name' => 'Partially Updated Name',
            'description' => 'Original Description'
        ]);
    }

    public function test_admin_can_update_Journal_metadata(): void
    {
        $admin = $this->getAdminUser();
        $journal = Journal::factory()->create();

        $metadata = [
            'updated_field' => 'new_value',
            'priority' => 'urgent',
            'tags' => ['important', 'updated']
        ];

        $data = [
            'type' => 'journals',
            'id' => (string) $journal->id,
            'attributes' => [
                'metadata' => $metadata
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journals')
            ->withData($data)
            ->patch("/api/v1/journals/{$journal->id}");

        $response->assertOk();
        
        $journal->refresh();
        $this->assertEquals($metadata, $journal->metadata);
    }

    public function test_customer_user_cannot_update_Journal(): void
    {
        $customer = $this->getCustomerUser();
        $journal = Journal::factory()->create();

        $data = [
            'type' => 'journals',
            'id' => (string) $journal->id,
            'attributes' => [
                'name' => 'Unauthorized Update'
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('journals')
            ->withData($data)
            ->patch("/api/v1/journals/{$journal->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_update_Journal(): void
    {
        $journal = Journal::factory()->create();

        $data = [
            'type' => 'journals',
            'id' => (string) $journal->id,
            'attributes' => [
                'name' => 'Guest Update'
            ]
        ];

        $response = $this->jsonApi()
            ->expects('journals')
            ->withData($data)
            ->patch("/api/v1/journals/{$journal->id}");

        $response->assertStatus(401);
    }

    public function test_cannot_update_nonexistent_Journal(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'journals',
            'id' => '999999',
            'attributes' => [
                'name' => 'Nonexistent Update'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journals')
            ->withData($data)
            ->patch('/api/v1/journals/999999');

        $response->assertStatus(404);
    }

    public function test_cannot_update_Journal_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();
        $journal = Journal::factory()->create();

        $data = [
            'type' => 'journals',
            'id' => (string) $journal->id,
            'attributes' => [
                'name' => '', // Empty name
                'is_active' => 'invalid_boolean'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journals')
            ->withData($data)
            ->patch("/api/v1/journals/{$journal->id}");

        $response->assertStatus(422);
    }
}
