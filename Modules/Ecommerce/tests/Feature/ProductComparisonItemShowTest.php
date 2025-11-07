<?php

namespace Modules\Ecommerce\Tests\Feature;

use Modules\Ecommerce\Models\ProductComparison;
use Modules\Ecommerce\Models\ProductComparisonItem;
use Modules\User\Models\User;
use Tests\TestCase;

class ProductComparisonItemShowTest extends TestCase
{
    /** @test */
    public function admin_can_view_any_comparison_item()
    {
        $admin = User::role('admin')->first();
        $item = ProductComparisonItem::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-comparison-items')
            ->get('/api/v1/product-comparison-items/' . $item->id);

        $response->assertSuccessful();
        $response->assertJson([
            'data' => [
                'type' => 'product-comparison-items',
                'id' => (string) $item->id,
                'attributes' => [
                    'comparison_id' => $item->comparison_id,
                    'product_id' => $item->product_id,
                    'position' => $item->position,
                ],
            ],
        ]);
    }

    /** @test */
    public function tech_user_can_view_comparison_item()
    {
        $tech = User::role('tech')->first();
        $item = ProductComparisonItem::factory()->create();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('product-comparison-items')
            ->get('/api/v1/product-comparison-items/' . $item->id);

        $response->assertSuccessful();
    }

    /** @test */
    public function customer_can_view_item_from_their_own_comparison()
    {
        $customer = User::role('customer')->first();
        $comparison = ProductComparison::factory()->create(['user_id' => $customer->id]);
        $item = ProductComparisonItem::factory()->create(['comparison_id' => $comparison->id]);

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('product-comparison-items')
            ->get('/api/v1/product-comparison-items/' . $item->id);

        $response->assertSuccessful();
    }

    /** @test */
    public function customer_can_view_item_from_public_comparison()
    {
        $customer = User::role('customer')->first();
        $comparison = ProductComparison::factory()->public()->create();
        $item = ProductComparisonItem::factory()->create(['comparison_id' => $comparison->id]);

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('product-comparison-items')
            ->get('/api/v1/product-comparison-items/' . $item->id);

        $response->assertSuccessful();
    }

    /** @test */
    public function customer_cannot_view_item_from_private_comparison_of_others()
    {
        $customer = User::role('customer')->first();
        $otherUser = User::factory()->create();
        $comparison = ProductComparison::factory()->private()->create(['user_id' => $otherUser->id]);
        $item = ProductComparisonItem::factory()->create(['comparison_id' => $comparison->id]);

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('product-comparison-items')
            ->get('/api/v1/product-comparison-items/' . $item->id);

        $response->assertStatus(403);
    }

    /** @test */
    public function guest_cannot_view_comparison_item()
    {
        $item = ProductComparisonItem::factory()->create();

        $response = $this->jsonApi()
            ->expects('product-comparison-items')
            ->get('/api/v1/product-comparison-items/' . $item->id);

        $response->assertStatus(401);
    }

    /** @test */
    public function can_include_comparison_in_item_response()
    {
        $admin = User::role('admin')->first();
        $item = ProductComparisonItem::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-comparison-items')
            ->get('/api/v1/product-comparison-items/' . $item->id . '?include=comparison');

        $response->assertSuccessful();
        $response->assertJsonStructure([
            'data' => [
                'relationships' => [
                    'comparison',
                ],
            ],
            'included',
        ]);
    }

    /** @test */
    public function can_include_product_in_item_response()
    {
        $admin = User::role('admin')->first();
        $item = ProductComparisonItem::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-comparison-items')
            ->get('/api/v1/product-comparison-items/' . $item->id . '?include=product');

        $response->assertSuccessful();
        $response->assertJsonStructure([
            'data' => [
                'relationships' => [
                    'product',
                ],
            ],
            'included',
        ]);
    }

    /** @test */
    public function returns_404_for_non_existent_item()
    {
        $admin = User::role('admin')->first();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-comparison-items')
            ->get('/api/v1/product-comparison-items/99999');

        $response->assertStatus(404);
    }
}
