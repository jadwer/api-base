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
            'expectedArrivalDate' => ['nullable', 'date', ...($this->has('orderDate') || $creating ? ['after_or_equal:orderDate'] : [])],
            // Rediseno de Compras (17-11): una orden nace pendiente (la aprobacion por
            // monto la decide el modelo) y su estatus solo cambia con las acciones
            // approve/reject/receive/cancel. Antes se podia crear ya "recibida" o
            // "cancelada": el observer solo actua en updated, asi que no entraba al stock
            // ni se creaba la CxP. En edicion se rechaza con mensaje en lugar de ignorarse.
            'status' => $creating
                ? ['sometimes', 'string', Rule::in(['pending'])]
                : [$this->notCaptured('El estatus cambia con las acciones Aprobar, Recibir o Cancelar.')],
            'orderNumber' => [$this->notCaptured('El número de orden lo asigna el sistema.')],
            'warehouseId' => ['nullable', 'integer', 'exists:warehouses,id'],
            'branchId' => ['nullable', 'integer', 'exists:branches,id'],
            'invoicingNotes' => ['sometimes', 'nullable', 'string'],
            'notes' => ['nullable', 'string'],

            // Validaciones para relaciones
            'contact' => [$creating ? 'required' : 'sometimes', JsonApiRule::toOne()],
            'warehouse' => ['nullable', JsonApiRule::toOne()],
            'branch' => ['nullable', JsonApiRule::toOne()],
        ];
    }

    public function messages(): array
    {
        return [
            'orderDate.required' => 'La fecha de la orden es obligatoria.',
            'orderDate.date' => 'La fecha de la orden debe ser una fecha válida.',
            'expectedArrivalDate.date' => 'La fecha estimada de llegada debe ser una fecha válida.',
            'expectedArrivalDate.after_or_equal' => 'La fecha estimada de llegada no puede ser anterior a la fecha de la orden.',
            'status.in' => 'Una orden nueva siempre queda pendiente; se aprueba, recibe o cancela con sus acciones.',
            'warehouseId.exists' => 'El almacén seleccionado no existe.',
            'branchId.exists' => 'La sucursal seleccionada no existe.',
            'contact.required' => 'El proveedor es obligatorio.',
        ];
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
