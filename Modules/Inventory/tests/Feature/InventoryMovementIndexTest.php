<?php

namespace Modules\Inventory\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Inventory\Models\InventoryMovement;

class InventoryMovementIndexTest extends TestCase
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

    public function test_admin_can_list_inventory_movements(): void
    {
        $admin = $this->getAdminUser();
        
        // Clear existing data and create fresh test data
        InventoryMovement::truncate();
        InventoryMovement::factory()->count(3)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('inventory-movements')
            ->get('/api/v1/inventory-movements');

        $response->assertOk();
        $response->assertJsonCount(3, 'data');
    }

    public function test_admin_can_sort_inventory_movements_by_movement_date(): void
    {
        $admin = $this->getAdminUser();
        
        InventoryMovement::factory()->create(['movement_date' => '2024-01-01 10:00:00']);
        InventoryMovement::factory()->create(['movement_date' => '2024-06-01 10:00:00']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('inventory-movements')
            ->get('/api/v1/inventory-movements?sort=movementDate');

        $response->assertOk();
        $dates = collect($response->json('data'))->pluck('attributes.movementDate');
        $this->assertTrue($dates->first() <= $dates->last());
    }

    public function test_admin_can_filter_inventory_movements_by_type(): void
    {
        $admin = $this->getAdminUser();
        
        // Clear existing data and create fresh test data
        InventoryMovement::truncate();
        InventoryMovement::factory()->count(2)->create(['movement_type' => 'entry']);
        InventoryMovement::factory()->count(1)->create(['movement_type' => 'exit']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('inventory-movements')
            ->get('/api/v1/inventory-movements?filter[movementType]=entry');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
    }

    public function test_admin_can_filter_inventory_movements_by_status(): void
    {
        $admin = $this->getAdminUser();
        
        // Clear existing data and create fresh test data
        InventoryMovement::truncate();
        InventoryMovement::factory()->count(2)->create(['status' => 'completed']);
        InventoryMovement::factory()->count(1)->create(['status' => 'pending']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('inventory-movements')
            ->get('/api/v1/inventory-movements?filter[status]=completed');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
    }

    public function test_tech_user_can_list_inventory_movements_with_permission(): void
    {
        $tech = $this->getTechUser();
        
        // Clear existing data and create fresh test data
        InventoryMovement::truncate();
        InventoryMovement::factory()->count(2)->create();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('inventory-movements')
            ->get('/api/v1/inventory-movements');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
    }

    public function test_customer_user_cannot_list_inventory_movements(): void
    {
        $customer = $this->getCustomerUser();
        
        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('inventory-movements')
            ->get('/api/v1/inventory-movements');

        $response->assertForbidden();
    }

    public function test_guest_cannot_list_inventory_movements(): void
    {
        $response = $this->jsonApi()
            ->expects('inventory-movements')
            ->get('/api/v1/inventory-movements');

        $response->assertStatus(401);
    }

    public function test_can_paginate_inventory_movements(): void
    {
        $admin = $this->getAdminUser();
        
        // Clear existing data and create fresh test data
        InventoryMovement::truncate();
        InventoryMovement::factory()->count(25)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('inventory-movements')
            ->get('/api/v1/inventory-movements?page[size]=10');

        $response->assertOk();
        $response->assertJsonCount(10, 'data');
        $response->assertJsonStructure([
            'data',
            'links',
            'meta'
        ]);
    }

    public function test_can_include_product_relationship(): void
    {
        $admin = $this->getAdminUser();
        
        // Clear existing data and create fresh test data
        InventoryMovement::truncate();
        InventoryMovement::factory()->count(2)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('inventory-movements')
            ->get('/api/v1/inventory-movements?include=product');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'relationships' => [
                        'product' => ['data']
                    ]
                ]
            ],
            'included' => [
                '*' => [
                    'type',
                    'id',
                    'attributes'
                ]
            ]
        ]);
    }

    public function test_can_include_warehouse_relationship(): void
    {
        $admin = $this->getAdminUser();
        
        // Clear existing data and create fresh test data
        InventoryMovement::truncate();
        InventoryMovement::factory()->count(2)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('inventory-movements')
            ->get('/api/v1/inventory-movements?include=warehouse');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'relationships' => [
                        'warehouse' => ['data']
                    ]
                ]
            ],
            'included'
        ]);
    }
}