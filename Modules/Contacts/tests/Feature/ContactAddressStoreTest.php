<?php

namespace Modules\Contacts\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Contacts\Models\ContactAddress;

class ContactAddressStoreTest extends TestCase
{



    public function test_admin_can_create_ContactAddress(): void
    {
        $admin = $this->getAdminUser();
        
        // Create a contact first
        $contact = \Modules\Contacts\Models\Contact::factory()->create();

        $data = [
            'type' => 'contact-addresses',
            'attributes' => [
                'contactId' => $contact->id,
                'addressType' => 'billing',
                'addressLine1' => 'Calle Falsa 123',
                'addressLine2' => 'Depto A',
                'city' => 'Ciudad de México',
                'state' => 'CDMX',
                'country' => 'MX',
                'postalCode' => '12345',
                'isDefault' => true
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contact-addresses')
            ->withData($data)
            ->post('/api/v1/contact-addresses');

        $response->assertCreated();
        
        $this->assertDatabaseHas('contact_addresses', [
            'contact_id' => $contact->id,
            'address_type' => 'billing', 
            'address_line_1' => 'Calle Falsa 123', 
            'address_line_2' => 'Depto A', 
            'city' => 'Ciudad de México', 
            'state' => 'CDMX', 
            'country' => 'MX', 
            'postal_code' => '12345', 
            'is_default' => true
        ]);
    }

    public function test_admin_can_create_ContactAddress_with_minimal_data(): void
    {
        $admin = $this->getAdminUser();
        
        // Create a contact first
        $contact = \Modules\Contacts\Models\Contact::factory()->create();

        $data = [
            'type' => 'contact-addresses',
            'attributes' => [
                'contactId' => $contact->id,
                'city' => 'Test City'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contact-addresses')
            ->withData($data)
            ->post('/api/v1/contact-addresses');

        $response->assertCreated();
    }

    public function test_customer_user_cannot_create_ContactAddress(): void
    {
        $customer = $this->getCustomerUser();
        
        // Create a contact first
        $contact = \Modules\Contacts\Models\Contact::factory()->create();

        $data = [
            'type' => 'contact-addresses',
            'attributes' => [
                'contactId' => $contact->id,
                'addressType' => 'billing',
                'city' => 'Test City'
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('contact-addresses')
            ->withData($data)
            ->post('/api/v1/contact-addresses');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_create_ContactAddress(): void
    {
        // Create a contact first
        $contact = \Modules\Contacts\Models\Contact::factory()->create();
        
        $data = [
            'type' => 'contact-addresses',
            'attributes' => [
                'contactId' => $contact->id,
                'addressType' => 'billing',
                'city' => 'Test City'
            ]
        ];

        $response = $this->jsonApi()
            ->expects('contact-addresses')
            ->withData($data)
            ->post('/api/v1/contact-addresses');

        $response->assertStatus(401);
    }

    public function test_cannot_create_ContactAddress_without_required_fields(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'contact-addresses',
            'attributes' => [
                'city' => 'Test City'
                // Missing contactId which should be required
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contact-addresses')
            ->withData($data)
            ->post('/api/v1/contact-addresses');

        $response->assertStatus(422);
        // ContactAddress doesn't have strict required fields, but this would fail due to missing contact_id
    }

    public function test_cannot_create_ContactAddress_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();
        
        // Create a contact first
        $contact = \Modules\Contacts\Models\Contact::factory()->create();

        $data = [
            'type' => 'contact-addresses',
            'attributes' => [
                'contactId' => $contact->id,
                'postalCode' => '123', // Invalid for MX postal code (should be 5 digits)
                'country' => 'MX',
                'isDefault' => 'not_boolean' // Invalid boolean
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contact-addresses')
            ->withData($data)
            ->post('/api/v1/contact-addresses');

        $response->assertStatus(422);
    }
}
