<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Accounting\Models\JournalEntry;

class JournalEntryUpdateTest extends TestCase
{
    private function getAdminUser(): User
    {
        return User::where('email', 'admin@example.com')->firstOrFail();
    }

    private function getTechUser(): User
    {
        return User::where('email', 'tech@example.com')->firstOrFail();
    }

    private function getCustomerUser(): User
    {
        return User::where('email', 'customer@example.com')->firstOrFail();
    }

    public function test_admin_can_update_JournalEntry(): void
    {
        $admin = $this->getAdminUser();
        $journalEntry = JournalEntry::factory()->create();

        $data = [
            'type' => 'journal-entries',
            'id' => (string) $journalEntry->id,
            'attributes' => [
                'name' => 'Updated JournalEntry',
                'description' => 'Updated description',
                'is_active' => false
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
            'name' => 'Updated JournalEntry',
            'description' => 'Updated description',
            'is_active' => false
        ]);
    }

    public function test_admin_can_partially_update_JournalEntry(): void
    {
        $admin = $this->getAdminUser();
        $journalEntry = JournalEntry::factory()->create([
            'name' => 'Original Name',
            'description' => 'Original Description'
        ]);

        $data = [
            'type' => 'journal-entries',
            'id' => (string) $journalEntry->id,
            'attributes' => [
                'name' => 'Partially Updated Name'
                // description should remain unchanged
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
            'name' => 'Partially Updated Name',
            'description' => 'Original Description'
        ]);
    }

    public function test_admin_can_update_JournalEntry_metadata(): void
    {
        $admin = $this->getAdminUser();
        $journalEntry = JournalEntry::factory()->create();

        $metadata = [
            'updated_field' => 'new_value',
            'priority' => 'urgent',
            'tags' => ['important', 'updated']
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
                'name' => 'Unauthorized Update'
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
                'name' => 'Guest Update'
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
                'name' => 'Nonexistent Update'
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
                'name' => '', // Empty name
                'is_active' => 'invalid_boolean'
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
