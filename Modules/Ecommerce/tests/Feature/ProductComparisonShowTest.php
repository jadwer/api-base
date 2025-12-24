<?php

namespace Modules\Ecommerce\Tests\Feature;

use Modules\Ecommerce\Models\ProductComparison;
use Modules\User\Models\User;
use Tests\TestCase;

class ProductComparisonShowTest extends TestCase
{
    public function test_admin_can_view_any_comparison()
    {
        $admin = User::role('admin')->first();
        $comparison = ProductComparison::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-comparisons')
            ->get('/api/v1/product-comparisons/' . $comparison->id);

        $response->assertSuccessful();
        $response->assertJson([
            'data' => [
                'type' => 'product-comparisons',
                'id' => (string) $comparison->id,
                'attributes' => [
                    'name' => $comparison->name,
                    'isPublic' => $comparison->is_public,
                    'user_id' => $comparison->user_id,
                ],
            ],
        ]);
    }

    public function test_tech_user_can_view_any_comparison()
    {
        $tech = User::role('tech')->first();
        $comparison = ProductComparison::factory()->create();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('product-comparisons')
            ->get('/api/v1/product-comparisons/' . $comparison->id);

        $response->assertSuccessful();
    }

    public function test_customer_can_view_their_own_comparison()
    {
        $customer = User::role('customer')->first();
        $comparison = ProductComparison::factory()->create(['user_id' => $customer->id]);

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('product-comparisons')
            ->get('/api/v1/product-comparisons/' . $comparison->id);

        $response->assertSuccessful();
        $response->assertJson([
            'data' => [
                'type' => 'product-comparisons',
                'id' => (string) $comparison->id,
                'attributes' => [
                    'user_id' => $customer->id,
                ],
            ],
        ]);
    }

    public function test_customer_can_view_public_comparison_from_others()
    {
        $customer = User::role('customer')->first();
        $otherUser = User::factory()->create();
        $comparison = ProductComparison::factory()->public()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('product-comparisons')
            ->get('/api/v1/product-comparisons/' . $comparison->id);

        $response->assertSuccessful();
        $response->assertJson([
            'data' => [
                'type' => 'product-comparisons',
                'attributes' => [
                    'isPublic' => true,
                ],
            ],
        ]);
    }

    public function test_customer_cannot_view_private_comparison_from_others()
    {
        $customer = User::role('customer')->first();
        $otherUser = User::factory()->create();
        $comparison = ProductComparison::factory()->private()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('product-comparisons')
            ->get('/api/v1/product-comparisons/' . $comparison->id);

        $response->assertStatus(403);
    }

    public function test_guest_cannot_view_comparison()
    {
        $comparison = ProductComparison::factory()->create();

        $response = $this->jsonApi()
            ->expects('product-comparisons')
            ->get('/api/v1/product-comparisons/' . $comparison->id);

        $response->assertStatus(401);
    }

    public function test_can_include_user_in_comparison_response()
    {
        $admin = User::role('admin')->first();
        $comparison = ProductComparison::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-comparisons')
            ->get('/api/v1/product-comparisons/' . $comparison->id . '?include=user');

        $response->assertSuccessful();
        $response->assertJsonStructure([
            'data' => [
                'relationships' => [
                    'user',
                ],
            ],
            'included',
        ]);
    }

    public function test_can_include_items_in_comparison_response()
    {
        $admin = User::role('admin')->first();
        $comparison = ProductComparison::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-comparisons')
            ->get('/api/v1/product-comparisons/' . $comparison->id . '?include=items');

        $response->assertSuccessful();
    }

    public function test_returns_404_for_non_existent_comparison()
    {
        $admin = User::role('admin')->first();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-comparisons')
            ->get('/api/v1/product-comparisons/99999');

        $response->assertStatus(404);
    }
}
