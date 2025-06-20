<?php

namespace Modules\User\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserDestroyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Ejecuta los seeders del módulo User (incluye roles y users base)
        $this->artisan('module:seed', ['module' => 'User']);
    }

    public function test_admin_can_delete_regular_user(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $target = User::factory()->create();

        $this->actingAs($admin, 'sanctum');

        $response = $this->jsonApi()
            ->delete("/api/v1/users/{$target->id}");

        $response->assertStatus(204);
        $this->assertSoftDeleted('users', ['id' => $target->id]);
    }

    public function test_admin_cannot_delete_god_user(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $god = User::where('email', 'god@example.com')->firstOrFail();

        $this->actingAs($admin, 'sanctum');

        $response = $this->jsonApi()
            ->delete("/api/v1/users/{$god->id}");

        $response->assertForbidden();
        $response->assertSeeText('No puedes eliminar a un usuario god.');
    }

    public function test_tech_cannot_delete_admin_or_god(): void
    {
        $tech = User::where('email', 'tech@example.com')->firstOrFail();
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $god = User::where('email', 'god@example.com')->firstOrFail();

        $this->actingAs($tech, 'sanctum');


        $this->jsonApi()
            ->delete("/api/v1/users/{$admin->id}")
            ->assertForbidden()
            ->assertJsonFragment([
                'detail' => 'Un técnico no puede eliminar a un administrador.',
            ]);
        $this->jsonApi()
            ->delete("/api/v1/users/{$god->id}")
            ->assertForbidden()
            ->assertSeeText('No puedes eliminar a un usuario god.');
    }

    public function test_god_can_delete_any_user(): void
    {
        $god = User::where('email', 'god@example.com')->firstOrFail();
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $tech = User::where('email', 'tech@example.com')->firstOrFail();
        $target = User::factory()->create();

        $this->actingAs($god, 'sanctum');

        $this->jsonApi()->delete("/api/v1/users/{$admin->id}")->assertStatus(204);
        $this->jsonApi()->delete("/api/v1/users/{$tech->id}")->assertStatus(204);
        $this->jsonApi()->delete("/api/v1/users/{$target->id}")->assertStatus(204);

        $this->assertSoftDeleted('users', ['id' => $admin->id]);
        $this->assertSoftDeleted('users', ['id' => $tech->id]);
        $this->assertSoftDeleted('users', ['id' => $target->id]);
    }

    public function test_unauthenticated_user_cannot_delete_anyone(): void
    {
        $target = User::factory()->create();

        $this->jsonApi()
            ->delete("/api/v1/users/{$target->id}")
            ->assertStatus(401);
    }
}
