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
            'taxRate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'productName' => ['nullable', 'string', 'max:255'],
            'productSku' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'quoteId.required' => 'Quote is required.',
            'quoteId.exists' => 'The selected quote does not exist.',
            'productId.required' => 'Product is required.',
            'productId.exists' => 'The selected product does not exist.',
            'quantity.required' => 'Quantity is required.',
            'quantity.numeric' => 'Quantity must be a number.',
            'quantity.min' => 'Quantity must be at least 0.01.',
            'unitPrice.required' => 'Unit price is required.',
            'unitPrice.numeric' => 'Unit price must be a number.',
            'unitPrice.min' => 'Unit price must be at least 0.',
            'quotedPrice.required' => 'Quoted price is required.',
            'quotedPrice.numeric' => 'Quoted price must be a number.',
            'quotedPrice.min' => 'Quoted price must be at least 0.',
            'discountPercentage.numeric' => 'Discount percentage must be a number.',
            'discountPercentage.min' => 'Discount percentage must be at least 0.',
            'discountPercentage.max' => 'Discount percentage cannot exceed 100.',
            'taxRate.numeric' => 'Tax rate must be a number.',
            'taxRate.min' => 'Tax rate must be at least 0.',
            'taxRate.max' => 'Tax rate cannot exceed 100.',
            'notes.max' => 'Notes cannot exceed 1000 characters.',
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
