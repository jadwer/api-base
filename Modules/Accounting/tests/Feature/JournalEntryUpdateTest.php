<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\Accounting\Models\JournalEntry;

class JournalEntryUpdateTest extends TestCase
{
    public function test_admin_can_update_JournalEntry(): void
    {
        $admin = $this->getAdminUser();
        $journalEntry = JournalEntry::factory()->create();

        $data = [
            'type' => 'journal-entries',
            'id' => (string) $journalEntry->id,
            'attributes' => [
                'number' => 'JE-UPD',
                'date' => '2025-06-01'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-entries')
            ->withData($data)
            ->patch("/api/v1/journal-entries/{$journalEntry->id}");

        $response->assertOk();

        $this->assertDatabaseHas('journal_entries', [
            'id' => $journalEntry->id,
            'number' => 'JE-UPD',
            'date' => '2025-06-01'
        ]);
    }

    public function test_admin_can_partially_update_JournalEntry(): void
    {
        $admin = $this->getAdminUser();
        $journalEntry = JournalEntry::factory()->create([
            'number' => 'JE-OLD',
            'date' => '2025-01-01'
        ]);

        $data = [
            'type' => 'journal-entries',
            'id' => (string) $journalEntry->id,
            'attributes' => [
                'number' => 'JE-PART'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-entries')
            ->withData($data)
            ->patch("/api/v1/journal-entries/{$journalEntry->id}");

        $response->assertOk();

        $this->assertDatabaseHas('journal_entries', [
            'id' => $journalEntry->id,
            'number' => 'JE-OLD',
            'date' => '2025-01-01'
        ]);
    }

    public function test_admin_can_update_JournalEntry_metadata(): void
    {
        $admin = $this->getAdminUser();
        $journalEntry = JournalEntry::factory()->create();

        $metadata = [
            'updated_field' => 'new_value',
            'priority' => 'urgent'
        ];

        $data = [
            'type' => 'journal-entries',
            'id' => (string) $journalEntry->id,
            'attributes' => [
                'metadata' => $metadata
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-entries')
            ->withData($data)
            ->patch("/api/v1/journal-entries/{$journalEntry->id}");

        $response->assertOk();

        $journalEntry->refresh();
        $this->assertEquals($metadata, $journalEntry->metadata);
    }

    public function test_customer_user_cannot_update_JournalEntry(): void
    {
        $customer = $this->getCustomerUser();
        $journalEntry = JournalEntry::factory()->create();

        $data = [
            'type' => 'journal-entries',
            'id' => (string) $journalEntry->id,
            'attributes' => [
                'number' => 'JE-FORBIDDEN'
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('journal-entries')
            ->withData($data)
            ->patch("/api/v1/journal-entries/{$journalEntry->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_update_JournalEntry(): void
    {
        $journalEntry = JournalEntry::factory()->create();

        $data = [
            'type' => 'journal-entries',
            'id' => (string) $journalEntry->id,
            'attributes' => [
                'number' => 'JE-FORBIDDEN'
            ]
        ];

        $response = $this->jsonApi()
            ->expects('journal-entries')
            ->withData($data)
            ->patch("/api/v1/journal-entries/{$journalEntry->id}");

        $response->assertStatus(401);
    }

    public function test_cannot_update_nonexistent_JournalEntry(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'journal-entries',
            'id' => '999999',
            'attributes' => [
                'number' => 'JE-FORBIDDEN'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-entries')
            ->withData($data)
            ->patch('/api/v1/journal-entries/999999');

        $response->assertStatus(404);
    }

    public function test_cannot_update_JournalEntry_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();
        $journalEntry = JournalEntry::factory()->create();

        $data = [
            'type' => 'journal-entries',
            'id' => (string) $journalEntry->id,
            'attributes' => [
                'number' => '',
                'date' => 'invalid-date'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-entries')
            ->withData($data)
            ->patch("/api/v1/journal-entries/{$journalEntry->id}");

        $response->assertStatus(422);
    }
}
