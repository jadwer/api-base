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
            ],
            [
                'name' => 'Nintendo Switch OLED',
                'sku' => 'NIN-SWTOLED-64GB',
                'description' => 'Consola de videojuegos híbrida con pantalla OLED',
                'full_description' => 'Nintendo Switch OLED con pantalla OLED de 7", 64GB de almacenamiento interno, base con puerto LAN cableado, soporte ajustable mejorado y audio mejorado en modo sobremesa.',
                'price' => 349.99,
                'cost' => 230.00,
                'iva' => true,
                'img_path' => '/images/products/nintendo-switch-oled.jpg',
                'datasheet_path' => '/datasheets/nintendo-switch-oled.pdf',
                'unit' => 'pz',
                'brand' => 'Nintendo',
                'category' => 'Videojuegos'
            ],
            [
                'name' => 'PlayStation 5 Standard',
                'sku' => 'SNY-PS5-STD-825GB',
                'description' => 'Consola de videojuegos de nueva generación con lector de discos',
                'full_description' => 'PlayStation 5 con procesador AMD Ryzen Zen 2, GPU AMD RDNA 2, SSD ultra rápido de 825GB, audio 3D Tempest, DualSense con retroalimentación háptica y disparadores adaptativos.',
                'price' => 499.99,
                'cost' => 330.00,
                'iva' => true,
                'img_path' => '/images/products/playstation-5-standard.jpg',
                'datasheet_path' => '/datasheets/playstation-5-standard.pdf',
                'unit' => 'pz',
                'brand' => 'Sony',
                'category' => 'Videojuegos'
            ],
            [
                'name' => 'Xbox Series X',
                'sku' => 'MSF-XBOXSX-1TB',
                'description' => 'Consola de videojuegos más potente de Microsoft',
                'full_description' => 'Xbox Series X con CPU AMD Zen 2 de 8 núcleos, GPU AMD RDNA 2 de 12 teraflops, 1TB SSD NVMe, 4K nativo hasta 120fps, ray tracing por hardware y Quick Resume.',
                'price' => 499.99,
                'cost' => 330.00,
                'iva' => true,
                'img_path' => '/images/products/xbox-series-x.jpg',
                'datasheet_path' => '/datasheets/xbox-series-x.pdf',
                'unit' => 'pz',
                'brand' => 'Microsoft',
                'category' => 'Videojuegos'
            ],
            [
                'name' => 'AirPods Pro 2',
                'sku' => 'APL-AIRPODSP2-USBC',
                'description' => 'Audífonos inalámbricos con cancelación activa de ruido',
                'full_description' => 'AirPods Pro 2 con chip H2, cancelación activa de ruido mejorada, modo transparencia adaptativo, audio espacial personalizado y estuche MagSafe con USB-C.',
                'price' => 249.99,
                'cost' => 165.00,
                'iva' => true,
                'img_path' => '/images/products/airpods-pro-2.jpg',
                'datasheet_path' => '/datasheets/airpods-pro-2.pdf',
                'unit' => 'pz',
                'brand' => 'Apple',
                'category' => 'Audífonos'
            ],
            [
                'name' => 'Samsung Tab S9 Ultra',
                'sku' => 'SAM-TABS9U-512GB',
                'description' => 'Tablet premium con pantalla Super AMOLED de 14.6"',
                'full_description' => 'Galaxy Tab S9 Ultra con pantalla Super AMOLED de 14.6", procesador Snapdragon 8 Gen 2, 12GB RAM, 512GB almacenamiento, S Pen incluido y resistencia IP68.',
                'price' => 1199.99,
                'cost' => 790.00,
                'iva' => true,
                'img_path' => '/images/products/samsung-tab-s9-ultra.jpg',
                'datasheet_path' => '/datasheets/samsung-tab-s9-ultra.pdf',
                'unit' => 'pz',
                'brand' => 'Samsung',
                'category' => 'Tablets'
            ],
            [
                'name' => 'HP EliteBook 840 G10',
                'sku' => 'HP-EB840G10-I7-32GB',
                'description' => 'Laptop empresarial con procesador Intel i7 de 13va generación',
                'full_description' => 'HP EliteBook 840 G10 con Intel Core i7-1355U, 32GB DDR5, SSD 1TB PCIe Gen4, pantalla 14" WUXGA IPS, cámara IR, lector de huellas y certificación MIL-STD 810H.',
                'price' => 1899.99,
                'cost' => 1250.00,
                'iva' => true,
                'img_path' => '/images/products/hp-elitebook-840-g10.jpg',
                'datasheet_path' => '/datasheets/hp-elitebook-840-g10.pdf',
                'unit' => 'pz',
                'brand' => 'HP',
                'category' => 'Laptops'
            ],
            [
                'name' => 'Lenovo ThinkPad X1 Carbon',
                'sku' => 'LEN-X1C11-I7-1TB',
                'description' => 'Ultrabook empresarial ligero con pantalla 14" 2.8K OLED',
                'full_description' => 'ThinkPad X1 Carbon Gen 11 con Intel Core i7-1365U, 32GB LPDDR5, SSD 1TB PCIe Gen4, pantalla OLED 2.8K táctil, teclado TrackPoint, lector de huellas y 5G opcional.',
                'price' => 2299.99,
                'cost' => 1510.00,
                'iva' => true,
                'img_path' => '/images/products/lenovo-thinkpad-x1-carbon.jpg',
                'datasheet_path' => '/datasheets/lenovo-thinkpad-x1-carbon.pdf',
                'unit' => 'pz',
                'brand' => 'Lenovo',
                'category' => 'Laptops'
            ],
            [
                'name' => 'ASUS ROG Strix G15',
                'sku' => 'ASU-ROGSG15-RTX4070',
                'description' => 'Laptop gaming con RTX 4070 y procesador AMD Ryzen 7',
                'full_description' => 'ROG Strix G15 con AMD Ryzen 7 7735HS, NVIDIA GeForce RTX 4070, 16GB DDR5, SSD 1TB PCIe Gen4, pantalla 15.6" FHD 144Hz, teclado RGB per-key y sistema de refrigeración ROG.',
                'price' => 1699.99,
                'cost' => 1120.00,
                'iva' => true,
                'img_path' => '/images/products/asus-rog-strix-g15.jpg',
                'datasheet_path' => '/datasheets/asus-rog-strix-g15.pdf',
                'unit' => 'pz',
                'brand' => 'ASUS',
                'category' => 'Laptops'
            ],
            [
                'name' => 'iPhone 14',
                'sku' => 'APL-IPH14-128GB',
                'description' => 'Smartphone con chip A15 Bionic y cámara dual de 12MP',
                'full_description' => 'iPhone 14 con chip A15 Bionic, sistema de cámara dual de 12MP con modo Cine, pantalla Super Retina XDR de 6.1", Face ID, MagSafe y batería de todo el día.',
                'price' => 799.99,
                'cost' => 525.00,
                'iva' => true,
                'img_path' => '/images/products/iphone-14.jpg',
                'datasheet_path' => '/datasheets/iphone-14.pdf',
                'unit' => 'pz',
                'brand' => 'Apple',
                'category' => 'Smartphones'
            ],
            [
                'name' => 'Google Pixel 8 Pro',
                'sku' => 'GOO-PIX8P-256GB',
                'description' => 'Smartphone con IA avanzada y cámara Pro de 50MP',
                'full_description' => 'Pixel 8 Pro con Google Tensor G3, cámara Pro de 50MP con zoom óptico 5x, pantalla Super Actua de 6.7" LTPO, Magic Eraser, traducción en tiempo real y 7 años de actualizaciones.',
                'price' => 999.99,
                'cost' => 660.00,
                'iva' => true,
                'img_path' => '/images/products/google-pixel-8-pro.jpg',
                'datasheet_path' => '/datasheets/google-pixel-8-pro.pdf',
                'unit' => 'pz',
                'brand' => 'Google',
                'category' => 'Smartphones'
            ],
            [
                'name' => 'OnePlus 12',
                'sku' => 'ONE-OP12-512GB',
                'description' => 'Smartphone flagship con Snapdragon 8 Gen 3 y carga rápida',
                'full_description' => 'OnePlus 12 con Snapdragon 8 Gen 3, 16GB RAM LPDDR5X, cámara Hasselblad de 50MP, pantalla ProXDR de 6.82" 120Hz, carga SuperVOOC de 100W y OxygenOS 14.',
                'price' => 899.99,
                'cost' => 590.00,
                'iva' => true,
                'img_path' => '/images/products/oneplus-12.jpg',
                'datasheet_path' => '/datasheets/oneplus-12.pdf',
                'unit' => 'pz',
                'brand' => 'OnePlus',
                'category' => 'Smartphones'
            ],
            [
                'name' => 'Sony A7R V',
                'sku' => 'SNY-A7RV-BODY',
                'description' => 'Cámara mirrorless full-frame de 61MP con IA',
                'full_description' => 'Sony Alpha 7R V con sensor CMOS full-frame de 61MP, procesador BIONZ XR, estabilización de 8 paradas, video 8K, enfoque automático con IA y pantalla LCD articulada de 4 ejes.',
                'price' => 3899.99,
                'cost' => 2560.00,
                'iva' => true,
                'img_path' => '/images/products/sony-a7r-v.jpg',
                'datasheet_path' => '/datasheets/sony-a7r-v.pdf',
                'unit' => 'pz',
                'brand' => 'Sony',
                'category' => 'Cámaras'
            ],
            [
                'name' => 'Nikon Z9',
                'sku' => 'NIK-Z9-BODY',
                'description' => 'Cámara profesional con sensor stacked de 45.7MP',
                'full_description' => 'Nikon Z9 con sensor CMOS stacked de 45.7MP, procesador EXPEED 7, video 8K 30p sin recorte, obturador electrónico sin blackout, AF de 493 puntos y grabación ProRes RAW.',
                'price' => 5499.99,
                'cost' => 3610.00,
                'iva' => true,
                'img_path' => '/images/products/nikon-z9.jpg',
                'datasheet_path' => '/datasheets/nikon-z9.pdf',
                'unit' => 'pz',
                'brand' => 'Nikon',
                'category' => 'Cámaras'
            ],
            [
                'name' => 'Fujifilm X-T5',
                'sku' => 'FUJ-XT5-BODY-BLK',
                'description' => 'Cámara mirrorless APS-C con sensor X-Trans de 40.2MP',
                'full_description' => 'Fujifilm X-T5 con sensor X-Trans CMOS 5 HR de 40.2MP, procesador X-Processor 5, estabilización IBIS de 7 paradas, video 6.2K, simulaciones de película clásicas y dial de velocidad retro.',
                'price' => 1699.99,
                'cost' => 1120.00,
                'iva' => true,
                'img_path' => '/images/products/fujifilm-x-t5.jpg',
                'datasheet_path' => '/datasheets/fujifilm-x-t5.pdf',
                'unit' => 'pz',
                'brand' => 'Fujifilm',
                'category' => 'Cámaras'
            ],
            [
                'name' => 'Samsung QN95C Neo QLED 65"',
                'sku' => 'SAM-QN95C65-NEO',
                'description' => 'Smart TV Neo QLED 4K con Mini LED y Quantum Matrix',
                'full_description' => 'Samsung QN95C de 65" con tecnología Neo QLED, Quantum Matrix con Mini LED, procesador Neo Quantum 4K, Object Tracking Sound+, Gaming Hub y Tizen OS con Bixby.',
                'price' => 2499.99,
                'cost' => 1640.00,
                'iva' => true,
                'img_path' => '/images/products/samsung-qn95c-65.jpg',
                'datasheet_path' => '/datasheets/samsung-qn95c-65.pdf',
                'unit' => 'pz',
                'brand' => 'Samsung',
                'category' => 'Televisores'
            ],
            [
                'name' => 'TCL C845 QLED 75"',
                'sku' => 'TCL-C845-75-QLED',
                'description' => 'Smart TV QLED 4K con Quantum Dot y 144Hz',
                'full_description' => 'TCL C845 de 75" con panel QLED Quantum Dot, 144Hz VRR, MEMC, Dolby Vision IQ, Atmos, Google TV, Game Master 2.0 y control de voz hands-free.',
                'price' => 1899.99,
                'cost' => 1250.00,
                'iva' => true,
                'img_path' => '/images/products/tcl-c845-75.jpg',
                'datasheet_path' => '/datasheets/tcl-c845-75.pdf',
                'unit' => 'pz',
                'brand' => 'TCL',
                'category' => 'Televisores'
            ],
            [
                'name' => 'Philips OLED 48" Ambilight',
                'sku' => 'PHI-OLED48-AMB',
                'description' => 'Smart TV OLED con tecnología Ambilight de 3 lados',
                'full_description' => 'Philips OLED de 48" con panel OLED EX, Ambilight de 3 lados, P5 AI Perfect Picture, Dolby Vision & Atmos, Android TV 12, modo gaming 4K@120Hz con VRR.',
                'price' => 1299.99,
                'cost' => 855.00,
                'iva' => true,
                'img_path' => '/images/products/philips-oled-48-ambilight.jpg',
                'datasheet_path' => '/datasheets/philips-oled-48-ambilight.pdf',
                'unit' => 'pz',
                'brand' => 'Philips',
                'category' => 'Televisores'
            ],
            [
                'name' => 'JBL Charge 5',
                'sku' => 'JBL-CHARGE5-BLU',
                'description' => 'Altavoz Bluetooth portátil con powerbank integrado',
                'full_description' => 'JBL Charge 5 con JBL Pro Sound, resistencia IP67, hasta 20 horas de batería, powerbank para cargar dispositivos, PartyBoost para conectar múltiples altavoces.',
                'price' => 179.99,
                'cost' => 120.00,
                'iva' => true,
                'img_path' => '/images/products/jbl-charge-5.jpg',
                'datasheet_path' => '/datasheets/jbl-charge-5.pdf',
                'unit' => 'pz',
                'brand' => 'JBL',
                'category' => 'Audio'
            ],
            [
                'name' => 'Bose SoundBar 900',
                'sku' => 'BOS-SB900-BLK',
                'description' => 'Barra de sonido Dolby Atmos con audio espacial',
                'full_description' => 'Bose Soundbar 900 con tecnología Dolby Atmos, audio espacial TrueSpace, conectividad Wi-Fi, Bluetooth, AirPlay 2, control por voz Alexa y Google Assistant integrados.',
                'price' => 899.99,
                'cost' => 590.00,
                'iva' => true,
                'img_path' => '/images/products/bose-soundbar-900.jpg',
                'datasheet_path' => '/datasheets/bose-soundbar-900.pdf',
                'unit' => 'pz',
                'brand' => 'Bose',
                'category' => 'Audio'
            ],
            [
                'name' => 'Sonos Era 300',
                'sku' => 'SON-ERA300-BLK',
                'description' => 'Altavoz inteligente con audio espacial Dolby Atmos',
                'full_description' => 'Sonos Era 300 con 6 drivers para audio espacial 360°, Dolby Atmos, Trueplay con micrófono, control táctil, Bluetooth, Wi-Fi 6, AirPlay 2 y control por voz.',
                'price' => 449.99,
                'cost' => 295.00,
                'iva' => true,
                'img_path' => '/images/products/sonos-era-300.jpg',
                'datasheet_path' => '/datasheets/sonos-era-300.pdf',
                'unit' => 'pz',
                'brand' => 'Sonos',
                'category' => 'Audio'
            ],
            [
                'name' => 'Logitech MX Master 3S',
                'sku' => 'LOG-MXM3S-BLK',
                'description' => 'Ratón inalámbrico avanzado para productividad',
                'full_description' => 'Logitech MX Master 3S con sensor 8K DPI, rueda de desplazamiento electromagnética MagSpeed, 8 botones personalizables, conexión multi-dispositivo y batería de 70 días.',
                'price' => 99.99,
                'cost' => 66.00,
                'iva' => true,
                'img_path' => '/images/products/logitech-mx-master-3s.jpg',
                'datasheet_path' => '/datasheets/logitech-mx-master-3s.pdf',
                'unit' => 'pz',
                'brand' => 'Logitech',
                'category' => 'Accesorios'
            ],
            [
                'name' => 'Razer DeathAdder V3',
                'sku' => 'RAZ-DAV3-BLK',
                'description' => 'Ratón gaming ergonómico con sensor Focus Pro 30K',
                'full_description' => 'Razer DeathAdder V3 con sensor Focus Pro 30K DPI, switches ópticos Gen-3, 90 horas de batería, HyperSpeed Wireless, forma ergonómica clásica y Razer Synapse 3.',
                'price' => 89.99,
                'cost' => 59.00,
                'iva' => true,
                'img_path' => '/images/products/razer-deathadder-v3.jpg',
                'datasheet_path' => '/datasheets/razer-deathadder-v3.pdf',
                'unit' => 'pz',
                'brand' => 'Razer',
                'category' => 'Accesorios'
            ],
            [
                'name' => 'Corsair K95 RGB Platinum XT',
                'sku' => 'COR-K95RGBPXT-MXS',
                'description' => 'Teclado mecánico gaming con switches Cherry MX Speed',
                'full_description' => 'Corsair K95 RGB Platinum XT con switches Cherry MX Speed Silver, iluminación RGB per-key, 6 teclas macro dedicadas, rueda de volumen, reposamuñecas desmontable y construcción de aluminio.',
                'price' => 199.99,
                'cost' => 132.00,
                'iva' => true,
                'img_path' => '/images/products/corsair-k95-rgb-platinum-xt.jpg',
                'datasheet_path' => '/datasheets/corsair-k95-rgb-platinum-xt.pdf',
                'unit' => 'pz',
                'brand' => 'Corsair',
                'category' => 'Accesorios'
            ],
            [
                'name' => 'Apple Magic Keyboard',
                'sku' => 'APL-MAGKBD-USBC-ESP',
                'description' => 'Teclado inalámbrico con Touch ID y USB-C',
                'full_description' => 'Magic Keyboard con Touch ID, teclas de tijera rediseñadas, batería recargable vía USB-C, conexión Lightning o Bluetooth, layout en español y compatibilidad universal Mac.',
                'price' => 199.99,
                'cost' => 132.00,
                'iva' => true,
                'img_path' => '/images/products/apple-magic-keyboard.jpg',
                'datasheet_path' => '/datasheets/apple-magic-keyboard.pdf',
                'unit' => 'pz',
                'brand' => 'Apple',
                'category' => 'Accesorios'
            ],
            [
                'name' => 'Microsoft Surface Pro 9',
                'sku' => 'MSF-SP9-I7-512GB',
                'description' => 'Tablet 2-en-1 con procesador Intel Core i7',
                'full_description' => 'Surface Pro 9 con Intel Core i7-1255U, 16GB RAM, SSD 512GB, pantalla PixelSense de 13", cámara 5MP frontal, Windows 11 Pro y hasta 15.5 horas de batería.',
                'price' => 1599.99,
                'cost' => 1050.00,
                'iva' => true,
                'img_path' => '/images/products/microsoft-surface-pro-9.jpg',
                'datasheet_path' => '/datasheets/microsoft-surface-pro-9.pdf',
                'unit' => 'pz',
                'brand' => 'Microsoft',
                'category' => 'Tablets'
            ],
            [
                'name' => 'Amazon Kindle Paperwhite',
                'sku' => 'AMZ-KNDPW-11G-32GB',
                'description' => 'E-reader con pantalla E Ink de 6.8" resistente al agua',
                'full_description' => 'Kindle Paperwhite 11ª generación con pantalla E Ink Carta de 6.8", 300 ppi, luz frontal ajustable, resistencia IPX8, 32GB almacenamiento, hasta 10 semanas de batería y acceso a Kindle Unlimited.',
                'price' => 139.99,
                'cost' => 92.00,
                'iva' => true,
                'img_path' => '/images/products/amazon-kindle-paperwhite.jpg',
                'datasheet_path' => '/datasheets/amazon-kindle-paperwhite.pdf',
                'unit' => 'pz',
                'brand' => 'Amazon',
                'category' => 'Accesorios'
            ],
            [
                'name' => 'GoPro HERO12 Black',
                'sku' => 'GOP-H12BLK-128GB',
                'description' => 'Cámara de acción 5.3K con HyperSmooth 6.0',
                'full_description' => 'GoPro HERO12 Black con video 5.3K60, foto 27MP, HyperSmooth 6.0, TimeWarp 3.0, resistencia 10m bajo agua, pantalla táctil frontal y trasera, control por voz y conectividad Wi-Fi.',
                'price' => 399.99,
                'cost' => 265.00,
                'iva' => true,
                'img_path' => '/images/products/gopro-hero12-black.jpg',
                'datasheet_path' => '/datasheets/gopro-hero12-black.pdf',
                'unit' => 'pz',
                'brand' => 'GoPro',
                'category' => 'Cámaras'
            ],
            [
                'name' => 'DJI Mini 4 Pro',
                'sku' => 'DJI-MINI4P-FMC',
                'description' => 'Drone compacto con cámara 4K y detección de obstáculos omnidireccional',
                'full_description' => 'DJI Mini 4 Pro con cámara 4K/60fps, sensor 1/1.3", detección omnidireccional de obstáculos, ActiveTrack 360°, 45 minutos de vuelo, transmisión O4 20km y peso menor a 249g.',
                'price' => 759.99,
                'cost' => 500.00,
                'iva' => true,
                'img_path' => '/images/products/dji-mini-4-pro.jpg',
                'datasheet_path' => '/datasheets/dji-mini-4-pro.pdf',
                'unit' => 'pz',
                'brand' => 'DJI',
                'category' => 'Drones'
            ],
            [
                'name' => 'Anker PowerCore 26800mAh',
                'sku' => 'ANK-PC26800-PD',
                'description' => 'Batería externa con carga rápida PD 45W y 3 puertos',
                'full_description' => 'Anker PowerCore 26800 con Power Delivery 45W, 3 puertos de salida (2x USB-A + 1x USB-C), tecnología PowerIQ 3.0, carga completa en 4 horas y capacidad para cargar laptop, tablet y teléfono.',
                'price' => 89.99,
                'cost' => 59.00,
                'iva' => true,
                'img_path' => '/images/products/anker-powercore-26800.jpg',
                'datasheet_path' => '/datasheets/anker-powercore-26800.pdf',
                'unit' => 'pz',
                'brand' => 'Anker',
                'category' => 'Accesorios'
            ],
            [
                'name' => 'Belkin 3-in-1 MagSafe Charger',
                'sku' => 'BEL-3IN1MAG-15W',
                'description' => 'Cargador inalámbrico 3-en-1 para iPhone, AirPods y Apple Watch',
                'full_description' => 'Belkin MagSafe 3-in-1 con carga 15W para iPhone, base para AirPods, cargador Apple Watch, compatible MagSafe, certificación Qi, diseño plegable para viaje y fuente 40W incluida.',
                'price' => 149.99,
                'cost' => 99.00,
                'iva' => true,
                'img_path' => '/images/products/belkin-3in1-magsafe.jpg',
                'datasheet_path' => '/datasheets/belkin-3in1-magsafe.pdf',
                'unit' => 'pz',
                'brand' => 'Belkin',
                'category' => 'Accesorios'
            ],
            [
                'name' => 'SteelSeries Arctis Nova Pro',
                'sku' => 'STE-ANPRO-BLK',
                'description' => 'Audífonos gaming premium con cancelación activa de ruido',
                'full_description' => 'SteelSeries Arctis Nova Pro con drivers de neodimio 40mm, cancelación activa de ruido, micrófono ClearCast Gen 2, GameDAC Gen 2, audio Hi-Res, conexión dual inalámbrica 2.4GHz + Bluetooth.',
                'price' => 349.99,
                'cost' => 230.00,
                'iva' => true,
                'img_path' => '/images/products/steelseries-arctis-nova-pro.jpg',
                'datasheet_path' => '/datasheets/steelseries-arctis-nova-pro.pdf',
                'unit' => 'pz',
                'brand' => 'SteelSeries',
                'category' => 'Audífonos'
            ],
            [
                'name' => 'Rode PodMic USB',
                'sku' => 'ROD-PODMIC-USB',
                'description' => 'Micrófono broadcast dinámico con conexión USB y XLR',
                'full_description' => 'Rode PodMic USB con cápsula broadcast dinámica, conexión dual USB/XLR, filtro pop interno, montaje antivibración RYCOTE, monitoreo por audífonos sin latencia y procesamiento de señal interno.',
                'price' => 199.99,
                'cost' => 132.00,
                'iva' => true,
                'img_path' => '/images/products/rode-podmic-usb.jpg',
                'datasheet_path' => '/datasheets/rode-podmic-usb.pdf',
                'unit' => 'pz',
                'brand' => 'Rode',
                'category' => 'Audio'
            ],
            [
                'name' => 'Elgato Stream Deck MK.2',
                'sku' => 'ELG-SDMK2-15KEY',
                'description' => 'Controlador de transmisión con 15 teclas LCD personalizables',
                'full_description' => 'Elgato Stream Deck MK.2 con 15 teclas LCD de 72x72px, control de streaming, launcher de aplicaciones, integración con OBS, Twitch, YouTube, Spotify, Philips Hue y más de 1000 acciones.',
                'price' => 149.99,
                'cost' => 99.00,
                'iva' => true,
                'img_path' => '/images/products/elgato-stream-deck-mk2.jpg',
                'datasheet_path' => '/datasheets/elgato-stream-deck-mk2.pdf',
                'unit' => 'pz',
                'brand' => 'Elgato',
                'category' => 'Accesorios'
            ],
            [
                'name' => 'NVIDIA GeForce RTX 4090',
                'sku' => 'NVI-RTX4090-24GB',
                'description' => 'Tarjeta gráfica flagship con arquitectura Ada Lovelace',
                'full_description' => 'NVIDIA GeForce RTX 4090 con 16384 CUDA Cores, 24GB GDDR6X, ray tracing de 3ra generación, DLSS 3, AV1 encoding, DisplayPort 1.4a, HDMI 2.1a y boost clock de 2520 MHz.',
                'price' => 1599.99,
                'cost' => 1050.00,
                'iva' => true,
                'img_path' => '/images/products/nvidia-rtx-4090.jpg',
                'datasheet_path' => '/datasheets/nvidia-rtx-4090.pdf',
                'unit' => 'pz',
                'brand' => 'NVIDIA',
                'category' => 'Componentes'
            ],
            [
                'name' => 'AMD Ryzen 9 7950X',
                'sku' => 'AMD-R97950X-AM5',
                'description' => 'Procesador de 16 núcleos y 32 hilos con arquitectura Zen 4',
                'full_description' => 'AMD Ryzen 9 7950X con 16 núcleos, 32 hilos, frecuencia base 4.5GHz, boost hasta 5.7GHz, caché L3 64MB, soporte DDR5-5200, PCIe 5.0, socket AM5 y TDP 170W.',
                'price' => 699.99,
                'cost' => 460.00,
                'iva' => true,
                'img_path' => '/images/products/amd-ryzen-9-7950x.jpg',
                'datasheet_path' => '/datasheets/amd-ryzen-9-7950x.pdf',
                'unit' => 'pz',
                'brand' => 'AMD',
                'category' => 'Componentes'
            ],
            [
                'name' => 'Corsair Dominator Platinum RGB 32GB',
                'sku' => 'COR-DOMPRGB-32GB-6000',
                'description' => 'Memoria RAM DDR5-6000 32GB (2x16GB) con iluminación RGB',
                'full_description' => 'Corsair Dominator Platinum RGB 32GB (2x16GB) DDR5-6000 C36, disipadores de aluminio, iluminación RGB Capellix, overclocking optimizado, compatibilidad Intel XMP 3.0 y AMD EXPO.',
                'price' => 399.99,
                'cost' => 265.00,
                'iva' => true,
                'img_path' => '/images/products/corsair-dominator-rgb-32gb.jpg',
                'datasheet_path' => '/datasheets/corsair-dominator-rgb-32gb.pdf',
                'unit' => 'pz',
                'brand' => 'Corsair',
                'category' => 'Componentes'
            ],
            [
                'name' => 'Samsung 990 PRO 2TB',
                'sku' => 'SAM-990PRO-2TB-M2',
                'description' => 'SSD NVMe M.2 PCIe 4.0 de 2TB con disipador',
                'full_description' => 'Samsung 990 PRO 2TB NVMe M.2 con PCIe 4.0, velocidades secuenciales hasta 7450/6900 MB/s, controlador Samsung Pascal, V-NAND 3-bit MLC, disipador incluido y garantía 5 años.',
                'price' => 329.99,
                'cost' => 217.00,
                'iva' => true,
                'img_path' => '/images/products/samsung-990-pro-2tb.jpg',
                'datasheet_path' => '/datasheets/samsung-990-pro-2tb.pdf',
                'unit' => 'pz',
                'brand' => 'Samsung',
                'category' => 'Componentes'
            ],
            [
                'name' => 'ASUS ROG Strix Z790-E Gaming',
                'sku' => 'ASU-Z790E-WIFI6E',
                'description' => 'Motherboard ATX para Intel 12va/13va gen con Wi-Fi 6E',
                'full_description' => 'ASUS ROG Strix Z790-E con socket LGA1700, DDR5-7200+, PCIe 5.0, Wi-Fi 6E, Bluetooth 5.3, 2.5Gb Ethernet, SupremeFX audio, Aura Sync RGB, ROG heatsinks y BIOS ROG UEFI.',
                'price' => 449.99,
                'cost' => 295.00,
                'iva' => true,
                'img_path' => '/images/products/asus-rog-z790e.jpg',
                'datasheet_path' => '/datasheets/asus-rog-z790e.pdf',
                'unit' => 'pz',
                'brand' => 'ASUS',
                'category' => 'Componentes'
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