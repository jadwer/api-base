<?php

namespace Modules\MailerManager\JsonApi\V1\SystemEmails;

use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Fields\Boolean;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ArrayHash;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;
use Modules\MailerManager\Models\SystemEmail;

class SystemEmailSchema extends Schema
{
    public static string $model = SystemEmail::class;

    protected int $maxDepth = 3;

    public function fields(): array
    {
        return [
            ID::make(),
            Str::make('key')->readOnly(),
            Str::make('module')->readOnly(),
            Str::make('name')->readOnly(),
            Str::make('description')->readOnly(),
            Str::make('mailableClass', 'mailable_class')->readOnly(),
            ArrayHash::make('availableVariables', 'available_variables')->readOnly(),
            ArrayHash::make('sampleData', 'sample_data')->readOnly(),
            Boolean::make('isEnabled', 'is_enabled'),
            Str::make('defaultSubject', 'default_subject')->readOnly(),
            DateTime::make('createdAt')->sortable()->readOnly(),
            DateTime::make('updatedAt')->sortable()->readOnly(),
            BelongsTo::make('emailTemplate'),
        ];
    }

    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            Where::make('key'),
            Where::make('module'),
            Where::make('isEnabled', 'is_enabled'),
        ];
    }

    public function pagination(): PagePagination
    {
        return PagePagination::make();
    }

    public function includePaths(): iterable
    {
        return [
            'emailTemplate',
        ];
    }
}
