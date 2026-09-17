<?php

namespace Modules\Product\Support;

use Illuminate\Support\Str;
use Modules\Product\Models\Product;

/**
 * Slug de producto (SEO Bloque 1b, 2026-09).
 *
 * Forma: <nombre>-<marca>-<sku> slugificado (acentos a ASCII, todo lo que no
 * sea alfanumerico a guion), recortado a MAX_LENGTH sin partir palabra. La
 * unicidad se garantiza con sufijo numerico (-2, -3...). El slug es ESTABLE:
 * se genera al crear y no cambia al renombrar (romperia URLs ya indexadas);
 * regenerarlo es una accion explicita, nunca un efecto colateral.
 */
final class ProductSlug
{
    /** Deja margen para el sufijo dentro del varchar(191). */
    public const MAX_LENGTH = 150;

    public const FALLBACK = 'producto';

    /**
     * Base del slug a partir de nombre, marca y sku (cualquiera puede faltar).
     */
    public static function build(?string $name, ?string $brand = null, ?string $sku = null): string
    {
        $name = is_string($name) ? trim($name) : '';
        // Muchos nombres de LWM ya traen la marca ("... Repair Hach") o el sku
        // ("758IIOEM-123-4A" con sku "MY-758IIOEM-123-4A"): no repetir tokens.
        $brand = self::absentIn($name, $brand);
        $sku = self::absentIn($name, $sku);

        $parts = array_filter([$name, $brand, $sku], static fn ($v) => is_string($v) && trim($v) !== '');
        $slug = Str::slug(implode(' ', $parts), '-', 'es');

        if ($slug === '') {
            return self::FALLBACK;
        }

        if (strlen($slug) > self::MAX_LENGTH) {
            $cut = substr($slug, 0, self::MAX_LENGTH);
            $lastDash = strrpos($cut, '-');
            // Cortar en el ultimo guion para no dejar una palabra a medias
            $slug = ($lastDash !== false && $lastDash > 0) ? substr($cut, 0, $lastDash) : $cut;
            $slug = rtrim($slug, '-');
        }

        return $slug;
    }

    /**
     * Base para un producto (resuelve la marca por relacion si hace falta).
     */
    public static function forProduct(Product $product): string
    {
        $brandName = $product->relationLoaded('brand')
            ? $product->brand?->name
            : ($product->brand_id ? $product->brand()->value('name') : null);

        return self::build($product->name, $brandName, $product->sku);
    }

    /**
     * Devuelve $base o $base-N, el primero que no exista en products (ignorando
     * el propio registro). $taken permite reservar slugs asignados en el mismo
     * lote antes de que lleguen a la base (backfill).
     *
     * @param array<string,true> $taken
     */
    public static function unique(string $base, ?int $ignoreId = null, array &$taken = []): string
    {
        $candidate = $base;
        $n = 1;

        while (isset($taken[$candidate]) || self::exists($candidate, $ignoreId)) {
            $n++;
            $candidate = "{$base}-{$n}";
        }

        $taken[$candidate] = true;

        return $candidate;
    }

    /** Devuelve $token solo si NO aparece ya dentro de $haystack (sin distinguir mayusculas). */
    private static function absentIn(string $haystack, ?string $token): ?string
    {
        if (!is_string($token) || trim($token) === '') {
            return null;
        }
        $token = trim($token);
        if ($haystack !== '' && mb_stripos($haystack, $token) !== false) {
            return null;
        }
        // Tambien cubre el caso en que el sku contiene al nombre ("MY-758..." vs "758...")
        if ($haystack !== '' && mb_stripos($token, $haystack) !== false) {
            return $token === $haystack ? null : $token;
        }

        return $token;
    }

    private static function exists(string $slug, ?int $ignoreId): bool
    {
        $query = Product::query()->where('slug', $slug);
        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }
}
