<?php

namespace Modules\MailerManager\JsonApi\V1\SystemEmails;

use Illuminate\Auth\Access\Response;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Auth\Authorizer;

class SystemEmailAuthorizer implements Authorizer
{
    public function index(Request $request, string $modelClass): bool|Response
    {
        return $request->user()?->can('system-emails.index') ?? false;
    }

    public function store(Request $request, string $modelClass): bool|Response
    {
        return false;
    }

    public function show(Request $request, object $model): bool|Response
    {
        return $request->user()?->can('system-emails.show') ?? false;
    }

    public function update(Request $request, object $model): bool|Response
    {
        return $request->user()?->can('system-emails.update') ?? false;
    }

    public function destroy(Request $request, object $model): bool|Response
    {
        return false;
    }

    public function showRelated(Request $request, object $model, string $fieldName): bool|Response
    {
        return $request->user()?->can('system-emails.show') ?? false;
    }

    public function showRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return $request->user()?->can('system-emails.show') ?? false;
    }

    public function updateRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return $request->user()?->can('system-emails.update') ?? false;
    }

    public function attachRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return $request->user()?->can('system-emails.update') ?? false;
    }

    public function detachRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return $request->user()?->can('system-emails.update') ?? false;
    }
}
