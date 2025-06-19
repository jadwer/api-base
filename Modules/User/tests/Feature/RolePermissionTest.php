<?php

namespace Modules\User\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

use Modules\User\Database\Seeders\RoleSeeder;
use Modules\User\Database\Seeders\PermissionSeeder;
use Modules\User\Database\Seeders\AssignPermissionsSeeder;

class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        // Ejecutar seeders relacionados con roles y permisos
        $this->artisan('db:seed', ['--class' => RoleSeeder::class]);
        $this->artisan('db:seed', ['--class' => PermissionSeeder::class]);
        $this->artisan('db:seed', ['--class' => AssignPermissionsSeeder::class]);
    }

    public function test_roles_are_created()
    {
        $roles = ['god', 'admin', 'customer', 'guest'];

        foreach ($roles as $role) {
            $this->assertDatabaseHas('roles', [
                'name' => $role,
                'guard_name' => 'api',
            ]);
        }
    }

    public function test_permissions_are_created()
    {
        $permissions = [
            'users.index', 'users.view', 'users.store', 'users.update', 'users.delete',
        ];

        foreach ($permissions as $permission) {
            $this->assertDatabaseHas('permissions', [
                'name' => $permission,
                'guard_name' => 'api',
            ]);
        }
    }

    public function test_permissions_are_assigned_to_roles()
    {
        $role = Role::findByName('god', 'api');
        $permissions = ['users.index', 'users.view', 'users.store', 'users.update', 'users.delete'];

        foreach ($permissions as $permission) {
            $this->assertTrue($role->hasPermissionTo($permission), "Role 'god' is missing permission: $permission");
        }
    }
}
