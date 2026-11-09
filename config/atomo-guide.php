<?php

/**
 * Configuracion de atomo/guide en este host (sobre la del package).
 *
 * `middleware`: lo que la app agrega a GET /api/v1/guide/{type}/{id}. Aqui va
 * la visibilidad por cliente del portal (R12 del plan del flujo guiado): la
 * guia decide "ver" con el permiso {tipo}.show y con Spatie ese permiso gana a
 * cualquier Gate::define, asi que el dueno se valida antes, en este middleware.
 */
return [
    'middleware' => [
        \App\Http\Middleware\GuideDocumentVisibility::class,
    ],
];
