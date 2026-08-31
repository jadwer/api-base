<?php

namespace Modules\Contacts\JsonApi\V1\Contacts;

use LaravelJsonApi\Core\Resources\JsonApiResource;

class ContactResource extends JsonApiResource
{
    public function attributes($request): iterable
    {
        return [
            // Este Resource manual PISA al Schema (gotcha conocido): todo
            // campo del Schema DEBE estar aqui o el API guarda pero nunca
            // lo devuelve. Barrido completo 2026-08-31: los campos WS5/WS7
            // (comerciales y fiscales) faltaban y la edicion los mostraba
            // vacios aunque estuvieran guardados.
            'contactType' => $this->contact_type,
            'name' => $this->name,
            'legalName' => $this->legal_name,
            'taxId' => $this->tax_id,
            'email' => $this->email,
            'phone' => $this->phone,
            'phoneExtension' => $this->phone_extension,
            'website' => $this->website,
            'status' => $this->status,
            'isCustomer' => $this->is_customer,
            'isSupplier' => $this->is_supplier,
            'creditLimit' => $this->credit_limit,
            'currentCredit' => $this->current_credit,
            'classification' => $this->classification,
            'paymentTerms' => $this->payment_terms,
            'notes' => $this->notes,
            'metadata' => $this->metadata,
            // WS5 Comisiones
            'defaultSalespersonId' => $this->default_salesperson_id,
            'collectionsAgentId' => $this->collections_agent_id,
            'commissionPctOverride' => $this->commission_pct_override,
            // WS7.1 comerciales/fiscales (Bind)
            'regimenFiscal' => $this->regimen_fiscal,
            'usoCfdi' => $this->uso_cfdi,
            'creditMonths' => $this->credit_months,
            'bankAccountNumber' => $this->bank_account_number,
            'referralSource' => $this->referral_source,
            'cuentaContable' => $this->cuenta_contable,
            'discountPct' => $this->discount_pct,
            // Acceso al portal (computed: existe un User con este email)
            'hasPortalUser' => $this->has_portal_user,
            "createdAt" => $this->created_at,
            "updatedAt" => $this->updated_at,
        ];
    }

    public function relationships($request): iterable
    {
        return [
            'contactDocument' => $this->relation('contactDocument'),
            'contactDocuments' => $this->relation('contactDocuments'),
            'contactAddress' => $this->relation('contactAddress'),
            'contactAddresses' => $this->relation('contactAddresses'),
            'contactPerson' => $this->relation('contactPerson'),
            'contactPeople' => $this->relation('contactPeople'),
        ];
    }
}
