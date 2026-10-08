<?php

namespace Tests\Feature\Contracts;

use Illuminate\Database\Eloquent\Model;
use LaravelJsonApi\Contracts\Routing\Route as JsonApiRoute;
use LaravelJsonApi\Contracts\Schema\Attribute;
use LaravelJsonApi\Core\Facades\JsonApi;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use Mockery;
use Tests\TestCase;

/**
 * Candado B3 (paquete C, 2026-10-08): todo atributo escribible de un Schema v1
 * tiene regla en su Request hermano.
 *
 * Un atributo sin readOnly() y sin regla entra al modelo sin validar (o se
 * descarta en silencio si tampoco esta en fillable). La salida es una de dos:
 * el servidor lo gestiona y el atributo va ->readOnly(), o el cliente lo
 * escribe y el Request le pone regla. La lista blanca de abajo es la unica
 * excepcion y cada entrada lleva su razon.
 */
class WritableAttributesContractTest extends TestCase
{
    /**
     * Excepciones explicitas: "tipo" => [atributos], con la razon en comentario.
     *
     * @var array<string, string[]>
     */
    private const ALLOWLIST = [
        // Lo consume la regla 'confirmed' de password; con regla propia entraria a
        // validated() y el Schema intentaria guardarlo como columna.
        'users' => ['password_confirmation'],
    ];

    public function test_every_writable_attribute_has_a_rule(): void
    {
        $missing = [];
        $errors = [];
        $checked = 0;

        $server = JsonApi::server('v1');
        $schemas = (fn () => $this->allSchemas())->call($server);
        $writableTypes = $this->typesWithWriteRoutes();

        foreach ($schemas as $schemaClass) {
            $type = $schemaClass::type();
            if (! isset($writableTypes[$type])) {
                // solo GET (reportes virtuales, bitacoras): nada que validar
                continue;
            }
            $schema = $server->schemas()->schemaFor($type);
            $requestClass = preg_replace('/Schema$/', 'Request', $schemaClass);

            $writable = [];
            foreach (['POST', 'PATCH'] as $method) {
                $probe = \Illuminate\Http\Request::create('/', $method);
                foreach ($schema->attributes() as $attribute) {
                    if ($attribute instanceof Attribute && method_exists($attribute, 'isReadOnly') && ! $attribute->isReadOnly($probe)) {
                        $writable[$attribute->name()] = true;
                    }
                }
            }
            $writable = array_keys($writable);
            if ($writable === []) {
                continue;
            }

            if (! class_exists($requestClass)) {
                $errors[$type] = "sin Request ({$requestClass})";
                continue;
            }

            try {
                $keys = $this->ruleKeys($requestClass, $schema);
            } catch (\Throwable $e) {
                $errors[$type] = 'rules() fallo: ' . substr($e->getMessage(), 0, 200);
                continue;
            }

            $checked++;
            $without = [];
            foreach ($writable as $name) {
                if (in_array($name, self::ALLOWLIST[$type] ?? [], true)) {
                    continue;
                }
                if (! $this->hasRule($name, $keys)) {
                    $without[] = $name;
                }
            }
            if ($without !== []) {
                $missing[$type] = $without;
            }
        }

        ksort($missing);
        // si la deteccion de rutas o Requests se rompe, el candado no debe pasar en vacio
        $this->assertGreaterThan(80, $checked, 'El candado reviso muy pocos Schemas; revisa typesWithWriteRoutes().');
        $this->assertSame([], $errors + $missing, "Atributos escribibles sin regla (marca ->readOnly() o agrega la regla al Request):\n"
            . json_encode($errors + $missing, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * Llaves de rules() en alta y en edicion (union). La ruta JSON:API se
     * simula: sin id al crear, con un modelo vacio al editar.
     *
     * @return string[]
     */
    private function ruleKeys(string $requestClass, object $schema): array
    {
        $modelClass = $schema::model();
        $keys = [];
        foreach (['POST', 'PATCH'] as $method) {
            $model = null;
            if ($method === 'PATCH' && is_subclass_of($modelClass, Model::class)) {
                $model = new $modelClass();
                $model->forceFill([$model->getKeyName() => 1]);
                $model->exists = true;
            }

            $route = Mockery::mock(JsonApiRoute::class);
            $route->shouldReceive('hasResourceId')->andReturn($model !== null);
            $route->shouldReceive('model')->andReturn($model);
            $route->shouldReceive('modelOrResourceId')->andReturn($model ?? '1');
            $route->shouldReceive('resourceId')->andReturn('1');
            $route->shouldReceive('hasRelation')->andReturn(false);
            $route->shouldReceive('schema')->andReturn($schema);
            $route->shouldReceive('resourceType')->andReturn($schema::type());
            $this->app->instance(JsonApiRoute::class, $route);

            /** @var ResourceRequest $request */
            $request = $requestClass::create('/', $method);
            $request->setContainer($this->app);
            $request->setUserResolver(fn () => null);

            foreach (array_keys($request->rules()) as $key) {
                $keys[$key] = true;
            }
        }
        $this->app->forgetInstance(JsonApiRoute::class);

        return array_keys($keys);
    }

    /**
     * Tipos con alta o edicion por la ruta JSON:API estandar
     * (POST api/v1/{tipo} o PATCH api/v1/{tipo}/{id}).
     *
     * @return array<string, true>
     */
    private function typesWithWriteRoutes(): array
    {
        $types = [];
        foreach (app('router')->getRoutes() as $route) {
            $methods = $route->methods();
            $uri = $route->uri();
            if (in_array('POST', $methods, true) && preg_match('#^api/v1/([a-z0-9-]+)$#', $uri, $m)) {
                $types[$m[1]] = true;
            }
            if (in_array('PATCH', $methods, true) && preg_match('#^api/v1/([a-z0-9-]+)/\{[^}]+\}$#', $uri, $m)) {
                $types[$m[1]] = true;
            }
        }

        return $types;
    }

    /** Una regla cuenta si su llave es el atributo o un hijo (attr.*, attr.campo). */
    private function hasRule(string $name, array $keys): bool
    {
        foreach ($keys as $key) {
            if ($key === $name || str_starts_with($key, $name . '.')) {
                return true;
            }
        }

        return false;
    }
}
