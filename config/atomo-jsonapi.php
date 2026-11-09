<?php

/**
 * Configuracion de atomo/json-api-base en este host (sobre la del package,
 * vendor/atomo/json-api-base/config/atomo-jsonapi.php).
 *
 * `host_effects` va en false y SIN env(): esta app no nacio en Atomo y ya resuelve
 * a su modo el 409 de llave foranea, la prioridad de `auth.public-read` y no
 * usa `GET v1/lookup`. Con false el provider arranca lo neutro (mapa de
 * recursos por modulo, alias si falta, limitador) y se salta solo eso. Se
 * versiona aqui para no depender del .env de cada servidor (decision 09-11
 * con Atomo; antes el provider iba en dont-discover). Las clases
 * (TransitionAbility, Ownership, SchemaRegistry) siguen disponibles.
 */
return [
    'host_effects' => false,
    'auth_guard' => 'sanctum',
    'base_uri' => '/api/v1',
    'pagination' => [
        'default_size' => (int) env('ATOMO_JSONAPI_PAGE_SIZE', 50),
        'max_size' => (int) env('ATOMO_JSONAPI_MAX_PAGE_SIZE', 100),
    ],
    'lookup' => [
        'panel_url' => env('ATOMO_PANEL_URL', env('FRONTEND_URL', env('APP_URL', 'http://localhost'))),
        'record_path' => 'scanner',
        'info_provider' => null,
    ],
    'change_reason' => [
        'required' => false,
        'methods' => ['DELETE'],
        'min_length' => 3,
        'except' => [],
    ],
];
