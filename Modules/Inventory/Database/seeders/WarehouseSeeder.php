<?php

namespace Modules\Inventory\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Inventory\Models\Warehouse;

class WarehouseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $warehouses = [
            [
                'name' => 'Almacén Principal',
                'slug' => 'almacen-principal',
                'description' => 'Almacén central para productos principales',
                'code' => 'WH-001',
                'warehouse_type' => 'main',
                'address' => 'Av. Industrial 123',
                'city' => 'Ciudad de México',
                'state' => 'CDMX',
                'country' => 'México',
                'postal_code' => '01000',
                'phone' => '+52 55 1234 5678',
                'email' => 'almacen.principal@empresa.com',
                'manager_name' => 'Juan Pérez',
                'max_capacity' => 10000.00,
                'capacity_unit' => 'm3',
                'is_active' => true,
                'operating_hours' => ['mon-fri' => '08:00-18:00', 'sat' => '08:00-14:00'],
            ],
            [
                'name' => 'Almacén Secundario Norte',
                'slug' => 'almacen-secundario-norte',
                'description' => 'Almacén secundario para la región norte',
                'code' => 'WH-002',
                'warehouse_type' => 'secondary',
                'address' => 'Calle Norte 456',
                'city' => 'Monterrey',
                'state' => 'Nuevo León',
                'country' => 'México',
                'postal_code' => '64000',
                'phone' => '+52 81 2345 6789',
                'email' => 'almacen.norte@empresa.com',
                'manager_name' => 'María García',
                'max_capacity' => 5000.00,
                'capacity_unit' => 'm3',
                'is_active' => true,
                'operating_hours' => ['mon-fri' => '07:00-17:00'],
            ],
            [
                'name' => 'Centro de Distribución Sur',
                'slug' => 'centro-distribucion-sur',
                'description' => 'Centro de distribución para la región sur',
                'code' => 'CD-001',
                'warehouse_type' => 'distribution',
                'address' => 'Blvd. Sur 789',
                'city' => 'Guadalajara',
                'state' => 'Jalisco',
                'country' => 'México',
                'postal_code' => '44100',
                'phone' => '+52 33 3456 7890',
                'email' => 'distribucion.sur@empresa.com',
                'manager_name' => 'Carlos López',
                'max_capacity' => 15000.00,
                'capacity_unit' => 'm3',
                'is_active' => true,
                'operating_hours' => ['mon-sat' => '06:00-20:00'],
            ],
            [
                'name' => 'Almacén de Devoluciones',
                'slug' => 'almacen-devoluciones',
                'description' => 'Almacén especializado en manejo de devoluciones',
                'code' => 'WH-RET',
                'warehouse_type' => 'returns',
                'address' => 'Zona Industrial 321',
                'city' => 'Ciudad de México',
                'state' => 'CDMX',
                'country' => 'México',
                'postal_code' => '01001',
                'phone' => '+52 55 4567 8901',
                'email' => 'devoluciones@empresa.com',
                'manager_name' => 'Ana Martínez',
                'max_capacity' => 2000.00,
                'capacity_unit' => 'm3',
                'is_active' => true,
                'operating_hours' => ['mon-fri' => '09:00-16:00'],
            ]
        ];

        foreach ($warehouses as $warehouse) {
            Warehouse::firstOrCreate(
                ['code' => $warehouse['code']],
                $warehouse
            );
        }

        $this->command->info('Warehouses seeded successfully.');
    }
}
