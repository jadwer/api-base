<?php

namespace Modules\Product\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductImage;
use Modules\Product\Models\ProductVariant;

/**
 * Archivos de imagen de producto en el disco public (P1 granel, 2026-10-10).
 *
 * Las imagenes se guardan como products/<uuid>.ext, pero en la base conviven
 * formas legadas de la misma ruta: "foto.jpg", "products/foto.jpg",
 * "/storage/products/foto.jpg", URLs externas y rutas de public/ ("/images/").
 * Aqui se normaliza todo a la ruta del disco y se decide si un archivo quedo
 * huerfano. Mismo criterio que las fichas PDF (ProductObserver, 2026-10-06):
 * se borra solo si NINGUN product_images.file_path, products.img_path ni
 * product_variants.img_path lo usa, y nunca fuera de products/.
 */
final class ProductImageFiles
{
    /**
     * Ruta relativa al disco public (siempre dentro de products/) o null si el
     * valor no es un archivo local administrado por nosotros.
     */
    public static function diskPath(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '' || str_contains($value, '..') || str_contains($value, '\\')) {
            return null;
        }
        if (preg_match('#^[a-z][a-z0-9+.-]*://#i', $value)) {
            return null;
        }
        if (str_starts_with($value, '/storage/')) {
            $value = substr($value, strlen('/storage/'));
        } elseif (str_starts_with($value, '/')) {
            // /images/... vive en public/ del proyecto, no en el disco.
            return null;
        }
        if (! str_contains($value, '/')) {
            $value = 'products/' . $value;
        }

        return str_starts_with($value, 'products/') && strlen($value) > strlen('products/') ? $value : null;
    }

    /** Dos valores guardados apuntan al mismo archivo. */
    public static function same(?string $a, ?string $b): bool
    {
        $a = trim((string) $a);
        $b = trim((string) $b);
        if ($a === $b) {
            return true;
        }
        $da = self::diskPath($a);

        return $da !== null && $da === self::diskPath($b);
    }

    /**
     * Formas en que la misma ruta puede estar guardada en la base.
     *
     * @return list<string>
     */
    public static function storedVariants(string $diskPath): array
    {
        $variants = [$diskPath, '/storage/' . $diskPath];
        if (substr_count($diskPath, '/') === 1) {
            $variants[] = basename($diskPath);
        }

        return $variants;
    }

    public static function isReferenced(string $diskPath): bool
    {
        $variants = self::storedVariants($diskPath);

        return ProductImage::query()->whereIn('file_path', $variants)->exists()
            || Product::query()->whereIn('img_path', $variants)->exists()
            || ProductVariant::query()->whereIn('img_path', $variants)->exists();
    }

    /**
     * Borra el archivo si ya nadie lo usa. La verificacion y el borrado corren
     * despues del commit: si la transaccion se revierte, el archivo se queda.
     * Se puede apagar con PRODUCT_DELETE_ORPHAN_FILES=false (dev comparte
     * storage con prod: ver docs/granel-precio-variable/ROADMAP_GRANEL.md).
     */
    public static function deleteIfOrphan(?string $stored): void
    {
        if (! config('products.delete_orphan_files', true)) {
            return;
        }
        $path = self::diskPath($stored);
        if ($path === null) {
            return;
        }

        DB::afterCommit(static function () use ($path): void {
            if (self::isReferenced($path)) {
                return;
            }
            $disk = Storage::disk('public');
            if ($disk->exists($path)) {
                $disk->delete($path);
            }
        });
    }
}
