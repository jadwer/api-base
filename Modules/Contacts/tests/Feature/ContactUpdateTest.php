<?php

namespace Modules\Contacts\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Contacts\Models\Contact;

class ContactUpdateTest extends TestCase
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

    public function test_admin_can_update_Contact(): void
    {
        $admin = $this->getAdminUser();
        $contact = Contact::factory()->create();

        $data = [
            'type' => 'contacts',
            'id' => (string) $contact->id,
            'attributes' => [
                'name' => 'Updated Contact',
                'notes' => 'Updated notes',
                'status' => 'inactive'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contacts')
            ->withData($data)
            ->patch("/api/v1/contacts/{$contact->id}");

        $response->assertOk();
        
        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'name' => 'Updated Contact',
            'notes' => 'Updated notes',
            'status' => 'inactive'
        ]);
    }

    public function test_admin_can_partially_update_Contact(): void
    {
        $admin = $this->getAdminUser();
        $contact = Contact::factory()->create([
            'name' => 'Original Name',
            'notes' => 'Original Notes'
        ]);

        $data = [
            'type' => 'contacts',
            'id' => (string) $contact->id,
            'attributes' => [
                'name' => 'Partially Updated Name'
                // notes should remain unchanged
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contacts')
            ->withData($data)
            ->patch("/api/v1/contacts/{$contact->id}");

        $response->assertOk();
        
        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'name' => 'Partially Updated Name',
            'notes' => 'Original Notes'
        ]);
    }

    public function test_admin_can_update_Contact_metadata(): void
    {
        $admin = $this->getAdminUser();
        $contact = Contact::factory()->create();

        $metadata = [
            'updated_field' => 'new_value',
            'priority' => 'urgent',
            'tags' => ['important', 'updated']
        ];

        $data = [
            'type' => 'contacts',
            'id' => (string) $contact->id,
            'attributes' => [
                'metadata' => $metadata
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contacts')
            ->withData($data)
            ->patch("/api/v1/contacts/{$contact->id}");

        $response->assertOk();
        
        $contact->refresh();
        $this->assertEquals($metadata, $contact->metadata);
    }

    public function test_customer_user_cannot_update_Contact(): void
    {
        $customer = $this->getCustomerUser();
        $contact = Contact::factory()->create();

        $data = [
            'type' => 'contacts',
            'id' => (string) $contact->id,
            'attributes' => [
                'name' => 'Unauthorized Update'
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('contacts')
            ->withData($data)
            ->patch("/api/v1/contacts/{$contact->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_update_Contact(): void
    {
        $contact = Contact::factory()->create();

        $data = [
            'type' => 'contacts',
            'id' => (string) $contact->id,
            'attributes' => [
                'name' => 'Guest Update'
            ]
        ];

        $response = $this->jsonApi()
            ->expects('contacts')
            ->withData($data)
            ->patch("/api/v1/contacts/{$contact->id}");

        $response->assertStatus(401);
    }

    public function test_cannot_update_nonexistent_Contact(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'contacts',
            'id' => '999999',
            'attributes' => [
                'name' => 'Nonexistent Update'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contacts')
            ->withData($data)
            ->patch('/api/v1/contacts/999999');

        $response->assertStatus(404);
    }

    public function test_cannot_update_Contact_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();
        $contact = Contact::factory()->create();

        $data = [
            'type' => 'contacts',
            'id' => (string) $contact->id,
            'attributes' => [
                'name' => '', // Empty name
                'isCustomer' => 'invalid_boolean'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contacts')
            ->withData($data)
            ->patch("/api/v1/contacts/{$contact->id}");

        $response->assertStatus(422);
    }
}
