<?php

namespace Modules\PageBuilder\Tests\Feature;

use Modules\PageBuilder\Models\Page;
use Modules\User\Models\User;
use Tests\TestCase;

class PageUpdateTest extends TestCase
{
    public function test_admin_can_update_page(): void
    {
        /** @var \Illuminate\Contracts\Auth\Authenticatable|\Modules\User\Models\User $admin */
        $admin = User::factory()->withRole('admin')->create();
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
