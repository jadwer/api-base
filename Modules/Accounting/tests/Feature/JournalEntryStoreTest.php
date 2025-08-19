<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Accounting\Models\JournalEntry;

class JournalEntryStoreTest extends TestCase
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

    public function test_admin_can_create_JournalEntry(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'journal-entries',
            'attributes' => [
                'number' => 'test string',
                'date' => '2024-01-01',
                'currency' => 'test string',
                'exchangeRate' => 99.99,
                'reference' => 'test string',
                'description' => 'test description',
                'status' => 'active',
                'postedAt' => '2024-01-01',
                'sourceType' => 'test string'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-entries')
            ->withData($data)
            ->post('/api/v1/journal-entries');

        $response->assertCreated();
        
        $this->assertDatabaseHas('journal_entries', ['number' => 'test string', 'date' => 'test value', 'currency' => 'test string', 'exchange_rate' => 99.99, 'reference' => 'test string', 'description' => 'test description', 'status' => 'active', 'posted_at' => 'test value', 'source_type' => 'test string']);
    }

    public function test_admin_can_create_JournalEntry_with_minimal_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'journal-entries',
            'attributes' => [

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
                'name' => 'Unauthorized JournalEntry',
                'is_active' => true
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
                'name' => 'Guest JournalEntry',
                'is_active' => true
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
                'description' => 'Missing name'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-entries')
            ->withData($data)
            ->post('/api/v1/journal-entries');

        $response->assertStatus(422);
        $this->assertJsonApiValidationErrors(['/data/attributes/name'], $response);
    }

    public function test_cannot_create_JournalEntry_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'journal-entries',
            'attributes' => [
                'name' => '', // Empty name
                'is_active' => 'not_boolean' // Invalid boolean
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
