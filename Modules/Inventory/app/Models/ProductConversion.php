<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * ProductConversion Model
 *
 * Defines a conversion relationship between two products.
 * E.g., 1 unit of "Reactivo Granel 15,000L" produces 1500 units of "Reactivo Botella 10L" with 2% waste.
 *
 * @property int $id
 * @property int $source_product_id
 * @property int $destination_product_id
 * @property float $conversion_factor
 * @property float $waste_percentage
 * @property bool $whole_units Solo piezas completas: lo producido se redondea hacia abajo
 * @property bool $is_active
 * @property string|null $notes
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class ProductConversion extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'product_conversions';

    protected $fillable = [
        'source_product_id',
        'destination_product_id',
        'conversion_factor',
        'waste_percentage',
        'whole_units',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'id' => 'integer',
        'source_product_id' => 'integer',
        'destination_product_id' => 'integer',
        'conversion_factor' => 'float',
        'waste_percentage' => 'float',
        'whole_units' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected $attributes = [
        'whole_units' => false,
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'source_product_id', 'destination_product_id',
                'conversion_factor', 'waste_percentage', 'whole_units', 'is_active',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // Relationships

    /**
     * @return BelongsTo<\Modules\Product\Models\Product, $this>
     */
    public function sourceProduct(): BelongsTo
    {
        return $this->belongsTo(\Modules\Product\Models\Product::class, 'source_product_id');
    }

    /**
     * @return BelongsTo<\Modules\Product\Models\Product, $this>
     */
    public function destinationProduct(): BelongsTo
    {
        return $this->belongsTo(\Modules\Product\Models\Product::class, 'destination_product_id');
    }

    // Scopes

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForSourceProduct($query, int $productId)
    {
        return $query->where('source_product_id', $productId);
    }

    public function scopeForDestinationProduct($query, int $productId)
    {
        return $query->where('destination_product_id', $productId);
    }

    /**
     * Calculate produced quantity for a given source quantity (sin redondeo a
     * piezas; para el plan real usar planProduction()).
     */
    public function calculateProducedQuantity(float $sourceQuantity): float
    {
        return $sourceQuantity * $this->conversion_factor * (1 - $this->waste_percentage / 100);
    }

    /**
     * Calculate waste quantity for a given source quantity.
     */
    public function calculateWasteQuantity(float $sourceQuantity): float
    {
        return $sourceQuantity * $this->conversion_factor * ($this->waste_percentage / 100);
    }

    /**
     * Unidades destino netas por unidad origen (factor menos merma).
     */
    public function effectiveFactor(): float
    {
        return (float) $this->conversion_factor * (1 - (float) $this->waste_percentage / 100);
    }

    /**
     * Plan de produccion para una cantidad pedida del origen.
     *
     * Sin piezas enteras: sale todo lo pedido y producido = pedido * factor
     * neto (4 decimales).
     *
     * Con piezas enteras (whole_units):
     *   producido = piso(pedido * factor * (1 - merma))
     *   consumido = techo_4dec(producido / (factor * (1 - merma)))
     *   sobrante  = pedido - consumido (se queda en el granel, no es merma)
     * La merma sigue en unidades destino: consumido * factor * merma.
     *
     * @return array{requested: float, consumed: float, produced: float, waste: float, leftover: float}
     *
     * @throws \InvalidArgumentException si el factor neto es 0 o no alcanza para una pieza
     */
    public function planProduction(float $requestedQuantity): array
    {
        $effective = $this->effectiveFactor();
        if ($effective <= 0) {
            throw new \InvalidArgumentException('La conversion no produce nada: revisa el factor y la merma.');
        }

        $consumed = $requestedQuantity;
        // round(.., 6) evita que 3920.0000000001 o 3919.9999999 cambien el piso
        $produced = round($requestedQuantity * $effective, 6);

        if ($this->whole_units) {
            $produced = floor($produced);
            if ($produced < 1) {
                throw new \InvalidArgumentException('La cantidad no alcanza para producir una pieza completa.');
            }
            $consumed = ceil(round($produced / $effective * 10000, 6)) / 10000;
            $consumed = min($consumed, $requestedQuantity);
        } else {
            $produced = round($produced, 4);
        }

        $waste = round($consumed * (float) $this->conversion_factor * ((float) $this->waste_percentage / 100), 4);

        return [
            'requested' => round($requestedQuantity, 4),
            'consumed' => round($consumed, 4),
            'produced' => $produced,
            'waste' => $waste,
            'leftover' => round($requestedQuantity - $consumed, 4),
        ];
    }

    protected static function newFactory()
    {
        return \Modules\Inventory\Database\Factories\ProductConversionFactory::new();
    }
}
