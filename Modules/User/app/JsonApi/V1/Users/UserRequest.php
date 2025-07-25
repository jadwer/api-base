<?php

namespace Modules\User\JsonApi\V1\Users;

use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use LaravelJsonApi\Validation\Rule as JsonApiRule;
use Spatie\Permission\Models\Role;
use Modules\User\Models\User;

class UserRequest extends ResourceRequest
{
    public function rules(): array
    {
        $user = $this->model();

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user),
            ],
            'password' => $user
                ? ['nullable', 'string', 'min:8']
                : ['required', 'string', 'min:8'],
            'status' => ['required', Rule::in(['active', 'inactive', 'banned'])],
            'roles' => JsonApiRule::toMany(),
        ];
    }

    public function withDefaults(): array
    {
        return [
            'status' => 'active',
        ];
    }
}
