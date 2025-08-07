<?php

namespace Modules\PageBuilder\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\PageBuilder\Models\Page;
use Modules\User\Models\User;
use Illuminate\Support\Str;

class PageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Aseguramos que existe el usuario "System" como causer
        $system = User::find(1) ?? User::factory()->create([
            'id' => 1,
            'name' => 'System',
            'email' => 'system@page.local',
            'password' => 'system',
            'status' => 'active',
        ]);

        // Creamos páginas con trazabilidad - mix de draft y published
        $publishedPages = Page::factory()->published()->count(2)->create([
            'user_id' => $system->id,
        ]);
        
        $draftPages = Page::factory()->draft()->count(1)->create([
            'user_id' => $system->id,
        ]);
        
        $pages = $publishedPages->concat($draftPages);

        foreach ($pages as $page) {
            activity()
                ->causedBy($system)
                ->performedOn($page)
                ->log("Página '{$page->title}' creada en seed inicial");
        }
    }
}
