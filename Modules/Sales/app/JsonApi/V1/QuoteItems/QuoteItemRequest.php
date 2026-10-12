<?php

namespace Modules\Sales\JsonApi\V1\QuoteItems;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

class QuoteItemRequest extends ResourceRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $item = $this->model();
        $isCreating = $this->isCreating();

        return [
            'quoteId' => $isCreating
                ? ['required', 'exists:quotes,id']
                : ['sometimes', 'exists:quotes,id'],
            'productId' => $isCreating
                ? ['required', 'exists:products,id']
                : ['sometimes', 'exists:products,id'],
            'quantity' => [$isCreating ? 'required' : 'sometimes', 'numeric', 'min:0.01'],
            'unitPrice' => [$isCreating ? 'required' : 'sometimes', 'numeric', 'min:0'],
            'quotedPrice' => [$isCreating ? 'required' : 'sometimes', 'numeric', 'min:0'],
            'discountPercentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'discountAmount' => ['nullable', 'numeric', 'min:0'],
            'taxRate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'productName' => ['nullable', 'string', 'max:255'],
            'productSku' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    /**
     * Regla de precedencia del descuento:
     * el cliente debe enviar discountPercentage O discountAmount, nunca ambos
     * con valor en el mismo request. Si llegan ambos (no null) se responde 422.
     * El campo que llega es la fuente de verdad y el modelo deriva el otro
     * en QuoteItem::calculateTotals().
     *
     * Se valida sobre el payload crudo (json data.attributes) porque en updates
     * el validador de JSON:API mezcla los valores existentes del recurso y
     * marcaria falsos positivos.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $attributes = $this->json('data.attributes', []);

            $hasPercentage = array_key_exists('discountPercentage', $attributes)
                && $attributes['discountPercentage'] !== null;
            $hasAmount = array_key_exists('discountAmount', $attributes)
                && $attributes['discountAmount'] !== null;

            if ($hasPercentage && $hasAmount) {
                $validator->errors()->add(
                    'discountAmount',
                    'Send either discountPercentage or discountAmount, not both.'
                );
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'quoteId.required' => 'La cotización es obligatoria.',
            'quoteId.exists' => 'La cotización seleccionada no existe.',
            'productId.required' => 'El producto es obligatorio.',
            'productId.exists' => 'El producto seleccionado no existe.',
            'quantity.required' => 'La cantidad es obligatoria.',
            'quantity.numeric' => 'La cantidad debe ser un número.',
            'quantity.min' => 'La cantidad debe ser mayor a 0.',
            'unitPrice.required' => 'El precio unitario es obligatorio.',
            'unitPrice.numeric' => 'El precio unitario debe ser un número.',
            'unitPrice.min' => 'El precio unitario debe ser mayor o igual a 0.',
            'quotedPrice.required' => 'El precio cotizado es obligatorio.',
            'quotedPrice.numeric' => 'El precio cotizado debe ser un número.',
            'quotedPrice.min' => 'El precio cotizado debe ser mayor o igual a 0.',
            'discountPercentage.numeric' => 'El porcentaje de descuento debe ser un número.',
            'discountPercentage.min' => 'El porcentaje de descuento debe ser mayor o igual a 0.',
            'discountPercentage.max' => 'El porcentaje de descuento no puede ser mayor a 100.',
            'discountAmount.numeric' => 'El importe de descuento debe ser un número.',
            'discountAmount.min' => 'El importe de descuento debe ser mayor o igual a 0.',
            'taxRate.numeric' => 'La tasa de impuesto debe ser un número.',
            'taxRate.min' => 'La tasa de impuesto debe ser mayor o igual a 0.',
            'taxRate.max' => 'La tasa de impuesto no puede ser mayor a 100.',
            'notes.max' => 'Las notas no pueden tener más de 1000 caracteres.',
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
