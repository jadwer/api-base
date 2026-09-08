<?php

namespace Modules\User\Tests\Feature;

use Modules\User\Models\User;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Users v2 (rediseno del modulo de administracion, 2026-09): filtros que
 * consume la lista nueva: search unificado (nombre O email), role por
 * nombre de rol Spatie y status exacto. Contrato del frontend.
 */
class UserFiltersTest extends TestCase
{
    protected function actingAsAdmin(): User
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        return $admin;
    }

    public function test_search_filter_matches_name_or_email(): void
    {
        $this->actingAsAdmin();
        $byName = User::factory()->create(['name' => 'Zoraida Buscable', 'email' => 'zb@example.com']);
        $byEmail = User::factory()->create(['name' => 'Otro Usuario', 'email' => 'zoraida.mail@example.com']);
        User::factory()->create(['name' => 'Nada Que Ver', 'email' => 'nqv@example.com']);

        $response = $this->jsonApi()->get('/api/v1/users?filter[search]=zoraida');

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertContains((string) $byName->id, $ids);
        $this->assertContains((string) $byEmail->id, $ids);
        $this->assertCount(2, $ids);
    }

    public function test_role_filter_matches_spatie_role_name(): void
    {
        $this->actingAsAdmin();
        Role::findOrCreate('tech', 'web');
        $tech = User::factory()->create(['name' => 'Tecnico Filtrable']);
        $tech->assignRole('tech');
        User::factory()->create(['name' => 'Sin Rol Filtrable']);

        $response = $this->jsonApi()->get('/api/v1/users?filter[role]=tech');

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertContains((string) $tech->id, $ids);
        $names = collect($response->json('data'))->pluck('attributes.name');
        $this->assertFalse($names->contains('Sin Rol Filtrable'));
    }

    public function test_status_filter_matches_exact_value(): void
    {
        $this->actingAsAdmin();
        $inactive = User::factory()->create(['name' => 'Inactivo Uno', 'status' => 'inactive']);

        $response = $this->jsonApi()->get('/api/v1/users?filter[status]=inactive');

        $response->assertOk();
        $statuses = collect($response->json('data'))->pluck('attributes.status')->unique()->all();
        $this->assertSame(['inactive'], $statuses);
        $this->assertContains((string) $inactive->id, collect($response->json('data'))->pluck('id')->all());
    }

    public function test_pagination_works_with_filters(): void
    {
        $this->actingAsAdmin();
        User::factory()->count(30)->create(['status' => 'active']);

        $response = $this->jsonApi()
            ->get('/api/v1/users?filter[status]=active&page[number]=1&page[size]=10');

        $response->assertOk();
        $this->assertCount(10, $response->json('data'));
        $this->assertArrayHasKey('page', $response->json('meta') ?? []);
    }
}
