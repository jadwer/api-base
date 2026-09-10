<?php

namespace Modules\PermissionManager\Tests\Feature;

use Modules\PermissionManager\Models\Permission;
use Modules\PermissionManager\Support\PermissionCatalog;
use Tests\TestCase;

/**
 * Integridad del catalogo: TODO permiso sembrado debe tener entrada en
 * permission_catalog.php (regla 7: dato del sistema valido por
 * construccion). Si agregas un permiso nuevo y este test falla, agrega
 * su recurso (o su override) al catalogo.
 */
class PermissionCatalogCoverageTest extends TestCase
{
    public function test_todo_permiso_sembrado_tiene_entrada_en_el_catalogo(): void
    {
        $missing = Permission::pluck('name')
            ->filter(fn (string $name) => ! PermissionCatalog::resolve($name)['known'])
            ->values();

        $this->assertSame(
            [],
            $missing->all(),
            'Permisos sin entrada en permission_catalog.php: ' . $missing->implode(', ')
        );
    }

    public function test_todo_permiso_sembrado_quedo_con_label_y_modulo_en_db(): void
    {
        $unlabeled = Permission::whereNull('label')
            ->orWhereNull('module')
            ->pluck('name');

        $this->assertSame(
            [],
            $unlabeled->all(),
            'Permisos sin label/module tras PermissionCatalogSeeder: ' . $unlabeled->implode(', ')
        );
    }
}
