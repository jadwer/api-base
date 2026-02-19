<?php

namespace Modules\Contacts\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Contacts\Models\Contact;

class ContactStoreTest extends TestCase
{



    public function test_admin_can_create_Contact(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'contacts',
            'attributes' => [
                'contactType' => 'person',
                'name' => 'Test Name',
                'legalName' => 'Test Name',
                'email' => 'test@example.com',
                'phone' => 'test string',
                'website' => 'https://example.com',
                'status' => 'active',
                'isCustomer' => true,
                'isSupplier' => true,
                'creditLimit' => 99.99,
                'currentCredit' => 99.99,
                'classification' => 'premium',
                'paymentTerms' => 30,
                'notes' => 'test description',
                'metadata' => ['source' => 'web']
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contacts')
            ->withData($data)
            ->post('/api/v1/contacts');

        $response->assertCreated();
        
        $this->assertDatabaseHas('contacts', ['contact_type' => 'person', 'name' => 'Test Name', 'legal_name' => 'Test Name', 'email' => 'test@example.com', 'status' => 'active', 'is_customer' => true, 'is_supplier' => true]);
    }

    public function test_admin_can_create_Contact_with_minimal_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'contacts',
            'attributes' => [
                'contactType' => 'person',
                'name' => 'Test Name',
                'status' => 'active',
                'isCustomer' => true,
                'isSupplier' => true
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contacts')
            ->withData($data)
            ->post('/api/v1/contacts');

        $response->assertCreated();
    }

    public function test_customer_user_cannot_create_Contact(): void
    {
        $customer = $this->getCustomerUser();

        $data = [
            'type' => 'contacts',
            'attributes' => [
                'contactType' => 'person',
                'name' => 'Unauthorized Contact',
                'status' => 'active',
                'isCustomer' => true,
                'isSupplier' => false
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('contacts')
            ->withData($data)
            ->post('/api/v1/contacts');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_create_Contact(): void
    {
        $data = [
            'type' => 'contacts',
            'attributes' => [
                'contactType' => 'person',
                'name' => 'Guest Contact',
                'status' => 'active',
                'isCustomer' => true,
                'isSupplier' => false
            ]
        ];

        $response = $this->jsonApi()
            ->expects('contacts')
            ->withData($data)
            ->post('/api/v1/contacts');

        $response->assertStatus(401);
    }

    public function test_cannot_create_Contact_without_required_fields(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'contacts',
            'attributes' => [
                'notes' => 'Missing required fields'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contacts')
            ->withData($data)
            ->post('/api/v1/contacts');

        $response->assertStatus(422);
        $this->assertJsonApiValidationErrors(['/data/attributes/name'], $response);
    }

    public function test_cannot_create_Contact_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'contacts',
            'attributes' => [
                'name' => '', // Empty name
                'contactType' => 'invalid_type', // Invalid enum
                'isCustomer' => 'not_boolean' // Invalid boolean
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contacts')
            ->withData($data)
            ->post('/api/v1/contacts');

        $response->assertStatus(422);
    }
}
