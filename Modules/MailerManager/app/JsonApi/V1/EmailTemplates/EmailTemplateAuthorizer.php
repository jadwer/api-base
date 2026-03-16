<?php

namespace Modules\MailerManager\JsonApi\V1\EmailTemplates;

use Illuminate\Auth\Access\Response;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Auth\Authorizer;

class EmailTemplateAuthorizer implements Authorizer
{
    public function index(Request $request, string $modelClass): bool|Response
    {
        return $request->user()?->can('email-templates.index') ?? false;
    }

    public function store(Request $request, string $modelClass): bool|Response
    {
        return $request->user()?->can('email-templates.store') ?? false;
    }

    public function show(Request $request, object $model): bool|Response
    {
        return $request->user()?->can('email-templates.show') ?? false;
    }

    public function update(Request $request, object $model): bool|Response
    {
        return $request->user()?->can('email-templates.update') ?? false;
    }

    public function destroy(Request $request, object $model): bool|Response
    {
        return $request->user()?->can('email-templates.destroy') ?? false;
    }

    public function showRelated(Request $request, object $model, string $fieldName): bool|Response
    {
        return $request->user()?->can('email-templates.show') ?? false;
    }

    public function showRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return $request->user()?->can('email-templates.show') ?? false;
    }

    public function updateRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return $request->user()?->can('email-templates.update') ?? false;
    }

    public function attachRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return $request->user()?->can('email-templates.update') ?? false;
    }

    public function detachRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return $request->user()?->can('email-templates.update') ?? false;
    }
}
