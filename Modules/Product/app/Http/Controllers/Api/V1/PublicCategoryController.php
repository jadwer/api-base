<?php

namespace Modules\Product\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use LaravelJsonApi\Laravel\Http\Controllers\Actions;

/**
 * Public read-only categories for the public catalog navigation
 * (footer / product menu). Guests cannot reach /api/v1/categories,
 * which requires authentication.
 */
class PublicCategoryController extends Controller
{
    use Actions\FetchMany;
    use Actions\FetchOne;
}
