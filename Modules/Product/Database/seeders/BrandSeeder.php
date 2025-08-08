<?php

namespace Modules\Product\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Product\Models\Brand;

class BrandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // BASED ON: Migration create_brands_table.php (lines 18-20: name, description, slug)
        // BASED ON: BrandSchema.php (lines 20-22: name, description, slug fields)
        $brands = [
            [
                'name' => 'Samsung',
                'slug' => 'samsung',
                'description' => 'Multinacional surcoreana líder en tecnología y electrónicos'
            ],
            [
                'name' => 'Apple',
                'slug' => 'apple',
                'description' => 'Empresa estadounidense de tecnología, innovadora en dispositivos móviles'
            ],
            [
                'name' => 'Sony',
                'slug' => 'sony',
                'description' => 'Corporación japonesa especializada en electrónicos y entretenimiento'
            ],
            [
                'name' => 'LG',
                'slug' => 'lg',
                'description' => 'Empresa surcoreana de electrónicos y electrodomésticos'
            ],
            [
                'name' => 'Huawei',
                'slug' => 'huawei',
                'description' => 'Compañía china de telecomunicaciones y tecnología'
            ],
            [
                'name' => 'Dell',
                'slug' => 'dell',
                'description' => 'Empresa estadounidense especializada en computadoras y servidores'
            ],
            [
                'name' => 'HP',
                'slug' => 'hp',
                'description' => 'Hewlett-Packard, empresa estadounidense de tecnología e impresoras'
            ],
            [
                'name' => 'Lenovo',
                'slug' => 'lenovo',
                'description' => 'Multinacional china de computadoras personales y servidores'
            ],
            [
                'name' => 'Microsoft',
                'slug' => 'microsoft',
                'description' => 'Corporación estadounidense de software y servicios en la nube'
            ],
            [
                'name' => 'Google',
                'slug' => 'google',
                'description' => 'Empresa estadounidense especializada en servicios de Internet'
            ],
            [
                'name' => 'Canon',
                'slug' => 'canon',
                'description' => 'Empresa japonesa especializada en cámaras e impresoras'
            ],
            [
                'name' => 'Nikon',
                'slug' => 'nikon',
                'description' => 'Corporación japonesa de óptica y equipos fotográficos'
            ]
        ];

        foreach ($brands as $brand) {
            Brand::firstOrCreate(
                ['name' => $brand['name']],
                $brand
            );
        }
    }
}