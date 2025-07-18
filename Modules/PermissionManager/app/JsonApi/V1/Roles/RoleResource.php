<?php

namespace Modules\PermissionManager\JsonApi\V1\Roles;

use App\Models\Role;
use Illuminate\Http\Request;
use LaravelJsonApi\Core\Resources\JsonApiResource;

/**
 * @property Role $resource
 */
class RoleResource extends JsonApiResource
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
            'description' => $this->resource->description,
            'guard_name' => $this->resource->guard_name,
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
            'permissions' => $this->relation('permissions'),
        ];
        }
}
