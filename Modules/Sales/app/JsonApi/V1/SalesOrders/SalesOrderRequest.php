<?php

namespace Modules\Sales\JsonApi\V1\SalesOrders;

use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

class SalesOrderRequest extends ResourceRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $order = $this->model();
        $isCreating = $this->isCreating();
        
        return [
            'contactId' => $isCreating
                ? ['required', 'exists:contacts,id']
                : ['sometimes', 'exists:contacts,id'],
            'orderNumber' => [
                $isCreating ? 'required' : 'sometimes',
                'string',
                'max:50',
                Rule::unique('sales_orders', 'order_number')->ignore($order?->id)
            ],
            // Refactor ciclo (Patron 1): en creacion se valida el status inicial con el
            // enum completo de la maquina de OrderStatusService (antes omitia pending/
            // completed/returned/refunded, que convert() sí usa). En update se acepta pero
            // el Schema lo marca readOnlyOnUpdate -> el valor se IGNORA (no falla para no
            // romper el form de edicion del FE que envia status). Las transiciones reales
            // van por OrderStatusService (endpoint POST /orders/{id}/status).
            'status' => $isCreating
                ? ['required', Rule::in(['draft', 'pending', 'confirmed', 'processing', 'shipped', 'delivered', 'completed', 'cancelled', 'returned', 'refunded'])]
                : ['sometimes', 'string'],
            'orderDate' => [$isCreating ? 'required' : 'sometimes', 'date'],
            'branchId' => ['nullable', 'integer', 'exists:branches,id'],
            'shippingAddress' => ['nullable', 'array'],
            'billingAddress' => ['nullable', 'array'],
            'branch' => ['nullable', \LaravelJsonApi\Validation\Rule::toOne()],
            'approvedAt' => ['nullable', 'date'],
            'deliveredAt' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'invoicingNotes' => ['nullable', 'string'],
            'customerPoNumber' => ['nullable', 'string', 'max:100'],
            'acceptanceChannel' => ['nullable', Rule::in(array_keys(\Modules\Sales\Support\CustomerAcceptance::CHANNELS))],
            'paymentMethod' => ['nullable', Rule::in(['PPD', 'PUE'])],
            'creditDays' => ['nullable', 'integer', 'min:0', 'max:365'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'contactId.required' => 'El contacto es obligatorio.',
            'contactId.exists' => 'El contacto seleccionado no existe.',
            'orderNumber.required' => 'El número de pedido es obligatorio.',
            'orderNumber.unique' => 'Ese número de pedido ya existe.',
            'status.required' => 'El estado del pedido es obligatorio.',
            'status.in' => 'El estado del pedido no es válido.',
            'orderDate.required' => 'La fecha del pedido es obligatoria.',
            'orderDate.date' => 'La fecha del pedido debe ser una fecha válida.',
            'totalAmount.required' => 'El total es obligatorio.',
            'totalAmount.numeric' => 'El total debe ser un número.',
            'totalAmount.min' => 'El total debe ser mayor o igual a 0.',
            'discountTotal.numeric' => 'El descuento total debe ser un número.',
            'discountTotal.min' => 'El descuento total debe ser mayor o igual a 0.',
            'subtotalAmount.numeric' => 'El subtotal debe ser un número.',
            'subtotalAmount.min' => 'El subtotal debe ser mayor o igual a 0.',
            'taxAmount.numeric' => 'Los impuestos deben ser un número.',
            'taxAmount.min' => 'Los impuestos deben ser mayores o iguales a 0.',
            'notes.max' => 'Las notas no pueden tener más de 1000 caracteres.',
            'customerPoNumber.max' => 'La orden de compra del cliente no puede tener más de 100 caracteres.',
            'paymentMethod.in' => 'El método de pago debe ser PPD o PUE.',
            'creditDays.integer' => 'Los días de crédito deben ser un número entero.',
            'creditDays.min' => 'Los días de crédito deben ser mayores o iguales a 0.',
            'creditDays.max' => 'Los días de crédito no pueden ser más de 365.',
        ];
    }

    /**
     * Rules for delete operations.
     */
    public function deleteRules(): array
    {
        return [];
    }
}
