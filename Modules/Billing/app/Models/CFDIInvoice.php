<?php

namespace Modules\Billing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Billing\Database\Factories\CFDIInvoiceFactory;
use Modules\Finance\Models\ARInvoice;
use Modules\Contacts\Models\Contact;

class CFDIInvoice extends Model
{
    use HasFactory;

    protected $table = 'cfdi_invoices';

    protected $fillable = [
        'company_setting_id',
        'contact_id',
        'ar_invoice_id',
        'series',
        'folio',
        'uuid',
        'tipo_comprobante',
        'receptor_rfc',
        'receptor_nombre',
        'receptor_uso_cfdi',
        'receptor_regimen_fiscal',
        'receptor_domicilio_fiscal',
        'subtotal',
        'total',
        'descuento',
        'iva',
        'ieps',
        'isr_retenido',
        'iva_retenido',
        'moneda',
        'tipo_cambio',
        'forma_pago',
        'metodo_pago',
        'condiciones_pago',
        'cfdi_relacionado_tipo',
        'cfdi_relacionado_uuids',
        'status',
        'fecha_emision',
        'fecha_timbrado',
        'fecha_cancelacion',
        'xml_path',
        'pdf_path',
        'pac_response',
        'error_message',
        'metadata',
    ];

    protected $casts = [
        'subtotal' => 'integer',
        'total' => 'integer',
        'descuento' => 'integer',
        'iva' => 'integer',
        'ieps' => 'integer',
        'isr_retenido' => 'integer',
        'iva_retenido' => 'integer',
        'tipo_cambio' => 'decimal:6',
        'cfdi_relacionado_uuids' => 'array',
        'metadata' => 'array',
        'fecha_emision' => 'datetime',
        'fecha_timbrado' => 'datetime',
        'fecha_cancelacion' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function companySetting(): BelongsTo
    {
        return $this->belongsTo(CompanySetting::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function arInvoice(): BelongsTo
    {
        return $this->belongsTo(ARInvoice::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CFDIItem::class);
    }

    /**
     * Scopes
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeValid($query)
    {
        return $query->where('status', 'valid');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeBySeries($query, string $series)
    {
        return $query->where('series', $series);
    }

    public function scopeByCustomer($query, int $contactId)
    {
        return $query->where('contact_id', $contactId);
    }

    /**
     * Accessors - Convert cents to decimal amounts
     */
    public function getSubtotalAmountAttribute(): float
    {
        return $this->subtotal / 100;
    }

    public function getTotalAmountAttribute(): float
    {
        return $this->total / 100;
    }

    public function getDescuentoAmountAttribute(): float
    {
        return $this->descuento / 100;
    }

    public function getIvaAmountAttribute(): float
    {
        return $this->iva / 100;
    }

    /**
     * Helper Methods
     */
    public function getFolioCompleto(): string
    {
        return "{$this->series}-{$this->folio}";
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isValid(): bool
    {
        return $this->status === 'valid';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isTimbrado(): bool
    {
        return !empty($this->uuid) && !empty($this->fecha_timbrado);
    }

    public function canBeCancelled(): bool
    {
        return $this->isValid() && $this->isTimbrado();
    }

    public function isIngreso(): bool
    {
        return $this->tipo_comprobante === 'I';
    }

    public function isEgreso(): bool
    {
        return $this->tipo_comprobante === 'E';
    }

    public function hasRelatedCFDI(): bool
    {
        return !empty($this->cfdi_relacionado_uuids);
    }

    /**
     * Factory
     */
    protected static function newFactory()
    {
        return CFDIInvoiceFactory::new();
    }
}
