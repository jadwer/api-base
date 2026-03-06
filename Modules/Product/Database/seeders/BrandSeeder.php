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
            ['name' => 'Samsung', 'slug' => 'samsung', 'description' => 'Multinacional surcoreana líder en tecnología y electrónicos', 'is_active' => true],
            ['name' => 'Apple', 'slug' => 'apple', 'description' => 'Empresa estadounidense de tecnología, innovadora en dispositivos móviles', 'is_active' => true],
            ['name' => 'Sony', 'slug' => 'sony', 'description' => 'Corporación japonesa especializada en electrónicos y entretenimiento', 'is_active' => true],
            ['name' => 'LG', 'slug' => 'lg', 'description' => 'Empresa surcoreana de electrónicos y electrodomésticos', 'is_active' => true],
            ['name' => 'Huawei', 'slug' => 'huawei', 'description' => 'Compañía china de telecomunicaciones y tecnología', 'is_active' => false],
            ['name' => 'Dell', 'slug' => 'dell', 'description' => 'Empresa estadounidense especializada en computadoras y servidores', 'is_active' => true],
            ['name' => 'HP', 'slug' => 'hp', 'description' => 'Hewlett-Packard, empresa estadounidense de tecnología e impresoras', 'is_active' => true],
            ['name' => 'Lenovo', 'slug' => 'lenovo', 'description' => 'Multinacional china de computadoras personales y servidores', 'is_active' => true],
            ['name' => 'Microsoft', 'slug' => 'microsoft', 'description' => 'Corporación estadounidense de software y servicios en la nube', 'is_active' => true],
            ['name' => 'Google', 'slug' => 'google', 'description' => 'Empresa estadounidense especializada en servicios de Internet', 'is_active' => true],
            ['name' => 'Canon', 'slug' => 'canon', 'description' => 'Empresa japonesa especializada en cámaras e impresoras', 'is_active' => true],
            ['name' => 'Nikon', 'slug' => 'nikon', 'description' => 'Corporación japonesa de óptica y equipos fotográficos', 'is_active' => true],
            ['name' => 'Nintendo', 'slug' => 'nintendo', 'description' => 'Empresa japonesa de videojuegos y consolas', 'is_active' => true],
            ['name' => 'OnePlus', 'slug' => 'oneplus', 'description' => 'Empresa china de smartphones premium', 'is_active' => true],
            ['name' => 'Fujifilm', 'slug' => 'fujifilm', 'description' => 'Empresa japonesa de fotografía y equipos médicos', 'is_active' => true],
            ['name' => 'TCL', 'slug' => 'tcl', 'description' => 'Empresa china de electrónicos de consumo y televisores', 'is_active' => true],
            ['name' => 'Philips', 'slug' => 'philips', 'description' => 'Empresa holandesa de tecnología y salud', 'is_active' => false],
            ['name' => 'JBL', 'slug' => 'jbl', 'description' => 'Empresa estadounidense especializada en audio', 'is_active' => true],
            ['name' => 'Bose', 'slug' => 'bose', 'description' => 'Empresa estadounidense de sistemas de audio premium', 'is_active' => true],
            ['name' => 'Sonos', 'slug' => 'sonos', 'description' => 'Empresa estadounidense de sistemas de audio inteligente', 'is_active' => true],
            ['name' => 'Logitech', 'slug' => 'logitech', 'description' => 'Empresa suiza-estadounidense de periféricos de computadora', 'is_active' => true],
            ['name' => 'Razer', 'slug' => 'razer', 'description' => 'Empresa estadounidense especializada en hardware gaming', 'is_active' => true],
            ['name' => 'Corsair', 'slug' => 'corsair', 'description' => 'Empresa estadounidense de componentes gaming y PC', 'is_active' => true],
            ['name' => 'Amazon', 'slug' => 'amazon', 'description' => 'Empresa estadounidense de comercio electrónico y servicios', 'is_active' => true],
            ['name' => 'GoPro', 'slug' => 'gopro', 'description' => 'Empresa estadounidense de cámaras de acción', 'is_active' => true],
            ['name' => 'DJI', 'slug' => 'dji', 'description' => 'Empresa china líder en drones y estabilizadores', 'is_active' => true],
            ['name' => 'Anker', 'slug' => 'anker', 'description' => 'Empresa china de accesorios y cargadores', 'is_active' => true],
            ['name' => 'Belkin', 'slug' => 'belkin', 'description' => 'Empresa estadounidense de accesorios y conectividad', 'is_active' => true],
            ['name' => 'SteelSeries', 'slug' => 'steelseries', 'description' => 'Empresa danesa de periféricos gaming', 'is_active' => true],
            ['name' => 'Rode', 'slug' => 'rode', 'description' => 'Empresa australiana especializada en micrófonos', 'is_active' => true],
            ['name' => 'Elgato', 'slug' => 'elgato', 'description' => 'Empresa alemana de accesorios para streaming', 'is_active' => true],
            ['name' => 'NVIDIA', 'slug' => 'nvidia', 'description' => 'Empresa estadounidense de tarjetas gráficas y AI', 'is_active' => true],
            ['name' => 'AMD', 'slug' => 'amd', 'description' => 'Empresa estadounidense de procesadores y tarjetas gráficas', 'is_active' => true],
            ['name' => 'ASUS', 'slug' => 'asus', 'description' => 'Empresa taiwanesa de hardware y componentes', 'is_active' => true],
        ];

        foreach ($brands as $brand) {
            Brand::firstOrCreate(
                ['name' => $brand['name']],
                $brand
            );
        }
    }
}