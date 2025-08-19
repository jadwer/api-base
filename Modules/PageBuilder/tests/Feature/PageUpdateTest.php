<?php

namespace Modules\PageBuilder\Tests\Feature;

use Modules\PageBuilder\Models\Page;
use Modules\User\Models\User;
use Tests\TestCase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class PageUpdateTest extends TestCase
{
    public function test_admin_can_update_page(): void
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

        $response = $this->jsonApi()->withData([
            'type' => 'pages',
            'id' => (string) $page->getRouteKey(),
            'attributes' => [
                'title' => 'Updated Title',
                'status' => 'published'
            ],
        ])->patch("/api/v1/pages/{$page->getRouteKey()}");

        $response->assertOk();
        $this->assertDatabaseHas('pages', [
            'id' => $page->id, 
            'title' => 'Updated Title',
            'status' => 'published'
        ]);
    }

    public function test_unauthenticated_user_cannot_update_page(): void
    {
        $page = Page::factory()->create();

        $response = $this->jsonApi()->withData([
            'type' => 'pages',
            'id' => (string) $page->getRouteKey(),
            'attributes' => ['title' => 'Unauthorized'],
        ])->patch("/api/v1/pages/{$page->getRouteKey()}");

        $response->assertUnauthorized();
    }
}
