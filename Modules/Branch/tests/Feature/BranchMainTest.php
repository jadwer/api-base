<?php

namespace Modules\Branch\Tests\Feature;

use Modules\Branch\Models\Branch;
use Tests\TestCase;

/**
 * La sucursal principal (Matriz) nace por migracion y solo puede haber una.
 */
class BranchMainTest extends TestCase
{
    public function test_main_branch_is_seeded_by_migration(): void
    {
        $this->assertSame(1, Branch::where('is_main', true)->count());
        $this->assertSame(Branch::MAIN_CODE, Branch::main()->code);
        $this->assertSame(Branch::main()->id, Branch::mainId());
    }

    public function test_marking_another_branch_as_main_demotes_the_previous_one(): void
    {
        $previous = Branch::main();

        $toluca = Branch::factory()->create(['name' => 'Toluca', 'code' => 'TOL', 'is_main' => true]);

        $this->assertSame(1, Branch::where('is_main', true)->count());
        $this->assertTrue($toluca->fresh()->is_main);
        $this->assertFalse($previous->fresh()->is_main);
        $this->assertSame($toluca->id, Branch::mainId());
    }

    public function test_default_branch_is_the_users_branch_or_main(): void
    {
        $toluca = Branch::factory()->create(['code' => 'TOL']);
        $admin = $this->getAdminUser();

        $this->assertSame(Branch::mainId(), Branch::defaultId());

        $admin->update(['branch_id' => $toluca->id]);
        $this->actingAs($admin->fresh(), 'sanctum');

        $this->assertSame($toluca->id, Branch::defaultId());
    }

    public function test_code_must_be_unique(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('branches')
            ->withData([
                'type' => 'branches',
                'attributes' => ['name' => 'Otra matriz', 'code' => Branch::MAIN_CODE],
            ])
            ->post('/api/v1/branches');

        $response->assertStatus(422);
    }
}
