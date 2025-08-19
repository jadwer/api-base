<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Accounting\Models\JournalEntry;

class JournalEntryShowTest extends TestCase
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

    public function test_admin_can_view_JournalEntry(): void
    {
        $admin = $this->getAdminUser();
        $journalEntry = JournalEntry::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-entries')
            ->get("/api/v1/journal-entries/{$journalEntry->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                        'journalId',
                        'periodId',
                        'number',
                        'date',
                        'currency',
                        'exchangeRate',
                        'reference',
                        'description',
                        'status',
                        'approvedById',
                        'postedById',
                        'postedAt',
                        'reversalOfId',
                        'sourceType',
                        'sourceId',
                    'createdAt',
                    'updatedAt'
                ]
            ]
        ]);
    }

    public function test_admin_can_view_JournalEntry_with_specific_data(): void
    {
        $admin = $this->getAdminUser();
        
        $journalEntry = JournalEntry::factory()->create(['number' => 'test string', 'date' => now(), 'currency' => 'test string', 'exchange_rate' => 99.99, 'reference' => 'test string', 'description' => 'test description', 'status' => 'active', 'posted_at' => now(), 'source_type' => 'test string']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-entries')
            ->get("/api/v1/journal-entries/{$journalEntry->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                        'journalId',
                        'periodId',
                        'number',
                        'date',
                        'currency',
                        'exchangeRate',
                        'reference',
                        'description',
                        'status',
                        'approvedById',
                        'postedById',
                        'postedAt',
                        'reversalOfId',
                        'sourceType',
                        'sourceId',
                    'createdAt',
                    'updatedAt'
                ]
            ]
        ]);
    }

    public function test_tech_user_can_view_JournalEntry_with_permission(): void
    {
        $tech = $this->getTechUser();
        $journalEntry = JournalEntry::factory()->create();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('journal-entries')
            ->get("/api/v1/journal-entries/{$journalEntry->id}");

        $response->assertOk();
    }

    public function test_customer_user_cannot_view_JournalEntry(): void
    {
        $customer = $this->getCustomerUser();
        $journalEntry = JournalEntry::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('journal-entries')
            ->get("/api/v1/journal-entries/{$journalEntry->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_view_JournalEntry(): void
    {
        $journalEntry = JournalEntry::factory()->create();

        $response = $this->jsonApi()
            ->expects('journal-entries')
            ->get("/api/v1/journal-entries/{$journalEntry->id}");

        $response->assertStatus(401);
    }

    public function test_returns_404_for_nonexistent_JournalEntry(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-entries')
            ->get('/api/v1/journal-entries/999999');

        $response->assertStatus(404);
    }

    public function test_response_includes_timestamps(): void
    {
        $admin = $this->getAdminUser();
        $journalEntry = JournalEntry::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-entries')
            ->get("/api/v1/journal-entries/{$journalEntry->id}");

        $response->assertOk();
        
        $this->assertNotNull($response->json('data.attributes.createdAt'));
        $this->assertNotNull($response->json('data.attributes.updatedAt'));
    }
}
