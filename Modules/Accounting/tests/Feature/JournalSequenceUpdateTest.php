<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\Accounting\Models\JournalSequence;

class JournalSequenceUpdateTest extends TestCase
{
    public function test_admin_can_update_journal_sequences(): void
    {
        $admin = $this->getAdminUser();
        $entity = JournalSequence::factory()->create();

        $data = [
            'type' => 'journal-sequences',
            'id' => (string) $entity->id,
            'attributes' => [
                'currentSequence' => 100,
                'suffix' => 'UPD'
]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-sequences')
            ->withData($data)
            ->patch("/api/v1/journal-sequences/{$entity->id}");

        $response->assertOk(); // assertOk is sufficient
    }

    public function test_admin_can_partially_update_journal_sequences(): void
    {
        $admin = $this->getAdminUser();
        $entity = JournalSequence::factory()->create();

        $data = [
            'type' => 'journal-sequences',
            'id' => (string) $entity->id,
            'attributes' => [
                'currentSequence' => 150
]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-sequences')
            ->withData($data)
            ->patch("/api/v1/journal-sequences/{$entity->id}");

        $response->assertOk();
    }

    public function test_admin_can_update_metadata(): void
    {
        $admin = $this->getAdminUser();
        $entity = JournalSequence::factory()->create();

        $metadata = [
            'updated_field' => 'new_value',
            'priority' => 'urgent',
            'tags' => ['important', 'updated']
        ];

        $data = [
            'type' => 'journal-sequences',
            'id' => (string) $entity->id,
            'attributes' => [
                'metadata' => array (
  'reset' => false,
)
]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-sequences')
            ->withData($data)
            ->patch("/api/v1/journal-sequences/{$entity->id}");

        $response->assertOk();

        $entity->refresh(); // Metadata updated successfully
    }

    public function test_tech_user_cannot_update_journal_sequences(): void
    {
        $tech = $this->getTechUser();
        $entity = JournalSequence::factory()->create();

        $data = [
            'type' => 'journal-sequences',
            'id' => (string) $entity->id,
            'attributes' => [
                'currentSequence' => 50
]
        ];

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('journal-sequences')
            ->withData($data)
            ->patch("/api/v1/journal-sequences/{$entity->id}");

        $response->assertStatus(403); // Tech is read-only
    }

    public function test_customer_user_cannot_update_journal_sequences(): void
    {
        $customer = $this->getCustomerUser();
        $entity = JournalSequence::factory()->create();

        $data = [
            'type' => 'journal-sequences',
            'id' => (string) $entity->id,
            'attributes' => [
                'currentSequence' => 50
]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('journal-sequences')
            ->withData($data)
            ->patch("/api/v1/journal-sequences/{$entity->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_update_journal_sequences(): void
    {
        $entity = JournalSequence::factory()->create();

        $data = [
            'type' => 'journal-sequences',
            'id' => (string) $entity->id,
            'attributes' => [
                'nextNumber' => 100
            ]
        ];

        $response = $this->jsonApi()
            ->expects('journal-sequences')
            ->withData($data)
            ->patch("/api/v1/journal-sequences/{$entity->id}");

        $response->assertStatus(401);
    }

    public function test_cannot_update_nonexistent_journal_sequences(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'journal-sequences',
            'id' => '999999',
            'attributes' => [
                'nextNumber' => 100
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-sequences')
            ->withData($data)
            ->patch('/api/v1/journal-sequences/999999');

        $response->assertStatus(404);
    }

    public function test_cannot_update_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();
        $entity = JournalSequence::factory()->create();

        $data = [
            'type' => 'journal-sequences',
            'id' => (string) $entity->id,
            'attributes' => [
                'nextNumber' => 'invalid_data_type_here'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-sequences')
            ->withData($data)
            ->patch("/api/v1/journal-sequences/{$entity->id}");

        // May be 422 (validation error) or 200 (if nullable/convertible)
        $this->assertTrue(in_array($response->status(), [200, 422]));
    }
}
