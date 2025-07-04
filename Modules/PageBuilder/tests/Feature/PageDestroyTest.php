<?php

namespace Modules\PageBuilder\Tests\Feature;

use Modules\PageBuilder\Models\Page;
use Modules\User\Models\User;
use Tests\TestCase;

class PageDestroyTest extends TestCase
{
    public function test_admin_can_delete_page(): void
    {
        /** @var \Illuminate\Contracts\Auth\Authenticatable|\Modules\User\Models\User $admin */

        $admin = User::factory()->withRole('admin')->create();
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
