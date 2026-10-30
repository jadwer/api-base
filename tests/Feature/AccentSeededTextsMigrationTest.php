<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Billing\Models\CompanySetting;
use Tests\TestCase;

class AccentSeededTextsMigrationTest extends TestCase
{
    use RefreshDatabase;

    private function runMigration(): void
    {
        $migration = require database_path('migrations/2026_10_30_120000_accent_seeded_ui_texts.php');
        $migration->up();
    }

    public function test_accents_series_names_and_template_text_but_not_attributes(): void
    {
        $seriesId = DB::table('invoice_series')->insertGetId([
            'code' => 'ZZ', 'name' => 'Facturacion Normal', 'description' => 'Serie para notas de credito',
            'company_setting_id' => CompanySetting::factory()->create()->id,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $templateId = DB::table('email_templates')->insertGetId([
            'name' => 'Cotizacion Enviada', 'slug' => 'zz-cotizacion', 'subject' => 'Cotizacion {{quote_number}}',
            'html' => '<a href="https://x.mx/cotizacion">Ver cotizacion</a><h1>COTIZACION</h1>',
            'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->runMigration();
        $this->runMigration(); // idempotente

        $series = DB::table('invoice_series')->find($seriesId);
        $this->assertSame('Facturación Normal', $series->name);
        $this->assertSame('Serie para notas de crédito', $series->description);

        $template = DB::table('email_templates')->find($templateId);
        $this->assertSame('Cotización Enviada', $template->name);
        $this->assertSame('zz-cotizacion', $template->slug);
        $this->assertSame('Cotización {{quote_number}}', $template->subject);
        $this->assertSame('<a href="https://x.mx/cotizacion">Ver cotización</a><h1>COTIZACIÓN</h1>', $template->html);
    }
}
