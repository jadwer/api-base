<?php

namespace Modules\Billing\JsonApi\V1\CFDIItems;

use Illuminate\Http\Request;
use LaravelJsonApi\Core\Resources\JsonApiResource;

class CFDIItemResource extends JsonApiResource
{
    /**
     * Get the resource's attributes.
     *
     * @param Request|null $request
     * @return iterable
     */
    public function attributes($request): iterable
    {
        return [
            'numeroLinea' => $this->numero_linea,
            'claveProdServ' => $this->clave_prod_serv,
            'claveUnidad' => $this->clave_unidad,
            'unidad' => $this->unidad,
            'cantidad' => $this->cantidad,
            'descripcion' => $this->descripcion,
            'noIdentificacion' => $this->no_identificacion,
            'valorUnitario' => $this->valor_unitario,
            'importe' => $this->importe,
            'descuento' => $this->descuento,
            'impuestos' => $this->impuestos,
            'objetoImp' => $this->objeto_imp,
            'numeroPedimento' => $this->numero_pedimento,
            'cuentaPredial' => $this->cuenta_predial,
            'informacionAduanera' => $this->informacion_aduanera,
            'metadata' => $this->metadata,
            // Barrido Paquete B 2026-08-31: el Resource manual pisa al
            // Schema; todo campo del Schema debe estar aqui o el API
            // guarda pero nunca lo devuelve.
            'cfdiInvoiceId' => $this->cfdi_invoice_id,
            'productId' => $this->product_id,
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }

    /**
     * Get the resource's relationships.
     *
     * @param Request|null $request
     * @return iterable
     */
    public function relationships($request): iterable
    {
        return [
            $this->relation('cfdiInvoice'),
            $this->relation('product'),
        ];
    }
}
