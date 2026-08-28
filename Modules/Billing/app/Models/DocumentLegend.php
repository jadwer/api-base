<?php

namespace Modules\Billing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Billing\Database\Factories\DocumentLegendFactory;

/**
 * Leyenda configurable por tipo de documento (se imprime en el PDF).
 *
 * Una fila por document_type. El body admite placeholders {nombre} que
 * DocumentLegendRenderer resuelve al generar cada documento. Si un tipo no
 * tiene leyenda activa, el renderer cae a CompanySetting.commercial_conditions
 * (solo quote/sales_order, que son los que historicamente la imprimen) y
 * despues a los defaults; por eso esta tabla nace vacia sin seeder.
 *
 * @property int $id
 * @property string $document_type
 * @property string $body
 * @property bool $is_active
 */
class DocumentLegend extends Model
{
    use HasFactory;

    public const TYPE_QUOTE = 'quote';
    public const TYPE_SALES_ORDER = 'sales_order';
    public const TYPE_CFDI_INVOICE = 'cfdi_invoice';
    public const TYPE_REMISSION = 'remission';

    public const TYPES = [
        self::TYPE_QUOTE,
        self::TYPE_SALES_ORDER,
        self::TYPE_CFDI_INVOICE,
        self::TYPE_REMISSION,
    ];

    protected $table = 'document_legends';

    protected $fillable = [
        'document_type',
        'body',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForType($query, string $documentType)
    {
        return $query->where('document_type', $documentType);
    }

    protected static function newFactory()
    {
        return DocumentLegendFactory::new();
    }
}
