<?php

namespace Modules\Audit\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Audit\Models\Audit;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuditShowTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($this->admin, 'sanctum');

        $this->admin->givePermissionTo('audit.show');
    }

    public function test_it_returns_a_single_audit_with_causer_and_optional_subject()
    {
        $audit = Audit::query()->latest()->firstOrFail();

        $response = $this->jsonApi()->get("/api/v1/audits/{$audit->id}");

        $response->assertOk();

        $data = $response->json('data');
        $this->assertEquals('audits', $data['type']);
        $this->assertEquals((string) $audit->id, $data['id']);

        $attributes = $data['attributes'];

        foreach ([
            'event',
            'userId',
            'auditableType',
            'auditableId',
            'oldValues',
            'newValues',
            'ipAddress',
            'userAgent',
            'createdAt',
            'updatedAt',
            'causer',
            'subject',
        ] as $key) {
            $this->assertArrayHasKey($key, $attributes, "Falta el atributo '$key'");
        }

        if (!is_null($attributes['causer'])) {
            $this->assertIsArray($attributes['causer']);
            $this->assertArrayHasKey('id', $attributes['causer']);
            $this->assertArrayHasKey('email', $attributes['causer']);
        }

        if (!is_null($attributes['subject'])) {
            $this->assertIsArray($attributes['subject']);
            $this->assertArrayHasKey('id', $attributes['subject']);
            $this->assertArrayHasKey('name', $attributes['subject']);
        }
    }

public function test_it_fails_if_user_lacks_permission()
{
    // Crear usuario sin permiso explícito
    /** @var \Modules\User\Models\User $user */
    $user = User::factory()->create();
    $this->actingAs($user, 'sanctum');

    // Buscar cualquier audit existente
    $audit = \Modules\Audit\Models\Audit::firstOrFail();

    // Asegurar que el usuario no tiene permisos
    $user->syncPermissions([]); // elimina todos

    // Probar acceso
    $response = $this->jsonApi()->get("/api/v1/audits/{$audit->getRouteKey()}");

    $response->assertForbidden(); // espera 403
}
}
