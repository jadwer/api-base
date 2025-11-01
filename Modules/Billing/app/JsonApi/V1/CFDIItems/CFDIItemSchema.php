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
     *
     * @var string
     */
    public static string $model = CFDIItem::class;

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
     *
     * @return array
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
        return 'cfdi-items';
    }
}
