<?php

namespace Modules\Contacts\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Contacts\Models\Contact;

class ContactIndexTest extends TestCase
{



    public function test_admin_can_list_Contacts(): void
    {
        $admin = $this->getAdminUser();
        
        Contact::factory()->count(3)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contacts')
            ->get('/api/v1/contacts');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'attributes' => [
                        'contactType',
                        'name',
                        'legalName',
                        'taxId',
                        'email',
                        'phone',
                        'website',
                        'status',
                        'isCustomer',
                        'isSupplier',
                        'creditLimit',
                        'currentCredit',
                        'classification',
                        'paymentTerms',
                        'notes',
                        'metadata',
                    ]
                ]
            ]
        ]);
    }

    public function test_admin_can_sort_Contacts_by_name(): void
    {
        $admin = $this->getAdminUser();
        
        Contact::factory()->create(['name' => 'Test Name']);
        Contact::factory()->create(['name' => 'Test Name']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contacts')
            ->get('/api/v1/contacts?sort=name');

        $response->assertOk();
    }

    public function test_admin_can_filter_Contacts_by_type(): void
    {
        $admin = $this->getAdminUser();
        
        Contact::factory()->create(['contact_type' => 'test string']);
        Contact::factory()->create(['contact_type' => 'test string']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contacts')
            ->get('/api/v1/contacts?filter[contactType]=test');

        $response->assertOk();
    }

    public function test_tech_user_can_list_Contacts_with_permission(): void
    {
        $tech = $this->getTechUser();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('contacts')
            ->get('/api/v1/contacts');

        $response->assertOk();
    }

    public function test_customer_user_cannot_list_Contacts(): void
    {
        $customer = $this->getCustomerUser();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('contacts')
            ->get('/api/v1/contacts');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_list_Contacts(): void
    {
        $response = $this->jsonApi()
            ->expects('contacts')
            ->get('/api/v1/contacts');

        $response->assertStatus(401);
    }

    public function test_can_paginate_Contacts(): void
    {
        $admin = $this->getAdminUser();
        
        Contact::factory()->count(25)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contacts')
            ->get('/api/v1/contacts?page[size]=10');

        $response->assertOk();
        $this->assertCount(10, $response->json('data'));
        $response->assertJsonStructure(['links', 'meta']);
    }

    public function test_admin_can_filter_prospects(): void
    {
        $admin = $this->getAdminUser();

        Contact::factory()->prospect()->create(['name' => 'Prospect A']);
        Contact::factory()->customer()->create(['name' => 'Customer A']);
        Contact::factory()->supplier()->create(['name' => 'Supplier A']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contacts')
            ->get('/api/v1/contacts?filter[isProspect]=1');

        $response->assertOk();

        $data = $response->json('data');
        $names = array_column(array_column($data, 'attributes'), 'name');
        $this->assertContains('Prospect A', $names);
        $this->assertNotContains('Customer A', $names);
        $this->assertNotContains('Supplier A', $names);
    }
}
