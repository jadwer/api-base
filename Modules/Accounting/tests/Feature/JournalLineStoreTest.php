<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\Accounting\Models\JournalLine;

class JournalLineStoreTest extends TestCase
{
    public function test_admin_can_create_JournalLine(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'journal-lines',
            'attributes' => [
                'debit' => 1000.00,
                'credit' => 0.00,
                'description' => 'Test line'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-lines')
            ->withData($data)
            ->post('/api/v1/journal-lines');

        $response->assertCreated();

        $this->assertDatabaseHas('journal_lines', [
            'debit' => 1000.00,
            'credit' => 0.00,
            'description' => 'Test line'
        ]);
    }

    public function test_admin_can_create_JournalLine_with_minimal_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'journal-lines',
            'attributes' => [
                'debit' => 100.00
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-lines')
            ->withData($data)
            ->post('/api/v1/journal-lines');

        $response->assertCreated();
    }

    public function test_customer_user_cannot_create_JournalLine(): void
    {
        $customer = $this->getCustomerUser();

        $data = [
            'type' => 'journal-lines',
            'attributes' => [
                'debit' => 999999.00
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('journal-lines')
            ->withData($data)
            ->post('/api/v1/journal-lines');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_create_JournalLine(): void
    {
        $data = [
            'type' => 'journal-lines',
            'attributes' => [
                'debit' => 999999.00
            ]
        ];

        $response = $this->jsonApi()
            ->expects('journal-lines')
            ->withData($data)
            ->post('/api/v1/journal-lines');

        $response->assertStatus(401);
    }

    public function test_cannot_create_JournalLine_without_required_fields(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'journal-lines',
            'attributes' => [
                // Missing required fields
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-lines')
            ->withData($data)
            ->post('/api/v1/journal-lines');

        $response->assertStatus(422);
    }

    public function test_cannot_create_JournalLine_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'journal-lines',
            'attributes' => [
                'debit' => 'invalid',
                'credit' => 'invalid'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-lines')
            ->withData($data)
            ->post('/api/v1/journal-lines');

        $response->assertStatus(422);
    }
}
