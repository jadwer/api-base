<?php

namespace Modules\User\JsonApi\V1\Users;

use LaravelJsonApi\Eloquent\Schema;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsToMany;
use Modules\User\Models\User;

class UserSchema extends Schema
{
    public static string $model = User::class;

    public function fields(): array
    {
        return [
            ID::make(),
            Str::make('name')->sortable(),
            Str::make('email')->sortable(),
            Str::make('status'),
            Str::make('role')
                ->readOnly()
                ->serializeUsing(function ($model, $column) {
                    // Devolver el primer rol del usuario
                    return $model->getRoleNames()->first();
                }),
            BelongsToMany::make('roles')->type('roles'),
            Str::make('password')->hidden(),
            Str::make('password_confirmation')->hidden(),
            DateTime::make('email_verified_at')->readOnly(),
            DateTime::make('created_at')->readOnly()->sortable(),
            DateTime::make('updated_at')->readOnly(),
            DateTime::make('deleted_at')->readOnly(),
        ];
    }

    public function filters(): array
    {
        return [];
    }

    public function includePaths(): array
    {
        return [];
    }

    public static function type(): string
    {
        return 'users';
    }
    
    public function with(): array
    {
        return ['roles'];
    }
}
