<?php

namespace Tests\Feature\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use LaravelJsonApi\Core\Facades\JsonApi;
use Tests\TestCase;

/**
 * Contrato de la salida JSON:API (2026-09-30, decision de Gabino).
 *
 * El Schema es la fuente unica de campos; los Resources manuales que
 * duplicaban la lista se desalineaban en silencio (86 campos guardados que
 * nunca se devolvian). Esta prueba es el candado que sustituye a la doble
 * lista:
 *
 * 1. Lista registrada: tests/Feature/Contracts/jsonapi-fields.json guarda los
 *    campos (atributos y relaciones) que devuelve cada tipo. Si aparece o
 *    desaparece uno, la prueba falla y el cambio se aprueba a proposito con
 *      JSONAPI_CONTRACT_UPDATE=1 php artisan test tests/Feature/Contracts
 *    y revisando el diff del JSON en el commit.
 * 2. Lista negra: ningun tipo puede devolver un campo con nombre sensible,
 *    aunque alguien olvide el ->hidden() del Schema.
 *
 * JSONAPI_GOLDEN_OUT=/ruta.json vuelca ademas los VALORES serializados de un
 * registro de fabrica por tipo (semilla y fecha fijas), para comparar antes y
 * despues de tocar un Resource.
 */
class JsonApiContractTest extends TestCase
{
    private const SNAPSHOT = __DIR__ . '/jsonapi-fields.json';

    /** Nombres que jamas deben salir en una respuesta. */
    private const SENSITIVE = '/(password|passwd|secret|token|api_?key|private_?key|key_?password|client_?secret|remember)/i';

    /** Nombres permitidos aunque coincidan con la lista negra (no son secretos). */
    private const SENSITIVE_ALLOWED = ['idempotencyKey'];

    public function test_fields_match_the_approved_contract(): void
    {
        $current = $this->collect(false)['fields'];

        if (getenv('JSONAPI_CONTRACT_UPDATE')) {
            file_put_contents(self::SNAPSHOT, json_encode($current, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
            $this->markTestSkipped('Contrato JSON:API regenerado; revisa el diff de jsonapi-fields.json.');
        }

        $this->assertFileExists(self::SNAPSHOT, 'Falta el contrato; generalo con JSONAPI_CONTRACT_UPDATE=1.');
        $approved = json_decode(file_get_contents(self::SNAPSHOT), true);

        $diff = [];
        foreach (array_unique(array_merge(array_keys($approved), array_keys($current))) as $type) {
            $before = $approved[$type] ?? [];
            $after = $current[$type] ?? [];
            $added = array_values(array_diff($after, $before));
            $removed = array_values(array_diff($before, $after));
            if ($added || $removed) {
                $diff[$type] = array_filter(['nuevos' => $added, 'quitados' => $removed]);
            }
        }

        $this->assertSame([], $diff, "La salida JSON:API cambio respecto al contrato aprobado:\n"
            . json_encode($diff, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            . "\nSi es intencional: JSONAPI_CONTRACT_UPDATE=1 php artisan test tests/Feature/Contracts");
    }

    public function test_no_sensitive_field_is_exposed(): void
    {
        $leaks = [];
        foreach ($this->collect(false)['fields'] as $type => $fields) {
            foreach ($fields as $field) {
                $name = ltrim($field, '@');
                if (preg_match(self::SENSITIVE, $name) && ! in_array($name, self::SENSITIVE_ALLOWED, true)) {
                    $leaks[] = "{$type}.{$name}";
                }
            }
        }

        $this->assertSame([], $leaks, 'Campos sensibles expuestos (usa ->hidden() en el Schema): ' . implode(', ', $leaks));
    }

    /**
     * El catalogo publico es anonimo: jamas costos, margenes ni datos de
     * proveedor (fuga real de `cost` en julio 2026, commit ed86a7d).
     */
    public function test_public_server_never_exposes_costs(): void
    {
        $leaks = [];
        foreach ($this->collect(false)['fields'] as $type => $fields) {
            if (! str_starts_with($type, 'public:')) {
                continue;
            }
            foreach ($fields as $field) {
                if (preg_match('/(cost|margin|supplier|purchase)/i', $field)) {
                    $leaks[] = "{$type}.{$field}";
                }
            }
        }

        $this->assertSame([], $leaks, 'El API publico expone datos internos: ' . implode(', ', $leaks));
    }

    public function test_golden_values_dump(): void
    {
        $out = getenv('JSONAPI_GOLDEN_OUT');
        if (! $out) {
            $this->markTestSkipped('Solo con JSONAPI_GOLDEN_OUT (comparacion antes/despues).');
        }

        $result = $this->collect(true);
        file_put_contents($out, json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n");
        $this->assertFileExists($out);
    }

    /**
     * Recorre todos los Schemas de los servidores y serializa un modelo con su
     * Resource real. Relaciones van con prefijo "@".
     *
     * @return array{fields: array<string, string[]>, values: array<string, mixed>, errors: array<string, string>}
     */
    private function collect(bool $withValues): array
    {
        Carbon::setTestNow('2026-01-15 12:00:00');
        $fields = [];
        $values = [];
        $errors = [];
        $request = request();

        foreach (['v1', 'public'] as $serverName) {
            $server = JsonApi::server($serverName);
            $schemas = (fn () => $this->allSchemas())->call($server);

            foreach ($schemas as $schemaClass) {
                $type = $schemaClass::type();
                $key = "{$serverName}:{$type}";
                $model = $this->makeModel($schemaClass::model(), $withValues);
                if ($model === null) {
                    $errors[$key] = 'no es modelo Eloquent';
                    continue;
                }

                try {
                    $resource = $server->resources()->create($model);
                    $attrs = iterator_to_array($resource->attributes($request));
                    $rels = array_keys(iterator_to_array($resource->relationships($request)));
                } catch (\Throwable $e) {
                    $errors[$key] = substr($e->getMessage(), 0, 200);
                    continue;
                }

                $names = array_merge(array_keys($attrs), array_map(fn ($r) => '@' . $r, $rels));
                sort($names);
                $fields[$key] = $names;

                if ($withValues) {
                    $values[$key] = json_decode(json_encode($attrs), true);
                }
            }
        }

        ksort($fields);
        ksort($values);

        return ['fields' => $fields, 'values' => $values, 'errors' => $errors];
    }

    /** Registro de fabrica (semilla fija) o, si no hay fabrica, un modelo vacio con id 1. */
    private function makeModel(string $modelClass, bool $withValues): ?Model
    {
        if (! is_subclass_of($modelClass, Model::class)) {
            return null;
        }

        if ($withValues && method_exists($modelClass, 'factory')) {
            try {
                fake()->seed(20260930);
                return $modelClass::factory()->create()->fresh() ?? $modelClass::factory()->create();
            } catch (\Throwable) {
                // sin fabrica util: cae al modelo vacio
            } finally {
                // Faker::seed llama mt_srand global; sin esto los tests posteriores del proceso quedan con azar fijo
                mt_srand();
            }
        }

        /** @var Model $model */
        $model = new $modelClass();
        $model->forceFill([$model->getKeyName() => 1]);
        $model->exists = true;

        return $model;
    }
}
