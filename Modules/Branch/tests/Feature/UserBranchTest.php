<?php

namespace Modules\Branch\Tests\Feature;

use Modules\Branch\Models\Branch;
use Modules\User\Models\User;
use Tests\TestCase;

/**
 * Usuario con sucursal principal y sucursales con acceso (pantalla de Bind).
 */
class UserBranchTest extends TestCase
{
    private function payload(array $relationships = []): array
    {
        $data = [
            'type' => 'users',
            'attributes' => [
                'name' => 'Vendedor Toluca',
                'email' => 'toluca@example.com',
                'password' => 'password123',
                'status' => 'active',
            ],
        ];
        if ($relationships !== []) {
            $data['relationships'] = $relationships;
        }

        return $data;
    }

    public function test_user_created_without_branch_lands_in_main_branch(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('users')
            ->withData($this->payload())
            ->post('/api/v1/users');

        $response->assertCreated();
        $this->assertDatabaseHas('users', ['email' => 'toluca@example.com', 'branch_id' => Branch::mainId()]);
    }

    public function test_user_can_be_created_with_main_branch_and_access_branches(): void
    {
        $admin = $this->getAdminUser();
        $toluca = Branch::factory()->create(['code' => 'TOL']);
        $queretaro = Branch::factory()->create(['code' => 'QRO']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('users')
            ->withData($this->payload([
                'branch' => ['data' => ['type' => 'branches', 'id' => (string) $toluca->id]],
                'branches' => ['data' => [
                    ['type' => 'branches', 'id' => (string) $toluca->id],
                    ['type' => 'branches', 'id' => (string) $queretaro->id],
                ]],
            ]))
            ->post('/api/v1/users');

        $response->assertCreated();

        $user = User::where('email', 'toluca@example.com')->firstOrFail();
        $this->assertSame($toluca->id, $user->branch_id);
        $this->assertEqualsCanonicalizing([$toluca->id, $queretaro->id], $user->branches()->pluck('branches.id')->all());
        $this->assertEqualsCanonicalizing([$toluca->id, $queretaro->id], $user->accessibleBranchIds());
    }

    public function test_user_show_includes_branch_and_access_branches(): void
    {
        $admin = $this->getAdminUser();
        $toluca = Branch::factory()->create(['code' => 'TOL']);
        $user = User::factory()->create(['branch_id' => $toluca->id]);
        $user->branches()->sync([$toluca->id, Branch::mainId()]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('users')
            ->includePaths('branch', 'branches')
            ->get("/api/v1/users/{$user->id}");

        $response->assertFetchedOne($user);
        $response->assertIsIncluded('branches', $toluca);
        $response->assertIsIncluded('branches', Branch::main());
    }

    public function test_users_can_be_filtered_by_branch(): void
    {
        $admin = $this->getAdminUser();
        $toluca = Branch::factory()->create(['code' => 'TOL']);
        $inToluca = User::factory()->create(['branch_id' => $toluca->id]);
        User::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('users')
            ->filter(['branch' => $toluca->id])
            ->get('/api/v1/users');

        $response->assertFetchedMany([$inToluca]);
    }

    public function test_admin_sees_all_branches(): void
    {
        $this->assertNull($this->getAdminUser()->accessibleBranchIds());
    }
}
