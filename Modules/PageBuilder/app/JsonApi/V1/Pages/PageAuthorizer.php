<?php

namespace Modules\PageBuilder\JsonApi\V1\Pages;

use Illuminate\Auth\Access\Response;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Auth\Authorizer;
use Modules\PageBuilder\Models\Page;

class PageAuthorizer implements Authorizer
{
    /**
     * Autoriza el listado de páginas.
     * Permite acceso total solo si el usuario tiene el permiso `page.index`.
     * Si no lo tiene o no está autenticado, podrá acceder a la ruta,
     * pero los resultados estarán filtrados por `published_at` desde el controlador o query builder.
     */
    public function index(Request $request, string $modelClass): bool|Response
    {
        return true;
    }

    /**
     * Permite guardar una página solo a usuarios con permisos.
     */
    public function store(Request $request, string $modelClass): bool|Response
    {
        return $request->user()?->can('page.store') ?? false;
    }

    /**
     * Permite ver una página publicada sin login, o cualquier página si tiene permisos.
     */
    public function show(Request $request, object $model): bool|Response
    {
        /** @var Page $page */
        $page = $model;

        if ($request->user()?->can('page.show')) {
            return true;
        }

        return $page->published_at !== null;
    }

    /**
     * Solo usuarios con permiso pueden editar.
     */
    public function update(Request $request, object $model): bool|Response
    {
        return $request->user()?->can('page.update') ?? false;
    }

    /**
     * Solo usuarios con permiso pueden eliminar.
     */
    public function destroy(Request $request, object $model): bool|Response
    {
        return $request->user()?->can('page.destroy') ?? false;
    }

    /**
     * Mostrar relaciones → misma regla que `show`.
     */
    public function showRelated(Request $request, object $model, string $fieldName): bool|Response
    {
        return $this->show($request, $model);
    }

    public function showRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return $this->show($request, $model);
    }

    /**
     * Solo editores pueden cambiar relaciones.
     */
    public function updateRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return $request->user()?->can('page.update') ?? false;
    }

    public function attachRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return $request->user()?->can('page.update') ?? false;
    }

    public function detachRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return $request->user()?->can('page.update') ?? false;
    }
}
