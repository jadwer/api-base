<?php

namespace App\Support;

/**
 * Nombre seguro para Content-Disposition (2026-09-30).
 *
 * Symfony rechaza con excepcion (500) un filename con "/" o "\", y en los
 * headers armados a mano una comilla rompe el valor. Los nombres de descarga
 * se arman con datos del registro (SKU, folio, OC del cliente, nombre
 * original del archivo), asi que todos pasan por aqui. Origen: fichas
 * tecnicas de 238 productos con SKU tipo "CH0138B/PR" daban 500.
 */
final class DownloadFilename
{
    public static function sanitize(?string $name, string $fallback = 'archivo'): string
    {
        $clean = preg_replace('~[\\\\/"\x00-\x1F\x7F]+~u', '-', (string) $name) ?? '';
        $clean = trim($clean, " -");

        return $clean === '' ? $fallback : $clean;
    }
}
