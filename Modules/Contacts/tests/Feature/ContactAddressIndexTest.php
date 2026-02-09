<?php

namespace Modules\Contacts\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Contacts\Models\ContactAddress;

class ContactAddressIndexTest extends TestCase
{



    public function test_admin_can_list_ContactAddresses(): void
    {
        $admin = $this->getAdminUser();
        
        ContactAddress::factory()->count(3)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contact-addresses')
            ->get('/api/v1/contact-addresses');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
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
                    ]
                ]
            ]
        ]);
    }

    public function test_admin_can_sort_ContactAddresses_by_type(): void
    {
        $admin = $this->getAdminUser();
        
        ContactAddress::factory()->create(['address_type' => 'test string']);
        ContactAddress::factory()->create(['address_type' => 'test string']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contact-addresses')
            ->get('/api/v1/contact-addresses?sort=addressType');

        $response->assertOk();
    }

    public function test_admin_can_filter_ContactAddresses_by_type(): void
    {
        $admin = $this->getAdminUser();
        
        ContactAddress::factory()->create(['address_type' => 'test string']);
        ContactAddress::factory()->create(['address_type' => 'test string']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contact-addresses')
            ->get('/api/v1/contact-addresses?filter[addressType]=test');

        $response->assertOk();
    }

    public function test_tech_user_can_list_ContactAddresses_with_permission(): void
    {
        $tech = $this->getTechUser();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('contact-addresses')
            ->get('/api/v1/contact-addresses');

        $response->assertOk();
    }

    public function test_customer_user_cannot_list_ContactAddresses(): void
    {
        $customer = $this->getCustomerUser();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('contact-addresses')
            ->get('/api/v1/contact-addresses');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_list_ContactAddresses(): void
    {
        $response = $this->jsonApi()
            ->expects('contact-addresses')
            ->get('/api/v1/contact-addresses');

        $response->assertStatus(401);
    }

    public function test_can_paginate_ContactAddresses(): void
    {
        $admin = $this->getAdminUser();
        
        ContactAddress::factory()->count(25)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contact-addresses')
            ->get('/api/v1/contact-addresses?page[size]=10');

        $response->assertOk();
        $this->assertCount(10, $response->json('data'));
        $response->assertJsonStructure(['links', 'meta']);
    }
}
