<?php

namespace Modules\Inventory\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Inventory\Models\Warehouse;
use Modules\Inventory\Models\WarehouseLocation;

class WarehouseLocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $warehouses = Warehouse::all();

        foreach ($warehouses as $warehouse) {
            // Crear diferentes tipos de ubicaciones según el tipo de almacén
            $locations = $this->getLocationsForWarehouse($warehouse);

            foreach ($locations as $location) {
                WarehouseLocation::firstOrCreate(
                    [
                        'warehouse_id' => $warehouse->id,
                        'code' => $location['code']
                    ],
                    $location
                );
            }
        }

        $this->command->info('Warehouse locations seeded successfully.');
    }

    private function getLocationsForWarehouse($warehouse)
    {
        $locations = [];

        switch ($warehouse->warehouse_type) {
            case 'main':
                // Almacén principal con múltiples zonas
                $zones = ['A', 'B', 'C'];
                $aisles = [1, 2, 3, 4];
                $racks = [1, 2, 3];
                
                foreach ($zones as $zone) {
                    foreach ($aisles as $aisle) {
                        foreach ($racks as $rack) {
                            $locations[] = [
                                'warehouse_id' => $warehouse->id,
                                'name' => "Zona {$zone} - Pasillo {$aisle} - Estante {$rack}",
                                'code' => "{$zone}-{$aisle}-{$rack}",
                                'location_type' => 'rack',
                                'is_active' => true,
                            ];
                        }
                    }
                }
                break;

            case 'secondary':
                // Almacén secundario más simple
                $aisles = [1, 2];
                $shelves = ['A', 'B', 'C'];
                
                foreach ($aisles as $aisle) {
                    foreach ($shelves as $shelf) {
                        $locations[] = [
                            'warehouse_id' => $warehouse->id,
                            'name' => "Pasillo {$aisle} - Estante {$shelf}",
                            'code' => "P{$aisle}-E{$shelf}",
                            'location_type' => 'shelf',
                            'is_active' => true,
                        ];
                    }
                }
                break;

            case 'distribution':
                // Centro de distribución con bahías
                $bays = [1, 2, 3, 4, 5];
                $zones = ['Loading', 'Sorting', 'Dispatch'];
                
                foreach ($zones as $zone) {
                    foreach ($bays as $bay) {
                        $locations[] = [
                            'warehouse_id' => $warehouse->id,
                            'name' => "{$zone} - Bahía {$bay}",
                            'code' => strtoupper(substr($zone, 0, 3)) . "-B{$bay}",
                            'location_type' => 'bay',
                            'is_active' => true,
                        ];
                    }
                }
                break;

            case 'returns':
                // Almacén de devoluciones con bins
                $areas = ['Inspection', 'Repair', 'Disposal'];
                $bins = [1, 2, 3];
                
                foreach ($areas as $area) {
                    foreach ($bins as $bin) {
                        $locations[] = [
                            'warehouse_id' => $warehouse->id,
                            'name' => "{$area} - Contenedor {$bin}",
                            'code' => strtoupper(substr($area, 0, 3)) . "-C{$bin}",
                            'location_type' => 'bin',
                            'is_active' => true,
                        ];
                    }
                }
                break;
        }

        return $locations;
    }
}
