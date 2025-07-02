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

    public function test_unauthenticated_user_cannot_list_pages(): void
    {
        $response = $this->jsonApi()->get('/api/v1/pages');
        $response->assertUnauthorized();
    }
}
