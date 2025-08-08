<?php

namespace Modules\Product\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Product\Models\Category;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // BASED ON: Migration create_categories_table.php (lines 18-20: name, description, slug)
        // BASED ON: CategorySchema.php (lines 20-22: name, description, slug fields)
        $categories = [
            [
                'name' => 'Smartphones',
                'slug' => 'smartphones',
                'description' => 'Teléfonos inteligentes con conectividad móvil avanzada'
            ],
            [
                'name' => 'Laptops',
                'slug' => 'laptops',
                'description' => 'Computadoras portátiles para trabajo y entretenimiento'
            ],
            [
                'name' => 'Tablets',
                'slug' => 'tablets',
                'description' => 'Dispositivos táctiles portátiles para navegación y multimedia'
            ],
            [
                'name' => 'Televisores',
                'slug' => 'televisores',
                'description' => 'Pantallas para entretenimiento doméstico y visualización'
            ],
            [
                'name' => 'Audífonos',
                'slug' => 'audifonos',
                'description' => 'Dispositivos de audio personales con cancelación de ruido'
            ],
            [
                'name' => 'Cámaras',
                'slug' => 'camaras',
                'description' => 'Equipos de fotografía digital profesional y doméstica'
            ],
            [
                'name' => 'Consolas',
                'slug' => 'consolas',
                'description' => 'Sistemas de videojuegos para entretenimiento interactivo'
            ],
            [
                'name' => 'Smartwatches',
                'slug' => 'smartwatches',
                'description' => 'Relojes inteligentes con monitoreo de salud y fitness'
            ],
            [
                'name' => 'Electrodomésticos',
                'slug' => 'electrodomesticos',
                'description' => 'Aparatos eléctricos para uso doméstico y cocina'
            ],
            [
                'name' => 'Accesorios',
                'slug' => 'accesorios',
                'description' => 'Complementos y periféricos para dispositivos electrónicos'
            ]
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(
                ['name' => $category['name']],
                $category
            );
        }
    }
}