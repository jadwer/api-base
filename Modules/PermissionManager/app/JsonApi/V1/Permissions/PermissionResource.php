<?php

namespace Modules\PermissionManager\JsonApi\V1\Permissions;

use Modules\PermissionManager\Models\Permission;
use Illuminate\Http\Request;
use LaravelJsonApi\Core\Resources\JsonApiResource;

/**
 * @property Permission $resource
 */
class PermissionResource extends JsonApiResource
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
            'guard_name' => $this->resource->guard_name,
            // El Resource manual PISA al Schema: los campos del catalogo
            // deben declararse tambien aqui o jamas salen en la API.
            'label' => $this->resource->label,
            'description' => $this->resource->description,
            'module' => $this->resource->module,
            'moduleLabel' => $this->resource->module_label,
            'resource' => $this->resource->resource,
            'resourceLabel' => $this->resource->resource_label,
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
            // @TODO
        ];
    }
}
