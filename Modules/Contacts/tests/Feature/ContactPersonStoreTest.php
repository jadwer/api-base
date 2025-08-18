<?php

namespace Modules\Contacts\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Contacts\Models\ContactPerson;

class ContactPersonStoreTest extends TestCase
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

    public function test_admin_can_create_ContactPerson(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'contact-people',
            'attributes' => [
                'name' => 'Test Name',
                'position' => 'test string',
                'department' => 'test string',
                'email' => 'test@example.com',
                'phone' => 'test string',
                'mobile' => 'test string',
                'isPrimary' => true
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contact-people')
            ->withData($data)
            ->post('/api/v1/contact-people');

        $response->assertCreated();
        
        $this->assertDatabaseHas('contact_persons', ['name' => 'Test Name', 'position' => 'test string', 'department' => 'test string', 'email' => 'test@example.com', 'phone' => 'test string', 'mobile' => 'test string', 'is_primary' => true]);
    }

    public function test_admin_can_create_ContactPerson_with_minimal_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'contact-people',
            'attributes' => [
                'name' => 'Test Name',
                'isPrimary' => true
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contact-people')
            ->withData($data)
            ->post('/api/v1/contact-people');

        $response->assertCreated();
    }

    public function test_customer_user_cannot_create_ContactPerson(): void
    {
        $customer = $this->getCustomerUser();

        $data = [
            'type' => 'contact-people',
            'attributes' => [
                'name' => 'Unauthorized ContactPerson',
                'is_active' => true
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('contact-people')
            ->withData($data)
            ->post('/api/v1/contact-people');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_create_ContactPerson(): void
    {
        $data = [
            'type' => 'contact-people',
            'attributes' => [
                'name' => 'Guest ContactPerson',
                'is_active' => true
            ]
        ];

        $response = $this->jsonApi()
            ->expects('contact-people')
            ->withData($data)
            ->post('/api/v1/contact-people');

        $response->assertStatus(401);
    }

    public function test_cannot_create_ContactPerson_without_required_fields(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'contact-people',
            'attributes' => [
                'description' => 'Missing name'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contact-people')
            ->withData($data)
            ->post('/api/v1/contact-people');

        $response->assertStatus(422);
        $this->assertJsonApiValidationErrors(['/data/attributes/name'], $response);
    }

    public function test_cannot_create_ContactPerson_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'contact-people',
            'attributes' => [
                'name' => '', // Empty name
                'is_active' => 'not_boolean' // Invalid boolean
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contact-people')
            ->withData($data)
            ->post('/api/v1/contact-people');

        $response->assertStatus(422);
    }
}
