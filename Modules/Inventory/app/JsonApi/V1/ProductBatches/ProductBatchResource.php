<?php

namespace Modules\Inventory\JsonApi\V1\ProductBatches;

use Illuminate\Http\Request;
use LaravelJsonApi\Core\Resources\JsonApiResource;

class ProductBatchResource extends JsonApiResource
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
            'batchNumber' => $this->batch_number,
            'lotNumber' => $this->lot_number,
            'manufacturingDate' => $this->manufacturing_date,
            'expirationDate' => $this->expiration_date,
            'bestBeforeDate' => $this->best_before_date,
            'initialQuantity' => $this->initial_quantity,
            'currentQuantity' => $this->current_quantity,
            'reservedQuantity' => $this->reserved_quantity,
            'availableQuantity' => $this->available_quantity,
            'unitCost' => $this->unit_cost,
            'totalValue' => $this->total_value,
            'status' => $this->status,
            'supplierName' => $this->supplier_name,
            'supplierBatch' => $this->supplier_batch,
            'qualityNotes' => $this->quality_notes,
            'testResults' => $this->test_results,
            'certifications' => $this->certifications,
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
            $this->relation('warehouseLocation'),
        ];
    }
}
