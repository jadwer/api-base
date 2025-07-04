<?php

namespace Modules\PageBuilder\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\PageBuilder\Models\Page;

class PageFilterTest extends TestCase
{
    public function test_admin_can_filter_pages_by_slug(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $pageA = Page::factory()->create(['slug' => 'home']);
        Page::factory()->create(['slug' => 'contact']);

        $response = $this->jsonApi()->get('/api/v1/pages?filter[slug]=home');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');

        $response->assertJson([
            'data' => [
                [
                    'id' => (string) $pageA->id,
                    'type' => 'pages',
                    'attributes' => [
                        'slug' => 'home',
                    ],
                ],
            ],
        ]);
    }

    public function test_filter_returns_empty_if_slug_does_not_match(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        Page::factory()->create(['slug' => 'home']);

        $response = $this->jsonApi()->get('/api/v1/pages?filter[slug]=does-not-exist');

        $response->assertOk();
        $response->assertJsonCount(0, 'data');
    }

    public function test_unauthenticated_user_cannot_filter_unpublished_pages(): void
    {
        Page::factory()->create(['slug' => 'private-page', 'published_at' => null]);

        $response = $this->jsonApi()->get('/api/v1/pages?filter[slug]=private-page');

        $response->assertOk();
        $response->assertJsonCount(0, 'data');
    }

    public function test_unauthenticated_user_can_filter_published_pages(): void
    {
        $page = Page::factory()->create(['slug' => 'home', 'published_at' => now()]);

        $response = $this->jsonApi()->get('/api/v1/pages?filter[slug]=home');
        $response->assertOk();
        $response->assertJsonCount(1, 'data');
    }

    public function test_user_without_permission_can_filter_published_pages(): void
    {
        $user = User::factory()->withRole('tech')->create();
        $this->actingAs($user, 'sanctum');

        $page = Page::factory()->create(['slug' => 'public', 'published_at' => now()]);

        $response = $this->jsonApi()->get('/api/v1/pages?filter[slug]=public');
        $response->assertOk();
        $response->assertJsonCount(1, 'data');
    }
}
