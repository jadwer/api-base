<?php

namespace Modules\PageBuilder\Tests\Feature;

use Modules\PageBuilder\Models\Page;
use Modules\User\Models\User;
use Tests\TestCase;

class PageStoreTest extends TestCase
{
    public function test_admin_can_create_page(): void
    {
        /** @var \Illuminate\Contracts\Auth\Authenticatable|\Modules\User\Models\User $admin */
        $admin = User::factory()->withRole('admin')->create();
        $this->actingAs($admin, 'sanctum');

        // Autenticar
        $this->actingAs($admin);

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
