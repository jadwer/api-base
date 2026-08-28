<?php

namespace Modules\SatCatalogs\Tests\Feature;

use Modules\SatCatalogs\Models\SatClaveProdServ;
use Modules\SatCatalogs\Models\SatClaveUnidad;
use Modules\SatCatalogs\Models\SatFormaPago;
use Modules\SatCatalogs\Models\SatTasaOCuota;
use Tests\TestCase;

/**
 * Tests for sat:sync-catalogs using a tiny SQLite fixture built on the fly.
 * Nothing is downloaded: the fixture mimics the phpcfdi catalogs.db schema
 * (cfdi_40_productos_servicios, cfdi_40_claves_unidades, cfdi_40_formas_pago,
 * cfdi_40_reglas_tasa_cuota).
 */
class SatSyncCatalogsCommandTest extends TestCase
{
    protected ?string $fixturePath = null;

    protected function tearDown(): void
    {
        if ($this->fixturePath !== null) {
            @unlink($this->fixturePath);
        }

        parent::tearDown();
    }

    public function test_command_upserts_the_catalog_tables_from_a_local_file(): void
    {
        $path = $this->createFixtureDatabase();

        $this->artisan('sat:sync-catalogs', ['--path' => $path])
            ->assertExitCode(0);

        // Inserted new rows
        $this->assertDatabaseHas('sat_clave_prod_serv', [
            'clave' => '99999901',
            'descripcion' => 'Producto ficticio de prueba',
        ]);
        $this->assertDatabaseHas('sat_clave_unidad', [
            'clave' => 'ZZT',
            'nombre' => 'Unidad ficticia',
            'simbolo' => 'zz',
        ]);
        $this->assertDatabaseHas('sat_forma_pago', [
            'clave' => '98',
            'descripcion' => 'Forma de pago ficticia',
        ]);

        // Updated an existing row (upsert, not duplicate insert)
        $this->assertDatabaseHas('sat_clave_prod_serv', [
            'clave' => '01010101',
            'descripcion' => 'Descripcion actualizada por sync',
        ]);
        $this->assertSame(1, SatClaveProdServ::where('clave', '01010101')->count());

        // Tri-state flags: '' -> null, '1' -> true
        $this->assertNull(SatClaveProdServ::find('99999901')->incluye_iva);
        $this->assertTrue((bool) SatClaveProdServ::find('99999902')->incluye_iva);

        // Tasas: only Fijo rows are imported, Rango rows are skipped
        $this->assertTrue(
            SatTasaOCuota::where('impuesto', 'IEPS')
                ->where('valor', 0.265)
                ->where('traslado', true)
                ->exists()
        );
        $this->assertFalse(
            SatTasaOCuota::where('impuesto', 'ISR')
                ->where('valor', 0.35)
                ->exists()
        );

        // Codigos postales: nombres de estado/municipio denormalizados
        $this->assertDatabaseHas('sat_codigos_postales', [
            'codigo_postal' => '06600',
            'estado_clave' => 'CMX',
            'estado' => 'Ciudad de México',
            'municipio_clave' => '015',
            'municipio' => 'Cuauhtémoc',
        ]);
        // CP sin municipio: claves vacias quedan null
        $this->assertDatabaseHas('sat_codigos_postales', [
            'codigo_postal' => '99999',
            'estado_clave' => 'ZAC',
            'municipio_clave' => null,
        ]);
        $this->assertDatabaseHas('sat_colonias', [
            'codigo_postal' => '06600',
            'clave' => '0930',
            'nombre' => 'Juárez',
        ]);
    }

    public function test_command_is_idempotent(): void
    {
        $path = $this->createFixtureDatabase();

        $this->artisan('sat:sync-catalogs', ['--path' => $path])->assertExitCode(0);

        $prodServCount = SatClaveProdServ::count();
        $unidadCount = SatClaveUnidad::count();
        $formaPagoCount = SatFormaPago::count();
        $tasaCount = SatTasaOCuota::count();

        $cpCount = \Modules\SatCatalogs\Models\SatCodigoPostal::count();
        $coloniaCount = \Modules\SatCatalogs\Models\SatColonia::count();

        $this->artisan('sat:sync-catalogs', ['--path' => $path])->assertExitCode(0);

        $this->assertSame($prodServCount, SatClaveProdServ::count());
        $this->assertSame($unidadCount, SatClaveUnidad::count());
        $this->assertSame($formaPagoCount, SatFormaPago::count());
        $this->assertSame($tasaCount, SatTasaOCuota::count());
        $this->assertSame($cpCount, \Modules\SatCatalogs\Models\SatCodigoPostal::count());
        $this->assertSame($coloniaCount, \Modules\SatCatalogs\Models\SatColonia::count());
    }

