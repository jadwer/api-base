<?php

namespace Modules\Contacts\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Contacts\Models\ContactPerson;

class ContactPersonUpdateTest extends TestCase
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

    public function test_admin_can_update_ContactPerson(): void
    {
        $admin = $this->getAdminUser();
        $contactPerson = ContactPerson::factory()->create();

        $data = [
            'type' => 'contact-people',
            'id' => (string) $contactPerson->id,
            'attributes' => [
                'name' => 'Updated ContactPerson',
                'position' => 'Updated position',
                'isPrimary' => false
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contact-people')
            ->withData($data)
            ->patch("/api/v1/contact-people/{$contactPerson->id}");

        $response->assertOk();
        
        $this->assertDatabaseHas('contact_persons', [
            'id' => $contactPerson->id,
            'name' => 'Updated ContactPerson',
            'position' => 'Updated position',
            'is_primary' => false
        ]);
    }

    public function test_admin_can_partially_update_ContactPerson(): void
    {
        $admin = $this->getAdminUser();
        $contactPerson = ContactPerson::factory()->create([
            'name' => 'Original Name',
            'position' => 'Original Position'
        ]);

        $data = [
            'type' => 'contact-people',
            'id' => (string) $contactPerson->id,
            'attributes' => [
                'name' => 'Partially Updated Name'
                // position should remain unchanged
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contact-people')
            ->withData($data)
            ->patch("/api/v1/contact-people/{$contactPerson->id}");

        $response->assertOk();
        
        $this->assertDatabaseHas('contact_persons', [
            'id' => $contactPerson->id,
            'name' => 'Partially Updated Name',
            'position' => 'Original Position'
        ]);
    }

    public function test_admin_can_update_ContactPerson_metadata(): void
    {
        $admin = $this->getAdminUser();
        $contactPerson = ContactPerson::factory()->create();

        $metadata = [
            'updated_field' => 'new_value',
            'priority' => 'urgent',
            'tags' => ['important', 'updated']
        ];

        $data = [
            'type' => 'contact-people',
            'id' => (string) $contactPerson->id,
            'attributes' => [
                'metadata' => $metadata
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contact-people')
            ->withData($data)
            ->patch("/api/v1/contact-people/{$contactPerson->id}");

        $response->assertOk();
        
        $contactPerson->refresh();
        $this->assertEquals($metadata, $contactPerson->metadata);
    }

    public function test_customer_user_cannot_update_ContactPerson(): void
    {
        $customer = $this->getCustomerUser();
        $contactPerson = ContactPerson::factory()->create();

        $data = [
            'type' => 'contact-people',
            'id' => (string) $contactPerson->id,
            'attributes' => [
                'name' => 'Unauthorized Update'
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('contact-people')
            ->withData($data)
            ->patch("/api/v1/contact-people/{$contactPerson->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_update_ContactPerson(): void
    {
        $contactPerson = ContactPerson::factory()->create();

        $data = [
            'type' => 'contact-people',
            'id' => (string) $contactPerson->id,
            'attributes' => [
                'name' => 'Guest Update'
            ]
        ];

        $response = $this->jsonApi()
            ->expects('contact-people')
            ->withData($data)
            ->patch("/api/v1/contact-people/{$contactPerson->id}");

        $response->assertStatus(401);
    }

    public function test_cannot_update_nonexistent_ContactPerson(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'contact-people',
            'id' => '999999',
            'attributes' => [
                'name' => 'Nonexistent Update'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contact-people')
            ->withData($data)
            ->patch('/api/v1/contact-people/999999');

        $response->assertStatus(404);
    }

    public function test_cannot_update_ContactPerson_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();
        $contactPerson = ContactPerson::factory()->create();

        $data = [
            'type' => 'contact-people',
            'id' => (string) $contactPerson->id,
            'attributes' => [
                'name' => '', // Empty name
                'isPrimary' => 'invalid_boolean'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contact-people')
            ->withData($data)
            ->patch("/api/v1/contact-people/{$contactPerson->id}");

        $response->assertStatus(422);
    }
}
