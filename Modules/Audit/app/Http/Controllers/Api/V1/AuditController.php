<?php

namespace Modules\Audit\Http\Controllers\Api\V1;

use LaravelJsonApi\Laravel\Http\Controllers\JsonApiController;
use LaravelJsonApi\Contracts\Routing\Route;
use LaravelJsonApi\Contracts\Store\Store;
use Spatie\Activitylog\Models\Activity;

class AuditController extends JsonApiController
{
    /*
    public function index(Route $route, Store $store)
    {
        $this->authorize('viewAny', Activity::class);
        return parent::index($route, $store);
    }
        */
}
