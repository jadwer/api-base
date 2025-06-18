<?php

namespace Modules\User\JsonApi\V1\Users;

use LaravelJsonApi\Eloquent\Schema;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use Modules\User\Models\User;

class UserSchema extends Schema
{
    public static string $model = User::class;

    public function fields(): array
    {
        return [
            ID::make(),
            Str::make('name'),
            Str::make('email'),
            Str::make('status'),
            DateTime::make('email_verified_at')->readOnly(),
            DateTime::make('created_at')->readOnly(),
            DateTime::make('updated_at')->readOnly(),
            DateTime::make('deleted_at')->readOnly(),
        ];
    }

    public function filters(): array
    {
        return [];
    }

    public function sortables(): array
    {
        return ['name', 'email', 'created_at'];
    }

    public function includePaths(): array
    {
        return [];
    }
}
