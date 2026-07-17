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
            'approvedAt' => ['nullable', 'date'],
            'deliveredAt' => ['nullable', 'date'],
            'discountTotal' => ['nullable', 'numeric', 'min:0'],
            'subtotal' => ['nullable', 'numeric', 'min:0'],
            'taxAmount' => ['nullable', 'numeric', 'min:0'],
            'totalAmount' => [$isCreating ? 'required' : 'sometimes', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'customerPoNumber' => ['nullable', 'string', 'max:100'],
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
            'contactId.required' => 'Contact is required.',
            'contactId.exists' => 'The selected contact does not exist.',
            'orderNumber.required' => 'Order number is required.',
            'orderNumber.unique' => 'This order number is already taken.',
            'status.required' => 'Order status is required.',
            'status.in' => 'Invalid order status.',
            'orderDate.required' => 'Order date is required.',
            'orderDate.date' => 'Order date must be a valid date.',
            'totalAmount.required' => 'Total amount is required.',
            'totalAmount.numeric' => 'Total amount must be a number.',
            'totalAmount.min' => 'Total amount must be at least 0.',
            'discountTotal.numeric' => 'Discount total must be a number.',
            'discountTotal.min' => 'Discount total must be at least 0.',
            'subtotal.numeric' => 'Subtotal must be a number.',
            'subtotal.min' => 'Subtotal must be at least 0.',
            'taxAmount.numeric' => 'Tax amount must be a number.',
            'taxAmount.min' => 'Tax amount must be at least 0.',
            'notes.max' => 'Notes cannot exceed 1000 characters.',
            'customerPoNumber.max' => 'Customer PO number cannot exceed 100 characters.',
            'paymentMethod.in' => 'Payment method must be PPD or PUE.',
            'creditDays.integer' => 'Credit days must be an integer.',
            'creditDays.min' => 'Credit days must be at least 0.',
            'creditDays.max' => 'Credit days cannot exceed 365.',
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
