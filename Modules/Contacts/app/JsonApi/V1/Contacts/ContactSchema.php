<?php

namespace Modules\Contacts\JsonApi\V1\Contacts;

use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Boolean;
use LaravelJsonApi\Eloquent\Fields\ArrayHash;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Relations\HasMany;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Filters\Scope;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;
use Modules\Contacts\Models\Contact;

class ContactSchema extends Schema
{
    public static string $model = Contact::class;

    public function fields(): array
    {
        return [
            ID::make(),
            
            Str::make('contactType', 'contact_type')->sortable(),
            Str::make('name')->sortable(),
            Str::make('legalName', 'legal_name')->sortable(),
            Str::make('taxId', 'tax_id')->sortable(),
            Str::make('email')->sortable(),
            Str::make('phone')->sortable(),
            Str::make('website')->sortable(),
            Str::make('status')->sortable(),
            Boolean::make('isCustomer', 'is_customer')->sortable(),
            Boolean::make('isSupplier', 'is_supplier')->sortable(),
            Number::make('creditLimit', 'credit_limit')->sortable(),
            Number::make('currentCredit', 'current_credit')->sortable(),
            Str::make('classification')->sortable(),
            Number::make('paymentTerms', 'payment_terms')->sortable(),
            Str::make('notes'),
            ArrayHash::make('metadata'),

            // WS5 Commissions
            Number::make('defaultSalespersonId', 'default_salesperson_id'),
            Number::make('collectionsAgentId', 'collections_agent_id'),
            Number::make('commissionPctOverride', 'commission_pct_override'),

            // WS7.1 Bind fields
            Str::make('regimenFiscal', 'regimen_fiscal'),
            Str::make('usoCfdi', 'uso_cfdi'),
            Number::make('creditMonths', 'credit_months'),
            Str::make('bankAccountNumber', 'bank_account_number'),
            Str::make('referralSource', 'referral_source'),
            Str::make('cuentaContable', 'cuenta_contable'),
            Number::make('discountPct', 'discount_pct'),


            // Timestamps
            DateTime::make('createdAt')->sortable()->readOnly(),
            DateTime::make('updatedAt')->sortable()->readOnly(),

            // Relationships
            HasMany::make('contactDocuments'),
            // Relationships
            HasMany::make('contactAddresses'),
            // Relationships
            HasMany::make('contactPeople'),
        ];
    }

    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            \LaravelJsonApi\Eloquent\Filters\Where::make('contactType', 'contact_type'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('name'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('legalName', 'legal_name'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('taxId', 'tax_id'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('email'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('phone'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('website'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('status'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('isCustomer', 'is_customer')->asBoolean(),
            \LaravelJsonApi\Eloquent\Filters\Where::make('isSupplier', 'is_supplier')->asBoolean(),
            \LaravelJsonApi\Eloquent\Filters\Where::make('classification'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('paymentTerms', 'payment_terms'),
            Scope::make('isProspect', 'prospects')->asBoolean(),
        ];
    }


    public function includePaths(): array
    {
        return [
            'contactDocuments',
            'contactAddresses',
            'contactPeople',
        ];
    }

    public function pagination(): ?Paginator
    {
        return PagePagination::make();
    }

    public static function type(): string
    {
        return "contacts";
    }
}