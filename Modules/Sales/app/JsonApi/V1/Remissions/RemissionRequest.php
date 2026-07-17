<?php

namespace Modules\Sales\JsonApi\V1\Remissions;

use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

/**
 * SA-M006: Remission Request validation
 */
class RemissionRequest extends ResourceRequest
{
    /**
     * Get the validation rules for creating a new resource.
     */
    public function rules(): array
    {
        return [
            'salesOrderId' => ['required', 'integer', 'exists:sales_orders,id'],
            'shipmentId' => ['nullable', 'integer', 'exists:shipments,id'],
            'warehouseId' => ['nullable', 'integer', 'exists:warehouses,id'],
            'remissionNumber' => ['sometimes', 'string', 'max:50'],
            'status' => ['sometimes', Rule::in(['draft', 'printed', 'delivered', 'cancelled'])],
            'remissionDate' => ['sometimes', 'date'],
            'deliveryDate' => ['nullable', 'date'],
            'deliveredBy' => ['nullable', 'string', 'max:200'],
            'receivedBy' => ['nullable', 'string', 'max:200'],
            'deliveryNotes' => ['nullable', 'string', 'max:2000'],
            'shippingAddress' => ['nullable', 'array'],
            'internalNotes' => ['nullable', 'string', 'max:2000'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    /**
     * Get the validation rules for updating the resource.
     */
    public function updateRules(): array
    {
        return [
            'shipmentId' => ['nullable', 'integer', 'exists:shipments,id'],
            'warehouseId' => ['nullable', 'integer', 'exists:warehouses,id'],
            // Refactor ciclo (Patron 1): status se acepta pero el Schema lo marca
            // readOnlyOnUpdate -> se IGNORA en update. Las transiciones van por los
            // endpoints print/deliver/cancel (deliver ahora descuenta stock real).
            'status' => ['sometimes', 'string'],
            'remissionDate' => ['sometimes', 'date'],
            'deliveryDate' => ['nullable', 'date'],
            'deliveredBy' => ['nullable', 'string', 'max:200'],
            'receivedBy' => ['nullable', 'string', 'max:200'],
            'deliveryNotes' => ['nullable', 'string', 'max:2000'],
            'shippingAddress' => ['nullable', 'array'],
            'internalNotes' => ['nullable', 'string', 'max:2000'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
