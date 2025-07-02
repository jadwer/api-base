<?php

namespace Modules\PageBuilder\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\PageBuilder\Models\Page;

class PageShowTest extends TestCase
{
    public function test_admin_can_view_a_page(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $page = Page::factory()->create();

        $response = $this->jsonApi()->get("/api/v1/pages/{$page->id}");

        $response->assertOk();

        $response->assertJson([
            'data' => [
                'id' => (string) $page->id,
                'type' => 'pages',
                'attributes' => [
                    'title'       => $page->title,
                    'slug'        => $page->slug,
                    'html'        => $page->html,
                    'css'         => $page->css,
                    'json'        => $page->json,
                    'publishedAt' => $page->published_at?->toJSON(),
                    'createdAt'   => $page->created_at->toJSON(),
                    'updatedAt'   => $page->updated_at->toJSON(),
                ],
            ],
        ]);
    }

    public function test_unauthenticated_user_cannot_view_page(): void
    {
        $page = Page::factory()->create();

        $response = $this->jsonApi()->get("/api/v1/pages/{$page->id}");

        $response->assertUnauthorized();
    }

    public function test_user_without_permission_cannot_view_page(): void
    {
        $user = User::factory()->withRole('tech')->create();
        $this->actingAs($user, 'sanctum');

        $page = Page::factory()->create();

        $response = $this->jsonApi()->get("/api/v1/pages/{$page->id}");

        $response->assertForbidden();
    }
}
