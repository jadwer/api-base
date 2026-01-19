<?php

namespace Modules\Billing\JsonApi\V1\InvoiceSeries;

use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\Boolean;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;
use Modules\Billing\Models\InvoiceSeries;

/**
 * SA-M011: Invoice Series JSON:API Schema
 */
class InvoiceSeriesSchema extends Schema
{
    /**
     * The model the schema corresponds to.
     */
    public static string $model = InvoiceSeries::class;

    /**
     * Get the resource fields.
     */
    public function fields(): array
    {
        return [
            ID::make(),

            // Identification
            Str::make('code'),
            Str::make('name'),
            Str::make('description'),
            Str::make('cfdiType', 'cfdi_type'),

            // Folio tracking
            Number::make('currentFolio', 'current_folio'),
            Number::make('initialFolio', 'initial_folio'),
            Number::make('folioPadding', 'folio_padding'),

            // Format options
            Boolean::make('includeYear', 'include_year'),
            Str::make('yearFormat', 'year_format'),
            Str::make('separator'),

            // Behavior
            Boolean::make('isActive', 'is_active'),
            Boolean::make('isDefault', 'is_default'),
            Boolean::make('resetYearly', 'reset_yearly'),
            Number::make('lastResetYear', 'last_reset_year'),

            // Restrictions
            Str::make('allowedRoles', 'allowed_roles'),
            Str::make('sourceType', 'source_type'),

            // Computed
            Str::make('cfdiTypeLabel', 'cfdi_type_label')->readOnly(),

            DateTime::make('createdAt')->readOnly(),
            DateTime::make('updatedAt')->readOnly(),

            // Relationships
            BelongsTo::make('companySetting'),
        ];
    }

    /**
     * Get the resource filters.
     */
    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            Where::make('cfdiType', 'cfdi_type'),
            Where::make('isActive', 'is_active')->asBoolean(),
            Where::make('isDefault', 'is_default')->asBoolean(),
            Where::make('sourceType', 'source_type'),
            Where::make('companySettingId', 'company_setting_id'),
        ];
    }

    /**
     * Get the resource paginator.
     */
    public function pagination(): ?Paginator
    {
        return PagePagination::make()
            ->withDefaultPerPage(25)
            ->withMaxPerPage(100);
    }
}
