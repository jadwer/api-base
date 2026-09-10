<?php

namespace Modules\PermissionManager\Tests\Feature;

use Modules\PermissionManager\Support\PermissionCatalog;
use Modules\User\Models\User;
use Tests\TestCase;

/**
 * Catalogo de permisos: resolucion de labels y API con filtros.
 * La DB de test ya corrio PermissionCatalogSeeder (TestDatabaseSeeder).
 */
class PermissionCatalogTest extends TestCase
{
    public function test_resolve_compone_label_y_descripcion_desde_plantillas(): void
    {
        $resolved = PermissionCatalog::resolve('products.store');

        $this->assertSame('Agregar producto', $resolved['label']);
        $this->assertSame('Permiso que permite agregar un producto', $resolved['description']);
        $this->assertSame('productos', $resolved['module']);
        $this->assertSame('products', $resolved['resource']);
        $this->assertTrue($resolved['known']);
    }

    public function test_resolve_respeta_genero_femenino_y_recursos_con_namespace(): void
    {
        $resolved = PermissionCatalog::resolve('billing.cfdi-invoices.update');

        $this->assertSame('Editar factura CFDI', $resolved['label']);
        $this->assertSame('Permiso que permite editar una factura CFDI', $resolved['description']);
        $this->assertSame('facturacion', $resolved['module']);
        $this->assertSame('billing.cfdi-invoices', $resolved['resource']);
    }

    public function test_resolve_usa_overrides_para_verbos_especiales(): void
    {
        $resolved = PermissionCatalog::resolve('billing.cfdi-invoices.stamp');

        $this->assertSame('Timbrar factura', $resolved['label']);
        $this->assertStringContainsString('PAC', $resolved['description']);
        $this->assertSame('facturacion', $resolved['module']);
        $this->assertTrue($resolved['known']);
    }

    public function test_resolve_de_permiso_desconocido_genera_fallback_sin_romper(): void
    {
        $resolved = PermissionCatalog::resolve('nuevo-modulo.cosas.index');

        $this->assertFalse($resolved['known']);
        $this->assertNotSame('', $resolved['label']);
        $this->assertNull($resolved['module']);
        $this->assertSame('nuevo-modulo.cosas', $resolved['resource']);
    }

    public function test_module_label_resuelve_nombre_legible(): void
    {
        $this->assertSame('Recursos Humanos', PermissionCatalog::moduleLabel('rrhh'));
        $this->assertSame('Facturación CFDI', PermissionCatalog::moduleLabel('facturacion'));
    }

    public function test_api_expone_label_descripcion_y_modulo(): void
    {
        $god = User::where('email', 'god@example.com')->first();

        $response = $this->actingAs($god, 'sanctum')
            ->jsonApi()
            ->expects('permissions')
            ->filter(['resource' => 'users'])
            ->get('/api/v1/permissions');

        $response->assertSuccessful();
        $attributes = collect($response->json('data'))->pluck('attributes');
        $this->assertCount(5, $attributes);
        $this->assertContains('Agregar usuario', $attributes->pluck('label'));
        $this->assertSame(['usuarios'], $attributes->pluck('module')->unique()->values()->all());
        $this->assertSame(['Usuarios'], $attributes->pluck('moduleLabel')->unique()->values()->all());
        $this->assertSame(['Usuarios'], $attributes->pluck('resourceLabel')->unique()->values()->all());
    }

    public function test_api_filtra_por_modulo(): void
    {
        $god = User::where('email', 'god@example.com')->first();

        $response = $this->actingAs($god, 'sanctum')
            ->jsonApi()
            ->expects('permissions')
            ->filter(['module' => 'productos'])
            ->get('/api/v1/permissions');

        $response->assertSuccessful();
        $modules = collect($response->json('data'))->pluck('attributes.module')->unique()->values()->all();
        $this->assertSame(['productos'], $modules);
    }

    public function test_api_busca_por_label_legible(): void
    {
        $god = User::where('email', 'god@example.com')->first();

        $response = $this->actingAs($god, 'sanctum')
            ->jsonApi()
            ->expects('permissions')
            ->filter(['search' => 'Timbrar'])
            ->get('/api/v1/permissions');

        $response->assertSuccessful();
        $names = collect($response->json('data'))->pluck('attributes.name');
        $this->assertContains('billing.cfdi-invoices.stamp', $names);
    }
}
