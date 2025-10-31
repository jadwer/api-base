<?php

namespace Modules\Ecommerce\Tests\Feature;

use Modules\Ecommerce\Models\ProductComparison;
use Modules\User\Models\User;
use Tests\TestCase;

class ProductComparisonIndexTest extends TestCase
{
    /** @test */
    public function admin_can_list_all_comparisons()
    {
        $admin = User::role('admin')->first();

        ProductComparison::factory()->count(3)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->get('/api/v1/product-comparisons');

        $response->assertSuccessful();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'type',
                    'id',
                    'attributes' => [
                        'userId',
                        'name',
                        'isPublic',
                        'createdAt',
                        'updatedAt',
                    ],
                ],
            ],
        ]);
    }

    /** @test */
    public function tech_user_can_list_comparisons()
    {
        $tech = User::role('tech')->first();

        ProductComparison::factory()->count(2)->create();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->get('/api/v1/product-comparisons');

        $response->assertSuccessful();
    }

    /** @test */
    public function customer_can_list_their_own_and_public_comparisons()
    {
        $customer = User::role('customer')->first();

        // Create customer's own comparisons
        ProductComparison::factory()->count(2)->create(['user_id' => $customer->id]);

        // Create public comparisons from other users
        ProductComparison::factory()->public()->count(2)->create();

        // Create private comparisons from other users (should not be visible)
        ProductComparison::factory()->private()->count(2)->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->get('/api/v1/product-comparisons');

        $response->assertSuccessful();
    }

    /** @test */
    public function guest_cannot_list_comparisons()
    {
        ProductComparison::factory()->count(3)->create();

        $response = $this->jsonApi()
            ->get('/api/v1/product-comparisons');

        $response->assertStatus(401);
    }

    /** @test */
    public function can_filter_comparisons_by_user_id()
    {
        $admin = User::role('admin')->first();
        $user = User::factory()->create();

        ProductComparison::factory()->count(2)->create(['user_id' => $user->id]);
        ProductComparison::factory()->count(3)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->get('/api/v1/product-comparisons?filter[userId]=' . $user->id);

        $response->assertSuccessful();
        $response->assertJsonCount(2, 'data');
    }

    /** @test */
    public function can_filter_comparisons_by_is_public()
    {
        $admin = User::role('admin')->first();

        ProductComparison::factory()->public()->count(2)->create();
        ProductComparison::factory()->private()->count(3)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->get('/api/v1/product-comparisons?filter[isPublic]=1');

        $response->assertSuccessful();
        $response->assertJsonCount(2, 'data');
    }

    /** @test */
    public function can_sort_comparisons_by_name()
    {
        $admin = User::role('admin')->first();

        ProductComparison::factory()->create(['name' => 'Comparison C']);
        ProductComparison::factory()->create(['name' => 'Comparison A']);
        ProductComparison::factory()->create(['name' => 'Comparison B']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->get('/api/v1/product-comparisons?sort=name');

        $response->assertSuccessful();

        $names = collect($response->json('data'))->pluck('attributes.name')->toArray();
        $this->assertEquals(['Comparison A', 'Comparison B', 'Comparison C'], $names);
    }

    /** @test */
    public function can_include_user_relationship()
    {
        $admin = User::role('admin')->first();

        ProductComparison::factory()->count(2)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->get('/api/v1/product-comparisons?include=user');

        $response->assertSuccessful();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'relationships' => [
                        'user',
                    ],
                ],
            ],
            'included',
        ]);
    }

    /** @test */
    public function can_include_items_relationship()
    {
        $admin = User::role('admin')->first();

        $comparison = ProductComparison::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->get('/api/v1/product-comparisons?include=items');

        $response->assertSuccessful();
    }
}
