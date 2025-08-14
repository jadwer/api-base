<?php

namespace Modules\Inventory\JsonApi\V1\InventoryMovements;

use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use LaravelJsonApi\Validation\Rule as JsonApiRule;
use Modules\Inventory\Models\InventoryMovement;

class InventoryMovementRequest extends ResourceRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $movement = $this->model();

        return [
            // Campos requeridos
            'movementType' => [
                'required',
                'string',
                Rule::in(['entry', 'exit', 'transfer', 'adjustment'])
            ],
            'movementDate' => ['required', 'date'],
            'quantity' => ['required', 'numeric', 'not_in:0'],
            'unitCost' => ['required', 'numeric', 'min:0'],
            
            // Campos opcionales
            'referenceType' => [
                'nullable',
                'string',
                Rule::in(['purchase', 'sale', 'transfer', 'adjustment', 'manual'])
            ],
            'referenceId' => ['nullable', 'integer', 'min:1'],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => [
                'sometimes',
                'string',
                Rule::in(['pending', 'completed', 'cancelled'])
            ],
            'previousStock' => ['nullable', 'numeric'],
            'newStock' => ['nullable', 'numeric'],
            
            // Campos JSON
            'batchInfo' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
            
            // Relaciones obligatorias
            'product' => JsonApiRule::toOne()->required(),
            'warehouse' => JsonApiRule::toOne()->required(),
            'user' => JsonApiRule::toOne()->required(),
            
            // Relaciones opcionales
            'location' => JsonApiRule::toOne(),
            'destinationWarehouse' => JsonApiRule::toOne(),
            'destinationLocation' => JsonApiRule::toOne(),
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'movementType.required' => 'El tipo de movimiento es obligatorio.',
            'movementType.in' => 'El tipo de movimiento debe ser: entry, exit, transfer o adjustment.',
            'movementDate.required' => 'La fecha del movimiento es obligatoria.',
            'movementDate.date' => 'La fecha del movimiento debe ser una fecha válida.',
            'quantity.required' => 'La cantidad es obligatoria.',
            'quantity.numeric' => 'La cantidad debe ser un número.',
            'quantity.not_in' => 'La cantidad no puede ser cero.',
            'unitCost.required' => 'El costo unitario es obligatorio.',
            'unitCost.numeric' => 'El costo unitario debe ser un número.',
            'unitCost.min' => 'El costo unitario debe ser mayor o igual a 0.',
            'referenceType.in' => 'El tipo de referencia debe ser: purchase, sale, transfer, adjustment o manual.',
            'referenceId.integer' => 'El ID de referencia debe ser un número entero.',
            'referenceId.min' => 'El ID de referencia debe ser mayor a 0.',
            'description.max' => 'La descripción no puede tener más de 1000 caracteres.',
            'status.in' => 'El estado debe ser: pending, completed o cancelled.',
            'previousStock.numeric' => 'El stock anterior debe ser un número.',
            'newStock.numeric' => 'El nuevo stock debe ser un número.',
            'batchInfo.array' => 'La información de lotes debe ser un objeto.',
            'metadata.array' => 'Los metadatos deben ser un objeto.',
            'product.required' => 'El producto es obligatorio.',
            'warehouse.required' => 'El warehouse es obligatorio.',
            'user.required' => 'El usuario es obligatorio.',
        ];
    }

    /**
     * Get default values for the request.
     */
    public function withDefaults(): array
    {
        return [
            'status' => InventoryMovement::STATUS_PENDING,
            'movementDate' => now(),
            'batchInfo' => [],
            'metadata' => [],
        ];
    }

    /**
     * Apply custom validation logic after standard validation.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $data = $this->validated();
            
            // Validar que las transferencias tengan warehouse destino
            if ($data['movementType'] === 'transfer') {
                if (!$this->hasRelation('destinationWarehouse')) {
                    $validator->errors()->add(
                        'destinationWarehouse',
                        'Las transferencias requieren un warehouse de destino.'
                    );
                }
                
                // Validar que origen y destino sean diferentes
                if ($this->hasRelation('warehouse') && $this->hasRelation('destinationWarehouse')) {
                    $warehouseId = $this->input('data.relationships.warehouse.data.id');
                    $destinationId = $this->input('data.relationships.destinationWarehouse.data.id');
                    
                    if ($warehouseId === $destinationId) {
                        $validator->errors()->add(
                            'destinationWarehouse',
                            'El warehouse de destino debe ser diferente al de origen.'
                        );
                    }
                }
            }
            
            // Validar cantidades según tipo de movimiento
            if (isset($data['quantity'])) {
                $quantity = $data['quantity'];
                $movementType = $data['movementType'];
                
                if (in_array($movementType, ['exit']) && $quantity < 0) {
                    $validator->errors()->add(
                        'quantity',
                        'Las salidas deben tener cantidad positiva (el sistema la convertirá a negativa).'
                    );
                }
                
                if (in_array($movementType, ['entry', 'transfer']) && $quantity < 0) {
                    $validator->errors()->add(
                        'quantity',
                        'Las entradas y transferencias deben tener cantidad positiva.'
                    );
                }
            }
        });
    }

    /**
     * Check if a relationship is present in the request.
     */
    private function hasRelation(string $relationName): bool
    {
        return $this->input("data.relationships.{$relationName}.data") !== null;
    }
}