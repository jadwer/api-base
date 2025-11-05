<?php

namespace Modules\CRM\JsonApi\V1\Campaigns;

use Illuminate\Http\Request;
use LaravelJsonApi\Core\Resources\JsonApiResource;

class CampaignResource extends JsonApiResource
{
    /**
     * Get the resource's attributes.
     *
     * @param Request|null $request
     * @return iterable
     */
    public function attributes($request): iterable
    {
        return parent::attributes($request);
    }

    /**
     * Get the resource's relationships.
     *
     * @param Request|null $request
     * @return iterable
     */
    public function relationships($request): iterable
    {
        return parent::relationships($request);
    }
}
