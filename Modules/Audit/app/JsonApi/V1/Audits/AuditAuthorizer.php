<?php


namespace Modules\Audit\JsonApi\V1\Audits;

use Spatie\Activitylog\Models\Activity;

use Illuminate\Auth\Access\Response;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Auth\Authorizer;
use Modules\User\Models\User;

class AuditAuthorizer implements Authorizer
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
    $user = $request->user();

        if (!$user) {
            return false;
        }


        return $user->hasAnyRole(['admin', 'god']);
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
        // TODO: Implement store() method.
        return false;
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
        $user = $request->user();

        if (!$user) {
            return false;
        }

        return $user->hasAnyRole(['admin', 'god']);
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
        // TODO: Implement update() method.
        return false;
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
        // TODO: Implement destroy() method.
        return true;
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
        // TODO: Implement showRelated() method.
        return true;
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
        // TODO: Implement showRelationship() method.
        return true;
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
        // TODO: Implement updateRelationship() method.
        return true;
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
        // TODO: Implement attachRelationship() method.
        return true;
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
        // TODO: Implement detachRelationship() method.
        return true;
    }
}
