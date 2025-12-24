<?php

namespace Modules\Ecommerce\Tests\Feature;

use Modules\Ecommerce\Models\ProductComparison;
use Modules\User\Models\User;
use Tests\TestCase;

class ProductComparisonUpdateTest extends TestCase
{
    public function test_admin_can_update_any_comparison()
    {
        $admin = User::role('admin')->first();
        $comparison = ProductComparison::factory()->create(['name' => 'Old Name']);

        $data = [
            'type' => 'product-comparisons',
            'id' => (string) $comparison->id,
            'attributes' => [
                'name' => 'Updated Name',
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-comparisons')
            ->withData($data)
            ->patch('/api/v1/product-comparisons/' . $comparison->id);

        $response->assertSuccessful();
        $response->assertJson([
            'data' => [
                'type' => 'product-comparisons',
                'id' => (string) $comparison->id,
                'attributes' => [
                    'name' => 'Updated Name',
                ],
            ],
        ]);

        $this->assertDatabaseHas('product_comparisons', [
            'id' => $comparison->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_customer_can_update_their_own_comparison()
    {
        $customer = User::role('customer')->first();
        $comparison = ProductComparison::factory()->create([
            'user_id' => $customer->id,
            'name' => 'My Comparison',
        ]);

        $data = [
            'type' => 'product-comparisons',
            'id' => (string) $comparison->id,
            'attributes' => [
                'name' => 'My Updated Comparison',
            ],
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('product-comparisons')
            ->withData($data)
            ->patch('/api/v1/product-comparisons/' . $comparison->id);

        $response->assertSuccessful();
        $response->assertJson([
            'data' => [
                'attributes' => [
                    'name' => 'My Updated Comparison',
                ],
            ],
        ]);
    }

    public function test_customer_cannot_update_other_users_comparison()
    {
        $customer = User::role('customer')->first();
        $otherUser = User::factory()->create();
        $comparison = ProductComparison::factory()->create(['user_id' => $otherUser->id]);

        $data = [
            'type' => 'product-comparisons',
            'id' => (string) $comparison->id,
            'attributes' => [
                'name' => 'Hacked Name',
            ],
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('product-comparisons')
            ->withData($data)
            ->patch('/api/v1/product-comparisons/' . $comparison->id);

        $response->assertStatus(403);
    }

    public function test_tech_user_cannot_update_comparison()
    {
        $tech = User::role('tech')->first();
        $comparison = ProductComparison::factory()->create();

        $data = [
            'type' => 'product-comparisons',
            'id' => (string) $comparison->id,
            'attributes' => [
                'name' => 'New Name',
            ],
        ];

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('product-comparisons')
            ->withData($data)
            ->patch('/api/v1/product-comparisons/' . $comparison->id);

        $response->assertStatus(403);
    }

    public function test_guest_cannot_update_comparison()
    {
        $comparison = ProductComparison::factory()->create();

        $data = [
            'type' => 'product-comparisons',
            'id' => (string) $comparison->id,
            'attributes' => [
                'name' => 'Guest Update',
            ],
        ];

        $response = $this->jsonApi()
            ->expects('product-comparisons')
            ->withData($data)
            ->patch('/api/v1/product-comparisons/' . $comparison->id);

        $response->assertStatus(401);
    }

    public function test_can_update_is_public_field()
    {
        $customer = User::role('customer')->first();
        $comparison = ProductComparison::factory()->private()->create(['user_id' => $customer->id]);

        $data = [
            'type' => 'product-comparisons',
            'id' => (string) $comparison->id,
            'attributes' => [
                'isPublic' => true,
            ],
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('product-comparisons')
            ->withData($data)
            ->patch('/api/v1/product-comparisons/' . $comparison->id);

        $response->assertSuccessful();
        $response->assertJson([
            'data' => [
                'attributes' => [
                    'isPublic' => true,
                ],
            ],
        ]);

        $this->assertDatabaseHas('product_comparisons', [
            'id' => $comparison->id,
            'is_public' => true,
        ]);
    }

    public function test_can_make_public_comparison_private()
    {
        $customer = User::role('customer')->first();
        $comparison = ProductComparison::factory()->public()->create(['user_id' => $customer->id]);

        $data = [
            'type' => 'product-comparisons',
            'id' => (string) $comparison->id,
            'attributes' => [
                'isPublic' => false,
            ],
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('product-comparisons')
            ->withData($data)
            ->patch('/api/v1/product-comparisons/' . $comparison->id);

        $response->assertSuccessful();

        $this->assertDatabaseHas('product_comparisons', [
            'id' => $comparison->id,
            'is_public' => false,
        ]);
    }

    public function test_name_cannot_exceed_255_characters_on_update()
    {
        $admin = User::role('admin')->first();
        $comparison = ProductComparison::factory()->create();

        $longName = str_repeat('a', 256);

        $data = [
            'type' => 'product-comparisons',
            'id' => (string) $comparison->id,
            'attributes' => [
                'name' => $longName,
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-comparisons')
            ->withData($data)
            ->patch('/api/v1/product-comparisons/' . $comparison->id);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    public function test_returns_404_for_non_existent_comparison()
    {
        $admin = User::role('admin')->first();

        $data = [
            'type' => 'product-comparisons',
            'id' => '99999',
            'attributes' => [
                'name' => 'New Name',
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-comparisons')
            ->withData($data)
            ->patch('/api/v1/product-comparisons/99999');

        $response->assertStatus(404);
    }
}
