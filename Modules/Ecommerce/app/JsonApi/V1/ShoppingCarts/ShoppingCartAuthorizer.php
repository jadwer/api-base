<?php

namespace Modules\Ecommerce\JsonApi\V1\ShoppingCarts;

use Illuminate\Http\Request;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Log;
use LaravelJsonApi\Contracts\Auth\Authorizer;

class ShoppingCartAuthorizer implements Authorizer
{
    /**
     * Check if user owns the shopping cart
     */
    private function isOwner(object $model, $user): bool
    {
        if (!$user) {
            return false;
        }
        return isset($model->user_id) && $model->user_id === $user->id;
    }

    public function index(Request $request, string $modelClass): bool|Response
    {
        $user = $request->user();
        if (!$user) {
            return false;
        }

        // Admin/god/tech can list all shopping carts
        if ($user->hasAnyRole(['god', 'admin', 'tech'])) {
            return true;
        }

        // Customer needs permission but will be filtered by ownership in query
        return $user->can('ecommerce.shopping-carts.index');
    }

    public function store(Request $request, string $modelClass): bool|Response
    {
        $user = $request->user();
        if (!$user) {
            return false;
        }

        // Admin/god/tech can create shopping carts
        if ($user->hasAnyRole(['god', 'admin', 'tech'])) {
            return true;
        }

        // Customer can create their own cart
        return $user->can('ecommerce.shopping-carts.store');
    }

    public function show(Request $request, object $model): bool|Response
    {
        $user = $request->user();
        if (!$user) {
            return false;
        }

        // Admin/god/tech can view any shopping cart
        if ($user->hasAnyRole(['god', 'admin', 'tech'])) {
            return true;
        }

        // Customer can only view their own cart
        if ($this->isOwner($model, $user)) {
            return true;
        }

        return Response::deny('You can only view your own shopping cart.');
    }

    public function update(Request $request, object $model): bool|Response
    {
        $user = $request->user();
        if (!$user) {
            return false;
        }

        // Admin/god/tech can update any shopping cart
        if ($user->hasAnyRole(['god', 'admin', 'tech'])) {
            return true;
        }

        // Customer can only update their own cart
        if ($this->isOwner($model, $user)) {
            return true;
        }

        return Response::deny('You can only update your own shopping cart.');
    }

    public function destroy(Request $request, object $model): bool|Response
    {
        $user = $request->user();
        if (!$user) {
            return false;
        }

        // Admin/god/tech can delete any shopping cart
        if ($user->hasAnyRole(['god', 'admin', 'tech'])) {
            return true;
        }

        // Customer can only delete their own cart
        if ($this->isOwner($model, $user)) {
            return true;
        }

        return Response::deny('You can only delete your own shopping cart.');
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
