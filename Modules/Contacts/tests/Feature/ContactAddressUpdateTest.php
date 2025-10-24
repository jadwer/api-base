<?php

namespace Modules\Contacts\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Contacts\Models\ContactAddress;

class ContactAddressUpdateTest extends TestCase
{



    public function test_admin_can_update_ContactAddress(): void
    {
        $admin = $this->getAdminUser();
        $contactAddress = ContactAddress::factory()->create();

        $data = [
            'type' => 'contact-addresses',
            'id' => (string) $contactAddress->id,
            'attributes' => [
                'addressType' => 'shipping',
                'addressLine1' => 'Nueva Dirección 456',
                'city' => 'Guadalajara',
                'state' => 'Jalisco',
                'isDefault' => false
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contact-addresses')
            ->withData($data)
            ->patch("/api/v1/contact-addresses/{$contactAddress->id}");

        $response->assertOk();
        
        $this->assertDatabaseHas('contact_addresses', [
            'id' => $contactAddress->id,
            'address_type' => 'shipping',
            'address_line_1' => 'Nueva Dirección 456',
            'city' => 'Guadalajara',
            'state' => 'Jalisco',
            'is_default' => false
        ]);
    }

    public function test_admin_can_partially_update_ContactAddress(): void
    {
        $admin = $this->getAdminUser();
        $contactAddress = ContactAddress::factory()->create([
            'address_type' => 'billing',
            'city' => 'Original City'
        ]);

        $data = [
            'type' => 'contact-addresses',
            'id' => (string) $contactAddress->id,
            'attributes' => [
                'city' => 'Updated City'
                // type should remain unchanged
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contact-addresses')
            ->withData($data)
            ->patch("/api/v1/contact-addresses/{$contactAddress->id}");

        $response->assertOk();
        
        $this->assertDatabaseHas('contact_addresses', [
            'id' => $contactAddress->id,
            'city' => 'Updated City',
            'address_type' => 'billing'
        ]);
    }

    public function test_admin_can_update_ContactAddress_metadata(): void
    {
        $admin = $this->getAdminUser();
        $contactAddress = ContactAddress::factory()->create();

        $metadata = [
            'updated_field' => 'new_value',
            'priority' => 'urgent',
            'tags' => ['important', 'updated']
        ];

        $data = [
            'type' => 'contact-addresses',
            'id' => (string) $contactAddress->id,
            'attributes' => [
                'metadata' => $metadata
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contact-addresses')
            ->withData($data)
            ->patch("/api/v1/contact-addresses/{$contactAddress->id}");

        $response->assertOk();
        
        $contactAddress->refresh();
        $this->assertEquals($metadata, $contactAddress->metadata);
    }

    public function test_customer_user_cannot_update_ContactAddress(): void
    {
        $customer = $this->getCustomerUser();
        $contactAddress = ContactAddress::factory()->create();

        $data = [
            'type' => 'contact-addresses',
            'id' => (string) $contactAddress->id,
            'attributes' => [
                'city' => 'Unauthorized Update'
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('contact-addresses')
            ->withData($data)
            ->patch("/api/v1/contact-addresses/{$contactAddress->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_update_ContactAddress(): void
    {
        $contactAddress = ContactAddress::factory()->create();

        $data = [
            'type' => 'contact-addresses',
            'id' => (string) $contactAddress->id,
            'attributes' => [
                'city' => 'Guest Update'
            ]
        ];

        $response = $this->jsonApi()
            ->expects('contact-addresses')
            ->withData($data)
            ->patch("/api/v1/contact-addresses/{$contactAddress->id}");

        $response->assertStatus(401);
    }

    public function test_cannot_update_nonexistent_ContactAddress(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'contact-addresses',
            'id' => '999999',
            'attributes' => [
                'city' => 'Nonexistent Update'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contact-addresses')
            ->withData($data)
            ->patch('/api/v1/contact-addresses/999999');

        $response->assertStatus(404);
    }

    public function test_cannot_update_ContactAddress_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();
        $contactAddress = ContactAddress::factory()->create();

        $data = [
            'type' => 'contact-addresses',
            'id' => (string) $contactAddress->id,
            'attributes' => [
                'postalCode' => '123', // Invalid for MX postal code
                'country' => 'MX',
                'isDefault' => 'invalid_boolean'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contact-addresses')
            ->withData($data)
            ->patch("/api/v1/contact-addresses/{$contactAddress->id}");

        $response->assertStatus(422);
    }
}
