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
            ['name' => 'Smartphones', 'slug' => 'smartphones', 'description' => 'Teléfonos inteligentes con conectividad móvil avanzada', 'is_active' => true],
            ['name' => 'Laptops', 'slug' => 'laptops', 'description' => 'Computadoras portátiles para trabajo y entretenimiento', 'is_active' => true],
            ['name' => 'Tablets', 'slug' => 'tablets', 'description' => 'Dispositivos táctiles portátiles para navegación y multimedia', 'is_active' => true],
            ['name' => 'Televisores', 'slug' => 'televisores', 'description' => 'Pantallas para entretenimiento doméstico y visualización', 'is_active' => true],
            ['name' => 'Audífonos', 'slug' => 'audifonos', 'description' => 'Dispositivos de audio personales con cancelación de ruido', 'is_active' => true],
            ['name' => 'Cámaras', 'slug' => 'camaras', 'description' => 'Equipos de fotografía digital profesional y doméstica', 'is_active' => true],
            ['name' => 'Consolas', 'slug' => 'consolas', 'description' => 'Sistemas de videojuegos para entretenimiento interactivo', 'is_active' => true],
            ['name' => 'Smartwatches', 'slug' => 'smartwatches', 'description' => 'Relojes inteligentes con monitoreo de salud y fitness', 'is_active' => true],
            ['name' => 'Electrodomésticos', 'slug' => 'electrodomesticos', 'description' => 'Aparatos eléctricos para uso doméstico y cocina', 'is_active' => false],
            ['name' => 'Accesorios', 'slug' => 'accesorios', 'description' => 'Complementos y periféricos para dispositivos electrónicos', 'is_active' => true],
            ['name' => 'Videojuegos', 'slug' => 'videojuegos', 'description' => 'Consolas y sistemas de entretenimiento interactivo', 'is_active' => true],
            ['name' => 'Audio', 'slug' => 'audio', 'description' => 'Equipos y sistemas de sonido profesional y doméstico', 'is_active' => true],
            ['name' => 'Drones', 'slug' => 'drones', 'description' => 'Vehículos aéreos no tripulados y accesorios', 'is_active' => true],
            ['name' => 'Componentes', 'slug' => 'componentes', 'description' => 'Piezas y componentes para construccion de PC', 'is_active' => true],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(
                ['name' => $category['name']],
                $category
            );
        }
    }
}