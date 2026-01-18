<?php

namespace Modules\Billing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Billing\Database\Factories\CompanySettingFactory;

class CompanySetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_name',
        'rfc',
        'tax_regime',
        'postal_code',
        'invoice_series',
        'credit_note_series',
        'next_invoice_folio',
        'next_credit_note_folio',
        'pac_provider',
        'pac_username',
        'pac_password',
        'pac_production_mode',
        'certificate_file',
        'key_file',
        'key_password',
        'logo_path',
        'address',
        'city',
        'state',
        'phone',
        'email',
        'website',
        'bank_accounts',
        'commercial_conditions',
        'quote_settings',
        'additional_settings',
        'is_active',
    ];

    protected $casts = [
        'next_invoice_folio' => 'integer',
        'next_credit_note_folio' => 'integer',
        'pac_production_mode' => 'boolean',
        'is_active' => 'boolean',
        'bank_accounts' => 'array',
        'commercial_conditions' => 'array',
        'quote_settings' => 'array',
        'additional_settings' => 'array',
        'pac_password' => 'encrypted',
        'key_password' => 'encrypted',
    ];

    protected $hidden = [
        'pac_password',
        'key_password',
    ];

    /**
     * Get the active company setting.
     */
    public static function getActive(): ?self
    {
        return self::where('is_active', true)->first();
    }

    /**
     * Set this setting as active and deactivate others.
     */
    public function activate(): void
    {
        // Deactivate all other settings
        self::where('id', '!=', $this->id)->update(['is_active' => false]);

        // Activate this setting
        $this->update(['is_active' => true]);
    }

    /**
     * Get next invoice folio and increment.
     */
    public function getNextInvoiceFolio(): string
    {
        $folio = $this->invoice_series . str_pad($this->next_invoice_folio, 6, '0', STR_PAD_LEFT);
        $this->increment('next_invoice_folio');
        return $folio;
    }

    /**
     * Get next credit note folio and increment.
     */
    public function getNextCreditNoteFolio(): string
    {
        $folio = $this->credit_note_series . str_pad($this->next_credit_note_folio, 6, '0', STR_PAD_LEFT);
        $this->increment('next_credit_note_folio');
        return $folio;
    }

    /**
     * Check if PAC is configured.
     */
    public function hasPACConfigured(): bool
    {
        return !empty($this->pac_provider)
            && !empty($this->pac_username)
            && !empty($this->pac_password);
    }

    /**
     * Check if certificates are configured.
     */
    public function hasCertificatesConfigured(): bool
    {
        return !empty($this->certificate_file)
            && !empty($this->key_file)
            && !empty($this->key_password);
    }

    /**
     * Check if ready for CFDI generation.
     */
    public function isReadyForCFDI(): bool
    {
        return $this->hasPACConfigured() && $this->hasCertificatesConfigured();
    }

    /**
     * Scope to get only active settings.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get settings by RFC.
     */
    public function scopeByRFC($query, string $rfc)
    {
        return $query->where('rfc', $rfc);
    }

    /**
     * Get bank accounts for a specific currency.
     *
     * @param string|null $currency Filter by currency (MXN, USD, etc.)
     * @return array
     */
    public function getBankAccounts(?string $currency = null): array
    {
        $accounts = $this->bank_accounts ?? [];

        if ($currency) {
            return array_filter($accounts, fn($account) =>
                ($account['currency'] ?? 'MXN') === $currency
            );
        }

        return $accounts;
    }

    /**
     * Get commercial conditions.
     *
     * @return array
     */
    public function getCommercialConditions(): array
    {
        return $this->commercial_conditions ?? $this->getDefaultCommercialConditions();
    }

    /**
     * Get default commercial conditions.
     *
     * @return array
     */
    public function getDefaultCommercialConditions(): array
    {
        return [
            'Precios expresados en moneda nacional (MXN) mas IVA.',
            'Tiempo de entrega sujeto a disponibilidad de inventario.',
            'Cotizacion valida por 30 dias a partir de la fecha de emision.',
            'Forma de pago: 50% anticipo, 50% contra entrega.',
            'Los precios pueden variar sin previo aviso despues de la vigencia.',
        ];
    }

    /**
     * Get quote settings with defaults.
     *
     * @return array
     */
    public function getQuoteSettings(): array
    {
        return array_merge([
            'default_validity_days' => 30,
            'default_currency' => 'MXN',
            'show_product_images' => true,
            'show_bank_accounts' => true,
            'show_commercial_conditions' => true,
        ], $this->quote_settings ?? []);
    }

    /**
     * Get company full address.
     *
     * @return string
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address,
            $this->city,
            $this->state,
            $this->postal_code,
        ]);

        return implode(', ', $parts);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return CompanySettingFactory::new();
    }
}
