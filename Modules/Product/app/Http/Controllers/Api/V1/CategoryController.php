<?php

namespace Modules\Product\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use LaravelJsonApi\Laravel\Http\Controllers\Actions;
use Illuminate\Database\QueryException;
use LaravelJsonApi\Core\Responses\ErrorResponse;
use LaravelJsonApi\Core\Document\Error;
use LaravelJsonApi\Contracts\Routing\Route;
use LaravelJsonApi\Contracts\Store\Store;

class CategoryController extends Controller
{

    use Actions\FetchMany;
    use Actions\FetchOne;
    use Actions\Store;
    use Actions\Update;
    use Actions\Destroy {
        destroy as protected destroyAction;
    }
    use Actions\FetchRelated;
    use Actions\FetchRelationship;
    use Actions\UpdateRelationship;
    use Actions\AttachRelationship;
    use Actions\DetachRelationship;

    /**
     * Delete a category with proper error handling for foreign key constraints.
     */
    public function destroy(Route $route, Store $store)
    {
        try {
            return $this->destroyAction($route, $store);
        } catch (QueryException $e) {
            // Handle foreign key constraint violation (Error code 23000)
            if ($e->getCode() === '23000') {
                $error = Error::make()
                    ->setStatus('409')
                    ->setTitle('Cannot Delete Category')
                    ->setDetail('This category cannot be deleted because it has associated products. Please remove or reassign the products first.')
                    ->setCode('FOREIGN_KEY_CONSTRAINT');

                return ErrorResponse::make($error);
            }
            
            // Re-throw other database exceptions
            throw $e;
        }
    }

}
