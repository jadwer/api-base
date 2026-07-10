<?php

namespace Modules\SatCatalogs\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * SAT c_ClaveUnidad catalog (CFDI 4.0). Global data, shared by all tenants.
 *
 * Source: phpcfdi/resources-sat-catalogs table cfdi_40_claves_unidades.
 */
class SatClaveUnidad extends Model
{
    protected $table = 'sat_clave_unidad';

    protected $primaryKey = 'clave';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'clave',
        'nombre',
        'descripcion',
        'simbolo',
    ];
}
