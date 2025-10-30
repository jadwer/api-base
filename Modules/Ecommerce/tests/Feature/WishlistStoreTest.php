<?php

namespace Modules\Ecommerce\Tests\Feature;

use Modules\Ecommerce\Models\Wishlist;
use Modules\User\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WishlistStoreTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
    }

    /** @test */
    public function admin_can_create_wishlist()
    {
        $admin = User::role('admin')->first();

        $data = [
            'type' => 'wishlists',
            'attributes' => [
                'name' => 'My Favorite Products',
                'isDefault' => true,
                'isPublic' => false,
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('wishlists')
            ->withData($data)
            ->post('/api/v1/wishlists');

        $response->assertCreated();
        $response->assertJson([
            'data' => [
                'type' => 'wishlists',
                'attributes' => [
                    'name' => 'My Favorite Products',
                    'isDefault' => true,
                    'isPublic' => false,
                    'userId' => $admin->id,
                ],
            ],
        ]);

        $this->assertDatabaseHas('wishlists', [
            'user_id' => $admin->id,
            'name' => 'My Favorite Products',
            'is_default' => true,
            'is_public' => false,
        ]);
    }

    /** @test */
    public function customer_can_create_wishlist()
    {
        $customer = User::role('customer')->first();

        $data = [
            'type' => 'wishlists',
            'attributes' => [
                'name' => 'Christmas Gifts',
                'isDefault' => false,
                'isPublic' => true,
            ],
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('wishlists')
            ->withData($data)
            ->post('/api/v1/wishlists');

        $response->assertCreated();
        $response->assertJson([
            'data' => [
                'type' => 'wishlists',
                'attributes' => [
                    'name' => 'Christmas Gifts',
                    'isDefault' => false,
                    'isPublic' => true,
                    'userId' => $customer->id,
                ],
            ],
        ]);
    }

    /** @test */
    public function tech_user_cannot_create_wishlist()
    {
        $tech = User::role('tech')->first();

        $data = [
            'type' => 'wishlists',
            'attributes' => [
                'name' => 'Test Wishlist',
                'isDefault' => false,
                'isPublic' => false,
            ],
        ];

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('wishlists')
            ->withData($data)
            ->post('/api/v1/wishlists');

        $response->assertStatus(403);
    }

    /** @test */
    public function guest_cannot_create_wishlist()
    {
        $data = [
            'type' => 'wishlists',
            'attributes' => [
                'name' => 'Guest Wishlist',
                'isDefault' => false,
                'isPublic' => false,
            ],
        ];

        $response = $this->jsonApi()
            ->expects('wishlists')
            ->withData($data)
            ->post('/api/v1/wishlists');

        $response->assertStatus(401);
    }

    /** @test */
    public function name_is_optional_and_has_default_value()
    {
        $admin = User::role('admin')->first();

        $data = [
            'type' => 'wishlists',
            'attributes' => [
                'isDefault' => false,
                'isPublic' => false,
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('wishlists')
            ->withData($data)
            ->post('/api/v1/wishlists');

        $response->assertCreated();
        $this->assertDatabaseHas('wishlists', [
            'user_id' => $admin->id,
            'name' => 'My Wishlist',
        ]);
    }

    /** @test */
    public function can_create_public_wishlist()
    {
        $customer = User::role('customer')->first();

        $data = [
            'type' => 'wishlists',
            'attributes' => [
                'name' => 'Public Wishlist',
                'isDefault' => false,
                'isPublic' => true,
            ],
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('wishlists')
            ->withData($data)
            ->post('/api/v1/wishlists');

        $response->assertCreated();
        $response->assertJson([
            'data' => [
                'attributes' => [
                    'isPublic' => true,
                ],
            ],
        ]);

        $this->assertDatabaseHas('wishlists', [
            'user_id' => $customer->id,
            'is_public' => true,
        ]);
    }

    /** @test */
    public function can_create_default_wishlist()
    {
        $customer = User::role('customer')->first();

        $data = [
            'type' => 'wishlists',
            'attributes' => [
                'name' => 'Default Wishlist',
                'isDefault' => true,
                'isPublic' => false,
            ],
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('wishlists')
            ->withData($data)
            ->post('/api/v1/wishlists');

        $response->assertCreated();
        $response->assertJson([
            'data' => [
                'attributes' => [
                    'isDefault' => true,
                ],
            ],
        ]);

        $this->assertDatabaseHas('wishlists', [
            'user_id' => $customer->id,
            'is_default' => true,
        ]);
    }

    /** @test */
    public function name_cannot_exceed_255_characters()
    {
        $admin = User::role('admin')->first();

        $longName = str_repeat('a', 256);

        $data = [
            'type' => 'wishlists',
            'attributes' => [
                'name' => $longName,
                'isDefault' => false,
                'isPublic' => false,
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('wishlists')
            ->withData($data)
            ->post('/api/v1/wishlists');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'name' => 'El nombre no puede exceder 255 caracteres.',
        ]);
    }

    /** @test */
    public function is_default_must_be_boolean()
    {
        $admin = User::role('admin')->first();

        $data = [
            'type' => 'wishlists',
            'attributes' => [
                'name' => 'Test Wishlist',
                'isDefault' => 'not-a-boolean',
                'isPublic' => false,
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('wishlists')
            ->withData($data)
            ->post('/api/v1/wishlists');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'isDefault' => 'El campo predeterminado debe ser verdadero o falso.',
        ]);
    }

    /** @test */
    public function is_public_must_be_boolean()
    {
        $admin = User::role('admin')->first();

        $data = [
            'type' => 'wishlists',
            'attributes' => [
                'name' => 'Test Wishlist',
                'isDefault' => false,
                'isPublic' => 'not-a-boolean',
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('wishlists')
            ->withData($data)
            ->post('/api/v1/wishlists');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'isPublic' => 'El campo público debe ser verdadero o falso.',
        ]);
    }
}
