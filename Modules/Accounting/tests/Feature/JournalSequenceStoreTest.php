<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Accounting\Models\JournalSequence;

class JournalSequenceStoreTest extends TestCase
{



    public function test_admin_can_create_JournalSequence(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'journal-sequences',
            'attributes' => [
                'fiscalYear' => 100,
                'currentNumber' => 100,
                'metadata' => 'test value'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-sequences')
            ->withData($data)
            ->post('/api/v1/journal-sequences');

        $response->assertCreated();
        
        $this->assertDatabaseHas('journal_sequences', ['fiscal_year' => 100, 'current_number' => 100, 'metadata' => 'test value']);
    }

    public function test_admin_can_create_JournalSequence_with_minimal_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'journal-sequences',
            'attributes' => [

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
                'name' => 'Unauthorized JournalSequence',
                'is_active' => true
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
                'name' => 'Guest JournalSequence',
                'is_active' => true
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
                'description' => 'Missing name'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-sequences')
            ->withData($data)
            ->post('/api/v1/journal-sequences');

        $response->assertStatus(422);
        $this->assertJsonApiValidationErrors(['/data/attributes/name'], $response);
    }

    public function test_cannot_create_JournalSequence_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'journal-sequences',
            'attributes' => [
                'name' => '', // Empty name
                'is_active' => 'not_boolean' // Invalid boolean
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
