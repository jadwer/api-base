<?php

namespace Modules\PageBuilder\Tests\Feature;

use Modules\PageBuilder\Models\Page;
use Modules\User\Models\User;
use Tests\TestCase;

class PageIndexTest extends TestCase
{
    public function test_admin_can_list_pages(): void
    {
        $admin = User::factory()->withRole('admin')->create();
        $this->actingAs($admin, 'sanctum');

        Page::factory()->count(3)->create();

        $response = $this->jsonApi()->get('/api/v1/pages');

        $response->assertOk();

        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'attributes' => [
                        'title',
                        'slug',
                        'html',
                        'css',
                        'json',
                        'publishedAt',
                        'createdAt',
                        'updatedAt',
                    ],
                    'links' => [
                        'self',
                    ],
                ],
            ],
        ]);
    }

    public function test_unauthenticated_user_can_list_only_published_pages(): void
    {
        $publishedPages = Page::factory()->count(2)->create(['published_at' => now()]);
        Page::factory()->count(2)->create(['published_at' => null]);

        $response = $this->jsonApi()->get('/api/v1/pages');

        $response->assertOk();

        // Validamos que estén las publicadas
        foreach ($publishedPages as $page) {
            $response->assertJsonFragment(['id' => (string)$page->id]);
        }

        // Solo 2 nuevas publicadas, además de las del seeder (3)
        $response->assertJsonCount(5, 'data');
    }
}
