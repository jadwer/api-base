<?php

/*
 * Correcciones a la traduccion al espanol del paquete laravel-json-api/spec.
 * Laravel combina este archivo con el del paquete, asi que solo van las
 * claves que se reescriben (espacios iniciales, orden de palabras y
 * terminos en ingles). Regla 9: todo mensaje visible en espanol.
 */

return [
    'member_empty' => [
        'detail' => 'El miembro :member no puede estar vacío.',
    ],

    'member_field_not_allowed' => [
        'detail' => 'El miembro :member no puede tener el campo :field.',
    ],

    'member_field_not_supported' => [
        'detail' => 'El campo :field no está permitido en este recurso.',
    ],

    'field_expects_to_one' => [
        'detail' => 'El campo :field debe ser una relación a uno.',
    ],

    'field_expects_to_many' => [
        'detail' => 'El campo :field debe ser una relación a muchos.',
    ],

    'resource_type_not_supported' => [
        'title' => 'No soportado',
        'detail' => 'El tipo de recurso :type no es soportado por este endpoint.',
    ],

    'resource_type_not_supported_by_to_one_relationship' => [
        'title' => 'Entidad no procesable',
        'detail' => 'El campo :field debe ser una relación a uno con recursos :types.',
    ],

    'resource_type_not_supported_by_to_many_relationship' => [
        'title' => 'Entidad no procesable',
        'detail' => 'El campo :field debe ser una relación a muchos con recursos :types.',
    ],

    'resource_type_not_recognised' => [
        'title' => 'No soportado',
        'detail' => 'El tipo de recurso :type no se reconoce.',
    ],

    'resource_id_not_supported' => [
        'title' => 'No soportado',
        'detail' => 'El id de recurso :id no es soportado por este endpoint.',
    ],

    'resource_client_ids_not_supported' => [
        'title' => 'No soportado',
        'detail' => 'El tipo de recurso :type no admite ids generados por el cliente.',
    ],

    'resource_exists' => [
        'detail' => 'El recurso :id ya existe.',
    ],

    'resource_not_found' => [
        'title' => 'No encontrado',
    ],

    'resource_field_exists_in_attributes_and_relationships' => [
        'detail' => 'El campo :field no puede ser atributo y relación a la vez.',
    ],
];
