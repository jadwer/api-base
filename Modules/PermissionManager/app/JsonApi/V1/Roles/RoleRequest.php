<?php

namespace Modules\PermissionManager\JsonApi\V1\Roles;

use Illuminate\Validation\Rule;
use LaravelJsonApi\Core\Responses\DataResponse;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use LaravelJsonApi\Validation\Rule as JsonApiRule;
use Modules\PermissionManager\Models\Permission;

class RoleRequest extends ResourceRequest
{
    public function rules(): array
    {
        $role = $this->model();
        $uniqueName = Rule::unique('roles', 'name');

        if ($role) {
            $uniqueName->ignoreModel($role);
        }

        return [
            'name' => ['required', 'string', $uniqueName],
            'description' => ['nullable', 'string'],
            'guard_name' => ['required', Rule::in(['api'])],
            'permissions' => JsonApiRule::toMany(),
        ];
    }
}
