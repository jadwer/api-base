<?php

namespace Modules\Sales\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Resuelve imagenes de producto a data URI para incrustarlas en PDFs (dompdf).
 *
 * dompdf NO soporta WebP, que es el formato de la mayoria de las imagenes
 * reales de producto. Ademas, seguir symlinks del storage con el chroot de
 * dompdf es fragil. Este helper lee el archivo, lo convierte a PNG cuando
 * hace falta (via GD) y devuelve un data URI, que dompdf incrusta sin tocar
 * el filesystem. Devuelve null si no hay imagen utilizable (el blade cae al
 * marcador vacio, sin romper el PDF).
 */
class PdfImageHelper
{
    /**
     * @param string|null $rawPath valor de products.img_path (con o sin prefijo)
     * @return string|null data URI (data:image/png;base64,...) o null
     */
    public static function productImageDataUri(?string $rawPath): ?string
    {
        if (empty($rawPath)) {
            return null;
        }

        $absolute = self::resolveAbsolutePath($rawPath);
        if ($absolute === null) {
            return null;
        }

        $binary = @file_get_contents($absolute);
        if ($binary === false || $binary === '') {
            return null;
        }

        // Si dompdf ya soporta el formato (png/jpg), incrustar directo.
        $extension = strtolower(pathinfo($absolute, PATHINFO_EXTENSION));
        if (in_array($extension, ['png', 'jpg', 'jpeg'], true)) {
            return 'data:image/' . ($extension === 'jpg' ? 'jpeg' : $extension)
                . ';base64,' . base64_encode($binary);
        }

        // WebP y otros: convertir a PNG con GD.
        $png = self::toPng($binary);
        if ($png === null) {
            return null;
        }

        return 'data:image/png;base64,' . base64_encode($png);
    }

    /**
     * Prueba las rutas donde puede vivir el archivo (img_path es inconsistente:
     * unos productos lo guardan con prefijo products/ y otros sin el).
     */
    private static function resolveAbsolutePath(string $rawPath): ?string
    {
        $candidates = [
            storage_path('app/public/' . $rawPath),
            storage_path('app/public/products/' . $rawPath),
            public_path('storage/' . $rawPath),
            public_path('storage/products/' . $rawPath),
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Convierte binario de imagen (webp/gif/etc.) a PNG usando GD.
     */
    private static function toPng(string $binary): ?string
    {
        if (!function_exists('imagecreatefromstring')) {
            return null;
        }

        $image = @imagecreatefromstring($binary);
        if ($image === false) {
            return null;
        }

        // Preservar transparencia al pasar a PNG.
        imagepalettetotruecolor($image);
        imagealphablending($image, false);
        imagesavealpha($image, true);

        ob_start();
        $ok = imagepng($image);
        $png = ob_get_clean();
        imagedestroy($image);

        return ($ok && $png !== '') ? $png : null;
    }
}
