<?php

namespace Modules\SatCatalogs\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * SAT c_FormaPago catalog (CFDI 4.0). Global data, shared by all tenants.
 *
 * Source: phpcfdi/resources-sat-catalogs table cfdi_40_formas_pago.
 */
class SatFormaPago extends Model
{
    protected $table = 'sat_forma_pago';

    protected $primaryKey = 'clave';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'clave',
        'descripcion',
    ];
}
