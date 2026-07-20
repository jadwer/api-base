<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Accounting\Models\Account;

class AccountIndexTest extends TestCase
{



    /**
     * Paquete A (auditoria 10 pasos): filter[search] es el contrato del buscador
     * del FE; antes no existia y el listado respondia 400 al teclear.
     */
    public function test_admin_can_search_Accounts(): void
    {
        $admin = $this->getAdminUser();

        $match = Account::factory()->create(['code' => '9901', 'name' => 'Cuenta Buscable Unica']);
        Account::factory()->create(['code' => '9902', 'name' => 'Otra Cuenta']);

        // Por codigo
        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('accounts')
            ->get('/api/v1/accounts?filter[search]=9901');

        $response->assertOk();
        $this->assertContains((string) $match->id, collect($response->json('data'))->pluck('id'));

        // Por nombre
        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('accounts')
            ->get('/api/v1/accounts?filter[search]=Buscable Unica');

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id');
        $this->assertContains((string) $match->id, $ids);
        $this->assertCount(1, $ids);
    }

    public function test_admin_can_list_Accounts(): void
    {
        $admin = $this->getAdminUser();
        
        Account::factory()->count(3)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('accounts')
            ->get('/api/v1/accounts');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'attributes' => [
                        'code',
                        'name',
                        'accountType',
                        'nature',
                        'level',
                        'parentId',
                        'currency',
                        'isPostable',
                        'isCashFlow',
                        'status',
                        'metadata',
                    ]
                ]
            ]
        ]);
    }

    public function test_admin_can_sort_Accounts_by_name(): void
    {
        $admin = $this->getAdminUser();
        
        Account::factory()->create(['name' => 'Test Name']);
        Account::factory()->create(['name' => 'Test Name']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('accounts')
            ->get('/api/v1/accounts?sort=name');

        $response->assertOk();
    }

    public function test_admin_can_filter_Accounts_by_status(): void
    {
        $admin = $this->getAdminUser();
        
        Account::factory()->create(['status' => 'active']);
        Account::factory()->create(['status' => 'active']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('accounts')
            ->get('/api/v1/accounts?filter[status]=test');

        $response->assertOk();
    }

    public function test_tech_user_can_list_Accounts_with_permission(): void
    {
        $tech = $this->getTechUser();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('accounts')
            ->get('/api/v1/accounts');

        $response->assertOk();
    }

    public function test_customer_user_cannot_list_Accounts(): void
    {
        $customer = $this->getCustomerUser();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('accounts')
            ->get('/api/v1/accounts');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_list_Accounts(): void
    {
        $response = $this->jsonApi()
            ->expects('accounts')
            ->get('/api/v1/accounts');

        $response->assertStatus(401);
    }

    public function test_can_paginate_Accounts(): void
    {
        $admin = $this->getAdminUser();
        
        Account::factory()->count(25)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('accounts')
            ->get('/api/v1/accounts?page[size]=10');

        $response->assertOk();
        $this->assertCount(10, $response->json('data'));
        $response->assertJsonStructure(['links', 'meta']);
    }
}
