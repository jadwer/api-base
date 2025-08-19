<?php

namespace Modules\Finance\JsonApi\V1\APInvoiceLines;

use LaravelJsonApi\Core\Resources\JsonApiResource;

class APInvoiceLineResource extends JsonApiResource
{
    public function attributes($request): iterable
    {
        return [
            'apInvoiceId' => $this->ap_invoice_id,
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
            'aPInvoice' => $this->relation('aPInvoice'),
        ];
    }
}
