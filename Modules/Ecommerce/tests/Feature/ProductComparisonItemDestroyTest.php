<?php

namespace Modules\Ecommerce\Tests\Feature;

use Modules\Ecommerce\Models\ProductComparison;
use Modules\Ecommerce\Models\ProductComparisonItem;
use Modules\User\Models\User;
use Tests\TestCase;

class ProductComparisonItemDestroyTest extends TestCase
{
    /** @test */
    public function admin_can_delete_any_comparison_item()
    {
        $admin = User::role('admin')->first();
        $item = ProductComparisonItem::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-comparison-items')
            ->delete('/api/v1/product-comparison-items/' . $item->id);

        $response->assertNoContent();

        $this->assertDatabaseMissing('product_comparison_items', [
            'id' => $item->id,
        ]);
    }

    /** @test */
    public function customer_can_delete_item_from_their_own_comparison()
    {
        $customer = User::role('customer')->first();
        $comparison = ProductComparison::factory()->create(['user_id' => $customer->id]);
        $item = ProductComparisonItem::factory()->create(['comparison_id' => $comparison->id]);

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('product-comparison-items')
            ->delete('/api/v1/product-comparison-items/' . $item->id);

        $response->assertNoContent();

        $this->assertDatabaseMissing('product_comparison_items', [
            'id' => $item->id,
        ]);
    }

    /** @test */
    public function customer_cannot_delete_item_from_other_users_comparison()
    {
        $customer = User::role('customer')->first();
        $otherUser = User::factory()->create();
        $comparison = ProductComparison::factory()->create(['user_id' => $otherUser->id]);
        $item = ProductComparisonItem::factory()->create(['comparison_id' => $comparison->id]);

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('product-comparison-items')
            ->delete('/api/v1/product-comparison-items/' . $item->id);

        $response->assertStatus(403);

        $this->assertDatabaseHas('product_comparison_items', [
            'id' => $item->id,
        ]);
    }

    /** @test */
    public function tech_user_cannot_delete_comparison_item()
    {
        $tech = User::role('tech')->first();
        $item = ProductComparisonItem::factory()->create();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('product-comparison-items')
            ->delete('/api/v1/product-comparison-items/' . $item->id);

        $response->assertStatus(403);

        $this->assertDatabaseHas('product_comparison_items', [
            'id' => $item->id,
        ]);
    }

    /** @test */
    public function guest_cannot_delete_comparison_item()
    {
        $item = ProductComparisonItem::factory()->create();

        $response = $this->jsonApi()
            ->expects('product-comparison-items')
            ->delete('/api/v1/product-comparison-items/' . $item->id);

        $response->assertStatus(401);

        $this->assertDatabaseHas('product_comparison_items', [
            'id' => $item->id,
        ]);
    }

    /** @test */
    public function returns_404_for_non_existent_item()
    {
        $admin = User::role('admin')->first();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-comparison-items')
            ->delete('/api/v1/product-comparison-items/99999');

        $response->assertStatus(404);
    }

    /** @test */
    public function deleting_item_does_not_delete_comparison()
    {
        $admin = User::role('admin')->first();
        $comparison = ProductComparison::factory()->hasItems(3)->create();
        $item = $comparison->items->first();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-comparison-items')
            ->delete('/api/v1/product-comparison-items/' . $item->id);

        $response->assertNoContent();

        $this->assertDatabaseHas('product_comparisons', [
            'id' => $comparison->id,
        ]);

        $this->assertDatabaseCount('product_comparison_items', 2);
    }

    /** @test */
    public function deleting_item_does_not_delete_product()
    {
        $admin = User::role('admin')->first();
        $item = ProductComparisonItem::factory()->create();
        $productId = $item->product_id;

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-comparison-items')
            ->delete('/api/v1/product-comparison-items/' . $item->id);

        $response->assertNoContent();

        $this->assertDatabaseHas('products', [
            'id' => $productId,
        ]);
    }

    /** @test */
    public function can_delete_all_items_from_comparison()
    {
        $customer = User::role('customer')->first();
        $comparison = ProductComparison::factory()->hasItems(3)->create(['user_id' => $customer->id]);

        foreach ($comparison->items as $item) {
            $response = $this->actingAs($customer, 'sanctum')
                ->jsonApi()
                ->expects('product-comparison-items')
                ->delete('/api/v1/product-comparison-items/' . $item->id);

            $response->assertNoContent();
        }

        $this->assertDatabaseCount('product_comparison_items', 0);
        $this->assertDatabaseHas('product_comparisons', [
            'id' => $comparison->id,
        ]);
    }
}
