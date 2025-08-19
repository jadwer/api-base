<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Accounting\Models\Journal;

class JournalIndexTest extends TestCase
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

    public function test_admin_can_list_Journals(): void
    {
        $admin = $this->getAdminUser();
        
        Journal::factory()->count(3)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journals')
            ->get('/api/v1/journals');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'attributes' => [
                        'code',
                        'name',
                        'autoNumbering',
                        'sequencePrefix',
                        'sequenceNext',
                        'defaultCurrency',
                        'postPolicy',
                    ]
                ]
            ]
        ]);
    }

    public function test_admin_can_sort_Journals_by_name(): void
    {
        $admin = $this->getAdminUser();
        
        Journal::factory()->create(['name' => 'Test Name']);
        Journal::factory()->create(['name' => 'Test Name']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journals')
            ->get('/api/v1/journals?sort=name');

        $response->assertOk();
    }

    public function test_admin_can_filter_Journals_by_autoNumbering(): void
    {
        $admin = $this->getAdminUser();
        
        Journal::factory()->create(['auto_numbering' => true]);
        Journal::factory()->create(['auto_numbering' => true]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journals')
            ->get('/api/v1/journals?filter[autoNumbering]=test');

        $response->assertOk();
    }

    public function test_tech_user_can_list_Journals_with_permission(): void
    {
        $tech = $this->getTechUser();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('journals')
            ->get('/api/v1/journals');

        $response->assertOk();
    }

    public function test_customer_user_cannot_list_Journals(): void
    {
        $customer = $this->getCustomerUser();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('journals')
            ->get('/api/v1/journals');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_list_Journals(): void
    {
        $response = $this->jsonApi()
            ->expects('journals')
            ->get('/api/v1/journals');

        $response->assertStatus(401);
    }

    public function test_can_paginate_Journals(): void
    {
        $admin = $this->getAdminUser();
        
        Journal::factory()->count(25)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journals')
            ->get('/api/v1/journals?page[size]=10');

        $response->assertOk();
        $this->assertCount(10, $response->json('data'));
        $response->assertJsonStructure(['links', 'meta']);
    }
}
