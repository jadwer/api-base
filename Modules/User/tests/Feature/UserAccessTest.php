<?php

namespace Modules\User\Tests\Feature;

use Modules\PermissionManager\Models\Permission;
use Modules\PermissionManager\Models\Role;
use Modules\User\Models\User;
use Tests\TestCase;

/**
 * Permisos por usuario con rol = plantilla (decision A, 2026-09-24).
 */
class UserAccessTest extends TestCase
{
    private function god(): User
    {
        return User::where('email', 'god@example.com')->firstOrFail();
    }

    private function template(string $name, array $permissionNames): Role
    {
        $role = Role::firstOrCreate(['name' => $name, 'guard_name' => 'api']);
        $role->syncPermissions($permissionNames);

        return $role;
    }

    private function ids(array $names): array
    {
        return Permission::whereIn('name', $names)->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    private function putAccess(User $actor, User $user, array $payload)
    {
        return $this->actingAs($actor, 'sanctum')->putJson("/api/v1/users/{$user->id}/access", $payload);
    }

    public function test_template_permissions_minus_one_become_direct_permissions(): void
    {
        $this->template('ventas-plantilla', ['quotes.index', 'quotes.show', 'quotes.store', 'quotes.update']);
        $user = User::factory()->create();

        $response = $this->putAccess($this->getAdminUser(), $user, [
            'systemRoles' => [],
            'template' => 'ventas-plantilla',
            'permissionIds' => $this->ids(['quotes.index', 'quotes.show', 'quotes.store', 'contacts.index']),
        ]);

        $response->assertOk();
        $user = $user->fresh();
        $this->assertTrue($user->can('quotes.store'));
        $this->assertTrue($user->can('contacts.index'), 'se puede agregar un permiso que la plantilla no trae');
        $this->assertFalse($user->can('quotes.update'), 'se puede quitar un permiso que la plantilla si trae');
        $this->assertSame([], $user->getRoleNames()->all(), 'la plantilla no queda como rol');
        $this->assertSame('ventas-plantilla', $user->permission_template);
    }

    public function test_editing_the_template_later_does_not_change_existing_users(): void
    {
        $role = $this->template('ventas-plantilla', ['quotes.index']);
        $user = User::factory()->create();
        $this->putAccess($this->getAdminUser(), $user, [
            'systemRoles' => [],
            'template' => 'ventas-plantilla',
            'permissionIds' => $this->ids(['quotes.index']),
        ])->assertOk();

        $role->givePermissionTo('quotes.destroy');

        $this->assertFalse($user->fresh()->can('quotes.destroy'));
    }

    public function test_existing_user_shows_effective_permissions_and_keeps_access_when_converted(): void
    {
        $this->template('legacy-rol', ['quotes.index', 'sales-orders.index']);
        $user = User::factory()->create();
        $user->assignRole('legacy-rol');

        $show = $this->actingAs($this->getAdminUser(), 'sanctum')->getJson("/api/v1/users/{$user->id}/access");
        $show->assertOk();
        $effective = $show->json('data.effectivePermissionIds');
        $this->assertEqualsCanonicalizing($this->ids(['quotes.index', 'sales-orders.index']), $effective);
        $this->assertSame(['legacy-rol'], $show->json('data.templateRoles'));

        $this->putAccess($this->getAdminUser(), $user, [
            'systemRoles' => [],
            'template' => 'legacy-rol',
            'permissionIds' => $effective,
        ])->assertOk();

        $user = $user->fresh();
        $this->assertTrue($user->can('quotes.index'));
        $this->assertTrue($user->can('sales-orders.index'));
        $this->assertFalse($user->hasRole('legacy-rol'));
    }

    public function test_system_roles_are_kept_as_roles(): void
    {
        $user = User::factory()->create();

        $this->putAccess($this->getAdminUser(), $user, [
            'systemRoles' => ['customer'],
            'template' => null,
            'permissionIds' => [],
        ])->assertOk();

        $this->assertTrue($user->fresh()->hasRole('customer'));
    }

    public function test_only_god_can_grant_god(): void
    {
        $user = User::factory()->create();

        $this->putAccess($this->getAdminUser(), $user, [
            'systemRoles' => ['god'],
            'template' => null,
            'permissionIds' => [],
        ])->assertStatus(422);
        $this->assertFalse($user->fresh()->hasRole('god'));

        $this->putAccess($this->god(), $user, [
            'systemRoles' => ['god'],
            'template' => null,
            'permissionIds' => [],
        ])->assertOk();
        $this->assertTrue($user->fresh()->hasRole('god'));
    }

    public function test_user_cannot_lock_himself_out(): void
    {
        $actor = User::factory()->create();
        $actor->givePermissionTo(['users.update', 'users.show']);

        $response = $this->putAccess($actor, $actor, [
            'systemRoles' => [],
            'template' => null,
            'permissionIds' => $this->ids(['users.show']),
        ]);

        $response->assertStatus(422);
        $this->assertTrue($actor->fresh()->can('users.update'));
    }

    public function test_invalid_input_is_rejected_with_detail(): void
    {
        $user = User::factory()->create();

        $this->putAccess($this->getAdminUser(), $user, ['systemRoles' => ['ventas'], 'template' => null, 'permissionIds' => []])
            ->assertStatus(422)->assertJsonValidationErrors('systemRoles');
        $this->putAccess($this->getAdminUser(), $user, ['systemRoles' => [], 'template' => 'no-existe', 'permissionIds' => []])
            ->assertStatus(422)->assertJsonValidationErrors('template');
        $this->putAccess($this->getAdminUser(), $user, ['systemRoles' => [], 'template' => null, 'permissionIds' => [999999]])
            ->assertStatus(422)->assertJsonValidationErrors('permissionIds');
    }

    public function test_requires_permission(): void
    {
        $user = User::factory()->create();
        $nobody = User::factory()->create();

        $this->actingAs($nobody, 'sanctum')->getJson("/api/v1/users/{$user->id}/access")->assertForbidden();
        $this->putAccess($nobody, $user, ['systemRoles' => [], 'template' => null, 'permissionIds' => []])->assertForbidden();
    }

    public function test_guest_gets_401(): void
    {
        $user = User::factory()->create();

        $this->getJson("/api/v1/users/{$user->id}/access")->assertUnauthorized();
    }

    public function test_profile_reflects_direct_permissions(): void
    {
        $user = User::factory()->create();
        $this->putAccess($this->getAdminUser(), $user, [
            'systemRoles' => [],
            'template' => null,
            'permissionIds' => $this->ids(['quotes.index']),
        ])->assertOk();

        $names = collect($this->actingAs($user->fresh(), 'sanctum')->getJson('/api/v1/profile')->json('data.attributes.permissions'))->pluck('name');

        $this->assertContains('quotes.index', $names);
    }
}
