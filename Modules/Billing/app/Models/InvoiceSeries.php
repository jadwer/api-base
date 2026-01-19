<?php

namespace Modules\Billing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * SA-M011: Invoice Series Model
 *
 * Supports multiple invoice series (FAC, FAC-W, REFAC, N) with configurable
 * folio numbering, yearly reset, and role-based restrictions.
 *
 * @property int $id
 * @property int $company_setting_id
 * @property string $code
 * @property string $name
 * @property string|null $description
 * @property string $cfdi_type
 * @property int $current_folio
 * @property int $initial_folio
 * @property int $folio_padding
 * @property bool $include_year
 * @property string $year_format
 * @property string $separator
 * @property bool $is_active
 * @property bool $is_default
 * @property bool $reset_yearly
 * @property int|null $last_reset_year
 * @property string|null $allowed_roles
 * @property string|null $source_type
 */
class InvoiceSeries extends Model
{
    use HasFactory;

    protected $table = 'invoice_series';

    protected $fillable = [
        'company_setting_id',
        'code',
        'name',
        'description',
        'cfdi_type',
        'current_folio',
        'initial_folio',
        'folio_padding',
        'include_year',
        'year_format',
        'separator',
        'is_active',
        'is_default',
        'reset_yearly',
        'last_reset_year',
        'allowed_roles',
        'source_type',
    ];

    protected $casts = [
        'current_folio' => 'integer',
        'initial_folio' => 'integer',
        'folio_padding' => 'integer',
        'include_year' => 'boolean',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'reset_yearly' => 'boolean',
        'last_reset_year' => 'integer',
    ];

    /**
     * CFDI type labels
     */
    public const CFDI_TYPES = [
        'I' => 'Ingreso',
        'E' => 'Egreso',
        'P' => 'Pago',
        'N' => 'Nomina',
        'T' => 'Traslado',
    ];

    /**
     * Default series configurations
     */
    public const DEFAULT_SERIES = [
        [
            'code' => 'FAC',
            'name' => 'Factura Normal',
            'description' => 'Serie para facturacion general',
            'cfdi_type' => 'I',
            'is_default' => true,
        ],
        [
            'code' => 'FAC-W',
            'name' => 'Factura Web',
            'description' => 'Serie para ventas en linea',
            'cfdi_type' => 'I',
            'source_type' => 'web',
        ],
        [
            'code' => 'REFAC',
            'name' => 'Refacturacion',
            'description' => 'Serie para facturas regeneradas por cancelacion',
            'cfdi_type' => 'I',
        ],
        [
            'code' => 'N',
            'name' => 'Nota de Credito',
            'description' => 'Serie para notas de credito',
            'cfdi_type' => 'E',
            'is_default' => true,
        ],
    ];

    // ==================
    // Relationships
    // ==================

