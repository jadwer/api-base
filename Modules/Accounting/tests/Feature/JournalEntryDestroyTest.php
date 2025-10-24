<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Accounting\Models\JournalEntry;

class JournalEntryDestroyTest extends TestCase
{



    public function test_admin_can_delete_JournalEntry(): void
    {
        $admin = $this->getAdminUser();
        $journalEntry = JournalEntry::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-entries')
            ->delete("/api/v1/journal-entries/{$journalEntry->id}");

        $response->assertNoContent();
        
        $this->assertDatabaseMissing('journal_entries', [
            'id' => $journalEntry->id
        ]);
    }

    public function test_admin_can_delete_JournalEntry_with_metadata(): void
    {
        $admin = $this->getAdminUser();
        $journalEntry = JournalEntry::factory()->create([
            'metadata' => [
                'priority' => 'high',
                'source' => 'import'
            ]
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-entries')
            ->delete("/api/v1/journal-entries/{$journalEntry->id}");

        $response->assertNoContent();
        
        $this->assertDatabaseMissing('journal_entries', [
            'id' => $journalEntry->id
        ]);
    }

    public function test_can_delete_posted_JournalEntry(): void
    {
        $admin = $this->getAdminUser();
        $journalEntry = JournalEntry::factory()->posted()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-entries')
            ->delete("/api/v1/journal-entries/{$journalEntry->id}");

        $response->assertNoContent();
        
        $this->assertDatabaseMissing('journal_entries', [
            'id' => $journalEntry->id
        ]);
    }

    public function test_customer_user_cannot_delete_JournalEntry(): void
    {
        $customer = $this->getCustomerUser();
        $journalEntry = JournalEntry::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('journal-entries')
            ->delete("/api/v1/journal-entries/{$journalEntry->id}");

        $response->assertStatus(403);
        
        $this->assertDatabaseHas('journal_entries', [
            'id' => $journalEntry->id
        ]);
    }

    public function test_guest_cannot_delete_JournalEntry(): void
    {
        $journalEntry = JournalEntry::factory()->create();

        $response = $this->jsonApi()
            ->expects('journal-entries')
            ->delete("/api/v1/journal-entries/{$journalEntry->id}");

        $response->assertStatus(401);
        
        $this->assertDatabaseHas('journal_entries', [
            'id' => $journalEntry->id
        ]);
    }

    public function test_returns_404_when_deleting_nonexistent_JournalEntry(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-entries')
            ->delete('/api/v1/journal-entries/999999');

        $response->assertStatus(404);
    }

    public function test_delete_response_is_empty(): void
    {
        $admin = $this->getAdminUser();
        $journalEntry = JournalEntry::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-entries')
            ->delete("/api/v1/journal-entries/{$journalEntry->id}");

        $response->assertNoContent();
        $this->assertEmpty($response->getContent());
    }

    public function test_multiple_deletes_are_idempotent(): void
    {
        $admin = $this->getAdminUser();
        $journalEntry = JournalEntry::factory()->create();

        // First delete
        $response1 = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-entries')
            ->delete("/api/v1/journal-entries/{$journalEntry->id}");

        $response1->assertNoContent();

        // Second delete (should return 404)
        $response2 = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-entries')
            ->delete("/api/v1/journal-entries/{$journalEntry->id}");

        $response2->assertStatus(404);
    }
}
