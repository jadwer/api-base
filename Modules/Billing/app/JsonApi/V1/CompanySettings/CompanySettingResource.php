<?php

namespace Modules\Billing\JsonApi\V1\CompanySettings;

use Illuminate\Http\Request;
use LaravelJsonApi\Core\Resources\JsonApiResource;

class CompanySettingResource extends JsonApiResource
{
    public function attributes($request): iterable
    {
        return [
            // Company Fiscal Information
            'companyName' => $this->resource->company_name,
            'rfc' => $this->resource->rfc,
            'taxRegime' => $this->resource->tax_regime,
            'postalCode' => $this->resource->postal_code,

            // Invoice Series & Folios
            'invoiceSeries' => $this->resource->invoice_series,
            'creditNoteSeries' => $this->resource->credit_note_series,
            'nextInvoiceFolio' => $this->resource->next_invoice_folio,
            'nextCreditNoteFolio' => $this->resource->next_credit_note_folio,

            // PAC Configuration
            'pacProvider' => $this->resource->pac_provider,
            'pacUsername' => $this->resource->pac_username,
            // pac_password is intentionally excluded (encrypted, sensitive)
            'pacProductionMode' => $this->resource->pac_production_mode,

            // Digital Certificate
            'certificateFile' => $this->resource->certificate_file,
            'keyFile' => $this->resource->key_file,
            // key_password is intentionally excluded (encrypted, sensitive)

            // Additional Settings
            'logoPath' => $this->resource->logo_path,

            // Contact Information
            'address' => $this->resource->address,
            'city' => $this->resource->city,
            'state' => $this->resource->state,
            'phone' => $this->resource->phone,
            'email' => $this->resource->email,
            'website' => $this->resource->website,

            // Commercial Settings
            'bankAccounts' => $this->resource->bank_accounts,
            'commercialConditions' => $this->resource->commercial_conditions,
            'quoteSettings' => $this->resource->quote_settings,
            'additionalSettings' => $this->resource->additional_settings,

            // Status
            'isActive' => $this->resource->is_active,

            // Timestamps
            'createdAt' => $this->resource->created_at,
            'updatedAt' => $this->resource->updated_at,
        ];
    }

    public function relationships($request): iterable
    {
        return [
            // No relationships for now
        ];
    }
}
