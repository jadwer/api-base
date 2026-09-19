<?php

namespace Modules\Branch\Tests\Feature;

use Tests\TestCase;
use Modules\Branch\Models\Branch;

class BranchShowTest extends TestCase
{
    public function test_admin_can_view_Branch(): void
    {
        $admin = $this->getAdminUser();
        $branch = Branch::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('branches')
            ->get("/api/v1/branches/{$branch->id}");

        $response->assertOk();
    }

    public function test_guest_cannot_view_Branch(): void
    {
        $branch = Branch::factory()->create();

        $response = $this->jsonApi()
            ->expects('branches')
            ->get("/api/v1/branches/{$branch->id}");

        $response->assertStatus(401);
    }

    public function test_returns_404_for_nonexistent_Branch(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('branches')
            ->get('/api/v1/branches/99999');

        $response->assertStatus(404);
    }
}
