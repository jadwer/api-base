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
            ['type' => 'weight', 'code' => 'kg', 'name' => 'Kilogramo'],
            ['type' => 'weight', 'code' => 'g', 'name' => 'Gramo'],
            ['type' => 'volume', 'code' => 'l', 'name' => 'Litro'],
            ['type' => 'volume', 'code' => 'ml', 'name' => 'Mililitro'],
            ['type' => 'length', 'code' => 'm', 'name' => 'Metro'],
            ['type' => 'length', 'code' => 'cm', 'name' => 'Centímetro'],
            ['type' => 'unit', 'code' => 'pz', 'name' => 'Pieza'],
            ['type' => 'unit', 'code' => 'box', 'name' => 'Caja'],
        ];

        foreach ($units as $unit) {
            Unit::firstOrCreate(
                ['code' => $unit['code']],
                $unit
            );
        }
    }
}
