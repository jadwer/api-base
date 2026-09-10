<?php

namespace Modules\PermissionManager\JsonApi\V1\Permissions;

use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\Scope;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;
use Modules\PermissionManager\Models\Permission;

class PermissionSchema extends Schema
{

    /**
     * The model the schema corresponds to.
     *
     * @var string
     */
    public static string $model = Permission::class;

    /**
     * Get the resource fields.
     *
     * @return array
     */
    public function fields(): array
    {
        return [
            ID::make(),
            Str::make('name')->sortable(),
            Str::make('guard_name'),
            // Catalogo legible (poblado por PermissionCatalogSeeder);
            // readOnly: la fuente es el catalogo, no la API.
            Str::make('label')->sortable()->readOnly(),
            Str::make('description')->readOnly(),
            Str::make('module')->sortable()->readOnly(),
            Str::make('resource')->readOnly(),
            // Derivados del catalogo (accessors, no columnas): solo salida.
            Str::make('moduleLabel')->readOnly(),
            Str::make('resourceLabel')->readOnly(),
            DateTime::make('createdAt')->sortable()->readOnly(),
            DateTime::make('updatedAt')->sortable()->readOnly(),
        ];
    }

    /**
     * Get the resource filters.
     *
     * @return array
     */
    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            Where::make('module'),
            Where::make('resource'),
            Scope::make('search', 'searchFilter'),
        ];
    }

    /**
     * Get the resource paginator.
     *
     * @return Paginator|null
     */
    public function pagination(): ?Paginator
    {
        return PagePagination::make();
    }

}
