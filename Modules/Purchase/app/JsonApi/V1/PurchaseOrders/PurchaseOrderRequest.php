<?php

namespace Modules\Purchase\JsonApi\V1\PurchaseOrders;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use LaravelJsonApi\Validation\Rule as JsonApiRule;

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
            'status' => [$creating ? 'required' : 'sometimes', 'string', 'in:pending,approved,received,cancelled'],
            'totalAmount' => [$creating ? 'required' : 'sometimes', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            
            // Validaciones para relaciones
            'supplier' => [$creating ? 'required' : 'sometimes', JsonApiRule::toOne()],
        ];
    }
}
