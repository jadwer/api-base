<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Accounting\Models\JournalLine;

class JournalLineShowTest extends TestCase
{



    public function test_admin_can_view_JournalLine(): void
    {
        $admin = $this->getAdminUser();
        $journalLine = JournalLine::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-lines')
            ->get("/api/v1/journal-lines/{$journalLine->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                        'journalEntryId',
                        'accountId',
                        'contactId',
                        'debit',
                        'credit',
                        'description',
                        'reference',
                        'metadata',
                    'createdAt',
                    'updatedAt'
                ]
            ]
        ]);
    }

    public function test_admin_can_view_JournalLine_with_specific_data(): void
    {
        $admin = $this->getAdminUser();
        
        $journalLine = JournalLine::factory()->create(['debit' => 99.99, 'credit' => 99.99, 'description' => 'test description', 'reference' => 'test string', 'metadata' => 'test value']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-lines')
            ->get("/api/v1/journal-lines/{$journalLine->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                        'journalEntryId',
                        'accountId',
                        'contactId',
                        'debit',
                        'credit',
                        'description',
                        'reference',
                        'metadata',
                    'createdAt',
                    'updatedAt'
                ]
            ]
        ]);
    }

    public function test_tech_user_can_view_JournalLine_with_permission(): void
    {
        $tech = $this->getTechUser();
        $journalLine = JournalLine::factory()->create();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('journal-lines')
            ->get("/api/v1/journal-lines/{$journalLine->id}");

        $response->assertOk();
    }

    public function test_customer_user_cannot_view_JournalLine(): void
    {
        $customer = $this->getCustomerUser();
        $journalLine = JournalLine::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('journal-lines')
            ->get("/api/v1/journal-lines/{$journalLine->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_view_JournalLine(): void
    {
        $journalLine = JournalLine::factory()->create();

        $response = $this->jsonApi()
            ->expects('journal-lines')
            ->get("/api/v1/journal-lines/{$journalLine->id}");

        $response->assertStatus(401);
    }

    public function test_returns_404_for_nonexistent_JournalLine(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-lines')
            ->get('/api/v1/journal-lines/999999');

        $response->assertStatus(404);
    }

    public function test_response_includes_timestamps(): void
    {
        $admin = $this->getAdminUser();
        $journalLine = JournalLine::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-lines')
            ->get("/api/v1/journal-lines/{$journalLine->id}");

        $response->assertOk();
        
        $this->assertNotNull($response->json('data.attributes.createdAt'));
        $this->assertNotNull($response->json('data.attributes.updatedAt'));
    }
}
