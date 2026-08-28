<?php

namespace Modules\SatCatalogs\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * SAT c_CodigoPostal catalog (CFDI 4.0), denormalizado con los nombres de
 * estado y municipio para lookups sin joins. Global, compartido por tenants.
 *
 * Source: phpcfdi/resources-sat-catalogs table cfdi_40_codigos_postales
 * (nombres desde cfdi_40_estados y cfdi_40_municipios en el sync).
 */
class SatCodigoPostal extends Model
{
    protected $table = 'sat_codigos_postales';

    protected $primaryKey = 'codigo_postal';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'codigo_postal',
        'estado_clave',
        'estado',
        'municipio_clave',
        'municipio',
        'localidad_clave',
    ];

    public function colonias(): HasMany
    {
        return $this->hasMany(SatColonia::class, 'codigo_postal', 'codigo_postal');
    }
}
