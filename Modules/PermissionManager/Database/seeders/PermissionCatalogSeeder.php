<?php

namespace Modules\PermissionManager\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\PermissionManager\Models\Permission;
use Modules\PermissionManager\Support\PermissionCatalog;

/**
 * Rellena label/description/module/resource de TODOS los permisos ya
 * sembrados, desde el catalogo compuesto. Aditivo e idempotente: correr
 * DESPUES de los seeders de permisos por modulo (no los reemplaza).
 *
 * Un permiso sin entrada en el catalogo recibe un label generado y se
 * reporta como warning; el test PermissionCatalogCoverageTest convierte
 * esa deuda en rojo para que el catalogo nunca se quede atras.
 */
class PermissionCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $unknown = [];

        Permission::query()->chunkById(200, function ($permissions) use (&$unknown) {
            foreach ($permissions as $permission) {
                $resolved = PermissionCatalog::resolve($permission->name);

                $permission->forceFill([
                    'label' => $resolved['label'],
                    'description' => $resolved['description'],
                    'module' => $resolved['module'],
                    'resource' => $resolved['resource'],
                ])->save();

                if (! $resolved['known']) {
                    $unknown[] = $permission->name;
                }
            }
        });

        if ($unknown !== [] && $this->command) {
            $this->command->warn(
                'Permisos SIN entrada en permission_catalog.php (label generado): '
                . implode(', ', $unknown)
            );
        }
    }
}
