<?php

namespace Modules\Inventory\JsonApi\V1\WarehouseLocations;

use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use LaravelJsonApi\Validation\Rule as JsonApiRule;
use Modules\Inventory\Models\Stock;

class WarehouseLocationRequest extends ResourceRequest
{
    public function rules(): array
    {
        $location = $this->model();

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('warehouse_locations', 'code')->ignore($location),
            ],
            'description' => ['nullable', 'string'],
            'locationType' => [
                'required',
                'string',
                Rule::in(['aisle', 'rack', 'shelf', 'bin', 'zone', 'bay']),
            ],
            'aisle' => ['nullable', 'string', 'max:255'],
            'rack' => ['nullable', 'string', 'max:255'],
            'shelf' => ['nullable', 'string', 'max:255'],
            'level' => ['nullable', 'string', 'max:255'],
            'position' => ['nullable', 'string', 'max:255'],
            'barcode' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('warehouse_locations', 'barcode')->ignore($location),
            ],
            'maxWeight' => ['nullable', 'numeric', 'min:0'],
            'maxVolume' => ['nullable', 'numeric', 'min:0'],
            'dimensions' => ['nullable', 'string', 'max:255'],
            'isActive' => ['sometimes', 'boolean'],
            'isPickable' => ['sometimes', 'boolean'],
            'isReceivable' => ['sometimes', 'boolean'],
            'priority' => ['sometimes', 'integer', 'min:1', 'max:10'],
            'metadata' => ['nullable', 'array'],
            
            // Relación requerida
            'warehouse' => JsonApiRule::toOne(),
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Validar eliminación si es una request DELETE
            if (request()->isMethod('DELETE')) {
                $this->validateDeletion($validator);
            }
        });
    }

    /**
     * Validar que la ubicación pueda ser eliminada
     */
    protected function validateDeletion($validator): void
    {
        $location = $this->model();
        
        if ($location && $location->exists) {
            // Verificar si hay stock activo en esta ubicación
            $hasActiveStock = Stock::where('warehouse_location_id', $location->id)
                                  ->where('quantity', '>', 0)
                                  ->exists();
            
            if ($hasActiveStock) {
                $validator->errors()->add(
                    'location',
                    'No se puede eliminar esta ubicación porque tiene stock activo. Debe mover o eliminar el stock primero.'
                );
            }
        }
    }

    public function withDefaults(): array
    {
        return [
            'isActive' => true,
            'isPickable' => true,
            'isReceivable' => true,
            'priority' => 1,
            'locationType' => 'bin',
        ];
    }
}
