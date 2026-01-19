<?php

namespace Modules\Billing\JsonApi\V1\InvoiceSeries;

use LaravelJsonApi\Contracts\Auth\Authorizer as AuthorizerContract;
use Illuminate\Http\Request;
use Modules\Billing\Models\InvoiceSeries;

/**
 * SA-M011: Invoice Series Authorizer
 */
class InvoiceSeriesAuthorizer implements AuthorizerContract
{
    public function index(Request $request, string $modelClass): bool
    {
        return $request->user()?->can('billing.invoice-series.index') ?? false;
    }

    public function show(Request $request, object $model): bool
    {
        return $request->user()?->can('billing.invoice-series.show') ?? false;
    }

    public function store(Request $request, string $modelClass): bool
    {
        return $request->user()?->can('billing.invoice-series.store') ?? false;
    }

    public function update(Request $request, object $model): bool
    {
        return $request->user()?->can('billing.invoice-series.update') ?? false;
    }

    public function destroy(Request $request, object $model): bool
    {
        return $request->user()?->can('billing.invoice-series.destroy') ?? false;
    }

    public function showRelated(Request $request, object $model, string $fieldName): bool
    {
        return $request->user()?->can('billing.invoice-series.show') ?? false;
    }

    public function showRelationship(Request $request, object $model, string $fieldName): bool
    {
        return $request->user()?->can('billing.invoice-series.show') ?? false;
    }

    public function updateRelationship(Request $request, object $model, string $fieldName): bool
    {
        return $request->user()?->can('billing.invoice-series.update') ?? false;
    }

    public function attachRelationship(Request $request, object $model, string $fieldName): bool
    {
        return $request->user()?->can('billing.invoice-series.update') ?? false;
    }

    public function detachRelationship(Request $request, object $model, string $fieldName): bool
    {
        return $request->user()?->can('billing.invoice-series.update') ?? false;
    }
}
