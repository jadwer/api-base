<?php

namespace Modules\Inventory\Http\Controllers\Api\V1;

use Illuminate\Routing\Controller;
use LaravelJsonApi\Laravel\Http\Controllers\Actions;

class FractionationController extends Controller
{
    use Actions\FetchMany;
    use Actions\FetchOne;

    use Actions\FetchRelated;
    use Actions\FetchRelationship;
}
