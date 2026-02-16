<?php

namespace Modules\Inventory\JsonApi\V1\ProductConversions;

use LaravelJsonApi\Core\Resources\JsonApiResource;

class ProductConversionResource extends JsonApiResource
{
    public function attributes($request): iterable
    {
        return [
            'sourceProductId' => $this->source_product_id,
            'destinationProductId' => $this->destination_product_id,
            'conversionFactor' => $this->conversion_factor,
            'wastePercentage' => $this->waste_percentage,
            'isActive' => $this->is_active,
            'notes' => $this->notes,
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }

    public function relationships($request): iterable
    {
        return [
            'sourceProduct' => $this->relation('sourceProduct'),
            'destinationProduct' => $this->relation('destinationProduct'),
        ];
    }
}
