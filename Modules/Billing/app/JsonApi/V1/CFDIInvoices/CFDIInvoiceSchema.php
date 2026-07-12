<?php

namespace Modules\Billing\JsonApi\V1\CFDIInvoices;

use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\ArrayHash;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Relations\HasMany;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\Scope;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Modules\Billing\Models\CFDIInvoice;
use Modules\Contacts\Models\Contact;

class CFDIInvoiceSchema extends Schema
{
    /**
     * The model the schema corresponds to.
     *
     * @var string
     */
    public static string $model = CFDIInvoice::class;

    /**
     * Get the resource fields.
     *
     * @return array
     */
    public function fields(): array
    {
        return [
            ID::make(),

            // Foreign keys (for creation/updates)
            Number::make('companySettingId', 'company_setting_id'),
            Number::make('contactId', 'contact_id'),
            Number::make('arInvoiceId', 'ar_invoice_id'),

            // Relations
            BelongsTo::make('companySetting')->type('company-settings'),
            BelongsTo::make('contact')->type('contacts'),
            BelongsTo::make('arInvoice')->type('ar-invoices'),
            HasMany::make('items')->type('cfdi-items')->readOnly(),

            // CFDI Identification
            Str::make('series')->sortable(),
            Number::make('folio')->sortable(),
            Str::make('uuid')->sortable(),
            Str::make('tipoComprobante', 'tipo_comprobante')->sortable(),

            // Customer Information
            Str::make('receptorRfc', 'receptor_rfc')->sortable(),
            Str::make('receptorNombre', 'receptor_nombre')->sortable(),
            Str::make('receptorUsoCfdi', 'receptor_uso_cfdi'),
            Str::make('receptorRegimenFiscal', 'receptor_regimen_fiscal'),
            Str::make('receptorDomicilioFiscal', 'receptor_domicilio_fiscal'),

            // Amounts (in cents)
            Number::make('subtotal')->sortable(),
            Number::make('total')->sortable(),
            Number::make('descuento'),
            Number::make('iva'),
            Number::make('ieps'),
            Number::make('isrRetenido', 'isr_retenido'),
            Number::make('ivaRetenido', 'iva_retenido'),

            // Currency
            Str::make('moneda')->sortable(),
            Number::make('tipoCambio', 'tipo_cambio'),

            // Payment Information
            Str::make('formaPago', 'forma_pago'),
            Str::make('metodoPago', 'metodo_pago')->sortable(),
            Str::make('condicionesPago', 'condiciones_pago'),

            // Payment complement (REP, type P): the collection this document settles
            DateTime::make('fechaPago', 'fecha_pago'),
            Number::make('montoPago', 'monto_pago'),
            Str::make('formaPagoP', 'forma_pago_p'),
            Number::make('arPaymentId', 'ar_payment_id'),
            Number::make('numParcialidad')->extractUsing(
                static fn ($model) => optional($model->paymentDocs->first())->num_parcialidad
            )->readOnly(),
            Number::make('impSaldoInsoluto')->extractUsing(
                static fn ($model) => optional($model->paymentDocs->first())->imp_saldo_insoluto
            )->readOnly(),

            // Related CFDI
            Str::make('cfdiRelacionadoTipo', 'cfdi_relacionado_tipo'),
            ArrayHash::make('cfdiRelacionadoUuids', 'cfdi_relacionado_uuids'),

            // Status
            Str::make('status')->sortable(),

            // Dates
            DateTime::make('fechaEmision', 'fecha_emision')->sortable(),
            DateTime::make('fechaTimbrado', 'fecha_timbrado')->sortable(),
            DateTime::make('fechaCancelacion', 'fecha_cancelacion')->sortable(),

            // Files
            Str::make('xmlPath', 'xml_path'),
            Str::make('pdfPath', 'pdf_path'),

            // PAC Response (excluded from responses for security)
            // Str::make('pacResponse', 'pac_response'),
            Str::make('errorMessage', 'error_message'),

            // Metadata
            ArrayHash::make('metadata'),

            DateTime::make('createdAt')->sortable()->readOnly(),
            DateTime::make('updatedAt')->sortable()->readOnly(),
        ];
    }

    /**
     * Get the resource filters.
     *
     * @return array
     */
    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            Where::make('series'),
            Where::make('folio'),
            Where::make('uuid'),
            Where::make('tipoComprobante', 'tipo_comprobante'),
            Where::make('receptorRfc', 'receptor_rfc'),
            Where::make('status'),
            Where::make('metodoPago', 'metodo_pago'),
            Where::make('companySettingId', 'company_setting_id'),
            Where::make('contactId', 'contact_id'),
            Where::make('arInvoiceId', 'ar_invoice_id'),
            Scope::make('search', 'forSearch'),
            Scope::make('dateFrom', 'dateFrom'),
            Scope::make('dateTo', 'dateTo'),
        ];
    }

    /**
     * Get the resource include paths.
     *
     * @return iterable
     */
    public function includePaths(): iterable
    {
        return [
            'companySetting',
            'contact',
            'arInvoice',
            'items',
        ];
    }

    /**
     * Scope index queries: admins see all, customers see own invoices only.
     */
    public function indexQuery(?Request $request, Builder $query): Builder
    {
        if (!$request || !$request->user('sanctum')) {
            return $query->whereRaw('1 = 0');
        }

        $user = $request->user('sanctum');

        if ($user->hasAnyRole(['god', 'admin', 'administrator', 'tech'])) {
            return $query;
        }

        // Customers only see their own invoices (via contact email match)
        $contact = Contact::where('email', $user->email)->first();
        if ($contact) {
            return $query->where('contact_id', $contact->id);
        }

        return $query->whereRaw('1 = 0');
    }

    /**
     * Get the resource paginator.
     *
     * @return Paginator|null
     */
    public function pagination(): ?Paginator
    {
        return PagePagination::make();
    }

    /**
     * Get the JSON:API resource type.
     *
     * @return string
     */
    public static function type(): string
    {
        return 'cfdi-invoices';
    }
}
