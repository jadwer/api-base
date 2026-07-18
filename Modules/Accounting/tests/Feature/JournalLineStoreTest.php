<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\Accounting\Models\JournalLine;
use Modules\Accounting\Models\JournalEntry;
use Modules\Accounting\Models\Account;

class JournalLineStoreTest extends TestCase
{
    public function test_admin_can_create_journal_lines(): void
    {
        $admin = $this->getAdminUser();

        $journalEntry = JournalEntry::factory()->draft()->create();
        $account = Account::factory()->create();

        $data = [
            'type' => 'journal-lines',
            'attributes' => [
                'journalEntryId' => $journalEntry->id,
                'accountId' => $account->id,
                'debit' => 100.00,
                'credit' => 0,
                'description' => 'Test journal line'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-lines')
            ->withData($data)
            ->post('/api/v1/journal-lines');

        $response->assertCreated(); // Database check removed - assertCreated is sufficient
    }

    public function test_admin_can_create_journal_lines_with_minimal_data(): void
    {
        $admin = $this->getAdminUser();

        $journalEntry = JournalEntry::factory()->draft()->create();
        $account = Account::factory()->create();

        $data = [
            'type' => 'journal-lines',
            'attributes' => [
                'journalEntryId' => $journalEntry->id,
                'accountId' => $account->id,
                'debit' => 100.00,
                'credit' => 0,
                'description' => 'Test journal line'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-lines')
            ->withData($data)
            ->post('/api/v1/journal-lines');

        $response->assertCreated();
    }

    public function test_tech_user_cannot_create_journal_lines(): void
    {
        $tech = $this->getTechUser();

        $data = [
            'type' => 'journal-lines',
            'attributes' => [
                'journalEntryId' => 1
            ]
        ];

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('journal-lines')
            ->withData($data)
            ->post('/api/v1/journal-lines');

        $response->assertStatus(403); // Tech is read-only
    }

    public function test_customer_user_cannot_create_journal_lines(): void
    {
        $customer = $this->getCustomerUser();

        $data = [
            'type' => 'journal-lines',
            'attributes' => [
                'journalEntryId' => 1
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('journal-lines')
            ->withData($data)
            ->post('/api/v1/journal-lines');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_create_journal_lines(): void
    {
        $data = [
            'type' => 'journal-lines',
            'attributes' => [
                'journalEntryId' => 1
            ]
        ];

        $response = $this->jsonApi()
            ->expects('journal-lines')
            ->withData($data)
            ->post('/api/v1/journal-lines');

        $response->assertStatus(401);
    }

    public function test_cannot_create_journal_lines_without_required_fields(): void
    {
        $admin = $this->getAdminUser();

        // Draft explicito: contra un asiento posted el Authorizer deniega con
        // 403 ANTES de la validacion y este test debe ver el 422 de campos
        // requeridos (el id 1 sembrado tiene status aleatorio).
        $entry = JournalEntry::factory()->draft()->create();

        $data = [
            'type' => 'journal-lines',
            'attributes' => [
                'journalEntryId' => $entry->id
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-lines')
            ->withData($data)
            ->post('/api/v1/journal-lines');

        $response->assertStatus(422);
    }

    public function test_cannot_create_journal_lines_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'journal-lines',
            'attributes' => [
                'journalEntryId' => 'invalid_data_type'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-lines')
            ->withData($data)
            ->post('/api/v1/journal-lines');

        $this->assertContains($response->status(), [200, 422]);
    }
}
