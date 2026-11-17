<?php

namespace Modules\Product\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use LaravelJsonApi\Laravel\Http\Controllers\Actions;
use Illuminate\Database\QueryException;
use LaravelJsonApi\Core\Responses\ErrorResponse;
use LaravelJsonApi\Core\Document\Error;
use LaravelJsonApi\Contracts\Routing\Route;
use LaravelJsonApi\Contracts\Store\Store;

/**
 * PR-M003: JSON:API Controller for VariantAttribute.
 */
class VariantAttributeController extends Controller
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
     * Delete a variant attribute with proper error handling.
     */
    public function destroy(Route $route, Store $store)
    {
        try {
            return $this->destroyAction($route, $store);
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                $error = Error::make()
                    ->setStatus('409')
                    ->setTitle('No se puede eliminar el atributo')
                    ->setDetail('El atributo tiene valores asociados: elimina primero sus valores.')
                    ->setCode('FOREIGN_KEY_CONSTRAINT');

                return ErrorResponse::make($error);
            }

            throw $e;
        }
    }
}
