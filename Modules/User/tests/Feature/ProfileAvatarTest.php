<?php

namespace Modules\User\Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\User\Models\User;
use Tests\TestCase;

/**
 * Perfil propio (2026-09-30): avatar (preset o foto) y validacion de
 * PATCH /profile (bug H3: se podia cambiar el propio estado y correo).
 */
class ProfileAvatarTest extends TestCase
{
    public function test_profile_update_only_changes_the_name(): void
    {
        $user = User::factory()->create(['status' => 'active', 'email' => 'yo@lab.mx']);

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/v1/profile', ['name' => 'Nuevo Nombre', 'email' => 'otro@lab.mx', 'status' => 'banned'])
            ->assertOk()
            ->assertJsonPath('data.attributes.name', 'Nuevo Nombre');

        $user->refresh();
        $this->assertSame('yo@lab.mx', $user->email);
        $this->assertSame('active', $user->status);
    }

    public function test_profile_update_requires_a_name(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum')
            ->patchJson('/api/v1/profile', ['name' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors('name');
    }

    public function test_choose_a_preset_avatar(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/profile/avatar', ['preset' => 3])
            ->assertOk()
            ->assertJsonPath('data.attributes.avatar', 'preset:3');

        $this->actingAs($user, 'sanctum')->getJson('/api/v1/profile')->assertJsonPath('data.attributes.avatar', 'preset:3');
        $this->actingAs($user, 'sanctum')->postJson('/api/v1/profile/avatar', ['preset' => 9])->assertStatus(422);
    }

    public function test_upload_photo_replaces_previous_and_can_be_removed(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $first = $this->actingAs($user, 'sanctum')
            ->post('/api/v1/profile/avatar', ['avatar' => UploadedFile::fake()->image('yo.jpg', 300, 300)], ['Accept' => 'application/json'])
            ->assertOk();
        $firstPath = $user->fresh()->avatar;
        Storage::disk('public')->assertExists($firstPath);
        $this->assertStringContainsString('/storage/avatars/', $first->json('data.attributes.avatar'));

        $this->actingAs($user, 'sanctum')
            ->post('/api/v1/profile/avatar', ['avatar' => UploadedFile::fake()->image('yo2.png', 200, 200)], ['Accept' => 'application/json'])
            ->assertOk();
        Storage::disk('public')->assertMissing($firstPath);

        $this->actingAs($user, 'sanctum')->deleteJson('/api/v1/profile/avatar')->assertOk()->assertJsonPath('data.attributes.avatar', null);
        $this->assertNull($user->fresh()->avatar);
    }

    public function test_upload_rejects_non_images(): void
    {
        Storage::fake('public');

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->post('/api/v1/profile/avatar', ['avatar' => UploadedFile::fake()->create('virus.pdf', 10, 'application/pdf')], ['Accept' => 'application/json'])
            ->assertStatus(422);
    }

    public function test_guest_cannot_touch_the_profile(): void
    {
        $this->postJson('/api/v1/profile/avatar', ['preset' => 1])->assertStatus(401);
    }
}
