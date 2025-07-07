<?php

namespace Modules\User\JsonApi\V1\Users;

use LaravelJsonApi\Core\Resources\JsonApiResource;

class UserResource extends JsonApiResource
{
    public function attributes($request): iterable
    {
        return [
            'name'             => $this->name,
            'email'            => $this->email,
            'status'           => $this->status,
            'emailVerifiedAt'  => $this->email_verified_at,
            'createdAt'        => $this->created_at,
            'updatedAt'        => $this->updated_at,
            'deletedAt'        => $this->deleted_at,
            'role'             => $this->getRoleNames()->first(),
        ];
    }

    public function relationships($request): iterable
    {
        return [];
    }
}
