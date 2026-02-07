<?php

namespace Modules\Ecommerce\JsonApi\V1\ProductAnswers;

use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\Boolean;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;
use Modules\Ecommerce\Models\ProductAnswer;

class ProductAnswerSchema extends Schema
{
    /**
     * The model the schema corresponds to.
     */
    public static string $model = ProductAnswer::class;

    /**
     * The maximum include path depth.
     */
    protected int $maxDepth = 3;

    /**
     * Get the resource fields.
     */
    public function fields(): array
    {
        return [
            ID::make(),
            Str::make('answer')->sortable(),
            Boolean::make('isVerified', 'is_verified')->sortable(),
            BelongsTo::make('question')->type('product-questions')->readOnly(),
            BelongsTo::make('user')->type('users')->readOnly(),
            DateTime::make('createdAt', 'created_at')->sortable()->readOnly(),
            DateTime::make('updatedAt', 'updated_at')->sortable()->readOnly(),
        ];
    }

    /**
     * Get the resource filters.
     */
    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            Where::make('questionId', 'question_id'),
            Where::make('userId', 'user_id'),
            Where::make('isVerified', 'is_verified'),
        ];
    }

    public function includePaths(): iterable
    {
        return [
            'question',
            'user',
        ];
    }

    /**
     * Get the resource paginator.
     */
    public function pagination(): ?Paginator
    {
        return PagePagination::make();
    }
}
