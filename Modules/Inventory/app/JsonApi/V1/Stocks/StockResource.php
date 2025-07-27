<?php

namespace Modules\Inventory\JsonApi\V1\Stocks;

use Illuminate\Http\Request;
use LaravelJsonApi\Core\Resources\JsonApiResource;

class StockResource extends JsonApiResource
{
    /**
     * Get the resource's attributes.
     *
     * @param Request|null $request
     * @return iterable
     */
    public function attributes($request): iterable
    {
        return [
            'quantity' => $this->quantity,
            'reservedQuantity' => $this->reserved_quantity,
            'availableQuantity' => $this->available_quantity,
            'minimumStock' => $this->minimum_stock,
            'maximumStock' => $this->maximum_stock,
            'reorderPoint' => $this->reorder_point,
            'unitCost' => $this->unit_cost,
            'totalValue' => $this->total_value,
            'status' => $this->status,
            'lastMovementDate' => $this->last_movement_date,
            'lastMovementType' => $this->last_movement_type,
            'batchInfo' => $this->batch_info,
            'metadata' => $this->metadata,
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }

    /**
     * Get the resource's relationships.
     *
     * @param Request|null $request
     * @return iterable
     */
    public function relationships($request): iterable
    {
        return [
            $this->relation('product'),
            $this->relation('warehouse'),
            $this->relation('location'),
        ];
    }
}
