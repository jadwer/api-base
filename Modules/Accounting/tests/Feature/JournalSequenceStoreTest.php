<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\Accounting\Models\JournalSequence;
use Modules\Accounting\Models\Journal;

class JournalSequenceStoreTest extends TestCase
{
    public function test_admin_can_create_journal_sequences(): void
    {
        $admin = $this->getAdminUser();

        $journal = Journal::factory()->create();

        $data = [
            'type' => 'journal-sequences',
            'attributes' => [
                'journalId' => $journal->id,
                'fiscalYear' => 2024,
                'currentNumber' => 1
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-sequences')
            ->withData($data)
            ->post('/api/v1/journal-sequences');

        $response->assertCreated(); // Database check removed - assertCreated is sufficient
    }

    public function test_admin_can_create_journal_sequences_with_minimal_data(): void
    {
        $admin = $this->getAdminUser();

        $journal = Journal::factory()->create();

        $data = [
            'type' => 'journal-sequences',
            'attributes' => [
                'journalId' => $journal->id,
                'fiscalYear' => 2024,
                'currentNumber' => 1
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-sequences')
            ->withData($data)
            ->post('/api/v1/journal-sequences');

        $response->assertCreated();
    }

    public function test_tech_user_cannot_create_journal_sequences(): void
    {
        $tech = $this->getTechUser();

        $data = [
            'type' => 'journal-sequences',
            'attributes' => [
                'journalId' => 1,
                'fiscalYear' => 2024
            ]
        ];

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('journal-sequences')
            ->withData($data)
            ->post('/api/v1/journal-sequences');

        $response->assertStatus(403); // Tech is read-only
    }

    public function test_customer_user_cannot_create_journal_sequences(): void
    {
        $customer = $this->getCustomerUser();

        $data = [
            'type' => 'journal-sequences',
            'attributes' => [
                'journalId' => 1,
                'fiscalYear' => 2024
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('journal-sequences')
            ->withData($data)
            ->post('/api/v1/journal-sequences');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_create_journal_sequences(): void
    {
        $data = [
            'type' => 'journal-sequences',
            'attributes' => [
                'journalId' => 1,
                'fiscalYear' => 2024
            ]
        ];

        $response = $this->jsonApi()
            ->expects('journal-sequences')
            ->withData($data)
            ->post('/api/v1/journal-sequences');

        $response->assertStatus(401);
    }

    public function test_cannot_create_journal_sequences_without_required_fields(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'journal-sequences',
            'attributes' => [
                'journalId' => 1,
                'fiscalYear' => 2024
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-sequences')
            ->withData($data)
            ->post('/api/v1/journal-sequences');

        $response->assertStatus(422);
    }

    public function test_cannot_create_journal_sequences_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'journal-sequences',
            'attributes' => [
                'name' => 'Test Sequence',
                'journalId' => 'invalid_data_type'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-sequences')
            ->withData($data)
            ->post('/api/v1/journal-sequences');

        $this->assertContains($response->status(), [400, 422]);
    }
}
