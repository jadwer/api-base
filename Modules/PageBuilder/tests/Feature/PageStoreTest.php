<?php

namespace Modules\PageBuilder\Tests\Feature;

use Modules\PageBuilder\Models\Page;
use Modules\User\Models\User;
use Tests\TestCase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class PageStoreTest extends TestCase
{
    public function test_admin_can_create_page(): void
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

        $response = $this->jsonApi()->withData([
            'type' => 'pages',
            'attributes' => [
                'title' => 'Test Page',
                'slug' => 'test-page',
                'html' => '<h1>Hello</h1>',
                'css' => 'h1 { color: red; }',
                'json' => ['type' => 'landing'],
                'status' => 'draft',
                'publishedAt' => now()->toISOString(),
            ],
            'relationships' => [
                'user' => [
                    'data' => [
                        'type' => 'users',
                        'id' => (string) $admin->id,
                    ]
                ]
            ]
        ])->post('/api/v1/pages');

        $response->assertCreated();
        $this->assertDatabaseHas('pages', [
            'slug' => 'test-page',
            'status' => 'draft',
            'user_id' => $admin->id
        ]);
    }

    public function test_unauthorized_user_cannot_create_page(): void
    {
        $response = $this->jsonApi()->withData([
            'type' => 'pages',
            'attributes' => [
                'title' => 'X',
                'slug' => 'x',
            ],
        ])->post('/api/v1/pages');

        $response->assertUnauthorized();
    }
}
