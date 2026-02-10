<?php

namespace Modules\Contacts\JsonApi\V1\ContactAddresses;

use Illuminate\Http\Request;
use Illuminate\Auth\Access\Response;
use LaravelJsonApi\Contracts\Auth\Authorizer;

class ContactAddressAuthorizer implements Authorizer
{
    public function index(Request $request, string $modelClass): bool|Response
    {
        $user = $request->user();
        if ($user && $user->hasAnyRole(['god', 'admin', 'administrator'])) {
            return true;
        }
        return $user?->can('contact-addresses.index') ?? false;
    }

    public function store(Request $request, string $modelClass): bool|Response
    {
        $user = $request->user();
        if ($user && $user->hasAnyRole(['god', 'admin', 'administrator'])) {
            return true;
        }
        return $user?->can('contact-addresses.store') ?? false;
    }

    public function show(Request $request, object $model): bool|Response
    {
        $user = $request->user();
        if ($user && $user->hasAnyRole(['god', 'admin', 'administrator'])) {
            return true;
        }
        return $user?->can('contact-addresses.show') ?? false;
    }

    public function update(Request $request, object $model): bool|Response
    {
        $user = $request->user();
        if ($user && $user->hasAnyRole(['god', 'admin', 'administrator'])) {
            return true;
        }
        return $user?->can('contact-addresses.update') ?? false;
    }

    public function destroy(Request $request, object $model): bool|Response
    {
        $user = $request->user();
        if ($user && $user->hasAnyRole(['god', 'admin', 'administrator'])) {
            return true;
        }
        return $user?->can('contact-addresses.destroy') ?? false;
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
