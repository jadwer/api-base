<?php

namespace Modules\Product\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Product\Models\Unit;

class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $units = [
            ['unit_type' => 'weight', 'code' => 'kg', 'name' => 'Kilogramo'],
            ['unit_type' => 'weight', 'code' => 'g', 'name' => 'Gramo'],
            ['unit_type' => 'volume', 'code' => 'l', 'name' => 'Litro'],
            ['unit_type' => 'volume', 'code' => 'ml', 'name' => 'Mililitro'],
            ['unit_type' => 'length', 'code' => 'm', 'name' => 'Metro'],
            ['unit_type' => 'length', 'code' => 'cm', 'name' => 'Centímetro'],
            ['unit_type' => 'unit', 'code' => 'pz', 'name' => 'Pieza'],
            ['unit_type' => 'unit', 'code' => 'box', 'name' => 'Caja'],
        ];

        foreach ($units as $unit) {
            Unit::firstOrCreate(
                ['code' => $unit['code']],
                $unit
            );
        }
    }
}
