<?php

namespace Modules\PageBuilder\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\PageBuilder\Models\Page;
use Modules\User\Models\User;
use Carbon\Carbon;

/**
 * Demo / placeholder pages for the public site of the api-base template.
 *
 * Opt-in: NOT called from PageBuilderDatabaseSeeder or CleanCatalogsSeeder
 * by default. Run manually for local demos or local QA:
 *
 *     php artisan db:seed --class=Modules\\PageBuilder\\Database\\Seeders\\DemoPagesSeeder
 *
 * Production tenants ship their own pages seeder (e.g. AcmePagesSeeder)
 * with real content. Both seeders use firstOrCreate so dashboard edits are
 * never overwritten by re-seeding.
 *
 * Pages here exist purely so a fresh dev install has the standard public
 * routes (/nosotros, /aviso-privacidad, etc.) populated with neutral
 * placeholder text, not tenant-specific copy.
 */
class DemoPagesSeeder extends Seeder
{
    public function run(): void
    {
        $system = User::find(1) ?? User::first();

        if (!$system) {
            // No users yet (e.g. first migrate:fresh before user seeders ran).
            // Skip silently; run order or manual invocation will populate later.
            return;
        }

        $pages = [
            [
                'slug' => 'nosotros',
                'title' => 'About Us',
                'description' => 'Learn more about us.',
                'html_content' => '<section class="about-section"><div class="container py-5"><h1>About Us</h1><p>Replace this placeholder copy with your company story from the dashboard PageBuilder.</p></div></section>',
                'css_content' => '',
            ],
            [
                'slug' => 'laboratorios',
                'title' => 'Services',
                'description' => 'Our services and capabilities.',
                'html_content' => '<section class="services-section"><div class="container py-5"><h1>Services</h1><p>Describe your services here.</p></div></section>',
                'css_content' => '',
            ],
            [
                'slug' => 'certificados',
                'title' => 'Certifications',
                'description' => 'Our certifications and credentials.',
                'html_content' => '<section class="cert-section"><div class="container py-5"><h1>Certifications</h1><p>List your certifications here.</p></div></section>',
                'css_content' => '',
            ],
            [
                'slug' => 'aviso-privacidad',
                'title' => 'Privacy Policy',
                'description' => 'Privacy policy.',
                'html_content' => '<section class="legal-section"><div class="container py-5"><h1>Privacy Policy</h1><p>Replace with your privacy policy.</p></div></section>',
                'css_content' => '',
            ],
            [
                'slug' => 'derechos-reservados',
                'title' => 'Terms of Use',
                'description' => 'Terms of use and copyright.',
                'html_content' => '<section class="legal-section"><div class="container py-5"><h1>Terms of Use</h1><p>Replace with your terms.</p></div></section>',
                'css_content' => '',
            ],
            [
                'slug' => 'catalogos',
                'title' => 'Catalogs',
                'description' => 'Product catalogs.',
                'html_content' => '<section class="catalog-section"><div class="container py-5"><h1>Catalogs</h1><p>List downloadable catalogs here.</p></div></section>',
                'css_content' => '',
            ],
        ];

        foreach ($pages as $pageData) {
            Page::firstOrCreate(
                ['slug' => $pageData['slug']],
                array_merge($pageData, [
                    'user_id' => $system->id,
                    'status' => 'published',
                    'published_at' => Carbon::now(),
                ])
            );
        }
    }
}
