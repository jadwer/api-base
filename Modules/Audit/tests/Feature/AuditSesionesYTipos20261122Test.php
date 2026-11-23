<?php

namespace Modules\Audit\Tests\Feature;

use Illuminate\Auth\Events\Login;
use Modules\Product\Models\Brand;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

/** PLA-17, PLA-18 y PLA-19 (2026-11-22). */
class AuditSesionesYTipos20261122Test extends TestCase
{
    public function test_el_inicio_de_sesion_queda_con_evento_ip_y_navegador_y_se_filtra_por_evento(): void
    {
        $admin = $this->getAdminUser();
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.9', 'HTTP_USER_AGENT' => 'Navegador de prueba']);
        request()->server->set('REMOTE_ADDR', '203.0.113.9');
        request()->headers->set('User-Agent', 'Navegador de prueba');

        event(new Login('sanctum', $admin, false));

        $activity = Activity::query()->latest('id')->first();
        $this->assertSame('login', $activity->event);
        $this->assertSame('203.0.113.9', $activity->properties['ip_address']);
        $this->assertSame('Navegador de prueba', $activity->properties['user_agent']);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/audits?filter[event]=login&sort=-id', ['Accept' => 'application/vnd.api+json'])
            ->assertOk();
        $first = collect($response->json('data'))->firstWhere('id', (string) $activity->id);
        $this->assertNotNull($first);
        $this->assertSame('203.0.113.9', $first['attributes']['ipAddress']);
    }

    public function test_un_cambio_de_modelo_guarda_la_ip_y_los_registros_viejos_siguen_mostrandola(): void
    {
        $admin = $this->getAdminUser();
        request()->server->set('REMOTE_ADDR', '198.51.100.7');
        $this->actingAs($admin, 'sanctum');

        $brand = Brand::factory()->create();
        $change = Activity::query()->where('subject_type', Brand::class)->where('subject_id', $brand->id)->latest('id')->first();
        $this->assertNotNull($change);
        $this->assertSame('198.51.100.7', $change->properties['ip_address']);

        $old = Activity::query()->create(['log_name' => 'default', 'description' => 'login', 'event' => 'login', 'properties' => ['ip' => '192.0.2.1']]);
        $old->forceFill(['properties' => ['ip' => '192.0.2.1']])->saveQuietly();
        $row = collect($this->getJson('/api/v1/audits?filter[event]=login&sort=-id', ['Accept' => 'application/vnd.api+json'])->json('data'))
            ->firstWhere('id', (string) $old->id);
        $this->assertSame('192.0.2.1', $row['attributes']['ipAddress']);
    }

    public function test_los_tipos_del_filtro_salen_de_la_bitacora_con_etiqueta_en_espanol(): void
    {
        $admin = $this->getAdminUser();
        $this->actingAs($admin, 'sanctum');
        Brand::factory()->create();

        $types = collect($this->getJson('/api/v1/audit-types')->assertOk()->json('data'));

        $this->assertSame('Marca', $types->firstWhere('value', Brand::class)['label'] ?? null);
        $this->assertFalse($types->contains('value', 'Modules\\Sales\\Models\\SalesQuote'));
        $this->actingAs($this->getCustomerUser(), 'sanctum')->getJson('/api/v1/audit-types')->assertForbidden();
    }
}
