<?php

namespace Modules\Inventory\JsonApi\V1\Fractionations;

use LaravelJsonApi\Core\Resources\JsonApiResource;

class FractionationResource extends JsonApiResource
{
    public function attributes($request): iterable
    {
        return [
            'folioNumber' => $this->folio_number,
            'sourceProductId' => $this->source_product_id,
            'destinationProductId' => $this->destination_product_id,
            'productConversionId' => $this->product_conversion_id,
            'warehouseId' => $this->warehouse_id,
            'userId' => $this->user_id,
            'sourceQuantity' => $this->source_quantity,
            'producedQuantity' => $this->produced_quantity,
            'wastePercentage' => $this->waste_percentage,
            'wasteQuantity' => $this->waste_quantity,
            'conversionFactorUsed' => $this->conversion_factor_used,
            'exitMovementId' => $this->exit_movement_id,
            'entryMovementId' => $this->entry_movement_id,
            'status' => $this->status,
            'notes' => $this->notes,
            'executedAt' => $this->executed_at,
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }

    public function relationships($request): iterable
    {
        return [
            'sourceProduct' => $this->relation('sourceProduct'),
            'destinationProduct' => $this->relation('destinationProduct'),
            'productConversion' => $this->relation('productConversion'),
            'warehouse' => $this->relation('warehouse'),
            'user' => $this->relation('user'),
            'exitMovement' => $this->relation('exitMovement'),
            'entryMovement' => $this->relation('entryMovement'),
        ];
    }
}
