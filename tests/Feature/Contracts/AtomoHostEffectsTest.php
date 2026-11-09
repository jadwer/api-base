<?php

namespace Tests\Feature\Contracts;

use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Candado de arranque para los packages de Atomo instalados en LWM
 * (docs/flujo-guiado/PLAN_LWM.md, seccion 2b y F1): sus providers no deben
 * cambiar el comportamiento del host. Si un 0.10.x agrega un middleware, una
 * ruta o pisa un alias, esta prueba lo dice antes que produccion. Desde 0.11.0
 * el provider de json-api-base arranca con host_effects en false (config versionada).
 */
class AtomoHostEffectsTest extends TestCase
{
    public function test_la_guia_esta_registrada_y_el_lookup_de_json_api_base_no(): void
    {
        $uris = collect(Route::getRoutes()->getRoutes())->map->uri();

        $this->assertTrue($uris->contains('api/v1/guide/{type}/{id}'), 'La ruta de atomo/guide no esta registrada');
        $this->assertFalse($uris->contains(fn ($u) => str_starts_with($u, 'api/v1/lookup')),
            'atomo/json-api-base registro su ruta lookup: config/atomo-jsonapi.php debe tener host_effects en false');
        $this->assertFalse(config('atomo-jsonapi.host_effects'), 'host_effects debe ir en false, versionado en config/atomo-jsonapi.php (no por env)');
        $this->assertTrue(app()->providerIsLoaded(\Atomo\JsonApiBase\Providers\AtomoJsonApiBaseServiceProvider::class), 'El provider de json-api-base debe arrancar (registro de modulos); solo sus efectos de host van apagados');
        $this->assertTrue(class_exists(\Atomo\Guide\Guide::class));
        $this->assertTrue(class_exists(\Atomo\JsonApiBase\Auth\TransitionAbility::class), 'La clase TransitionAbility debe autocargar aunque el provider este apagado');
    }

    public function test_la_ruta_de_la_guia_lleva_la_visibilidad_por_cliente_del_portal(): void
    {
        $route = collect(Route::getRoutes()->getRoutes())->first(fn ($r) => $r->uri() === 'api/v1/guide/{type}/{id}');

        $this->assertNotNull($route);
        $this->assertContains(\App\Http\Middleware\GuideDocumentVisibility::class, $route->gatherMiddleware(), 'atomo-guide.middleware debe traer GuideDocumentVisibility (R12)');
        $this->assertNotContains(\App\Http\Middleware\GuideDocumentVisibility::class, app('router')->getMiddlewareGroups()['api'] ?? [], 'Ya no va en el grupo api');
    }

    public function test_los_alias_de_middleware_de_lwm_siguen_siendo_los_de_lwm(): void
    {
        $aliases = app('router')->getMiddleware();

        $this->assertSame(\App\Http\Middleware\ApiRateLimiter::class, $aliases['api.ratelimit'] ?? null);
        $this->assertSame(\App\Http\Middleware\CacheJsonApiResponse::class, $aliases['cache.jsonapi'] ?? null);
        $this->assertSame(\App\Http\Middleware\LoginThrottler::class, $aliases['login.throttle'] ?? null);
        $this->assertSame(\App\Http\Middleware\ProfileMemory::class, $aliases['profile.memory'] ?? null);
        // auth.public-read lo registra json-api-base solo si la app no lo tiene; no es de LWM y no se usa aqui.
    }

    public function test_el_grupo_api_tiene_exactamente_lo_de_lwm_mas_los_tres_de_core(): void
    {
        $expected = [
            SubstituteBindings::class,
            \Atomo\Core\Http\Middleware\SetLocale::class,
            \Atomo\Core\Modules\EnsureModuleEnabled::class,
            \Atomo\Core\Http\Middleware\CaptureChangeReason::class,
        ];
        $actual = app('router')->getMiddlewareGroups()['api'] ?? [];

        sort($expected);
        sort($actual);
        $this->assertSame($expected, $actual, 'El grupo api cambio: revisar que agrego Atomo o LWM');
    }
}
