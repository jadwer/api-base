<?php

namespace Modules\PageBuilder\JsonApi\V1\Pages;

use Modules\PageBuilder\Models\Page;
use Illuminate\Http\Request;
use LaravelJsonApi\Core\Resources\JsonApiResource;

/**
 * @property Page $resource
 */
class PageResource extends JsonApiResource
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
            'title'        => $this->resource->title,
            'slug'         => $this->resource->slug,
            'html'         => $this->resource->html,
            'css'          => $this->resource->css,
            'json'         => $this->resource->json,
            'status'       => $this->resource->status,
            'publishedAt'  => $this->resource->published_at,
            'createdAt'    => $this->resource->created_at,
            'updatedAt'    => $this->resource->updated_at,
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
            'user' => $this->relation('user')->showDataIfLoaded(),

        ];
    }
}
