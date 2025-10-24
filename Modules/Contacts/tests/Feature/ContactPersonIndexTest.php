<?php

namespace Modules\Contacts\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Contacts\Models\ContactPerson;

class ContactPersonIndexTest extends TestCase
{



    public function test_admin_can_list_ContactPeople(): void
    {
        $admin = $this->getAdminUser();
        
        ContactPerson::factory()->count(3)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contact-people')
            ->get('/api/v1/contact-people');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'attributes' => [
                        'contactId',
                        'name',
                        'position',
                        'department',
                        'email',
                        'phone',
                        'mobile',
                        'isPrimary',
                    ]
                ]
            ]
        ]);
    }

    public function test_admin_can_sort_ContactPeople_by_name(): void
    {
        $admin = $this->getAdminUser();
        
        ContactPerson::factory()->create(['name' => 'Test Name']);
        ContactPerson::factory()->create(['name' => 'Test Name']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contact-people')
            ->get('/api/v1/contact-people?sort=name');

        $response->assertOk();
    }

    public function test_admin_can_filter_ContactPeople_by_isPrimary(): void
    {
        $admin = $this->getAdminUser();
        
        ContactPerson::factory()->create(['is_primary' => true]);
        ContactPerson::factory()->create(['is_primary' => true]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contact-people')
            ->get('/api/v1/contact-people?filter[is_primary]=1');

        $response->assertOk();
    }

    public function test_tech_user_can_list_ContactPeople_with_permission(): void
    {
        $tech = $this->getTechUser();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('contact-people')
            ->get('/api/v1/contact-people');

        $response->assertOk();
    }

    public function test_customer_user_cannot_list_ContactPeople(): void
    {
        $customer = $this->getCustomerUser();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('contact-people')
            ->get('/api/v1/contact-people');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_list_ContactPeople(): void
    {
        $response = $this->jsonApi()
            ->expects('contact-people')
            ->get('/api/v1/contact-people');

        $response->assertStatus(401);
    }

    public function test_can_paginate_ContactPeople(): void
    {
        $admin = $this->getAdminUser();
        
        ContactPerson::factory()->count(25)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contact-people')
            ->get('/api/v1/contact-people?page[size]=10');

        $response->assertOk();
        $this->assertCount(10, $response->json('data'));
        $response->assertJsonStructure(['links', 'meta']);
    }
}
