<?php

namespace Modules\User\Tests\Feature;

use Tests\TestCase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;

use Modules\User\Database\Seeders\RoleSeeder;
use Modules\User\Database\Seeders\PermissionSeeder;
use Modules\User\Database\Seeders\AssignPermissionsSeeder;

class RolePermissionTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();

    }

    public function test_roles_are_created(): void
    {
        $roles = ['god', 'admin', 'customer', 'guest'];

        foreach ($roles as $role) {
            $this->assertDatabaseHas('roles', [
                'name' => $role,
                'guard_name' => 'api',
            ]);
        }
    }

    public function test_permissions_are_created(): void
    {
        $permissions = [
            'users.index',
            'users.view',
            'users.store',
            'users.update',
            'users.delete',
        ];

        foreach ($permissions as $permission) {
            $this->assertDatabaseHas('permissions', [
                'name' => $permission,
                'guard_name' => 'api',
            ]);
        }
    }

    public function test_permissions_are_assigned_to_god_role(): void
    {
        $role = Role::findByName('god', 'api');
        $expectedPermissions = [
            'users.index',
            'users.view',
            'users.store',
            'users.update',
            'users.delete',
        ];

        foreach ($expectedPermissions as $permission) {
            $this->assertTrue(
                $role->hasPermissionTo($permission),
                "El rol 'god' no tiene el permiso '$permission'."
            );
        }

        // Verificamos que los permisos esperados estén incluidos, no la cuenta exacta
        $this->assertGreaterThanOrEqual(
            count($expectedPermissions),
            $role->permissions()->count()
        );
    }

    public function test_all_roles_that_should_have_permissions_have_them(): void
    {
        $rolesWithExpectedPermissions = Role::whereNotIn('name', ['guest'])->get();

        foreach ($rolesWithExpectedPermissions as $role) {
            $this->assertTrue(
                $role->permissions()->count() > 0,
                "El rol '{$role->name}' no tiene permisos asignados."
            );
        }
    }
}
