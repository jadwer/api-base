<?php

namespace Modules\Ecommerce\Tests\Feature;

use Modules\Ecommerce\Models\ProductComparison;
use Modules\User\Models\User;
use Tests\TestCase;

class ProductComparisonStoreTest extends TestCase
{
    public function test_admin_can_create_comparison()
    {
        $admin = User::role('admin')->first();

        $data = [
            'type' => 'product-comparisons',
            'attributes' => [
                'name' => 'Laptop Comparison',
                'isPublic' => false,
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-comparisons')
            ->withData($data)
            ->post('/api/v1/product-comparisons');

        $response->assertCreated();
        $response->assertJson([
            'data' => [
                'type' => 'product-comparisons',
                'attributes' => [
                    'name' => 'Laptop Comparison',
                    'isPublic' => false,
                ],
            ],
        ]);

        $this->assertDatabaseHas('product_comparisons', [
            'user_id' => $admin->id,
            'name' => 'Laptop Comparison',
            'is_public' => false,
        ]);
    }

    public function test_customer_can_create_comparison()
    {
        $customer = User::role('customer')->first();

        $data = [
            'type' => 'product-comparisons',
            'attributes' => [
                'name' => 'Phone Comparison',
                'isPublic' => true,
            ],
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('product-comparisons')
            ->withData($data)
            ->post('/api/v1/product-comparisons');

        $response->assertCreated();
        $response->assertJson([
            'data' => [
                'type' => 'product-comparisons',
                'attributes' => [
                    'name' => 'Phone Comparison',
                    'isPublic' => true,
                ],
            ],
        ]);

        $this->assertDatabaseHas('product_comparisons', [
            'user_id' => $customer->id,
            'name' => 'Phone Comparison',
            'is_public' => true,
        ]);
    }

    public function test_tech_user_cannot_create_comparison()
    {
        $tech = User::role('tech')->first();

        $data = [
            'type' => 'product-comparisons',
            'attributes' => [
                'name' => 'Test Comparison',
                'isPublic' => false,
            ],
        ];

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('product-comparisons')
            ->withData($data)
            ->post('/api/v1/product-comparisons');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_create_comparison()
    {
        $data = [
            'type' => 'product-comparisons',
            'attributes' => [
                'name' => 'Guest Comparison',
                'isPublic' => false,
            ],
        ];

        $response = $this->jsonApi()
            ->expects('product-comparisons')
            ->withData($data)
            ->post('/api/v1/product-comparisons');

        $response->assertStatus(401);
    }

    public function test_name_is_required()
    {
        $admin = User::role('admin')->first();

        $data = [
            'type' => 'product-comparisons',
            'attributes' => [
                'isPublic' => false,
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-comparisons')
            ->withData($data)
            ->post('/api/v1/product-comparisons');

        $response->assertStatus(422);
        $response->assertJsonFragment([
            'source' => ['pointer' => '/data/attributes/name']
        ]);
    }

    public function test_can_create_public_comparison()
    {
        $customer = User::role('customer')->first();

        $data = [
            'type' => 'product-comparisons',
            'attributes' => [
                'name' => 'Public Comparison',
                'isPublic' => true,
            ],
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('product-comparisons')
            ->withData($data)
            ->post('/api/v1/product-comparisons');

        $response->assertCreated();
        $response->assertJson([
            'data' => [
                'attributes' => [
                    'isPublic' => true,
                ],
            ],
        ]);

        $this->assertDatabaseHas('product_comparisons', [
            'user_id' => $customer->id,
            'is_public' => true,
        ]);
    }

    public function test_is_public_defaults_to_false()
    {
        $customer = User::role('customer')->first();

        $data = [
            'type' => 'product-comparisons',
            'attributes' => [
                'name' => 'Default Privacy Comparison',
            ],
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('product-comparisons')
            ->withData($data)
            ->post('/api/v1/product-comparisons');

        $response->assertCreated();
        $response->assertJson([
            'data' => [
                'attributes' => [
                    'isPublic' => false,
                ],
            ],
        ]);
    }

    public function test_name_cannot_exceed_255_characters()
    {
        $admin = User::role('admin')->first();

        $longName = str_repeat('a', 256);

        $data = [
            'type' => 'product-comparisons',
            'attributes' => [
                'name' => $longName,
                'isPublic' => false,
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-comparisons')
            ->withData($data)
            ->post('/api/v1/product-comparisons');

        $response->assertStatus(422);
        $response->assertJsonFragment([
            'source' => ['pointer' => '/data/attributes/name']
        ]);
    }

    public function test_is_public_must_be_boolean()
    {
        $admin = User::role('admin')->first();

        $data = [
            'type' => 'product-comparisons',
            'attributes' => [
                'name' => 'Test Comparison',
                'isPublic' => 'not-a-boolean',
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-comparisons')
            ->withData($data)
            ->post('/api/v1/product-comparisons');

        $response->assertStatus(422);
        $response->assertJsonFragment([
            'source' => ['pointer' => '/data/attributes/isPublic']
        ]);
    }

    public function test_user_is_automatically_set_to_authenticated_user()
    {
        $customer = User::role('customer')->first();

        $data = [
            'type' => 'product-comparisons',
            'attributes' => [
                'name' => 'My Comparison',
                'isPublic' => false,
            ],
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('product-comparisons')
            ->withData($data)
            ->post('/api/v1/product-comparisons');

        $response->assertCreated();

        $comparisonId = $response->json('data.id');
        $comparison = ProductComparison::find($comparisonId);

        $this->assertEquals($customer->id, $comparison->user_id);
    }
}
