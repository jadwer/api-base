<?php

namespace Modules\User\JsonApi\V1\Users;

use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
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
            'role' => ['required', Rule::exists('roles', 'name')],

        ];
    }


    public function creating(User $user): void
    {
        $this->assignRoleToUser($user);
    }

    public function updating(User $user): void
    {
        $this->assignRoleToUser($user);
    }

    protected function assignRoleToUser(User $user): void
    {
        $role = $this->input('role');

        if ($role && Role::where('name', $role)->exists()) {
            $user->syncRoles([$role]);
        }
    }
}
