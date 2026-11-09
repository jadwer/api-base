<?php

namespace Modules\Billing\JsonApi\V1\CFDIItems;

use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\ArrayHash;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;
use Modules\Billing\Models\CFDIItem;

class CFDIItemSchema extends Schema
{
    /**
     * The model the schema corresponds to.
     */
    public static string $model = CFDIItem::class;

    /**
     * Get the resource fields.
     */
    public function fields(): array
    {
        return [
            ID::make(),

            // Foreign keys (for creation/updates)
            Number::make('cfdiInvoiceId', 'cfdi_invoice_id'),
            Number::make('productId', 'product_id'),

            // Relations
            BelongsTo::make('cfdiInvoice')->type('cfdi-invoices'),
            BelongsTo::make('product')->type('products'),

            // Item Information
            Number::make('numeroLinea', 'numero_linea')->sortable(),
            Str::make('claveProdServ', 'clave_prod_serv'),
            Str::make('claveUnidad', 'clave_unidad'),
            Str::make('unidad'),
            Number::make('cantidad'),
            Str::make('descripcion'),
            Str::make('noIdentificacion', 'no_identificacion'),

            // Prices (in cents)
            Number::make('valorUnitario', 'valor_unitario'),
            Number::make('importe')->sortable(),
            Number::make('descuento'),

            // Taxes
            ArrayHash::make('impuestos'),
            Str::make('objetoImp', 'objeto_imp'),

            // Optional fields
            Str::make('numeroPedimento', 'numero_pedimento'),
            ArrayHash::make('cuentaPredial', 'cuenta_predial'),
            ArrayHash::make('informacionAduanera', 'informacion_aduanera'),

            // Metadata
            ArrayHash::make('metadata'),

            DateTime::make('createdAt')->sortable()->readOnly(),
            DateTime::make('updatedAt')->sortable()->readOnly(),
        ];
    }

    /**
     * Get the resource filters.
     */
    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            Where::make('cfdiInvoiceId', 'cfdi_invoice_id'),
            Where::make('productId', 'product_id'),
            Where::make('numeroLinea', 'numero_linea'),
            Where::make('claveProdServ', 'clave_prod_serv'),
            Where::make('objetoImp', 'objeto_imp'),
        ];
    }

    /**
     * Mismo alcance que CFDIInvoiceSchema::indexQuery: el personal con el permiso ve
     * todo; el cliente del portal solo las partidas de SUS CFDI (antes el listado
     * de partidas no filtraba y exponia las de todos los clientes, 2026-11-09).
     */
    public function indexQuery(?\Illuminate\Http\Request $request, \Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        $user = $request?->user('sanctum');
        if (! $user) {
            return $query->whereRaw('1 = 0');
        }
        if (\Modules\User\Support\UserAccess::seesAll($user, 'billing.cfdi-invoices.index')) {
            return $query;
        }
        $contact = \Modules\Contacts\Models\Contact::where('email', $user->email)->first();
        if ($contact) {
            return $query->whereHas('cfdiInvoice', fn ($q) => $q->where('contact_id', $contact->id));
        }

        return $query->whereRaw('1 = 0');
    }

    /**
     * Get the resource paginator.
     */
    public function pagination(): ?Paginator
    {
        return PagePagination::make();
    }

    /**
     * Get the JSON:API resource type.
     */
    public static function type(): string
    {
        return 'cfdi-items';
    }
}
