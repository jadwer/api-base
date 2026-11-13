<?php

namespace Modules\AppConfig\Tests\Feature;

use Modules\AppConfig\Models\AppSetting;
use Modules\User\Models\User;
use Tests\TestCase;

/**
 * Ajustes de lista cerrada: el backend sirve las opciones y valida contra
 * ellas; los del constructor de documentos traen etiqueta en espanol (2026-11-12).
 */
class AppSettingOptionsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\CleanDatabaseSeeder::class);
        $this->seed(\Modules\AppConfig\Database\Seeders\AppSettingSeeder::class);
    }

    private function admin(): User
    {
        return User::where('email', 'god@example.com')->firstOrFail();
    }

    public function test_el_grupo_documentos_trae_etiquetas_en_espanol_y_opciones(): void
    {
        $data = $this->actingAs($this->admin(), 'sanctum')->getJson('/api/v1/app-settings/group/documents')->assertOk()->json('data');

        $this->assertSame('Remisión con el diseño nuevo', $data['documents.builder.remission']['label']);
        $this->assertFalse($data['documents.builder.remission']['value']);
        $this->assertArrayNotHasKey('documents.builder.quote_mode', $data, 'el color vive en el diseño desde atomo/documents 0.11.3');
        $this->assertSame(['company', 'manual', 'billing'], array_column($data['documents.issuer_source']['options'], 'value'));
        $this->assertNull($data['documents.issuer_name']['options']);
    }

    public function test_un_valor_fuera_de_las_opciones_se_rechaza_en_espanol(): void
    {
        $this->actingAs($this->admin(), 'sanctum')
            ->patchJson('/api/v1/app-settings/documents.issuer_source', ['value' => 'otro'])
            ->assertStatus(422)
            ->assertJsonPath('errors.value.0', 'El valor de "Origen de los datos del emisor en cotizaciones" debe ser una de las opciones de la lista.');

        $this->assertSame('company', AppSetting::get('documents.issuer_source'));
    }

    public function test_un_valor_de_la_lista_se_guarda(): void
    {
        $this->actingAs($this->admin(), 'sanctum')
            ->patchJson('/api/v1/app-settings/documents.issuer_source', ['value' => 'billing'])
            ->assertOk()
            ->assertJsonPath('data.options.2.value', 'billing');

        $this->assertSame('billing', AppSetting::get('documents.issuer_source'));
    }

    public function test_un_ajuste_sin_opciones_acepta_texto_libre(): void
    {
        $this->actingAs($this->admin(), 'sanctum')
            ->patchJson('/api/v1/app-settings/documents.issuer_name', ['value' => 'Labor Wasser'])
            ->assertOk();
    }
}
