<?php

namespace Modules\PermissionManager\Support;

/**
 * Resuelve label, description, module y resource de un permiso
 * `recurso.verbo` contra el catalogo (config/permission_catalog.php).
 *
 * El verbo es SIEMPRE el ultimo segmento; el recurso es todo lo previo
 * (hay recursos con namespace de modulo: billing.cfdi-invoices).
 */
class PermissionCatalog
{
    private static ?array $catalog = null;

    public static function catalog(): array
    {
        if (self::$catalog === null) {
            self::$catalog = require module_path('PermissionManager', 'config/permission_catalog.php');
        }

        return self::$catalog;
    }

    /** Limpia el cache estatico (tests). */
    public static function flush(): void
    {
        self::$catalog = null;
    }

    /**
     * @return array{label: string, description: string, module: ?string, resource: string, known: bool}
     */
    public static function resolve(string $name): array
    {
        $catalog = self::catalog();

        $parts = explode('.', $name);
        $verb = array_pop($parts);
        $resource = implode('.', $parts);

        $entry = $catalog['resources'][$resource] ?? null;
        $module = $entry[0] ?? null;

        if (isset($catalog['overrides'][$name])) {
            [$label, $description] = $catalog['overrides'][$name];

            return compact('label', 'description', 'module', 'resource') + ['known' => $entry !== null];
        }

        $template = $catalog['verbs'][$verb] ?? null;

        if ($entry === null || $template === null) {
            // Fallback legible para permisos fuera de catalogo; el test de
            // cobertura los detecta, aqui no se rompe el seed.
            return [
                'label' => ucfirst(str_replace(['.', '-'], ' ', $name)),
                'description' => 'Permiso ' . $name,
                'module' => $module,
                'resource' => $resource,
                'known' => false,
            ];
        }

        [, $singular, $plural, $article] = $entry;
        $replace = ['{singular}' => $singular, '{plural}' => $plural, '{article}' => $article];

        return [
            'label' => ucfirst(strtr($template['label'], $replace)),
            'description' => strtr($template['description'], $replace),
            'module' => $module,
            'resource' => $resource,
            'known' => true,
        ];
    }

    /** Label legible del modulo de negocio (o el slug si no esta). */
    public static function moduleLabel(?string $module): ?string
    {
        if ($module === null) {
            return null;
        }

        return self::catalog()['modules'][$module] ?? $module;
    }

    /** Label legible del recurso en plural (ej. "Facturas CFDI"). */
    public static function resourceLabel(?string $resource): ?string
    {
        if ($resource === null) {
            return null;
        }

        $entry = self::catalog()['resources'][$resource] ?? null;

        if ($entry === null) {
            return $resource;
        }

        return ucfirst($entry[2]);
    }
}
