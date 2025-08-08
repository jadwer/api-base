<?php

namespace Modules\Product\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Product\Models\Product;
use Modules\Product\Models\Unit;
use Modules\Product\Models\Brand;
use Modules\Product\Models\Category;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get required related models
        $units = Unit::all();
        $brands = Brand::all();
        $categories = Category::all();

        if ($units->isEmpty() || $brands->isEmpty() || $categories->isEmpty()) {
            $this->command->warn('⚠️  Units, Brands or Categories not found. Run their seeders first.');
            return;
        }

        $products = [
            [
                'name' => 'iPhone 15 Pro',
                'sku' => 'APL-IPH15P-256',
                'description' => 'Smartphone Apple con chip A17 Pro y cámara avanzada',
                'full_description' => 'El iPhone 15 Pro cuenta con el revolucionario chip A17 Pro, sistema de cámara profesional con teleobjetivo 3x, y estructura de titanio de grado aeroespacial. Incluye Dynamic Island, pantalla Super Retina XDR de 6.1 pulgadas y tecnología ProRAW.',
                'price' => 1299.99,
                'cost' => 850.00,
                'iva' => true,
                'img_path' => '/images/products/iphone-15-pro.jpg',
                'datasheet_path' => '/datasheets/iphone-15-pro.pdf',
                'unit' => 'pz',
                'brand' => 'Apple',
                'category' => 'Smartphones'
            ],
            [
                'name' => 'Samsung Galaxy S24 Ultra',
                'sku' => 'SAM-GAL24U-512',
                'description' => 'Smartphone premium con S Pen y cámara de 200MP',
                'full_description' => 'El Galaxy S24 Ultra redefine la fotografía móvil con su cámara principal de 200MP, zoom óptico 5x, y el poder del S Pen integrado. Pantalla Dynamic AMOLED 2X de 6.8", procesador Snapdragon 8 Gen 3 y hasta 12GB de RAM.',
                'price' => 1399.99,
                'cost' => 920.00,
                'iva' => true,
                'img_path' => '/images/products/galaxy-s24-ultra.jpg',
                'datasheet_path' => '/datasheets/galaxy-s24-ultra.pdf',
                'unit' => 'pz',
                'brand' => 'Samsung',
                'category' => 'Smartphones'
            ],
            [
                'name' => 'MacBook Pro 14" M3',
                'sku' => 'APL-MBP14-M3-512',
                'description' => 'Laptop profesional con chip M3 y pantalla Liquid Retina XDR',
                'full_description' => 'MacBook Pro de 14 pulgadas con el potente chip M3, pantalla Liquid Retina XDR de 14.2", hasta 22 horas de batería y conectividad Thunderbolt 4. Ideal para profesionales creativos y desarrollo.',
                'price' => 2199.99,
                'cost' => 1450.00,
                'iva' => true,
                'img_path' => '/images/products/macbook-pro-14-m3.jpg',
                'datasheet_path' => '/datasheets/macbook-pro-14-m3.pdf',
                'unit' => 'pz',
                'brand' => 'Apple',
                'category' => 'Laptops'
            ],
            [
                'name' => 'Dell XPS 13 Plus',
                'sku' => 'DEL-XPS13P-I7-1TB',
                'description' => 'Ultrabook premium con procesador Intel i7 de 12va generación',
                'full_description' => 'Dell XPS 13 Plus con diseño minimalista, procesador Intel Core i7-1260P, 16GB LPDDR5, SSD 1TB, pantalla InfinityEdge 13.4" OLED 3.5K táctil y teclado capacitivo invisible.',
                'price' => 1699.99,
                'cost' => 1120.00,
                'iva' => true,
                'img_path' => '/images/products/dell-xps-13-plus.jpg',
                'datasheet_path' => '/datasheets/dell-xps-13-plus.pdf',
                'unit' => 'pz',
                'brand' => 'Dell',
                'category' => 'Laptops'
            ],
            [
                'name' => 'Sony WH-1000XM5',
                'sku' => 'SNY-WH1000XM5-BLK',
                'description' => 'Audífonos inalámbricos con cancelación de ruido líder en la industria',
                'full_description' => 'Los Sony WH-1000XM5 ofrecen la mejor cancelación de ruido del mercado, con procesadores V1 y QN1, hasta 30 horas de batería, carga rápida de 3 minutos para 3 horas de reproducción.',
                'price' => 399.99,
                'cost' => 260.00,
                'iva' => true,
                'img_path' => '/images/products/sony-wh-1000xm5.jpg',
                'datasheet_path' => '/datasheets/sony-wh-1000xm5.pdf',
                'unit' => 'pz',
                'brand' => 'Sony',
                'category' => 'Audífonos'
            ],
            [
                'name' => 'LG C4 OLED 55"',
                'sku' => 'LG-C4OLED55-4K',
                'description' => 'Smart TV OLED 4K con procesador α9 Gen7 AI',
                'full_description' => 'Televisor LG C4 OLED de 55" con tecnología OLED evo, procesador α9 Gen7 AI 4K, Dolby Vision IQ, Dolby Atmos, webOS 24, HDMI 2.1 para gaming 4K@120Hz con VRR y ALLM.',
                'price' => 1499.99,
                'cost' => 980.00,
                'iva' => true,
                'img_path' => '/images/products/lg-c4-oled-55.jpg',
                'datasheet_path' => '/datasheets/lg-c4-oled-55.pdf',
                'unit' => 'pz',
                'brand' => 'LG',
                'category' => 'Televisores'
            ],
            [
                'name' => 'iPad Pro 12.9" M2',
                'sku' => 'APL-IPADPRO12-M2-256',
                'description' => 'Tablet profesional con chip M2 y pantalla Liquid Retina XDR',
                'full_description' => 'iPad Pro de 12.9" con chip M2 de 8 núcleos, pantalla Liquid Retina XDR de 12.9", cámara TrueDepth frontal ultra gran angular, compatible con Apple Pencil de 2da generación y Magic Keyboard.',
                'price' => 1199.99,
                'cost' => 780.00,
                'iva' => true,
                'img_path' => '/images/products/ipad-pro-12-m2.jpg',
                'datasheet_path' => '/datasheets/ipad-pro-12-m2.pdf',
                'unit' => 'pz',
                'brand' => 'Apple',
                'category' => 'Tablets'
            ],
            [
                'name' => 'Canon EOS R6 Mark II',
                'sku' => 'CAN-EOSR6M2-BODY',
                'description' => 'Cámara mirrorless full-frame con sensor CMOS de 24.2MP',
                'full_description' => 'Canon EOS R6 Mark II con sensor CMOS full-frame de 24.2MP, procesador DIGIC X, estabilización de imagen de hasta 8 paradas, video 4K 60p sin recorte, y sistema de enfoque Dual Pixel CMOS AF II.',
                'price' => 2499.99,
                'cost' => 1640.00,
                'iva' => true,
                'img_path' => '/images/products/canon-eos-r6-mark2.jpg',
                'datasheet_path' => '/datasheets/canon-eos-r6-mark2.pdf',
                'unit' => 'pz',
                'brand' => 'Canon',
                'category' => 'Cámaras'
            ]
        ];

        foreach ($products as $productData) {
            // Find related models
            $unit = $units->where('code', $productData['unit'])->first();
            $brand = $brands->where('name', $productData['brand'])->first();
            $category = $categories->where('name', $productData['category'])->first();

            if (!$unit || !$brand || !$category) {
                $this->command->warn("⚠️  Skipping {$productData['name']} - missing relations");
                continue;
            }

            // Remove relation keys and add IDs
            unset($productData['unit'], $productData['brand'], $productData['category']);
            $productData['unit_id'] = $unit->id;
            $productData['brand_id'] = $brand->id;
            $productData['category_id'] = $category->id;

            Product::firstOrCreate(
                ['sku' => $productData['sku']],
                $productData
            );
        }

        $this->command->info('✅ Products seeded successfully!');
    }
}