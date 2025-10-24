<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Accounting\Models\Journal;

class JournalDestroyTest extends TestCase
{



    public function test_admin_can_delete_Journal(): void
    {
        $admin = $this->getAdminUser();
        $journal = Journal::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journals')
            ->delete("/api/v1/journals/{$journal->id}");

        $response->assertNoContent();
        
        $this->assertDatabaseMissing('journals', [
            'id' => $journal->id
        ]);
    }

    public function test_admin_can_delete_Journal_with_metadata(): void
    {
        $admin = $this->getAdminUser();
        $journal = Journal::factory()->create([
            'metadata' => [
                'priority' => 'high',
                'source' => 'import'
            ]
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journals')
            ->delete("/api/v1/journals/{$journal->id}");

        $response->assertNoContent();
        
        $this->assertDatabaseMissing('journals', [
            'id' => $journal->id
        ]);
    }

    public function test_can_delete_inactive_Journal(): void
    {
        $admin = $this->getAdminUser();
        $journal = Journal::factory()->inactive()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journals')
            ->delete("/api/v1/journals/{$journal->id}");

        $response->assertNoContent();
        
        $this->assertDatabaseMissing('journals', [
            'id' => $journal->id
        ]);
    }

    public function test_customer_user_cannot_delete_Journal(): void
    {
        $customer = $this->getCustomerUser();
        $journal = Journal::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('journals')
            ->delete("/api/v1/journals/{$journal->id}");

        $response->assertStatus(403);
        
        $this->assertDatabaseHas('journals', [
            'id' => $journal->id
        ]);
    }

    public function test_guest_cannot_delete_Journal(): void
    {
        $journal = Journal::factory()->create();

        $response = $this->jsonApi()
            ->expects('journals')
            ->delete("/api/v1/journals/{$journal->id}");

        $response->assertStatus(401);
        
        $this->assertDatabaseHas('journals', [
            'id' => $journal->id
        ]);
    }

    public function test_returns_404_when_deleting_nonexistent_Journal(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journals')
            ->delete('/api/v1/journals/999999');

        $response->assertStatus(404);
    }

    public function test_delete_response_is_empty(): void
    {
        $admin = $this->getAdminUser();
        $journal = Journal::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journals')
            ->delete("/api/v1/journals/{$journal->id}");

        $response->assertNoContent();
        $this->assertEmpty($response->getContent());
    }

    public function test_multiple_deletes_are_idempotent(): void
    {
        $admin = $this->getAdminUser();
        $journal = Journal::factory()->create();

        // First delete
        $response1 = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journals')
            ->delete("/api/v1/journals/{$journal->id}");

        $response1->assertNoContent();

        // Second delete (should return 404)
        $response2 = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journals')
            ->delete("/api/v1/journals/{$journal->id}");

        $response2->assertStatus(404);
    }
}
