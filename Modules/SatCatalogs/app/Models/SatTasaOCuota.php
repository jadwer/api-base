<?php

namespace Modules\SatCatalogs\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * SAT c_TasaOCuota catalog (CFDI 4.0). Global data, shared by all tenants.
 *
 * tipo = Tasa | Cuota | Exento. When tipo is Exento, valor is null.
 * Source: phpcfdi/resources-sat-catalogs table cfdi_40_reglas_tasa_cuota
 * (only "Fijo" rows are synced; "Rango" rows are ranges, not concrete rates).
 */
class SatTasaOCuota extends Model
{
    protected $table = 'sat_tasa_o_cuota';

    public $timestamps = false;

    protected $fillable = [
        'tipo',
        'impuesto',
        'valor',
        'retencion',
        'traslado',
    ];

    protected $casts = [
        'valor' => 'float',
        'retencion' => 'boolean',
        'traslado' => 'boolean',
    ];
}
