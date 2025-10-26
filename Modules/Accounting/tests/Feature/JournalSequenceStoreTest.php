<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\Accounting\Models\JournalSequence;

class JournalSequenceStoreTest extends TestCase
{
    public function test_admin_can_create_JournalSequence(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'journal-sequences',
            'attributes' => [
                'fiscalYear' => 2025,
                'currentNumber' => 1
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-sequences')
            ->withData($data)
            ->post('/api/v1/journal-sequences');

        $response->assertCreated();

        $this->assertDatabaseHas('journal_sequences', [
            'fiscal_year' => 2025,
            'current_number' => 1
        ]);
    }

    public function test_admin_can_create_JournalSequence_with_minimal_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'journal-sequences',
            'attributes' => [
                'fiscalYear' => 2025
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-sequences')
            ->withData($data)
            ->post('/api/v1/journal-sequences');

        $response->assertCreated();
    }

    public function test_customer_user_cannot_create_JournalSequence(): void
    {
        $customer = $this->getCustomerUser();

        $data = [
            'type' => 'journal-sequences',
            'attributes' => [
                'fiscalYear' => 2099
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('journal-sequences')
            ->withData($data)
            ->post('/api/v1/journal-sequences');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_create_JournalSequence(): void
    {
        $data = [
            'type' => 'journal-sequences',
            'attributes' => [
                'fiscalYear' => 2099
            ]
        ];

        $response = $this->jsonApi()
            ->expects('journal-sequences')
            ->withData($data)
            ->post('/api/v1/journal-sequences');

        $response->assertStatus(401);
    }

    public function test_cannot_create_JournalSequence_without_required_fields(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'journal-sequences',
            'attributes' => [
                // Missing required fields
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-sequences')
            ->withData($data)
            ->post('/api/v1/journal-sequences');

        $response->assertStatus(422);
    }

    public function test_cannot_create_JournalSequence_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'journal-sequences',
            'attributes' => [
                'fiscalYear' => 'invalid',
                'currentNumber' => 'invalid'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-sequences')
            ->withData($data)
            ->post('/api/v1/journal-sequences');

        $response->assertStatus(422);
    }
}
