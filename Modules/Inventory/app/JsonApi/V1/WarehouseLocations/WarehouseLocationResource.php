<?php

namespace Modules\Inventory\JsonApi\V1\WarehouseLocations;

use LaravelJsonApi\Core\Resources\JsonApiResource;

class WarehouseLocationResource extends JsonApiResource
{
    public function attributes($request): iterable
    {
        return [
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'locationType' => $this->location_type,
            'aisle' => $this->aisle,
            'rack' => $this->rack,
            'shelf' => $this->shelf,
            'level' => $this->level,
            'position' => $this->position,
            'barcode' => $this->barcode,
            'maxWeight' => $this->max_weight,
            'maxVolume' => $this->max_volume,
            'dimensions' => $this->dimensions,
            'isActive' => $this->is_active,
            'isPickable' => $this->is_pickable,
            'isReceivable' => $this->is_receivable,
            'priority' => $this->priority,
            'metadata' => $this->metadata,
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }

    public function relationships($request): iterable
    {
        return [
            'warehouse' => $this->relation('warehouse'),
            'stock' => $this->relation('stock'),
            'productBatches' => $this->relation('productBatches'),
        ];
    }
}
