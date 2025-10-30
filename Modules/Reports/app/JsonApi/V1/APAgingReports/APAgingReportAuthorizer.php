<?php

namespace Modules\Reports\JsonApi\V1\APAgingReports;

use Illuminate\Http\Request;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Log;
use LaravelJsonApi\Contracts\Auth\Authorizer;

class APAgingReportAuthorizer implements Authorizer
{
    /**
     * Authorize viewing balance sheet list
     *
     * @param Request $request
     * @param string $modelClass
     * @return bool|Response
     */
    public function index(Request $request, string $modelClass): bool|Response
    {
        Log::info('APAgingReportAuthorizer@index called', [
            'user_id' => $request->user()?->id,
            'model_class' => $modelClass,
        ]);

        $user = $request->user();

        // Only admin, finance, and tech roles can view financial reports
        return $user?->hasAnyRole(['god', 'admin', 'tech']) ?? false;
    }

    /**
     * Authorize viewing a specific balance sheet
     *
     * @param Request $request
     * @param object $model
     * @return bool|Response
     */
    public function show(Request $request, object $model): bool|Response
    {
        $user = $request->user();

        // Only admin, finance, and tech roles can view financial reports
        return $user?->hasAnyRole(['god', 'admin', 'tech']) ?? false;
    }

    /**
     * Reports cannot be created directly
     *
     * @param Request $request
     * @param string $modelClass
     * @return bool|Response
     */
    public function store(Request $request, string $modelClass): bool|Response
    {
        // Balance sheets are generated, not created
        return false;
    }

    /**
     * Reports cannot be updated
     *
     * @param Request $request
     * @param object $model
     * @return bool|Response
     */
    public function update(Request $request, object $model): bool|Response
    {
        // Balance sheets are generated, not edited
        return false;
    }

    /**
     * Reports cannot be deleted
     *
     * @param Request $request
     * @param object $model
     * @return bool|Response
     */
    public function destroy(Request $request, object $model): bool|Response
    {
        // Balance sheets cannot be deleted
        return false;
    }

    /**
     * Authorize viewing related resources
     *
     * @param Request $request
     * @param object $model
     * @param string $fieldName
     * @return bool|Response
     */
    public function showRelated(Request $request, object $model, string $fieldName): bool|Response
    {
        return $this->show($request, $model);
    }

    /**
     * Authorize viewing relationships
     *
     * @param Request $request
     * @param object $model
     * @param string $fieldName
     * @return bool|Response
     */
    public function showRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return $this->show($request, $model);
    }

    /**
     * Reports don't support relationship updates
     *
     * @param Request $request
     * @param object $model
     * @param string $fieldName
     * @return bool|Response
     */
    public function updateRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return false;
    }

    /**
     * Reports don't support attaching relationships
     *
     * @param Request $request
     * @param object $model
     * @param string $fieldName
     * @return bool|Response
     */
    public function attachRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return false;
    }

    /**
     * Reports don't support detaching relationships
     *
     * @param Request $request
     * @param object $model
     * @param string $fieldName
     * @return bool|Response
     */
    public function detachRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return false;
    }
}
