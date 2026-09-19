<?php

namespace Modules\HR\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\HR\Models\Department;
use Modules\HR\Models\Position;

/**
 * Organigrama inicial del cliente (imagen enviada el 18-sep-2026, 13
 * personas): 4 departamentos y sus puestos, con el rol por defecto donde
 * ya existe uno en produccion (ventas-jr). Los demas roles se asignan
 * cuando el cliente los defina; mientras, default_role queda vacio y el
 * usuario se configura a mano.
 *
 * Idempotente por nombre de departamento y titulo de puesto. NO forma
 * parte de CleanDatabaseSeeder (es dato del cliente, no del template):
 *   php artisan db:seed --class="Modules\\HR\\Database\\Seeders\\OrganizationChartSeeder"
 */
class OrganizationChartSeeder extends Seeder
{
    public const ORGANIZATION = [
        'Dirección General' => [
            'description' => 'Dirección de la empresa',
            'positions' => [
                ['title' => 'Director General', 'level' => 'executive', 'default_role' => 'admin'],
            ],
        ],
        'Ventas y Telemarketing' => [
            'description' => 'Desarrollo comercial y atención a clientes',
            'positions' => [
                ['title' => 'Ejecutivo de Ventas', 'level' => 'mid', 'default_role' => 'ventas-jr'],
                ['title' => 'Telemarketing', 'level' => 'junior', 'default_role' => 'ventas-jr'],
            ],
        ],
        'Almacén y Logística' => [
            'description' => 'Recepción, resguardo y distribución',
            'positions' => [
                ['title' => 'Responsable de Almacén', 'level' => 'lead', 'default_role' => null],
                ['title' => 'Auxiliar de Almacén', 'level' => 'entry', 'default_role' => null],
                ['title' => 'Auxiliar de Logística', 'level' => 'entry', 'default_role' => null],
            ],
        ],
        'Finanzas' => [
            'description' => 'Administración y control financiero',
            'positions' => [
                ['title' => 'Cuentas por Cobrar', 'level' => 'mid', 'default_role' => null],
                ['title' => 'Cuentas por Pagar', 'level' => 'mid', 'default_role' => null],
                ['title' => 'Compras', 'level' => 'mid', 'default_role' => null],
                ['title' => 'Facturación', 'level' => 'mid', 'default_role' => null],
            ],
        ],
    ];

    public function run(): void
    {
        $created = ['departments' => 0, 'positions' => 0];

        foreach (self::ORGANIZATION as $name => $spec) {
            $department = Department::firstOrCreate(
                ['name' => $name],
                ['description' => $spec['description'], 'is_active' => true]
            );
            if ($department->wasRecentlyCreated) {
                $created['departments']++;
            }

            foreach ($spec['positions'] as $pos) {
                $position = Position::firstOrCreate(
                    ['department_id' => $department->id, 'title' => $pos['title']],
                    ['level' => $pos['level'], 'is_active' => true, 'default_role' => $pos['default_role']]
                );
                if ($position->wasRecentlyCreated) {
                    $created['positions']++;
                } elseif ($position->default_role === null && $pos['default_role'] !== null) {
                    // Solo rellena el rol si el puesto no lo tiene; no pisa ajustes manuales.
                    $position->update(['default_role' => $pos['default_role']]);
                }
            }
        }

        if ($this->command) {
            $this->command->info(sprintf(
                'Organigrama: %d departamentos y %d puestos nuevos (el resto ya existia).',
                $created['departments'],
                $created['positions']
            ));
        }
    }
}
