<?php

namespace Modules\SatCatalogs\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * SAT c_ClaveProdServ catalog (CFDI 4.0). Global data, shared by all tenants.
 *
 * Source: phpcfdi/resources-sat-catalogs table cfdi_40_productos_servicios.
 */
class SatClaveProdServ extends Model
{
    protected $table = 'sat_clave_prod_serv';

    protected $primaryKey = 'clave';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'clave',
        'descripcion',
        'incluye_iva',
        'incluye_ieps',
        'palabras_similares',
        'vigencia_hasta',
    ];

    protected $casts = [
        'incluye_iva' => 'boolean',
        'incluye_ieps' => 'boolean',
        'vigencia_hasta' => 'date',
    ];
}
