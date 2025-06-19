<?php

namespace Modules\User\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed del módulo de usuarios
        $this->artisan('module:seed', ['module' => 'User']);
    }

    public function test_authenticated_user_can_see_users_list(): void
    {
        $authUser = User::where('email', 'god@example.com')->firstOrFail();
        $this->actingAs($authUser, 'sanctum');

        User::factory()->create(['name' => 'Zoe']);
        User::factory()->create(['name' => 'Ana']);
        User::factory()->create(['name' => 'Luis']);

        $response = $this->getJson('/api/v1/users', [
            'Accept' => 'application/vnd.api+json',
        ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'attributes' => [
                        'name',
                        'email',
                        'status',
                        'createdAt',
                        'updatedAt',
                    ]
                ]
            ]
        ]);
    }

    public function test_unauthenticated_user_cannot_access_users_list(): void
    {
        $response = $this->getJson('/api/v1/users', [
            'Accept' => 'application/vnd.api+json',
        ]);

        $response->assertUnauthorized();
    }

    public function test_users_list_can_be_sorted_by_name(): void
    {
        $authUser = User::where('email', 'god@example.com')->firstOrFail();
        $this->actingAs($authUser, 'sanctum');

        // Crea usuarios en orden no alfabético
        User::factory()->create(['name' => 'Zoe']);
        User::factory()->create(['name' => 'Ana']);
        User::factory()->create(['name' => 'Luis']);

        $response = $this->getJson('/api/v1/users?sort=name', [
            'Accept' => 'application/vnd.api+json',
        ]);

        $response->assertOk();

        $names = collect($response->json('data'))->pluck('attributes.name')->all();

        // Verificamos que estén ordenados alfabéticamente
        $expected = collect($names)->sort()->values()->all();
        $this->assertSame($expected, $names);
    }

    public function test_users_list_can_be_sorted_by_name_descending(): void
    {
        $authUser = User::where('email', 'god@example.com')->firstOrFail();
        $this->actingAs($authUser, 'sanctum');

        User::factory()->create(['name' => 'Carlos']);
        User::factory()->create(['name' => 'Ana']);
        User::factory()->create(['name' => 'Beto']);

        $response = $this->getJson('/api/v1/users?sort=-name', [
            'Accept' => 'application/vnd.api+json',
        ]);

        $response->assertOk();

        $names = collect($response->json('data'))->pluck('attributes.name')->toArray();

        $sorted = $names;
        rsort($sorted); // Orden descendente

        $this->assertEquals($sorted, $names);
    }
}
