<?php

namespace Modules\Ecommerce\Tests\Feature;

use Modules\Ecommerce\Models\ProductComparison;
use Modules\Ecommerce\Models\ProductComparisonItem;
use Modules\User\Models\User;
use Tests\TestCase;

class ProductComparisonItemUpdateTest extends TestCase
{
    public function test_admin_can_update_any_comparison_item()
    {
        $admin = User::role('admin')->first();
        $item = ProductComparisonItem::factory()->create(['position' => 0]);

        $data = [
            'type' => 'product-comparison-items',
            'id' => (string) $item->id,
            'attributes' => [
                'position' => 5,
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-comparison-items')
            ->withData($data)
            ->patch('/api/v1/product-comparison-items/' . $item->id);

        $response->assertSuccessful();
        $response->assertJson([
            'data' => [
                'type' => 'product-comparison-items',
                'id' => (string) $item->id,
                'attributes' => [
                    'position' => 5,
                ],
            ],
        ]);

        $this->assertDatabaseHas('product_comparison_items', [
            'id' => $item->id,
            'position' => 5,
        ]);
    }

    public function test_customer_can_update_item_in_their_own_comparison()
    {
        $customer = User::role('customer')->first();
        $comparison = ProductComparison::factory()->create(['user_id' => $customer->id]);
        $item = ProductComparisonItem::factory()->create([
            'comparison_id' => $comparison->id,
            'position' => 0,
        ]);

        $data = [
            'type' => 'product-comparison-items',
            'id' => (string) $item->id,
            'attributes' => [
                'position' => 3,
            ],
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('product-comparison-items')
            ->withData($data)
            ->patch('/api/v1/product-comparison-items/' . $item->id);

        $response->assertSuccessful();
        $response->assertJson([
            'data' => [
                'attributes' => [
                    'position' => 3,
                ],
            ],
        ]);
    }

    public function test_customer_cannot_update_item_in_other_users_comparison()
    {
        $customer = User::role('customer')->first();
        $otherUser = User::factory()->create();
        $comparison = ProductComparison::factory()->create(['user_id' => $otherUser->id]);
        $item = ProductComparisonItem::factory()->create(['comparison_id' => $comparison->id]);

        $data = [
            'type' => 'product-comparison-items',
            'id' => (string) $item->id,
            'attributes' => [
                'position' => 10,
            ],
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('product-comparison-items')
            ->withData($data)
            ->patch('/api/v1/product-comparison-items/' . $item->id);

        $response->assertStatus(403);
    }

    public function test_tech_user_cannot_update_comparison_item()
    {
        $tech = User::role('tech')->first();
        $item = ProductComparisonItem::factory()->create();

        $data = [
            'type' => 'product-comparison-items',
            'id' => (string) $item->id,
            'attributes' => [
                'position' => 2,
            ],
        ];

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('product-comparison-items')
            ->withData($data)
            ->patch('/api/v1/product-comparison-items/' . $item->id);

        $response->assertStatus(403);
    }

    public function test_guest_cannot_update_comparison_item()
    {
        $item = ProductComparisonItem::factory()->create();

        $data = [
            'type' => 'product-comparison-items',
            'id' => (string) $item->id,
            'attributes' => [
                'position' => 1,
            ],
        ];

        $response = $this->jsonApi()
            ->expects('product-comparison-items')
            ->withData($data)
            ->patch('/api/v1/product-comparison-items/' . $item->id);

        $response->assertStatus(401);
    }

    public function test_can_reorder_items_by_updating_position()
    {
        $customer = User::role('customer')->first();
        $comparison = ProductComparison::factory()->create(['user_id' => $customer->id]);
        $item1 = ProductComparisonItem::factory()->create(['comparison_id' => $comparison->id, 'position' => 0]);
        $item2 = ProductComparisonItem::factory()->create(['comparison_id' => $comparison->id, 'position' => 1]);
        $item3 = ProductComparisonItem::factory()->create(['comparison_id' => $comparison->id, 'position' => 2]);

        // Move item1 to position 2
        $data = [
            'type' => 'product-comparison-items',
            'id' => (string) $item1->id,
            'attributes' => [
                'position' => 2,
            ],
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('product-comparison-items')
            ->withData($data)
            ->patch('/api/v1/product-comparison-items/' . $item1->id);

        $response->assertSuccessful();

        $this->assertDatabaseHas('product_comparison_items', [
            'id' => $item1->id,
            'position' => 2,
        ]);
    }

    public function test_position_must_be_integer()
    {
        $admin = User::role('admin')->first();
        $item = ProductComparisonItem::factory()->create();

        $data = [
            'type' => 'product-comparison-items',
            'id' => (string) $item->id,
            'attributes' => [
                'position' => 'not-a-number',
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-comparison-items')
            ->withData($data)
            ->patch('/api/v1/product-comparison-items/' . $item->id);

        $response->assertStatus(422);
        $response->assertJsonFragment([
            'source' => ['pointer' => '/data/attributes/position']
        ]);
    }

    public function test_position_cannot_be_negative()
    {
        $admin = User::role('admin')->first();
        $item = ProductComparisonItem::factory()->create();

        $data = [
            'type' => 'product-comparison-items',
            'id' => (string) $item->id,
            'attributes' => [
                'position' => -1,
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-comparison-items')
            ->withData($data)
            ->patch('/api/v1/product-comparison-items/' . $item->id);

        $response->assertStatus(422);
        $response->assertJsonFragment([
            'source' => ['pointer' => '/data/attributes/position']
        ]);
    }

    public function test_returns_404_for_non_existent_item()
    {
        $admin = User::role('admin')->first();

        $data = [
            'type' => 'product-comparison-items',
            'id' => '99999',
            'attributes' => [
                'position' => 1,
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-comparison-items')
            ->withData($data)
            ->patch('/api/v1/product-comparison-items/99999');

        $response->assertStatus(404);
    }
}
