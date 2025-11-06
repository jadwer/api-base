<?php

namespace Modules\Ecommerce\Tests\Feature;

use Modules\Ecommerce\Models\ProductComparison;
use Modules\User\Models\User;
use Tests\TestCase;

class ProductComparisonDestroyTest extends TestCase
{
    /** @test */
    public function admin_can_delete_any_comparison()
    {
        $admin = User::role('admin')->first();
        $comparison = ProductComparison::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-comparisons')
            ->delete('/api/v1/product-comparisons/' . $comparison->id);

        $response->assertNoContent();

        $this->assertDatabaseMissing('product_comparisons', [
            'id' => $comparison->id,
        ]);
    }

    /** @test */
    public function customer_can_delete_their_own_comparison()
    {
        $customer = User::role('customer')->first();
        $comparison = ProductComparison::factory()->create(['userId' => $customer->id]);

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('product-comparisons')
            ->delete('/api/v1/product-comparisons/' . $comparison->id);

        $response->assertNoContent();

        $this->assertDatabaseMissing('product_comparisons', [
            'id' => $comparison->id,
        ]);
    }

    /** @test */
    public function customer_cannot_delete_other_users_comparison()
    {
        $customer = User::role('customer')->first();
        $otherUser = User::factory()->create();
        $comparison = ProductComparison::factory()->create(['userId' => $otherUser->id]);

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('product-comparisons')
            ->delete('/api/v1/product-comparisons/' . $comparison->id);

        $response->assertStatus(403);

        $this->assertDatabaseHas('product_comparisons', [
            'id' => $comparison->id,
        ]);
    }

    /** @test */
    public function tech_user_cannot_delete_comparison()
    {
        $tech = User::role('tech')->first();
        $comparison = ProductComparison::factory()->create();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('product-comparisons')
            ->delete('/api/v1/product-comparisons/' . $comparison->id);

        $response->assertStatus(403);

        $this->assertDatabaseHas('product_comparisons', [
            'id' => $comparison->id,
        ]);
    }

    /** @test */
    public function guest_cannot_delete_comparison()
    {
        $comparison = ProductComparison::factory()->create();

        $response = $this->jsonApi()
            ->expects('product-comparisons')
            ->delete('/api/v1/product-comparisons/' . $comparison->id);

        $response->assertStatus(401);

        $this->assertDatabaseHas('product_comparisons', [
            'id' => $comparison->id,
        ]);
    }

    /** @test */
    public function returns_404_for_non_existent_comparison()
    {
        $admin = User::role('admin')->first();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-comparisons')
            ->delete('/api/v1/product-comparisons/99999');

        $response->assertStatus(404);
    }

    /** @test */
    public function deleting_comparison_cascades_to_items()
    {
        $admin = User::role('admin')->first();
        $comparison = ProductComparison::factory()->hasItems(3)->create();

        $this->assertDatabaseCount('product_comparison_items', 3);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-comparisons')
            ->delete('/api/v1/product-comparisons/' . $comparison->id);

        $response->assertNoContent();

        $this->assertDatabaseCount('product_comparison_items', 0);
    }

    /** @test */
    public function customer_can_delete_public_comparison_they_own()
    {
        $customer = User::role('customer')->first();
        $comparison = ProductComparison::factory()->public()->create(['userId' => $customer->id]);

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('product-comparisons')
            ->delete('/api/v1/product-comparisons/' . $comparison->id);

        $response->assertNoContent();

        $this->assertDatabaseMissing('product_comparisons', [
            'id' => $comparison->id,
        ]);
    }

    /** @test */
    public function customer_cannot_delete_public_comparison_from_others()
    {
        $customer = User::role('customer')->first();
        $otherUser = User::factory()->create();
        $comparison = ProductComparison::factory()->public()->create(['userId' => $otherUser->id]);

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('product-comparisons')
            ->delete('/api/v1/product-comparisons/' . $comparison->id);

        $response->assertStatus(403);
    }
}
