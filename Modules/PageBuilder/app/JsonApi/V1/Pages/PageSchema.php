<?php

namespace Modules\PageBuilder\JsonApi\V1\Pages;

use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ArrayHash;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;


use Modules\PageBuilder\Models\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class PageSchema extends Schema
{
    /**
     * The model the schema corresponds to.
     *
     * @var string
     */
    public static string $model = Page::class;

    protected int $maxDepth = 3;

    /**
     * Get the resource fields.
     *
     * @return array
     */
    public function fields(): array
    {
        return [
            ID::make(),
            Str::make('title')->sortable(),
            Str::make('slug'),
            Str::make('html'),
            Str::make('css'),
            ArrayHash::make('json'),
            Str::make('status')->sortable(),
            DateTime::make('publishedAt')->sortable(),
            BelongsTo::make('user'),
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
            Where::make('slug'),
            Where::make('status'),
        ];
    }

     public function indexQuery(?Request $request, Builder $query): Builder
    {
        $user = $request->user();

        if ($user?->can('page.index')) {
            return $query;
        }

        return $query->whereNotNull('published_at');
    }

}
