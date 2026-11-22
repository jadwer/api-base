<?php

namespace Modules\Product\JsonApi\V1\PublicProducts;

use Illuminate\Auth\Access\Response;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Auth\Authorizer;

class PublicProductAuthorizer implements Authorizer
{
    /**
     * Authorize the index controller action.
     * Allow public access for catalog browsing.
     */
    public function index(Request $request, string $modelClass): bool|Response
    {
        return true;
    }

    /**
     * Authorize the store controller action.
     * Not available for public catalog.
     */
    public function store(Request $request, string $modelClass): bool|Response
    {
        return false;
    }

    /**
     * Authorize the show controller action.
     * Publico, pero con la misma regla del listado: un producto interno,
     * inactivo o de marca o categoria desactivada no se consulta por id
     * (SEG-04, 22-11; el filtro solo actuaba en el index).
     */
    public function show(Request $request, object $model): bool|Response
    {
        return \Modules\Product\Models\Product::wherePubliclyVisible(\Modules\Product\Models\Product::query())
            ->whereKey($model->getKey())
            ->exists();
    }

    /**
     * Authorize the update controller action.
     * Not available for public catalog.
     */
    public function update(Request $request, object $model): bool|Response
    {
        return false;
    }

    /**
     * Authorize the destroy controller action.
     * Not available for public catalog.
     */
    public function destroy(Request $request, object $model): bool|Response
    {
        return false;
    }

    /**
     * Authorize the show-related controller action
     * Allow public access to related entities (unit, category, brand).
     */
    public function showRelated(Request $request, object $model, string $fieldName): bool|Response
    {
        return $this->show($request, $model);
    }

    /**
     * Authorize the show-relationship controller action.
     * Allow public access to relationship data.
     */
    public function showRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return $this->show($request, $model);
    }

    /**
     * Authorize the update-relationship controller action.
     * Not available for public catalog.
     */
    public function updateRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return false;
    }

    /**
     * Authorize the attach-relationship controller action.
     * Not available for public catalog.
     */
    public function attachRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return false;
    }

    /**
     * Authorize the detach-relationship controller action.
     * Not available for public catalog.
     */
    public function detachRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return false;
    }
}