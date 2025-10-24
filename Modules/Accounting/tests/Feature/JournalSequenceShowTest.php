<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Accounting\Models\JournalSequence;

class JournalSequenceShowTest extends TestCase
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

    public function test_admin_can_view_JournalSequence(): void
    {
        $admin = $this->getAdminUser();
        $journalSequence = JournalSequence::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-sequences')
            ->get("/api/v1/journal-sequences/{$journalSequence->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                        'journalId',
                        'fiscalYear',
                        'currentNumber',
                        'metadata',
                    'createdAt',
                    'updatedAt'
                ]
            ]
        ]);
    }

    public function test_admin_can_view_JournalSequence_with_specific_data(): void
    {
        $admin = $this->getAdminUser();
        
        $journalSequence = JournalSequence::factory()->create(['fiscal_year' => 100, 'current_number' => 100, 'metadata' => 'test value']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-sequences')
            ->get("/api/v1/journal-sequences/{$journalSequence->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                        'journalId',
                        'fiscalYear',
                        'currentNumber',
                        'metadata',
                    'createdAt',
                    'updatedAt'
                ]
            ]
        ]);
    }

    public function test_tech_user_can_view_JournalSequence_with_permission(): void
    {
        $tech = $this->getTechUser();
        $journalSequence = JournalSequence::factory()->create();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('journal-sequences')
            ->get("/api/v1/journal-sequences/{$journalSequence->id}");

        $response->assertOk();
    }

    public function test_customer_user_cannot_view_JournalSequence(): void
    {
        $customer = $this->getCustomerUser();
        $journalSequence = JournalSequence::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('journal-sequences')
            ->get("/api/v1/journal-sequences/{$journalSequence->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_view_JournalSequence(): void
    {
        $journalSequence = JournalSequence::factory()->create();

        $response = $this->jsonApi()
            ->expects('journal-sequences')
            ->get("/api/v1/journal-sequences/{$journalSequence->id}");

        $response->assertStatus(401);
    }

    public function test_returns_404_for_nonexistent_JournalSequence(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-sequences')
            ->get('/api/v1/journal-sequences/999999');

        $response->assertStatus(404);
    }

    public function test_response_includes_timestamps(): void
    {
        $admin = $this->getAdminUser();
        $journalSequence = JournalSequence::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-sequences')
            ->get("/api/v1/journal-sequences/{$journalSequence->id}");

        $response->assertOk();
        
        $this->assertNotNull($response->json('data.attributes.createdAt'));
        $this->assertNotNull($response->json('data.attributes.updatedAt'));
    }
}
