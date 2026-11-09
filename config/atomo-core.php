<?php

/**
 * Configuracion de atomo/core en este host (se mezcla con la del package).
 *
 * `locales` fijo en espanol y SIN env(): el middleware SetLocale de atomo/core
 * toma X-Locale y luego Accept-Language, y el navegador manda en-US; con un
 * segundo idioma permitido las frases del flujo guiado saldrian en ingles
 * (regla 9: todo lo que ve el usuario va en espanol). Lo demas queda como lo
 * trae el package.
 */
return [
    'locales' => ['es'],
];
