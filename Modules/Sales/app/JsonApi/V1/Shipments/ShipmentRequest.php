<?php

namespace Modules\Sales\JsonApi\V1\Shipments;

use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

/**
 * SA-M001: JSON:API Request validation for Shipment.
 */
class ShipmentRequest extends ResourceRequest
{
    public function rules(): array
    {
        $model = $this->model();
        $isCreating = $this->isCreating();

        return [
            'salesOrderId' => [
                $isCreating ? 'required' : 'sometimes',
                'exists:sales_orders,id',
            ],
            'warehouseId' => ['nullable', 'exists:warehouses,id'],
            'shipmentNumber' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('shipments', 'shipment_number')->ignore($model),
            ],
            'status' => [
                'sometimes',
                Rule::in(['pending', 'processing', 'shipped', 'delivered', 'cancelled', 'returned']),
            ],
            'carrier' => ['nullable', 'string', 'max:100'],
            'trackingNumber' => ['nullable', 'string', 'max:100'],
            'trackingUrl' => ['nullable', 'string', 'max:500', 'url'],
            'shipDate' => ['nullable', 'date'],
            'estimatedDelivery' => ['nullable', 'date'],
            'actualDelivery' => ['nullable', 'date'],
            'shippingAddress' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'shippingCost' => ['nullable', 'numeric', 'min:0'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'salesOrderId.required' => 'La orden de venta es requerida.',
            'salesOrderId.exists' => 'La orden de venta seleccionada no existe.',
            'warehouseId.exists' => 'El almacen seleccionado no existe.',
            'shipmentNumber.unique' => 'Este numero de envio ya existe.',
            'shipmentNumber.max' => 'El numero de envio no puede exceder :max caracteres.',
            'status.in' => 'Estado de envio invalido.',
            'carrier.max' => 'El transportista no puede exceder :max caracteres.',
            'trackingNumber.max' => 'El numero de rastreo no puede exceder :max caracteres.',
            'trackingUrl.url' => 'La URL de rastreo debe ser una URL valida.',
            'trackingUrl.max' => 'La URL de rastreo no puede exceder :max caracteres.',
            'shipDate.date' => 'La fecha de envio debe ser una fecha valida.',
            'estimatedDelivery.date' => 'La fecha estimada de entrega debe ser una fecha valida.',
            'actualDelivery.date' => 'La fecha de entrega debe ser una fecha valida.',
            'shippingAddress.max' => 'La direccion de envio no puede exceder :max caracteres.',
            'notes.max' => 'Las notas no pueden exceder :max caracteres.',
            'shippingCost.numeric' => 'El costo de envio debe ser un numero.',
            'shippingCost.min' => 'El costo de envio no puede ser negativo.',
            'weight.numeric' => 'El peso debe ser un numero.',
            'weight.min' => 'El peso no puede ser negativo.',
        ];
    }
}
