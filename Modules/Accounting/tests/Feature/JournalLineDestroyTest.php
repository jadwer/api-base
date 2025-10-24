<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Accounting\Models\JournalLine;

class JournalLineDestroyTest extends TestCase
{



    public function test_admin_can_delete_JournalLine(): void
    {
        $admin = $this->getAdminUser();
        $journalLine = JournalLine::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-lines')
            ->delete("/api/v1/journal-lines/{$journalLine->id}");

        $response->assertNoContent();
        
        $this->assertDatabaseMissing('journal_lines', [
            'id' => $journalLine->id
        ]);
    }

    public function test_admin_can_delete_JournalLine_with_metadata(): void
    {
        $admin = $this->getAdminUser();
        $journalLine = JournalLine::factory()->create([
            'metadata' => [
                'priority' => 'high',
                'source' => 'import'
            ]
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-lines')
            ->delete("/api/v1/journal-lines/{$journalLine->id}");

        $response->assertNoContent();
        
        $this->assertDatabaseMissing('journal_lines', [
            'id' => $journalLine->id
        ]);
    }

    public function test_can_delete_JournalLine_with_reference(): void
    {
        $admin = $this->getAdminUser();
        $journalLine = JournalLine::factory()->create([
            'reference' => 'INV-2024-001',
            'description' => 'Test journal line'
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-lines')
            ->delete("/api/v1/journal-lines/{$journalLine->id}");

        $response->assertNoContent();
        
        $this->assertDatabaseMissing('journal_lines', [
            'id' => $journalLine->id
        ]);
    }

    public function test_customer_user_cannot_delete_JournalLine(): void
    {
        $customer = $this->getCustomerUser();
        $journalLine = JournalLine::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('journal-lines')
            ->delete("/api/v1/journal-lines/{$journalLine->id}");

        $response->assertStatus(403);
        
        $this->assertDatabaseHas('journal_lines', [
            'id' => $journalLine->id
        ]);
    }

    public function test_guest_cannot_delete_JournalLine(): void
    {
        $journalLine = JournalLine::factory()->create();

        $response = $this->jsonApi()
            ->expects('journal-lines')
            ->delete("/api/v1/journal-lines/{$journalLine->id}");

        $response->assertStatus(401);
        
        $this->assertDatabaseHas('journal_lines', [
            'id' => $journalLine->id
        ]);
    }

    public function test_returns_404_when_deleting_nonexistent_JournalLine(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-lines')
            ->delete('/api/v1/journal-lines/999999');

        $response->assertStatus(404);
    }

    public function test_delete_response_is_empty(): void
    {
        $admin = $this->getAdminUser();
        $journalLine = JournalLine::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-lines')
            ->delete("/api/v1/journal-lines/{$journalLine->id}");

        $response->assertNoContent();
        $this->assertEmpty($response->getContent());
    }

    public function test_multiple_deletes_are_idempotent(): void
    {
        $admin = $this->getAdminUser();
        $journalLine = JournalLine::factory()->create();

        // First delete
        $response1 = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-lines')
            ->delete("/api/v1/journal-lines/{$journalLine->id}");

        $response1->assertNoContent();

        // Second delete (should return 404)
        $response2 = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-lines')
            ->delete("/api/v1/journal-lines/{$journalLine->id}");

        $response2->assertStatus(404);
    }
}
