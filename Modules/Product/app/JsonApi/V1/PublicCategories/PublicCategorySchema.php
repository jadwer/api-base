<?php

namespace Modules\Product\JsonApi\V1\PublicCategories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use LaravelJsonApi\Eloquent\Schema;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Fields\Boolean;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use Modules\Product\Models\Product;
use Modules\Product\Models\PublicCategory;

/**
 * Read-only categories for the public catalog navigation (footer / menu).
 * Separate from CategorySchema so the public server can expose it without the
 * authenticated authorizer that returns 401 to guests. Same model, only the
 * fields the navigation needs.
 */
class PublicCategorySchema extends Schema
{
    public static string $model = PublicCategory::class;

    /**
     * Only active categories are shown in the public navigation. The count
     * only includes products the public catalog actually shows: the model
     * accessor counts every product, private ones included.
     */
    public function indexQuery(?Request $request, Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->withCount(['products as public_products_count' => fn($q) => Product::wherePubliclyVisible($q)]);
    }

    public function fields(): array
    {
        return [
            ID::make(),
            Str::make('name')->sortable(),
            Str::make('description'),
            Str::make('slug')->sortable(),
            Boolean::make('isActive', 'is_active')->sortable(),
            Number::make('productsCount', 'public_products_count')->readOnly()->extractUsing(
                fn(PublicCategory $category) => $category->public_products_count
                    ?? Product::wherePubliclyVisible($category->products()->getQuery())->count()
            ),
            // updatedAt alimenta el lastmod del sitemap de las tiendas.
            DateTime::make('createdAt', 'created_at')->readOnly(),
            DateTime::make('updatedAt', 'updated_at')->readOnly(),
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
