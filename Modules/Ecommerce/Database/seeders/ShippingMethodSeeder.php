<?php

namespace Modules\Ecommerce\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Ecommerce\Models\ShippingMethod;

class ShippingMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $methods = [
            [
                'name' => 'Envío Estándar',
                'code' => 'standard',
                'carrier' => 'Estafeta',
                'base_cost' => 80.00,
                'cost_per_kg' => 15.00,
                'estimated_days_min' => 3,
                'estimated_days_max' => 5,
                'is_active' => true,
                'available_countries' => ['MX'],
                'description' => 'Envío estándar a toda la República Mexicana',
            ],
            [
                'name' => 'Envío Express',
                'code' => 'express',
                'carrier' => 'DHL',
                'base_cost' => 150.00,
                'cost_per_kg' => 25.00,
                'estimated_days_min' => 1,
                'estimated_days_max' => 2,
                'is_active' => true,
                'available_countries' => ['MX'],
                'description' => 'Envío express 1-2 días hábiles',
            ],
            [
                'name' => 'Envío Económico',
                'code' => 'economic',
                'carrier' => 'RedPack',
                'base_cost' => 50.00,
                'cost_per_kg' => 10.00,
                'estimated_days_min' => 5,
                'estimated_days_max' => 7,
                'is_active' => true,
                'available_countries' => ['MX'],
                'description' => 'Envío económico 5-7 días hábiles',
            ],
            [
                'name' => 'Recolección en Tienda',
                'code' => 'pickup',
                'carrier' => null,
                'base_cost' => 0.00,
                'cost_per_kg' => 0.00,
                'estimated_days_min' => 1,
                'estimated_days_max' => 1,
                'is_active' => true,
                'available_countries' => ['MX'],
                'description' => 'Recoger en tienda sin costo de envío',
            ],
        ];

        foreach ($methods as $methodData) {
            ShippingMethod::updateOrCreate(
                ['code' => $methodData['code']],
                $methodData
            );
        }

        $this->command->info('✅ Shipping methods seeded successfully!');
    }
}
