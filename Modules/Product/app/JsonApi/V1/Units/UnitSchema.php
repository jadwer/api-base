<?php

namespace Modules\Product\JsonApi\V1\Units;

use LaravelJsonApi\Eloquent\Schema;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use Modules\Product\Models\Unit;

class UnitSchema extends Schema
{
    public static string $model = Unit::class;

    public function fields(): array
    {
        return [
            ID::make(),
            Str::make('name')->sortable(),
            Str::make('code')->sortable(),
            Str::make('unitType', 'unit_type')->sortable(),
            DateTime::make('created_at')->readOnly()->sortable(),
            DateTime::make('updated_at')->readOnly(),
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
        return 'units';
    }
}
