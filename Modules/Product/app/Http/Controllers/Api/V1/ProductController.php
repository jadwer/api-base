<?php

namespace Modules\Product\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use LaravelJsonApi\Laravel\Http\Controllers\Actions;
use Illuminate\Database\QueryException;
use LaravelJsonApi\Core\Responses\ErrorResponse;
use LaravelJsonApi\Core\Document\Error;
use LaravelJsonApi\Contracts\Routing\Route;
use LaravelJsonApi\Contracts\Store\Store;

class ProductController extends Controller
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
     * Un producto con historia (movimientos, compras, cotizaciones, fraccionamientos o
     * conversiones; FK restrict) no se borra: 409 en espanol para que la pantalla ofrezca
     * desactivarlo (rediseno de Productos, 17-11). Antes respondia 500.
     */
    public function destroy(Route $route, Store $store)
    {
        try {
            return $this->destroyAction($route, $store);
        } catch (\Exception $e) {
            // La FK la lanza el store como QueryException (codigo SQLSTATE 23000)
            if ($e instanceof QueryException && $e->getCode() === '23000') {
                return ErrorResponse::make(Error::make()
                    ->setStatus('409')
                    ->setTitle('No se puede eliminar el producto')
                    ->setDetail('El producto tiene historia (movimientos, compras o cotizaciones): desactívalo en lugar de eliminarlo para conservar sus registros.')
                    ->setCode('FOREIGN_KEY_CONSTRAINT'));
            }

            throw $e;
        }
    }
}
