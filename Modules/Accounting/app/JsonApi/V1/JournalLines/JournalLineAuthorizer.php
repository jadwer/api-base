<?php

namespace Modules\Accounting\JsonApi\V1\JournalLines;

use Illuminate\Http\Request;
use Illuminate\Auth\Access\Response;
use LaravelJsonApi\Contracts\Auth\Authorizer;

class JournalLineAuthorizer implements Authorizer
{
    public function index(Request $request, string $modelClass): bool|Response
    {
        $user = $request->user();
        return $user?->can('journal-lines.index') ?? false;
    }

    public function store(Request $request, string $modelClass): bool|Response
    {
        $user = $request->user();
        if (!$user || !$user->can('journal-lines.store')) {
            return false;
        }

        // Agregar lineas a un asiento posteado lo descuadra sin pasar por
        // validateBalance; el asiento posteado es inmutable junto con sus lineas.
        $entryId = $request->input('data.relationships.journalEntry.data.id')
            ?? $request->input('data.attributes.journalEntryId');
        if ($entryId) {
            $entry = \Modules\Accounting\Models\JournalEntry::find($entryId);
            if ($entry && in_array($entry->status, ['posted', 'reversed'], true)) {
                return Response::deny('No se pueden agregar lineas a un asiento posteado o reversado.');
            }
        }

        return true;
    }

    public function show(Request $request, object $model): bool|Response
    {
        $user = $request->user();
        return $user?->can('journal-lines.show') ?? false;
    }

    public function update(Request $request, object $model): bool|Response
    {
        $user = $request->user();
        if (!$user || !$user->can('journal-lines.update')) {
            return false;
        }

        if (in_array($model->journalEntry?->status, ['posted', 'reversed'], true)) {
            return Response::deny('Las lineas de un asiento posteado o reversado no pueden modificarse.');
        }

        return true;
    }

    public function destroy(Request $request, object $model): bool|Response
    {
        $user = $request->user();
        if (!$user || !$user->can('journal-lines.destroy')) {
            return false;
        }

        if (in_array($model->journalEntry?->status, ['posted', 'reversed'], true)) {
            return Response::deny('Las lineas de un asiento posteado o reversado no pueden eliminarse.');
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
