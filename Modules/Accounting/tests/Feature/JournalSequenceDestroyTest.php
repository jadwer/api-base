<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Accounting\Models\JournalSequence;

class JournalSequenceDestroyTest extends TestCase
{



    public function test_admin_can_delete_JournalSequence(): void
    {
        $admin = $this->getAdminUser();
        $journalSequence = JournalSequence::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-sequences')
            ->delete("/api/v1/journal-sequences/{$journalSequence->id}");

        $response->assertNoContent();
        
        $this->assertDatabaseMissing('journal_sequences', [
            'id' => $journalSequence->id
        ]);
    }

    public function test_admin_can_delete_JournalSequence_with_metadata(): void
    {
        $admin = $this->getAdminUser();
        $journalSequence = JournalSequence::factory()->create([
            'metadata' => [
                'priority' => 'high',
                'source' => 'import'
            ]
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-sequences')
            ->delete("/api/v1/journal-sequences/{$journalSequence->id}");

        $response->assertNoContent();
        
        $this->assertDatabaseMissing('journal_sequences', [
            'id' => $journalSequence->id
        ]);
    }

    public function test_can_delete_JournalSequence_with_custom_year(): void
    {
        $admin = $this->getAdminUser();
        $journalSequence = JournalSequence::factory()->create([
            'fiscal_year' => 2024,
            'current_number' => 100
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-sequences')
            ->delete("/api/v1/journal-sequences/{$journalSequence->id}");

        $response->assertNoContent();
        
        $this->assertDatabaseMissing('journal_sequences', [
            'id' => $journalSequence->id
        ]);
    }

    public function test_customer_user_cannot_delete_JournalSequence(): void
    {
        $customer = $this->getCustomerUser();
        $journalSequence = JournalSequence::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('journal-sequences')
            ->delete("/api/v1/journal-sequences/{$journalSequence->id}");

        $response->assertStatus(403);
        
        $this->assertDatabaseHas('journal_sequences', [
            'id' => $journalSequence->id
        ]);
    }

    public function test_guest_cannot_delete_JournalSequence(): void
    {
        $journalSequence = JournalSequence::factory()->create();

        $response = $this->jsonApi()
            ->expects('journal-sequences')
            ->delete("/api/v1/journal-sequences/{$journalSequence->id}");

        $response->assertStatus(401);
        
        $this->assertDatabaseHas('journal_sequences', [
            'id' => $journalSequence->id
        ]);
    }

    public function test_returns_404_when_deleting_nonexistent_JournalSequence(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-sequences')
            ->delete('/api/v1/journal-sequences/999999');

        $response->assertStatus(404);
    }

    public function test_delete_response_is_empty(): void
    {
        $admin = $this->getAdminUser();
        $journalSequence = JournalSequence::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-sequences')
            ->delete("/api/v1/journal-sequences/{$journalSequence->id}");

        $response->assertNoContent();
        $this->assertEmpty($response->getContent());
    }

    public function test_multiple_deletes_are_idempotent(): void
    {
        $admin = $this->getAdminUser();
        $journalSequence = JournalSequence::factory()->create();

        // First delete
        $response1 = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-sequences')
            ->delete("/api/v1/journal-sequences/{$journalSequence->id}");

        $response1->assertNoContent();

        // Second delete (should return 404)
        $response2 = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-sequences')
            ->delete("/api/v1/journal-sequences/{$journalSequence->id}");

        $response2->assertStatus(404);
    }
}
