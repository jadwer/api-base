<?php

namespace Modules\Contacts\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Contacts\Models\ContactPerson;

class ContactPersonStoreTest extends TestCase
{



    public function test_admin_can_create_ContactPerson(): void
    {
        $admin = $this->getAdminUser();
        
        // Create a Contact first
        $contact = \Modules\Contacts\Models\Contact::factory()->create();

        $data = [
            'type' => 'contact-people',
            'attributes' => [
                'contactId' => $contact->id,
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
        
        $this->assertDatabaseHas('contact_persons', [
            'contact_id' => $contact->id,
            'name' => 'Test Name', 
            'position' => 'test string', 
            'department' => 'test string', 
            'email' => 'test@example.com', 
            'phone' => 'test string', 
            'mobile' => 'test string', 
            'is_primary' => true
        ]);
    }

    public function test_admin_can_create_ContactPerson_with_minimal_data(): void
    {
        $admin = $this->getAdminUser();
        
        // Create a Contact first
        $contact = \Modules\Contacts\Models\Contact::factory()->create();

        $data = [
            'type' => 'contact-people',
            'attributes' => [
                'contactId' => $contact->id,
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
        
        // Create a Contact first
        $contact = \Modules\Contacts\Models\Contact::factory()->create();

        $data = [
            'type' => 'contact-people',
            'attributes' => [
                'contactId' => $contact->id,
                'name' => 'Unauthorized ContactPerson',
                'isPrimary' => true
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
        // Create a Contact first
        $contact = \Modules\Contacts\Models\Contact::factory()->create();
        
        $data = [
            'type' => 'contact-people',
            'attributes' => [
                'contactId' => $contact->id,
                'name' => 'Guest ContactPerson',
                'isPrimary' => true
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
                'position' => 'Missing name'
                // Missing required contactId and name
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contact-people')
            ->withData($data)
            ->post('/api/v1/contact-people');

        $response->assertStatus(422);
        $this->assertJsonApiValidationErrors(['/data/attributes/name', '/data/attributes/contactId'], $response);
    }

    public function test_cannot_create_ContactPerson_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();
        
        // Create a Contact first
        $contact = \Modules\Contacts\Models\Contact::factory()->create();

        $data = [
            'type' => 'contact-people',
            'attributes' => [
                'contactId' => $contact->id,
                'name' => '', // Empty name
                'isPrimary' => 'not_boolean' // Invalid boolean
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
