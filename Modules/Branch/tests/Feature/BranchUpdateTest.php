<?php

namespace Modules\Branch\Tests\Feature;

use Tests\TestCase;
use Modules\Branch\Models\Branch;

class BranchUpdateTest extends TestCase
{
    public function test_admin_can_update_Branch(): void
    {
        $admin = $this->getAdminUser();
        $branch = Branch::factory()->create();

        $data = [
            'type' => 'branches',
            'id' => (string) $branch->id,
            'attributes' => [
                'name' => 'Test Name',
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('branches')
            ->withData($data)
            ->patch("/api/v1/branches/{$branch->id}");

        $response->assertOk();
        $this->assertDatabaseHas('branches', ['name' => 'Test Name']);
    }

    public function test_guest_cannot_update_Branch(): void
    {
        $branch = Branch::factory()->create();

        $data = [
            'type' => 'branches',
            'id' => (string) $branch->id,
            'attributes' => [
                'name' => 'Test Name',
            ]
        ];

        $response = $this->jsonApi()
            ->expects('branches')
            ->withData($data)
            ->patch("/api/v1/branches/{$branch->id}");

        $response->assertStatus(401);
    }

    public function test_cannot_update_nonexistent_Branch(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'branches',
            'id' => '99999',
            'attributes' => [
                'name' => 'Test Name',
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('branches')
            ->withData($data)
            ->patch('/api/v1/branches/99999');

        $response->assertStatus(404);
    }
}
