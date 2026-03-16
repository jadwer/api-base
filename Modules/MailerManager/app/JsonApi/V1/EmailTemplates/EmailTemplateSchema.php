<?php

namespace Modules\MailerManager\JsonApi\V1\EmailTemplates;

use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ArrayHash;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Relations\HasMany;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;
use Modules\MailerManager\Models\EmailTemplate;

class EmailTemplateSchema extends Schema
{
    public static string $model = EmailTemplate::class;

    protected int $maxDepth = 3;

    public function fields(): array
    {
        return [
            ID::make(),
            Str::make('name')->sortable(),
            Str::make('slug'),
            Str::make('description'),
            Str::make('subject'),
            Str::make('html'),
            Str::make('css'),
            ArrayHash::make('json'),
            Str::make('status')->sortable(),
            Str::make('category')->sortable(),
            DateTime::make('createdAt')->sortable()->readOnly(),
            DateTime::make('updatedAt')->sortable()->readOnly(),
            BelongsTo::make('user'),
            HasMany::make('systemEmails'),
        ];
    }

    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            Where::make('slug'),
            Where::make('status'),
            Where::make('category'),
        ];
    }

    public function pagination(): PagePagination
    {
        return PagePagination::make();
    }

    public function includePaths(): iterable
    {
        return [
            'user',
            'systemEmails',
        ];
    }
}
