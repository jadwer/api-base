<?php

namespace Modules\SatCatalogs\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\SatCatalogs\Models\SatClaveProdServ;
use Modules\SatCatalogs\Models\SatClaveUnidad;
use Modules\SatCatalogs\Models\SatFormaPago;
use Modules\SatCatalogs\Models\SatTasaOCuota;

/**
 * Useful subset of the SAT catalogs so dev/demo/tests work WITHOUT internet.
 *
 * The full catalogs (~52k ClaveProdServ, ~2.4k ClaveUnidad) are loaded with
 * `php artisan sat:sync-catalogs`. Claves and descriptions below were taken
 * verbatim from phpcfdi/resources-sat-catalogs v10.11.20260703, biased to the
 * chemicals/laboratory business of the current tenants.
 */
class SatCatalogsSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedClaveProdServ();
        $this->seedClaveUnidad();
        $this->seedFormaPago();
        $this->seedTasaOCuota();
    }

    protected function seedClaveProdServ(): void
    {
        $claves = [
            // Generic key (SAT: "public in general" / not in catalog)
            ['clave' => '01010101', 'descripcion' => 'No existe en el catálogo', 'palabras_similares' => 'Público en general'],

            // Chemicals (12352xxx)
            ['clave' => '12352100', 'descripcion' => 'Derivados orgánicos y compuestos sustituidos'],
            ['clave' => '12352101', 'descripcion' => 'Compuestos halogenados orgánicos'],
            ['clave' => '12352102', 'descripcion' => 'Compuestos orgánicos nitro o nitroso'],
            ['clave' => '12352104', 'descripcion' => 'Alcoholes o sus sustitutos'],
            ['clave' => '12352200', 'descripcion' => 'Productos bioquímicos'],
            ['clave' => '12352201', 'descripcion' => 'Carbohidratos o sus derivados'],
            ['clave' => '12352300', 'descripcion' => 'Compuestos inorgánicos'],
            ['clave' => '12352301', 'descripcion' => 'Ácidos inorgánicos'],
            ['clave' => '12352302', 'descripcion' => 'Sales metálicas inorgánicas'],
            ['clave' => '12352303', 'descripcion' => 'Óxidos inorgánicos'],
            ['clave' => '12352304', 'descripcion' => 'Peróxidos inorgánicos'],
            ['clave' => '12352305', 'descripcion' => 'Hidróxidos inorgánicos'],

            // Laboratory equipment and supplies (411xxxxx)
            ['clave' => '41103000', 'descripcion' => 'Equipo de enfriamiento para laboratorio'],
            ['clave' => '41103900', 'descripcion' => 'Centrifugadoras de laboratorio y accesorios'],
            ['clave' => '41104100', 'descripcion' => 'Contenedores de recogida y transporte de especímenes, y suministros'],
            ['clave' => '41104800', 'descripcion' => 'Equipo y suministros de laboratorio para el vertido, la destilación, la evaporación y la extracción'],
            ['clave' => '41104900', 'descripcion' => 'Equipo y suministros de filtrado para laboratorio'],
            ['clave' => '41111500', 'descripcion' => 'Instrumentos de medición del peso'],
            ['clave' => '41112200', 'descripcion' => 'Instrumentos de medida de temperatura y calor'],
            ['clave' => '41112400', 'descripcion' => 'Instrumentos de medida y control de la presión'],
            ['clave' => '41113000', 'descripcion' => 'Instrumentos de suministros evaluación química'],
            ['clave' => '41116000', 'descripcion' => 'Reactivos de analizadores clínicos y diagnósticos'],
            ['clave' => '41116100', 'descripcion' => 'Kits de ensayos manuales, controles de calidad, calibradores y normativas'],
            ['clave' => '41121500', 'descripcion' => 'Equipo y suministros de pipetas y manipulación de líquidos'],
            ['clave' => '41121800', 'descripcion' => 'Artículos de vidrio o plástico y suministros generales de laboratorio'],

            // Safety, cleaning and services
            ['clave' => '42132203', 'descripcion' => 'Guantes de examen o para procedimientos no quirúrgicos'],
            ['clave' => '46181500', 'descripcion' => 'Ropa de seguridad'],
            ['clave' => '47131700', 'descripcion' => 'Suministros para aseos'],
            ['clave' => '73101500', 'descripcion' => 'Producción petroquímica y de plástico'],
            ['clave' => '73101600', 'descripcion' => 'Producción de químicos y fertilizantes'],
            ['clave' => '77101500', 'descripcion' => 'Evaluación de impacto ambiental'],
            ['clave' => '85121800', 'descripcion' => 'Laboratorios médicos', 'palabras_similares' => 'Servicios de análisis clínicos'],
        ];

        foreach ($claves as $clave) {
            SatClaveProdServ::firstOrCreate(['clave' => $clave['clave']], $clave);
        }
    }

    protected function seedClaveUnidad(): void
    {
        $unidades = [
            ['clave' => 'H87', 'nombre' => 'Pieza', 'simbolo' => null],
            ['clave' => 'KGM', 'nombre' => 'Kilogramo', 'simbolo' => 'kg'],
            ['clave' => 'GRM', 'nombre' => 'Gramo', 'simbolo' => 'g'],
            ['clave' => 'LTR', 'nombre' => 'Litro', 'simbolo' => 'l'],
            ['clave' => 'MLT', 'nombre' => 'Mililitro', 'simbolo' => 'ml'],
            ['clave' => 'MTR', 'nombre' => 'Metro', 'simbolo' => 'm'],
            ['clave' => 'MTK', 'nombre' => 'Metro cuadrado', 'simbolo' => 'm²'],
            ['clave' => 'XBX', 'nombre' => 'Caja', 'simbolo' => null],
            ['clave' => 'XPK', 'nombre' => 'Paquete', 'simbolo' => null],
            ['clave' => 'XUN', 'nombre' => 'Unidad', 'simbolo' => null],
            ['clave' => 'E48', 'nombre' => 'Unidad de servicio', 'simbolo' => null],
            ['clave' => 'ACT', 'nombre' => 'Actividad', 'simbolo' => null],
            ['clave' => 'HUR', 'nombre' => 'Hora', 'simbolo' => 'h'],
            ['clave' => 'DAY', 'nombre' => 'Día', 'simbolo' => 'd'],
            ['clave' => 'KT', 'nombre' => 'Kit', 'simbolo' => null],
            ['clave' => 'SET', 'nombre' => 'Conjunto', 'simbolo' => null],
            ['clave' => 'EA', 'nombre' => 'Elemento', 'simbolo' => null],
        ];

        foreach ($unidades as $unidad) {
            SatClaveUnidad::firstOrCreate(['clave' => $unidad['clave']], $unidad);
        }
    }

    protected function seedFormaPago(): void
    {
        $formas = [
            ['clave' => '01', 'descripcion' => 'Efectivo'],
            ['clave' => '02', 'descripcion' => 'Cheque nominativo'],
            ['clave' => '03', 'descripcion' => 'Transferencia electrónica de fondos'],
            ['clave' => '04', 'descripcion' => 'Tarjeta de crédito'],
            ['clave' => '05', 'descripcion' => 'Monedero electrónico'],
            ['clave' => '06', 'descripcion' => 'Dinero electrónico'],
            ['clave' => '17', 'descripcion' => 'Compensación'],
            ['clave' => '28', 'descripcion' => 'Tarjeta de débito'],
            ['clave' => '30', 'descripcion' => 'Aplicación de anticipos'],
            ['clave' => '31', 'descripcion' => 'Intermediario pagos'],
            ['clave' => '99', 'descripcion' => 'Por definir'],
        ];

        foreach ($formas as $forma) {
            SatFormaPago::firstOrCreate(['clave' => $forma['clave']], $forma);
        }
    }

    protected function seedTasaOCuota(): void
    {
        $tasas = [
            // Traslados IVA
            ['tipo' => 'Tasa', 'impuesto' => 'IVA', 'valor' => 0.160000, 'retencion' => false, 'traslado' => true],
            ['tipo' => 'Tasa', 'impuesto' => 'IVA', 'valor' => 0.080000, 'retencion' => false, 'traslado' => true],
            ['tipo' => 'Tasa', 'impuesto' => 'IVA', 'valor' => 0.000000, 'retencion' => false, 'traslado' => true],
            ['tipo' => 'Exento', 'impuesto' => 'IVA', 'valor' => null, 'retencion' => false, 'traslado' => true],

            // Retenciones comunes
            ['tipo' => 'Tasa', 'impuesto' => 'ISR', 'valor' => 0.100000, 'retencion' => true, 'traslado' => false],
            ['tipo' => 'Tasa', 'impuesto' => 'IVA', 'valor' => 0.106667, 'retencion' => true, 'traslado' => false],
        ];

        foreach ($tasas as $tasa) {
            SatTasaOCuota::firstOrCreate($tasa);
        }
    }
}
