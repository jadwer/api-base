<?php

namespace Modules\Contacts\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Contacts\Models\ContactAddress;

class ContactAddressShowTest extends TestCase
{



    public function test_admin_can_view_ContactAddress(): void
    {
        $admin = $this->getAdminUser();
        $contactAddress = ContactAddress::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contact-addresses')
            ->get("/api/v1/contact-addresses/{$contactAddress->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                        'contactId',
                        'addressType',
                        'addressLine1',
                        'addressLine2',
                        'city',
                        'state',
                        'country',
                        'postalCode',
                        'isDefault',
                    'createdAt',
                    'updatedAt'
                ]
            ]
        ]);
    }

    public function test_admin_can_view_ContactAddress_with_specific_data(): void
    {
        $admin = $this->getAdminUser();
        
        $contactAddress = ContactAddress::factory()->create(['address_type' => 'test string', 'address_line_1' => 'test string', 'address_line_2' => 'test string', 'city' => 'test string', 'state' => 'test string', 'country' => 'test string', 'postal_code' => 'TEST123', 'is_default' => true]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contact-addresses')
            ->get("/api/v1/contact-addresses/{$contactAddress->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                        'contactId',
                        'addressType',
                        'addressLine1',
                        'addressLine2',
                        'city',
                        'state',
                        'country',
                        'postalCode',
                        'isDefault',
                    'createdAt',
                    'updatedAt'
                ]
            ]
        ]);
    }

    public function test_tech_user_can_view_ContactAddress_with_permission(): void
    {
        $tech = $this->getTechUser();
        $contactAddress = ContactAddress::factory()->create();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('contact-addresses')
            ->get("/api/v1/contact-addresses/{$contactAddress->id}");

        $response->assertOk();
    }

    public function test_customer_user_cannot_view_ContactAddress(): void
    {
        $customer = $this->getCustomerUser();
        $contactAddress = ContactAddress::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('contact-addresses')
            ->get("/api/v1/contact-addresses/{$contactAddress->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_view_ContactAddress(): void
    {
        $contactAddress = ContactAddress::factory()->create();

        $response = $this->jsonApi()
            ->expects('contact-addresses')
            ->get("/api/v1/contact-addresses/{$contactAddress->id}");

        $response->assertStatus(401);
    }

    public function test_returns_404_for_nonexistent_ContactAddress(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contact-addresses')
            ->get('/api/v1/contact-addresses/999999');

        $response->assertStatus(404);
    }

    public function test_response_includes_timestamps(): void
    {
        $admin = $this->getAdminUser();
        $contactAddress = ContactAddress::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contact-addresses')
            ->get("/api/v1/contact-addresses/{$contactAddress->id}");

        $response->assertOk();
        
        $this->assertNotNull($response->json('data.attributes.createdAt'));
        $this->assertNotNull($response->json('data.attributes.updatedAt'));
    }
}
