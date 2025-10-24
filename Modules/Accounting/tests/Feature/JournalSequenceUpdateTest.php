<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Accounting\Models\JournalSequence;

class JournalSequenceUpdateTest extends TestCase
{



    public function test_admin_can_update_JournalSequence(): void
    {
        $admin = $this->getAdminUser();
        $journalSequence = JournalSequence::factory()->create();

        $data = [
            'type' => 'journal-sequences',
            'id' => (string) $journalSequence->id,
            'attributes' => [
                'name' => 'Updated JournalSequence',
                'description' => 'Updated description',
                'is_active' => false
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-sequences')
            ->withData($data)
            ->patch("/api/v1/journal-sequences/{$journalSequence->id}");

        $response->assertOk();
        
        $this->assertDatabaseHas('journal_sequences', [
            'id' => $journalSequence->id,
            'name' => 'Updated JournalSequence',
            'description' => 'Updated description',
            'is_active' => false
        ]);
    }

    public function test_admin_can_partially_update_JournalSequence(): void
    {
        $admin = $this->getAdminUser();
        $journalSequence = JournalSequence::factory()->create([
            'name' => 'Original Name',
            'description' => 'Original Description'
        ]);

        $data = [
            'type' => 'journal-sequences',
            'id' => (string) $journalSequence->id,
            'attributes' => [
                'name' => 'Partially Updated Name'
                // description should remain unchanged
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-sequences')
            ->withData($data)
            ->patch("/api/v1/journal-sequences/{$journalSequence->id}");

        $response->assertOk();
        
        $this->assertDatabaseHas('journal_sequences', [
            'id' => $journalSequence->id,
            'name' => 'Partially Updated Name',
            'description' => 'Original Description'
        ]);
    }

    public function test_admin_can_update_JournalSequence_metadata(): void
    {
        $admin = $this->getAdminUser();
        $journalSequence = JournalSequence::factory()->create();

        $metadata = [
            'updated_field' => 'new_value',
            'priority' => 'urgent',
            'tags' => ['important', 'updated']
        ];

        $data = [
            'type' => 'journal-sequences',
            'id' => (string) $journalSequence->id,
            'attributes' => [
                'metadata' => $metadata
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-sequences')
            ->withData($data)
            ->patch("/api/v1/journal-sequences/{$journalSequence->id}");

        $response->assertOk();
        
        $journalSequence->refresh();
        $this->assertEquals($metadata, $journalSequence->metadata);
    }

    public function test_customer_user_cannot_update_JournalSequence(): void
    {
        $customer = $this->getCustomerUser();
        $journalSequence = JournalSequence::factory()->create();

        $data = [
            'type' => 'journal-sequences',
            'id' => (string) $journalSequence->id,
            'attributes' => [
                'name' => 'Unauthorized Update'
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('journal-sequences')
            ->withData($data)
            ->patch("/api/v1/journal-sequences/{$journalSequence->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_update_JournalSequence(): void
    {
        $journalSequence = JournalSequence::factory()->create();

        $data = [
            'type' => 'journal-sequences',
            'id' => (string) $journalSequence->id,
            'attributes' => [
                'name' => 'Guest Update'
            ]
        ];

        $response = $this->jsonApi()
            ->expects('journal-sequences')
            ->withData($data)
            ->patch("/api/v1/journal-sequences/{$journalSequence->id}");

        $response->assertStatus(401);
    }

    public function test_cannot_update_nonexistent_JournalSequence(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'journal-sequences',
            'id' => '999999',
            'attributes' => [
                'name' => 'Nonexistent Update'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-sequences')
            ->withData($data)
            ->patch('/api/v1/journal-sequences/999999');

        $response->assertStatus(404);
    }

    public function test_cannot_update_JournalSequence_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();
        $journalSequence = JournalSequence::factory()->create();

        $data = [
            'type' => 'journal-sequences',
            'id' => (string) $journalSequence->id,
            'attributes' => [
                'name' => '', // Empty name
                'is_active' => 'invalid_boolean'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-sequences')
            ->withData($data)
            ->patch("/api/v1/journal-sequences/{$journalSequence->id}");

        $response->assertStatus(422);
    }
}
