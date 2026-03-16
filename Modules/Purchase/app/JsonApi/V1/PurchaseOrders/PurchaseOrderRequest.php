<?php

namespace Modules\Purchase\JsonApi\V1\PurchaseOrders;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use LaravelJsonApi\Validation\Rule as JsonApiRule;
use Illuminate\Validation\Rule;

class PurchaseOrderRequest extends ResourceRequest
{
    /**
     * Get the validation rules for the resource.
     *
     * @return array
     */
    public function rules(): array
    {
        $creating = $this->isCreating();
        
        return [
            'orderDate' => [$creating ? 'required' : 'sometimes', 'date'],
            'status' => [$creating ? 'required' : 'sometimes', 'string', Rule::in(['pending', 'approved', 'received', 'cancelled'])],
            'totalAmount' => [$creating ? 'required' : 'sometimes', 'numeric', 'min:0'],
            'warehouseId' => ['nullable', 'integer', 'exists:warehouses,id'],
            'approvalStatus' => ['sometimes', 'nullable', 'string'],
            'invoicingStatus' => ['sometimes', 'nullable', 'string'],
            'financialStatus' => ['sometimes', 'nullable', 'string'],
            'notes' => ['nullable', 'string'],

            // Validaciones para relaciones
            'contact' => [$creating ? 'required' : 'sometimes', JsonApiRule::toOne()],
            'warehouse' => ['nullable', JsonApiRule::toOne()],
        ];
    }
}
