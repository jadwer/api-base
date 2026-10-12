<?php

namespace Modules\Sales\JsonApi\V1\Quotes;

use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

class QuoteRequest extends ResourceRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $quote = $this->model();
        $isCreating = $this->isCreating();

        return [
            'contactId' => $isCreating
                ? ['required', 'exists:contacts,id']
                : ['sometimes', 'exists:contacts,id'],
            'shoppingCartId' => ['nullable', 'exists:shopping_carts,id'],
            'branchId' => ['nullable', 'integer', 'exists:branches,id'],
            'branch' => ['nullable', \LaravelJsonApi\Validation\Rule::toOne()],
            'quoteNumber' => [
                $isCreating ? 'nullable' : 'sometimes', // Auto-generated if not provided
                'string',
                'max:50',
                Rule::unique('quotes', 'quote_number')->ignore($quote?->id)
            ],
            'status' => [
                'sometimes',
                Rule::in(['draft', 'sent', 'accepted', 'rejected', 'expired', 'converted', 'cancelled'])
            ],
            'quoteDate' => [$isCreating ? 'required' : 'sometimes', 'date'],
            'validUntil' => ['nullable', 'date', 'after_or_equal:quoteDate'],
            'estimatedEta' => ['nullable', 'string', 'max:255'],
            'subtotalAmount' => ['nullable', 'numeric', 'min:0'],
            'discountAmount' => ['nullable', 'numeric', 'min:0'],
            'taxAmount' => ['nullable', 'numeric', 'min:0'],
            'totalAmount' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'paymentMethod' => ['nullable', Rule::in(['PPD', 'PUE'])],
            'creditDays' => ['nullable', 'integer', 'min:0', 'max:365'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'internalNotes' => ['nullable', 'string', 'max:2000'],
            'termsAndConditions' => ['nullable', 'string', 'max:5000'],
            'shippingAddress' => ['nullable', 'array'],
            'billingAddress' => ['nullable', 'array'],
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
            'quoteNumber.unique' => 'Ese número de cotización ya existe.',
            'status.in' => 'El estado de la cotización no es válido.',
            'quoteDate.required' => 'La fecha de la cotización es obligatoria.',
            'quoteDate.date' => 'La fecha de la cotización debe ser una fecha válida.',
            'validUntil.date' => 'La vigencia debe ser una fecha válida.',
            'validUntil.after_or_equal' => 'La vigencia debe ser igual o posterior a la fecha de la cotización.',
            'totalAmount.numeric' => 'El total debe ser un número.',
            'totalAmount.min' => 'El total debe ser mayor o igual a 0.',
            'currency.size' => 'La moneda debe ser un código ISO de 3 letras (por ejemplo MXN o USD).',
            'paymentMethod.in' => 'El método de pago debe ser PPD o PUE.',
            'creditDays.integer' => 'Los días de crédito deben ser un número entero.',
            'creditDays.min' => 'Los días de crédito deben ser mayores o iguales a 0.',
            'creditDays.max' => 'Los días de crédito no pueden ser más de 365.',
            'notes.max' => 'Las notas no pueden tener más de 2000 caracteres.',
            'internalNotes.max' => 'Las notas internas no pueden tener más de 2000 caracteres.',
            'termsAndConditions.max' => 'Los términos y condiciones no pueden tener más de 5000 caracteres.',
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
