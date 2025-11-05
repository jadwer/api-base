<?php

namespace Modules\CRM\JsonApi\V1\PipelineStages;

use Illuminate\Http\Request;
use LaravelJsonApi\Core\Resources\JsonApiResource;

class PipelineStageResource extends JsonApiResource
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
            'name' => $this->resource->name,
            'type' => $this->resource->type,
            'probability' => $this->resource->probability,
            'sortOrder' => $this->resource->sort_order,
            'isActive' => $this->resource->is_active,
            'isClosedWon' => $this->resource->is_closed_won,
            'isClosedLost' => $this->resource->is_closed_lost,
            'createdAt' => $this->resource->created_at,
            'updatedAt' => $this->resource->updated_at,
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
            // No relationships for PipelineStage (configuration table)
        ];
    }
}
