<?php

namespace Modules\User\JsonApi\V1\Users;

use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Schema;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsToMany;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\Scope;
use LaravelJsonApi\Contracts\Pagination\Paginator;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
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
            Str::make('permissionTemplate', 'permission_template')->readOnly(),
            // Primer rol del usuario (extractUsing recibe el modelo; serializeUsing
            // solo recibe el valor y truena con dos parametros).
            Str::make('role')
                ->readOnly()
                ->extractUsing(static fn ($model) => $model->getRoleNames()->first()),
            BelongsToMany::make('roles')->type('roles'),
            // Multi-sucursal 2026-09: principal + con acceso
            BelongsTo::make('branch')->type('branches'),
            BelongsToMany::make('branches')->type('branches'),
            Str::make('password')->hidden(),
            Str::make('password_confirmation')->hidden(),
            // camelCase como las devolvia el Resource manual (el frontend las lee asi).
            DateTime::make('emailVerifiedAt', 'email_verified_at')->readOnly(),
            DateTime::make('createdAt', 'created_at')->readOnly()->sortable(),
            DateTime::make('updatedAt', 'updated_at')->readOnly(),
            DateTime::make('deletedAt', 'deleted_at')->readOnly(),
        ];
    }

    public function filters(): array
    {
        return [
            Scope::make('trashed', 'trashedFilter'),
            Where::make('name')->deserializeUsing(
                static fn($value) => "%{$value}%"
            )->using('like'),
            Where::make('email')->deserializeUsing(
                static fn($value) => "%{$value}%"
            )->using('like'),
            // Users v2: busqueda unificada, rol por nombre y status exacto.
            Scope::make('search', 'searchFilter'),
            Scope::make('role', 'roleFilter'),
            Where::make('status'),
            Where::make('branch', 'branch_id'),
        ];
    }

    /**
     * Users v2: paginacion opcional (SIN defaultPagination a proposito:
     * los consumidores existentes piden la lista completa para selects;
     * la lista nueva siempre manda page[number]/page[size]).
     */
    public function pagination(): ?Paginator
    {
        return PagePagination::make();
    }

    public function includePaths(): array
    {
        return [
            'roles',
            'branch',
            'branches',
        ];
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
