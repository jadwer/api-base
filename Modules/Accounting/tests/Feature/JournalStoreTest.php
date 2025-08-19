<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Accounting\Models\Journal;

class JournalStoreTest extends TestCase
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

    public function test_admin_can_create_Journal(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'journals',
            'attributes' => [
                'code' => 'TEST123',
                'name' => 'Test Name',
                'autoNumbering' => true,
                'sequencePrefix' => 'test string',
                'sequenceNext' => 100,
                'defaultCurrency' => 'test string',
                'postPolicy' => 'test string'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journals')
            ->withData($data)
            ->post('/api/v1/journals');

        $response->assertCreated();
        
        $this->assertDatabaseHas('journals', ['code' => 'TEST123', 'name' => 'Test Name', 'auto_numbering' => true, 'sequence_prefix' => 'test string', 'sequence_next' => 100, 'default_currency' => 'test string', 'post_policy' => 'test string']);
    }

    public function test_admin_can_create_Journal_with_minimal_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'journals',
            'attributes' => [
                'code' => 'TEST123',
                'name' => 'Test Name',
                'autoNumbering' => true
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journals')
            ->withData($data)
            ->post('/api/v1/journals');

        $response->assertCreated();
    }

    public function test_customer_user_cannot_create_Journal(): void
    {
        $customer = $this->getCustomerUser();

        $data = [
            'type' => 'journals',
            'attributes' => [
                'name' => 'Unauthorized Journal',
                'is_active' => true
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('journals')
            ->withData($data)
            ->post('/api/v1/journals');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_create_Journal(): void
    {
        $data = [
            'type' => 'journals',
            'attributes' => [
                'name' => 'Guest Journal',
                'is_active' => true
            ]
        ];

        $response = $this->jsonApi()
            ->expects('journals')
            ->withData($data)
            ->post('/api/v1/journals');

        $response->assertStatus(401);
    }

    public function test_cannot_create_Journal_without_required_fields(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'journals',
            'attributes' => [
                'description' => 'Missing name'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journals')
            ->withData($data)
            ->post('/api/v1/journals');

        $response->assertStatus(422);
        $this->assertJsonApiValidationErrors(['/data/attributes/name'], $response);
    }

    public function test_cannot_create_Journal_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'journals',
            'attributes' => [
                'name' => '', // Empty name
                'is_active' => 'not_boolean' // Invalid boolean
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journals')
            ->withData($data)
            ->post('/api/v1/journals');

        $response->assertStatus(422);
    }
}
