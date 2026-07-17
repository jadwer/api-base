<?php

namespace Modules\Product\JsonApi\V1\PublicCategories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use LaravelJsonApi\Eloquent\Schema;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Fields\Boolean;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use Modules\Product\Models\Category;

/**
 * Read-only categories for the public catalog navigation (footer / menu).
 * Separate from CategorySchema so the public server can expose it without the
 * authenticated authorizer that returns 401 to guests. Same model, only the
 * fields the navigation needs.
 */
class PublicCategorySchema extends Schema
{
    public static string $model = Category::class;

    /**
     * Only active categories are shown in the public navigation.
     */
    public function indexQuery(?Request $request, Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function fields(): array
    {
        return [
            ID::make(),
            Str::make('name')->sortable(),
            Str::make('description'),
            Str::make('slug')->sortable(),
            Boolean::make('isActive', 'is_active')->sortable(),
            Number::make('productsCount')->readOnly(),
        ];
    }

    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            Where::make('name'),
            Where::make('slug'),
            Where::make('is_active')->asBoolean(),
        ];
    }

    public function pagination(): ?Paginator
    {
        return PagePagination::make();
    }

    public static function type(): string
    {
        return 'public-categories';
    }

    public static function authorizer(): string
    {
        return PublicCategoryAuthorizer::class;
    }
}
