<?php

namespace Modules\SatCatalogs\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * SAT c_Colonia catalog (CFDI 4.0): colonias por codigo postal.
 *
 * Source: phpcfdi/resources-sat-catalogs table cfdi_40_colonias.
 */
class SatColonia extends Model
{
    protected $table = 'sat_colonias';

    public $timestamps = false;

    protected $fillable = [
        'codigo_postal',
        'clave',
        'nombre',
    ];
}
