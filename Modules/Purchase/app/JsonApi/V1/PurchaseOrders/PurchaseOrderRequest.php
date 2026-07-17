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
            // Refactor ciclo (Patron 1): en creacion se valida el status inicial. En
            // update se acepta pero el Schema lo marca readOnlyOnUpdate, asi que el valor
            // se IGNORA (no lanza error para no romper el form de edicion del FE, que
            // envia status). Las transiciones reales van por approve/reject/receive/cancel.
            'status' => $creating
                ? ['required', 'string', Rule::in(['pending', 'approved', 'received', 'cancelled'])]
                : ['sometimes', 'string'],
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
