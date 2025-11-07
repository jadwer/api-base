<?php

namespace Modules\Ecommerce\Tests\Feature;

use Modules\Ecommerce\Models\ProductComparison;
use Modules\Ecommerce\Models\ProductComparisonItem;
use Modules\User\Models\User;
use Tests\TestCase;

class ProductComparisonItemIndexTest extends TestCase
{
    /** @test */
    public function admin_can_list_all_comparison_items()
    {
        $admin = User::role('admin')->first();

        ProductComparisonItem::factory()->count(3)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->get('/api/v1/product-comparison-items');

        $response->assertSuccessful();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'type',
                    'id',
                    'attributes' => [
                        'comparisonId',
                        'product_id',
                        'position',
                        'createdAt',
                        'updatedAt',
                    ],
                ],
            ],
        ]);
    }

    /** @test */
    public function tech_user_can_list_comparison_items()
    {
        $tech = User::role('tech')->first();

        ProductComparisonItem::factory()->count(2)->create();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->get('/api/v1/product-comparison-items');

        $response->assertSuccessful();
    }

    /** @test */
    public function customer_can_list_items_from_accessible_comparisons()
    {
        $customer = User::role('customer')->first();

        // Create comparison owned by customer
        $ownComparison = ProductComparison::factory()->create(['user_id' => $customer->id]);
        ProductComparisonItem::factory()->count(2)->create(['comparison_id' => $ownComparison->id]);

        // Create public comparison from others
        $publicComparison = ProductComparison::factory()->public()->create();
        ProductComparisonItem::factory()->count(2)->create(['comparison_id' => $publicComparison->id]);

        // Create private comparison from others (should not be accessible)
        $privateComparison = ProductComparison::factory()->private()->create();
        ProductComparisonItem::factory()->count(2)->create(['comparison_id' => $privateComparison->id]);

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->get('/api/v1/product-comparison-items');

        $response->assertSuccessful();
    }

    /** @test */
    public function guest_cannot_list_comparison_items()
    {
        ProductComparisonItem::factory()->count(3)->create();

        $response = $this->jsonApi()
            ->get('/api/v1/product-comparison-items');

        $response->assertStatus(401);
    }

    /** @test */
    public function can_filter_items_by_comparison_id()
    {
        $admin = User::role('admin')->first();
        $comparison1 = ProductComparison::factory()->create();
        $comparison2 = ProductComparison::factory()->create();

        ProductComparisonItem::factory()->count(2)->create(['comparison_id' => $comparison1->id]);
        ProductComparisonItem::factory()->count(3)->create(['comparison_id' => $comparison2->id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->get('/api/v1/product-comparison-items?filter[comparisonId]=' . $comparison1->id);

        $response->assertSuccessful();
        $response->assertJsonCount(2, 'data');
    }

    /** @test */
    public function can_sort_items_by_position()
    {
        $admin = User::role('admin')->first();
        $comparison = ProductComparison::factory()->create();

        ProductComparisonItem::factory()->create(['comparison_id' => $comparison->id, 'position' => 2]);
        ProductComparisonItem::factory()->create(['comparison_id' => $comparison->id, 'position' => 0]);
        ProductComparisonItem::factory()->create(['comparison_id' => $comparison->id, 'position' => 1]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->get('/api/v1/product-comparison-items?sort=position&filter[comparisonId]=' . $comparison->id);

        $response->assertSuccessful();

        $positions = collect($response->json('data'))->pluck('attributes.position')->toArray();
        $this->assertEquals([0, 1, 2], $positions);
    }

    /** @test */
    public function can_include_comparison_relationship()
    {
        $admin = User::role('admin')->first();

        ProductComparisonItem::factory()->count(2)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->get('/api/v1/product-comparison-items?include=comparison');

        $response->assertSuccessful();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'relationships' => [
                        'comparison',
                    ],
                ],
            ],
            'included',
        ]);
    }

    /** @test */
    public function can_include_product_relationship()
    {
        $admin = User::role('admin')->first();

        ProductComparisonItem::factory()->count(2)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->get('/api/v1/product-comparison-items?include=product');

        $response->assertSuccessful();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'relationships' => [
                        'product',
                    ],
                ],
            ],
            'included',
        ]);
    }

    /** @test */
    public function can_include_multiple_relationships()
    {
        $admin = User::role('admin')->first();

        ProductComparisonItem::factory()->count(2)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->get('/api/v1/product-comparison-items?include=comparison,product');

        $response->assertSuccessful();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'relationships' => [
                        'comparison',
                        'product',
                    ],
                ],
            ],
            'included',
        ]);
    }
}
