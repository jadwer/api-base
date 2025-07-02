<?php

namespace Modules\PageBuilder\JsonApi\V1\Pages;

use Illuminate\Auth\Access\Response;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Auth\Authorizer;

class PageAuthorizer implements Authorizer
{

    /**
     * Authorize the index controller action.
     *
     * @param Request $request
     * @param string $modelClass
     * @return bool|Response
     */
    public function index(Request $request, string $modelClass): bool|Response
    {
        return $request->user()?->can('page.index') ?? false;
    }

    /**
     * Authorize the store controller action.
     *
     * @param Request $request
     * @param string $modelClass
     * @return bool|Response
     */
    public function store(Request $request, string $modelClass): bool|Response
    {
        return $request->user()?->can('page.store') ?? false;
    }

    /**
     * Authorize the show controller action.
     *
     * @param Request $request
     * @param object $model
     * @return bool|Response
     */
    public function show(Request $request, object $model): bool|Response
    {
        return $request->user()?->can('page.show') ?? false;
    }

    /**
     * Authorize the update controller action.
     *
     * @param object $model
     * @param Request $request
     * @return bool|Response
     */
    public function update(Request $request, object $model): bool|Response
    {
        return $request->user()?->can('page.update') ?? false;
    }

    /**
     * Authorize the destroy controller action.
     *
     * @param Request $request
     * @param object $model
     * @return bool|Response
     */
    public function destroy(Request $request, object $model): bool|Response
    {
        return $request->user()?->can('page.destroy') ?? false;
    }

    /**
     * Authorize the show-related controller action
     *
     * @param Request $request
     * @param object $model
     * @param string $fieldName
     * @return bool|Response
     */
    public function showRelated(Request $request, object $model, string $fieldName): bool|Response
    {
        return $request->user()?->can('page.show') ?? false;
    }

    /**
     * Authorize the show-relationship controller action.
     *
     * @param Request $request
     * @param object $model
     * @param string $fieldName
     * @return bool|Response
     */
    public function showRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return $request->user()?->can('page.show') ?? false;
    }

    /**
     * Authorize the update-relationship controller action.
     *
     * @param Request $request
     * @param object $model
     * @param string $fieldName
     * @return bool|Response
     */
    public function updateRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return $request->user()?->can('page.update') ?? false;
    }

    /**
     * Authorize the attach-relationship controller action.
     *
     * @param Request $request
     * @param object $model
     * @param string $fieldName
     * @return bool|Response
     */
    public function attachRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return $request->user()?->can('page.update') ?? false;
    }

    /**
     * Authorize the detach-relationship controller action.
     *
     * @param Request $request
     * @param object $model
     * @param string $fieldName
     * @return bool|Response
     */
    public function detachRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return $request->user()?->can('page.update') ?? false;
    }

}
