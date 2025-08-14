<?php

namespace Modules\Inventory\JsonApi\V1\InventoryMovements;

use LaravelJsonApi\Core\Resources\JsonApiResource;

class InventoryMovementResource extends JsonApiResource
{
    /**
     * Get the resource attributes.
     */
    public function attributes($request): iterable
    {
        return [
            // Campos básicos del movimiento
            'movementType' => $this->movement_type,
            'referenceType' => $this->reference_type,
            'referenceId' => $this->reference_id,
            'movementDate' => $this->movement_date,
            'description' => $this->description,
            
            // Cantidades y costos
            'quantity' => $this->quantity,
            'unitCost' => $this->unit_cost,
            'totalValue' => $this->total_value,
            
            // Estado y auditoría
            'status' => $this->status,
            'previousStock' => $this->previous_stock,
            'newStock' => $this->new_stock,
            
            // Campos JSON
            'batchInfo' => $this->batch_info,
            'metadata' => $this->metadata,
            
            // Timestamps
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }

    /**
     * Get the resource relationships.
     */
    public function relationships($request): iterable
    {
        return [
            'product' => $this->relation('product'),
            'warehouse' => $this->relation('warehouse'),
            'location' => $this->relation('location'),
            'destinationWarehouse' => $this->relation('destinationWarehouse'),
            'destinationLocation' => $this->relation('destinationLocation'),
            'user' => $this->relation('user'),
        ];
    }
}