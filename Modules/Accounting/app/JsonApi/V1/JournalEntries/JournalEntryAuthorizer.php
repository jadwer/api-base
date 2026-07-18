<?php

namespace Modules\Accounting\JsonApi\V1\JournalEntries;

use Illuminate\Http\Request;
use Illuminate\Auth\Access\Response;
use LaravelJsonApi\Contracts\Auth\Authorizer;

class JournalEntryAuthorizer implements Authorizer
{
    public function index(Request $request, string $modelClass): bool|Response
    {
        $user = $request->user();
        return $user?->can('journal-entries.index') ?? false;
    }

    public function store(Request $request, string $modelClass): bool|Response
    {
        $user = $request->user();
        return $user?->can('journal-entries.store') ?? false;
    }
    
    public function show(Request $request, object $model): bool|Response
    {
        $user = $request->user();
        return $user?->can('journal-entries.show') ?? false;
    }
    
    public function update(Request $request, object $model): bool|Response
    {
        $user = $request->user();
        if (!$user || !$user->can('journal-entries.update')) {
            return false;
        }

        // Un asiento posteado es inmutable: modificarlo rompe la integridad
        // contable (folio, balance validado, periodo). Las reversas van por
        // reversal_of_id en un asiento nuevo, no editando el original.
        if (in_array($model->status, ['posted', 'reversed'], true)) {
            return Response::deny('Un asiento posteado o reversado no puede modificarse. Use una reversa.');
        }

        return true;
    }

    public function destroy(Request $request, object $model): bool|Response
    {
        $user = $request->user();
        if (!$user || !$user->can('journal-entries.destroy')) {
            return false;
        }

        if (in_array($model->status, ['posted', 'reversed'], true)) {
            return Response::deny('Un asiento posteado o reversado no puede eliminarse.');
        }

        return true;
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
