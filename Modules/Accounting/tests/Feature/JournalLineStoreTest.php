<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Accounting\Models\JournalLine;

class JournalLineStoreTest extends TestCase
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

    public function test_admin_can_create_JournalLine(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'journal-lines',
            'attributes' => [
                'debit' => 99.99,
                'credit' => 99.99,
                'baseAmount' => 99.99,
                'memo' => 'test string'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-lines')
            ->withData($data)
            ->post('/api/v1/journal-lines');

        $response->assertCreated();
        
        $this->assertDatabaseHas('journal_lines', ['debit' => 99.99, 'credit' => 99.99, 'base_amount' => 99.99, 'memo' => 'test string']);
    }

    public function test_admin_can_create_JournalLine_with_minimal_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'journal-lines',
            'attributes' => [

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
                'name' => 'Unauthorized JournalLine',
                'is_active' => true
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
                'name' => 'Guest JournalLine',
                'is_active' => true
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
                'description' => 'Missing name'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-lines')
            ->withData($data)
            ->post('/api/v1/journal-lines');

        $response->assertStatus(422);
        $this->assertJsonApiValidationErrors(['/data/attributes/name'], $response);
    }

    public function test_cannot_create_JournalLine_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'journal-lines',
            'attributes' => [
                'name' => '', // Empty name
                'is_active' => 'not_boolean' // Invalid boolean
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
