<?php

namespace Modules\Branch\Tests\Feature;

use Tests\TestCase;
use Modules\Branch\Models\Branch;

class BranchDestroyTest extends TestCase
{
    public function test_admin_can_delete_Branch(): void
    {
        $admin = $this->getAdminUser();
        $branch = Branch::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('branches')
            ->delete("/api/v1/branches/{$branch->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('branches', ['id' => $branch->id]);
    }

    public function test_guest_cannot_delete_Branch(): void
    {
        $branch = Branch::factory()->create();

        $response = $this->jsonApi()
            ->expects('branches')
            ->delete("/api/v1/branches/{$branch->id}");

        $response->assertStatus(401);
        $this->assertDatabaseHas('branches', ['id' => $branch->id]);
    }

    public function test_returns_404_when_deleting_nonexistent_Branch(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('branches')
            ->delete('/api/v1/branches/99999');

        $response->assertStatus(404);
    }
}