    public function companySetting(): BelongsTo
    {
        return $this->belongsTo(CompanySetting::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(CFDIInvoice::class, 'series', 'code');
    }

    // ==================
    // Scopes
    // ==================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForCfdiType($query, string $type)
    {
        return $query->where('cfdi_type', $type);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeForSource($query, ?string $source)
    {
        return $query->where(function ($q) use ($source) {
            $q->whereNull('source_type');
            if ($source) {
                $q->orWhere('source_type', $source);
            }
        });
    }

    // ==================
    // Folio Management
    // ==================

    /**
     * Get next folio and increment atomically.
     *
     * @return string The formatted folio (e.g., "FAC-26-000001")
     */
    public function getNextFolio(): string
    {
        return DB::transaction(function () {
            // Check for yearly reset
            $this->checkYearlyReset();

            // Lock row and increment
            $series = self::lockForUpdate()->find($this->id);
            $series->increment('current_folio');

            // Format folio
            return $this->formatFolio($series->current_folio);
        });
    }

    /**
     * Preview next folio without incrementing.
     *
     * @return string The formatted folio
     */
    public function previewNextFolio(): string
    {
        $this->checkYearlyReset();
        return $this->formatFolio($this->current_folio + 1);
    }

    /**
     * Format folio number according to series configuration.
     *
     * @param int $number
     * @return string
     */
    public function formatFolio(int $number): string
    {
        $parts = [$this->code];

        if ($this->include_year) {
            $parts[] = now()->format($this->year_format);
        }

        $parts[] = str_pad($number, $this->folio_padding, '0', STR_PAD_LEFT);

        return implode($this->separator, $parts);
    }

    /**
     * Check and perform yearly reset if needed.
     */
    protected function checkYearlyReset(): void
    {
        if (!$this->reset_yearly) {
            return;
        }

        $currentYear = (int)now()->format('Y');

        if ($this->last_reset_year !== $currentYear) {
            $this->update([
                'current_folio' => $this->initial_folio - 1,
                'last_reset_year' => $currentYear,
            ]);
            $this->refresh();
        }
    }

    /**
     * Set initial folio for the series.
     *
     * @param int $folio
     * @return void
     */
    public function setInitialFolio(int $folio): void
    {
        $this->update([
            'initial_folio' => $folio,
            'current_folio' => $folio - 1,
        ]);
    }

    // ==================
    // Access Control
    // ==================

    /**
     * Check if a role can use this series.
     *
     * @param string $role
     * @return bool
     */
    public function canBeUsedByRole(string $role): bool
    {
        if (empty($this->allowed_roles)) {
            return true;
        }

        $allowedRoles = json_decode($this->allowed_roles, true) ?? [];
        return in_array($role, $allowedRoles) || in_array('*', $allowedRoles);
    }

    /**
     * Check if series can be used for a specific source.
     *
     * @param string|null $source
     * @return bool
     */
    public function canBeUsedForSource(?string $source): bool
    {
        if (empty($this->source_type)) {
            return true;
        }

        return $this->source_type === $source;
    }

    // ==================
    // Static Helpers
    // ==================

    /**
     * Get the default series for a CFDI type.
     *
     * @param string $cfdiType
     * @param int|null $companySettingId
     * @return self|null
     */
    public static function getDefault(string $cfdiType, ?int $companySettingId = null): ?self
    {
        $query = self::active()
            ->forCfdiType($cfdiType)
            ->where('is_default', true);

        if ($companySettingId) {
            $query->where('company_setting_id', $companySettingId);
        } else {
            $settings = CompanySetting::getActive();
            if ($settings) {
                $query->where('company_setting_id', $settings->id);
            }
        }

        return $query->first();
    }

    /**
     * Find series by code.
     *
     * @param string $code
     * @param int|null $companySettingId
     * @return self|null
     */
    public static function findByCode(string $code, ?int $companySettingId = null): ?self
    {
        $query = self::where('code', $code);

        if ($companySettingId) {
            $query->where('company_setting_id', $companySettingId);
        } else {
            $settings = CompanySetting::getActive();
            if ($settings) {
                $query->where('company_setting_id', $settings->id);
            }
        }

        return $query->first();
    }

    /**
     * Get all available series for a CFDI type.
     *
     * @param string $cfdiType
     * @param string|null $source
     * @param int|null $companySettingId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getAvailable(string $cfdiType, ?string $source = null, ?int $companySettingId = null)
    {
        $query = self::active()
            ->forCfdiType($cfdiType)
            ->forSource($source);

        if ($companySettingId) {
            $query->where('company_setting_id', $companySettingId);
        } else {
            $settings = CompanySetting::getActive();
            if ($settings) {
                $query->where('company_setting_id', $settings->id);
            }
        }

        return $query->orderBy('is_default', 'desc')
            ->orderBy('code')
            ->get();
    }

    /**
     * Initialize default series for a company setting.
     *
     * @param CompanySetting $setting
     * @return void
     */
    public static function initializeDefaults(CompanySetting $setting): void
    {
        foreach (self::DEFAULT_SERIES as $seriesData) {
            self::firstOrCreate(
                [
                    'company_setting_id' => $setting->id,
                    'code' => $seriesData['code'],
                ],
                array_merge($seriesData, [
                    'company_setting_id' => $setting->id,
                ])
            );
        }
    }

    // ==================
    // Attributes
    // ==================

    /**
     * Get CFDI type label.
     */
    public function getCfdiTypeLabelAttribute(): string
    {
        return self::CFDI_TYPES[$this->cfdi_type] ?? $this->cfdi_type;
    }

    /**
     * Get allowed roles as array.
     */
    public function getAllowedRolesArrayAttribute(): array
    {
        if (empty($this->allowed_roles)) {
            return [];
        }
        return json_decode($this->allowed_roles, true) ?? [];
    }
}
