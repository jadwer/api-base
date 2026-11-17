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
            // Rediseno de Compras (17-11, regla 8): un pedido nace borrador o pendiente.
            // Antes se podia crear ya confirmado, enviado o entregado: eso se saltaba
            // OrderStatusService (sin descuento de stock, sin SalesOrderDelivered ni CxC
            // y sin el permiso sales-orders.confirm). En edicion el estatus se rechaza
            // con mensaje: cambia por POST /orders/{id}/status o por la accion confirmar.
            'status' => $isCreating
                ? ['required', Rule::in(['draft', 'pending'])]
                : [$this->notCaptured('El estatus del pedido cambia con sus acciones (confirmar, enviar, entregar, cancelar).')],
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
            'status.in' => 'Un pedido nuevo nace como borrador o pendiente; después cambia con sus acciones.',
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

    /**
     * Rechaza el campo solo si viene en la peticion: en PATCH laravel-json-api mezcla los
     * valores guardados antes de validar, 'prohibited' fallaria siempre.
     */
    private function notCaptured(string $message): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) use ($message): void {
            if (array_key_exists($attribute, (array) $this->input('data.attributes', []))) {
                $fail($message);
            }
        };
    }
}
