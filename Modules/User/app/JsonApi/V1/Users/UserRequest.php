<?php

namespace Modules\User\JsonApi\V1\Users;

use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

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
        ];
    }
}
