<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\Accounting\Models\JournalEntry;

class JournalEntryStoreTest extends TestCase
{
    public function test_admin_can_create_JournalEntry(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'journal-entries',
            'attributes' => [
                'number' => 'JE-001',
                'date' => '2025-01-01',
                'status' => 'draft'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-entries')
            ->withData($data)
            ->post('/api/v1/journal-entries');

        $response->assertCreated();

        $this->assertDatabaseHas('journal_entries', [
            'number' => 'JE-001',
            'date' => '2025-01-01',
            'status' => 'draft'
        ]);
    }

    public function test_admin_can_create_JournalEntry_with_minimal_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'journal-entries',
            'attributes' => [
                'number' => 'JE-MIN',
                'date' => '2025-01-01'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-entries')
            ->withData($data)
            ->post('/api/v1/journal-entries');

        $response->assertCreated();
    }

    public function test_customer_user_cannot_create_JournalEntry(): void
    {
        $customer = $this->getCustomerUser();

        $data = [
            'type' => 'journal-entries',
            'attributes' => [
                'number' => 'JE-FORBIDDEN'
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('journal-entries')
            ->withData($data)
            ->post('/api/v1/journal-entries');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_create_JournalEntry(): void
    {
        $data = [
            'type' => 'journal-entries',
            'attributes' => [
                'number' => 'JE-FORBIDDEN'
            ]
        ];

        $response = $this->jsonApi()
            ->expects('journal-entries')
            ->withData($data)
            ->post('/api/v1/journal-entries');

        $response->assertStatus(401);
    }

    public function test_cannot_create_JournalEntry_without_required_fields(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'journal-entries',
            'attributes' => [
                // Missing required fields
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-entries')
            ->withData($data)
            ->post('/api/v1/journal-entries');

        $response->assertStatus(422);
    }

    public function test_cannot_create_JournalEntry_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'journal-entries',
            'attributes' => [
                'number' => '',
                'date' => 'invalid-date'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-entries')
            ->withData($data)
            ->post('/api/v1/journal-entries');

        $response->assertStatus(422);
    }
}