    public function test_command_fails_with_clear_error_when_file_is_missing(): void
    {
        $this->artisan('sat:sync-catalogs', ['--path' => '/tmp/does-not-exist-catalogs.db'])
            ->assertExitCode(1);
    }

    /**
     * Build a minimal catalogs.db with the same table/column names as the
     * phpcfdi release asset.
     */
    protected function createFixtureDatabase(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'sat_fixture_') . '.db';
        $this->fixturePath = $path;

        $pdo = new \PDO('sqlite:' . $path, null, null, [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        ]);

        $pdo->exec(<<<'SQL'
            CREATE TABLE cfdi_40_productos_servicios (
                id text not null, texto text not null,
                iva_trasladado int not null, ieps_trasladado int not null,
                complemento text not null, vigencia_desde text not null,
                vigencia_hasta text not null, estimulo_frontera int not null,
                similares text not null, PRIMARY KEY("id")
            );
            CREATE TABLE cfdi_40_claves_unidades (
                id text not null, texto text not null, descripcion text not null,
                notas text not null, vigencia_desde text not null,
                vigencia_hasta text not null, simbolo text not null,
                PRIMARY KEY("id")
            );
            CREATE TABLE cfdi_40_formas_pago (
                id text not null, texto text not null,
                vigencia_desde text not null, vigencia_hasta text not null,
                PRIMARY KEY("id")
            );
            CREATE TABLE cfdi_40_reglas_tasa_cuota (
                tipo text not null, minimo text not null, valor text not null,
                impuesto text not null, factor text not null,
                traslado int not null, retencion int not null,
                vigencia_desde text not null, vigencia_hasta text not null
            );

            INSERT INTO cfdi_40_productos_servicios VALUES
                ('01010101', 'Descripcion actualizada por sync', '', '', '', '2022-01-01', '', '', 'Público en general'),
                ('99999901', 'Producto ficticio de prueba', '', '', '', '2022-01-01', '', '', ''),
                ('99999902', 'Producto ficticio con IVA incluido', '1', '', '', '2022-01-01', '', '', '');

            INSERT INTO cfdi_40_claves_unidades VALUES
                ('ZZT', 'Unidad ficticia', 'Una unidad de prueba', '', '2022-01-01', '', 'zz'),
                ('ZZU', 'Otra unidad ficticia', '', '', '2022-01-01', '', ''),
                ('H87', 'Pieza', '', '', '2022-01-01', '', '');

            INSERT INTO cfdi_40_formas_pago VALUES
                ('98', 'Forma de pago ficticia', '2022-01-01', ''),
                ('01', 'Efectivo', '2022-01-01', ''),
                ('03', 'Transferencia electrónica de fondos', '2022-01-01', '');

            INSERT INTO cfdi_40_reglas_tasa_cuota VALUES
                ('Fijo', '', '0.160000', 'IVA', 'Tasa', 1, 0, '2022-01-01', ''),
                ('Fijo', '', '0.265000', 'IEPS', 'Tasa', 1, 1, '2022-01-01', ''),
                ('Rango', '0.000000', '0.350000', 'ISR', 'Tasa', 0, 1, '2022-01-01', '');

            CREATE TABLE cfdi_40_codigos_postales (
                id text not null, estado text not null,
                municipio text not null, localidad text not null,
                PRIMARY KEY("id")
            );
            CREATE TABLE cfdi_40_estados (
                estado text not null, pais text not null, texto text not null
            );
            CREATE TABLE cfdi_40_municipios (
                municipio text not null, estado text not null, texto text not null
            );
            CREATE TABLE cfdi_40_colonias (
                colonia text not null, codigo_postal text not null, texto text not null
            );

            INSERT INTO cfdi_40_codigos_postales VALUES
                ('06600', 'CMX', '015', '06'),
                ('99999', 'ZAC', '', '');

            INSERT INTO cfdi_40_estados VALUES
                ('CMX', 'MEX', 'Ciudad de México'),
                ('ZAC', 'MEX', 'Zacatecas'),
                ('CMX', 'USA', 'Estado gringo homonimo (no debe usarse)');

            INSERT INTO cfdi_40_municipios VALUES
                ('015', 'CMX', 'Cuauhtémoc'),
                ('015', 'ZAC', 'Otro municipio 015 (otro estado)');

            INSERT INTO cfdi_40_colonias VALUES
                ('0930', '06600', 'Juárez'),
                ('0001', '99999', 'Colonia ficticia');
            SQL);

        return $path;
    }
}
