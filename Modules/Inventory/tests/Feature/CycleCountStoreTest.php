<?php

namespace Modules\Inventory\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Inventory\Models\CycleCount;
use Modules\Inventory\Models\Warehouse;
use Modules\Product\Models\Product;

class CycleCountStoreTest extends TestCase
{
    protected function getAdminUser(): User
    {
        return User::where('email', 'admin@example.com')->firstOrFail();
    }

    protected function getTechUser(): User
    {
        return User::where('email', 'tech@example.com')->firstOrFail();
    }

    protected function getCustomerUser(): User
    {
        return User::where('email', 'customer@example.com')->firstOrFail();
    }

    public function test_admin_can_create_cycle_count(): void
    {
        $admin = $this->getAdminUser();
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create();

        $data = [
            'type' => 'cycle-counts',
            'attributes' => [
                'warehouseId' => $warehouse->id,
                'productId' => $product->id,
                'scheduledDate' => now()->addDays(7)->toDateString(),
                'status' => 'scheduled',
                'abcClass' => 'A',
                'notes' => 'Quarterly inventory count',
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('cycle-counts')
            ->withData($data)
            ->post('/api/v1/cycle-counts');

        $response->assertCreated();
        $this->assertDatabaseHas('cycle_counts', [
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'status' => 'scheduled',
            'abc_class' => 'A',
        ]);
    }

    public function test_store_requires_warehouse_id(): void
    {
        $admin = $this->getAdminUser();
        $product = Product::factory()->create();

        $data = [
            'type' => 'cycle-counts',
            'attributes' => [
                'productId' => $product->id,
                'scheduledDate' => now()->addDays(7)->toDateString(),
                'status' => 'scheduled',
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('cycle-counts')
            ->withData($data)
            ->post('/api/v1/cycle-counts');

        $response->assertUnprocessable();
    }

    public function test_store_requires_product_id(): void
    {
        $admin = $this->getAdminUser();
        $warehouse = Warehouse::factory()->create();

        $data = [
            'type' => 'cycle-counts',
            'attributes' => [
                'warehouseId' => $warehouse->id,
                'scheduledDate' => now()->addDays(7)->toDateString(),
                'status' => 'scheduled',
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('cycle-counts')
            ->withData($data)
            ->post('/api/v1/cycle-counts');

        $response->assertUnprocessable();
    }

    public function test_store_validates_status(): void
    {
        $admin = $this->getAdminUser();
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create();

        $data = [
            'type' => 'cycle-counts',
            'attributes' => [
                'warehouseId' => $warehouse->id,
                'productId' => $product->id,
                'scheduledDate' => now()->addDays(7)->toDateString(),
                'status' => 'invalid_status',
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('cycle-counts')
            ->withData($data)
            ->post('/api/v1/cycle-counts');

        $response->assertUnprocessable();
    }

    public function test_unauthorized_user_cannot_create_cycle_count(): void
    {
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create();

        $data = [
            'type' => 'cycle-counts',
            'attributes' => [
                'warehouseId' => $warehouse->id,
                'productId' => $product->id,
                'scheduledDate' => now()->addDays(7)->toDateString(),
                'status' => 'scheduled',
            ]
        ];

        $response = $this->jsonApi()
            ->expects('cycle-counts')
            ->withData($data)
            ->post('/api/v1/cycle-counts');

        $response->assertUnauthorized();
    }

    public function test_user_without_permission_cannot_create_cycle_count(): void
    {
        $customer = $this->getCustomerUser();
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create();

        $data = [
            'type' => 'cycle-counts',
            'attributes' => [
                'warehouseId' => $warehouse->id,
                'productId' => $product->id,
                'scheduledDate' => now()->addDays(7)->toDateString(),
                'status' => 'scheduled',
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('cycle-counts')
            ->withData($data)
            ->post('/api/v1/cycle-counts');

        $response->assertForbidden();
    }
}
