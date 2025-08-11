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
            ],
            [
                'name' => 'Nintendo',
                'slug' => 'nintendo',
                'description' => 'Empresa japonesa de videojuegos y consolas'
            ],
            [
                'name' => 'OnePlus',
                'slug' => 'oneplus',
                'description' => 'Empresa china de smartphones premium'
            ],
            [
                'name' => 'Fujifilm',
                'slug' => 'fujifilm',
                'description' => 'Empresa japonesa de fotografía y equipos médicos'
            ],
            [
                'name' => 'TCL',
                'slug' => 'tcl',
                'description' => 'Empresa china de electrónicos de consumo y televisores'
            ],
            [
                'name' => 'Philips',
                'slug' => 'philips',
                'description' => 'Empresa holandesa de tecnología y salud'
            ],
            [
                'name' => 'JBL',
                'slug' => 'jbl',
                'description' => 'Empresa estadounidense especializada en audio'
            ],
            [
                'name' => 'Bose',
                'slug' => 'bose',
                'description' => 'Empresa estadounidense de sistemas de audio premium'
            ],
            [
                'name' => 'Sonos',
                'slug' => 'sonos',
                'description' => 'Empresa estadounidense de sistemas de audio inteligente'
            ],
            [
                'name' => 'Logitech',
                'slug' => 'logitech',
                'description' => 'Empresa suiza-estadounidense de periféricos de computadora'
            ],
            [
                'name' => 'Razer',
                'slug' => 'razer',
                'description' => 'Empresa estadounidense especializada en hardware gaming'
            ],
            [
                'name' => 'Corsair',
                'slug' => 'corsair',
                'description' => 'Empresa estadounidense de componentes gaming y PC'
            ],
            [
                'name' => 'Amazon',
                'slug' => 'amazon',
                'description' => 'Empresa estadounidense de comercio electrónico y servicios'
            ],
            [
                'name' => 'GoPro',
                'slug' => 'gopro',
                'description' => 'Empresa estadounidense de cámaras de acción'
            ],
            [
                'name' => 'DJI',
                'slug' => 'dji',
                'description' => 'Empresa china líder en drones y estabilizadores'
            ],
            [
                'name' => 'Anker',
                'slug' => 'anker',
                'description' => 'Empresa china de accesorios y cargadores'
            ],
            [
                'name' => 'Belkin',
                'slug' => 'belkin',
                'description' => 'Empresa estadounidense de accesorios y conectividad'
            ],
            [
                'name' => 'SteelSeries',
                'slug' => 'steelseries',
                'description' => 'Empresa danesa de periféricos gaming'
            ],
            [
                'name' => 'Rode',
                'slug' => 'rode',
                'description' => 'Empresa australiana especializada en micrófonos'
            ],
            [
                'name' => 'Elgato',
                'slug' => 'elgato',
                'description' => 'Empresa alemana de accesorios para streaming'
            ],
            [
                'name' => 'NVIDIA',
                'slug' => 'nvidia',
                'description' => 'Empresa estadounidense de tarjetas gráficas y AI'
            ],
            [
                'name' => 'AMD',
                'slug' => 'amd',
                'description' => 'Empresa estadounidense de procesadores y tarjetas gráficas'
            ],
            [
                'name' => 'ASUS',
                'slug' => 'asus',
                'description' => 'Empresa taiwanesa de hardware y componentes'
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