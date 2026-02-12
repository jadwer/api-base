<?php

namespace Modules\Billing\JsonApi\V1\CompanySettings;

use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\ArrayHash;
use LaravelJsonApi\Eloquent\Fields\ArrayList;
use LaravelJsonApi\Eloquent\Fields\Boolean;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;
use Modules\Billing\Models\CompanySetting;

class CompanySettingSchema extends Schema
{
    public static string $model = CompanySetting::class;

    protected int $maxDepth = 3;

    /**
     * MANDATORY: Define ALL resource fields.
     */
    public function fields(): array
    {
        return [
            ID::make(),

            // Company Fiscal Information
            Str::make('companyName', 'company_name')->sortable(),
            Str::make('rfc')->sortable(),
            Str::make('taxRegime', 'tax_regime')->sortable(),
            Str::make('postalCode', 'postal_code'),

            // Invoice Series & Folios
            Str::make('invoiceSeries', 'invoice_series'),
            Str::make('creditNoteSeries', 'credit_note_series'),
            Number::make('nextInvoiceFolio', 'next_invoice_folio'),
            Number::make('nextCreditNoteFolio', 'next_credit_note_folio'),

            // PAC Configuration
            Str::make('pacProvider', 'pac_provider'),
            Str::make('pacUsername', 'pac_username'),
            Str::make('pacPassword', 'pac_password')->hidden(), // Write-only (encrypted, never exposed)
            Boolean::make('pacProductionMode', 'pac_production_mode'),

            // Digital Certificate
            Str::make('certificateFile', 'certificate_file'),
            Str::make('keyFile', 'key_file'),
            Str::make('keyPassword', 'key_password')->hidden(), // Write-only (encrypted, never exposed)

            // Additional Settings
            Str::make('logoPath', 'logo_path'),

            // Contact Information
            Str::make('address'),
            Str::make('city'),
            Str::make('state'),
            Str::make('phone'),
            Str::make('email'),
            Str::make('website'),

            // Commercial Settings
            ArrayList::make('bankAccounts', 'bank_accounts'),
            ArrayList::make('commercialConditions', 'commercial_conditions'),
            ArrayHash::make('quoteSettings', 'quote_settings'),
            ArrayHash::make('additionalSettings', 'additional_settings'),

            // Status
            Boolean::make('isActive', 'is_active')->sortable(),

            // Timestamps
            DateTime::make('createdAt', 'created_at')->sortable()->readOnly(),
            DateTime::make('updatedAt', 'updated_at')->sortable()->readOnly(),
        ];
    }

    /**
     * MANDATORY: Define filters.
     */
    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            Where::make('rfc'),
            Where::make('taxRegime', 'tax_regime'),
            Where::make('isActive', 'is_active')->asBoolean(),
            Where::make('pacProvider', 'pac_provider'),
        ];
    }

    /**
     * MANDATORY: Define pagination.
     */
    public function pagination(): ?Paginator
    {
        return PagePagination::make();
    }
}
