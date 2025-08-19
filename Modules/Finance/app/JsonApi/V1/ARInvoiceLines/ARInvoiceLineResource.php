<?php

namespace Modules\Finance\JsonApi\V1\ARInvoiceLines;

use LaravelJsonApi\Core\Resources\JsonApiResource;

class ARInvoiceLineResource extends JsonApiResource
{
    public function attributes($request): iterable
    {
        return [
            'arInvoiceId' => $this->ar_invoice_id,
            'description' => $this->description,
            'quantity' => $this->quantity,
            'unitPrice' => $this->unit_price,
            'discount' => $this->discount,
            'lineTotal' => $this->line_total,
            "createdAt" => $this->created_at,
            "updatedAt" => $this->updated_at,
        ];
    }

    public function relationships($request): iterable
    {
        return [
            'aRInvoice' => $this->relation('aRInvoice'),
        ];
    }
}
