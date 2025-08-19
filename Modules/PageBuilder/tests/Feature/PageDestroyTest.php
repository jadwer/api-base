<?php

namespace Modules\PageBuilder\Tests\Feature;

use Modules\PageBuilder\Models\Page;
use Modules\User\Models\User;
use Tests\TestCase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class PageDestroyTest extends TestCase
{
    public function test_admin_can_delete_page(): void
    {
        // Create permissions
        $permissions = ['page.store', 'page.show', 'page.index', 'page.update', 'page.destroy'];
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'api']);
        }
        
        // Create admin role and assign permissions
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
        $adminRole->givePermissionTo($permissions);
        
        /** @var \Illuminate\Contracts\Auth\Authenticatable|\Modules\User\Models\User $admin */
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        $this->actingAs($admin, 'sanctum');

        $page = Page::factory()->create();

        $response = $this->jsonApi()->delete("/api/v1/pages/{$page->getRouteKey()}");

        $response->assertNoContent();
        $this->assertSoftDeleted('pages', ['id' => $page->id]);
    }

    public function test_unauthenticated_user_cannot_delete_page(): void
    {
        $page = Page::factory()->create();

        $response = $this->jsonApi()->delete("/api/v1/pages/{$page->getRouteKey()}");

        $response->assertUnauthorized();
    }
}
