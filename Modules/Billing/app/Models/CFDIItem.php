<?php

namespace Modules\Billing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Billing\Database\Factories\CFDIItemFactory;
use Modules\Product\Models\Product;

class CFDIItem extends Model
{
    use HasFactory;

    protected $table = 'cfdi_items';

    protected $fillable = [
        'cfdi_invoice_id',
        'product_id',
        'numero_linea',
        'clave_prod_serv',
        'clave_unidad',
        'unidad',
        'cantidad',
        'descripcion',
        'no_identificacion',
        'valor_unitario',
        'importe',
        'descuento',
        'impuestos',
        'objeto_imp',
        'numero_pedimento',
        'cuenta_predial',
        'informacion_aduanera',
        'metadata',
    ];

    protected $casts = [
        'cantidad' => 'decimal:6',
        'valor_unitario' => 'integer',
        'importe' => 'integer',
        'descuento' => 'integer',
        'impuestos' => 'array',
        'cuenta_predial' => 'array',
        'informacion_aduanera' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Relationships
     */
    public function cfdiInvoice(): BelongsTo
    {
        return $this->belongsTo(CFDIInvoice::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Scopes
     */
    public function scopeByInvoice($query, int $invoiceId)
    {
        return $query->where('cfdi_invoice_id', $invoiceId);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('numero_linea');
    }

    /**
     * Accessors - Convert cents to decimal amounts
     */
    public function getValorUnitarioAmountAttribute(): float
    {
        return $this->valor_unitario / 100;
    }

    public function getImporteAmountAttribute(): float
    {
        return $this->importe / 100;
    }

    public function getDescuentoAmountAttribute(): float
    {
        return $this->descuento / 100;
    }

    /**
     * Helper Methods
     */
    public function getImporteConDescuento(): int
    {
        return $this->importe - $this->descuento;
    }

    public function hasDiscount(): bool
    {
        return $this->descuento > 0;
    }

    public function hasTaxes(): bool
    {
        return !empty($this->impuestos);
    }

    public function isObjectOfTax(): bool
    {
        return in_array($this->objeto_imp, ['02', '03']);
    }

    public function isExemptFromTax(): bool
    {
        return $this->objeto_imp === '04';
    }

    public function isNotObjectOfTax(): bool
    {
        return $this->objeto_imp === '01';
    }

    public function getTotalIVA(): int
    {
        if (empty($this->impuestos['traslados'])) {
            return 0;
        }

        $iva = 0;
        foreach ($this->impuestos['traslados'] as $traslado) {
            if (isset($traslado['impuesto']) && $traslado['impuesto'] === '002') { // 002 = IVA
                $iva += (int) ($traslado['importe'] ?? 0);
            }
        }

        return $iva;
    }

    /**
     * Factory
     */
    protected static function newFactory()
    {
        return CFDIItemFactory::new();
    }
}
