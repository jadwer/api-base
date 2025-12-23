<?php

namespace Modules\Ecommerce\JsonApi\V1\CartItems;

use Illuminate\Http\Request;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Log;
use LaravelJsonApi\Contracts\Auth\Authorizer;

class CartItemAuthorizer implements Authorizer
{
    /**
     * Check if user owns the cart item (through shopping cart ownership)
     */
    private function isOwner(object $model, $user): bool
    {
        if (!$user || !$model->shoppingCart) {
            return false;
        }
        return $model->shoppingCart->user_id === $user->id;
    }

    public function index(Request $request, string $modelClass): bool|Response
    {
        $user = $request->user();
        if (!$user) {
            return false;
        }

        // Admin/god/tech can list all cart items
        if ($user->hasAnyRole(['god', 'admin', 'tech'])) {
            return true;
        }

        // Customer needs permission but will be filtered by ownership in query
        return $user->can('ecommerce.cart-items.index');
    }

    public function store(Request $request, string $modelClass): bool|Response
    {
        $user = $request->user();
        if (!$user) {
            return false;
        }

        // Admin/god/tech can create cart items
        if ($user->hasAnyRole(['god', 'admin', 'tech'])) {
            return true;
        }

        // Customer can only create items in their own cart (validation handled in request)
        return $user->can('ecommerce.cart-items.store');
    }

    public function show(Request $request, object $model): bool|Response
    {
        $user = $request->user();
        if (!$user) {
            return false;
        }

        // Admin/god/tech can view any cart item
        if ($user->hasAnyRole(['god', 'admin', 'tech'])) {
            return true;
        }

        // Customer can only view their own cart items
        if ($this->isOwner($model, $user)) {
            return true;
        }

        return Response::deny('You can only view your own cart items.');
    }

    public function update(Request $request, object $model): bool|Response
    {
        $user = $request->user();
        if (!$user) {
            return false;
        }

        // Admin/god/tech can update any cart item
        if ($user->hasAnyRole(['god', 'admin', 'tech'])) {
            return true;
        }

        // Customer can only update their own cart items
        if ($this->isOwner($model, $user)) {
            return true;
        }

        return Response::deny('You can only update your own cart items.');
    }

    public function destroy(Request $request, object $model): bool|Response
    {
        $user = $request->user();
        if (!$user) {
            return false;
        }

        // Admin/god/tech can delete any cart item
        if ($user->hasAnyRole(['god', 'admin', 'tech'])) {
            return true;
        }

        // Customer can only delete their own cart items
        if ($this->isOwner($model, $user)) {
            return true;
        }

        return Response::deny('You can only delete your own cart items.');
    }
    
    public function showRelated(Request $request, object $model, string $fieldName): bool|Response
    {
        return $this->show($request, $model);
    }
    
    public function showRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return $this->show($request, $model);
    }
    
    public function updateRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return $this->update($request, $model);
    }
    
    public function attachRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return $this->update($request, $model);
    }
    
    public function detachRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return $this->update($request, $model);
    }
}
