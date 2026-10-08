<?php

namespace Tests\Feature\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use LaravelJsonApi\Core\Facades\JsonApi;
use LaravelJsonApi\Eloquent\Fields\Attribute;
use Tests\TestCase;

/**
 * Candado de fechas sin hora (paquete C, fase C1, 2026-10-08).
 *
 * Toda columna con cast 'date' en el modelo debe salir del API como Y-m-d.
 * Serializada con DateTime::make sin mas sale como "2026-10-25T00:00:00.000000Z"
 * (medianoche UTC) y el navegador en America/Mexico_City la pinta un dia antes.
 * El remedio es ->serializeUsing(static fn ($v) => $v?->format('Y-m-d')).
 *
 * La prueba recorre los Schemas de los servidores, toma los atributos cuya
 * columna tiene cast date, les pone una fecha fija y exige que el Resource real
 * devuelva solo Y-m-d.
 */
class DateOnlyContractTest extends TestCase
{
    /**
     * Excepciones aprobadas a proposito, como "servidor:tipo.campo".
     * Hoy vacia: agregar aqui solo con una razon escrita al lado.
     */
    private const ALLOWED = [];

    private const DATE_CAST = '/^(immutable_)?date(:|$)/';

    public function test_date_cast_columns_serialize_as_plain_dates(): void
    {
        $request = request();
        $checked = 0;
        $violations = [];

        foreach (['v1', 'public'] as $serverName) {
            $server = JsonApi::server($serverName);
            $schemas = (fn () => $this->allSchemas())->call($server);

            foreach ($schemas as $schemaClass) {
                $modelClass = $schemaClass::model();
                if (! is_subclass_of($modelClass, Model::class)) {
                    continue;
                }

                /** @var Model $model */
                $model = new $modelClass();
                $casts = $model->getCasts();
                $schema = $server->schemas()->schemaFor($schemaClass::type());

                $dateFields = [];
                foreach ($schema->attributes() as $field) {
                    if (! $field instanceof Attribute) {
                        continue;
                    }
                    $cast = $casts[$field->column()] ?? null;
                    if (is_string($cast) && preg_match(self::DATE_CAST, $cast)) {
                        $dateFields[$field->serializedFieldName()] = $field->column();
                    }
                }

                if ($dateFields === []) {
                    continue;
                }

                $model->forceFill([$model->getKeyName() => 1]);
                foreach ($dateFields as $column) {
                    $model->setAttribute($column, Carbon::parse('2026-10-25'));
                }
                $model->exists = true;

                $attrs = iterator_to_array($server->resources()->create($model)->attributes($request));
                $type = "{$serverName}:{$schemaClass::type()}";

                foreach (array_keys($dateFields) as $name) {
                    if (! array_key_exists($name, $attrs) || in_array("{$type}.{$name}", self::ALLOWED, true)) {
                        continue;
                    }
                    $checked++;
                    $value = json_decode(json_encode($attrs[$name]), true);
                    if ($value !== null && ! (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value))) {
                        $violations[] = "{$type}.{$name} = " . json_encode($value);
                    }
                }
            }
        }

        $this->assertGreaterThan(0, $checked, 'No se encontro ningun campo date; el recorrido de schemas esta roto.');
        $this->assertSame([], $violations, "Columnas date que no salen como Y-m-d (usa ->serializeUsing(static fn (\$v) => \$v?->format('Y-m-d'))):\n"
            . implode("\n", $violations));
    }
}
