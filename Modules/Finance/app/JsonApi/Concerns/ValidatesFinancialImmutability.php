<?php

namespace Modules\Finance\JsonApi\Concerns;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Paquete B (integridad): un documento financiero con efectos contables o
 * cobros es INMUTABLE por PATCH; solo notes y metadata se editan. Los
 * cambios de estado van por sus endpoints de accion (post, void,
 * register-payment), nunca por PATCH, en ningun estado.
 *
 * El guard compara contra el valor actual: un cliente JSON:API que reenvia
 * el objeto completo sin cambios NO falla; solo fallan los cambios reales.
 */
trait ValidatesFinancialImmutability
{
    /** Estados en los que el documento completo queda inmutable. */
    abstract protected function immutableStatuses(): array;

    /** Campos editables en cualquier estado. */
    protected function alwaysEditableFields(): array
    {
        return ['notes', 'metadata'];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $model = $this->model();
            if (!$model) {
                return; // create: las rules normales aplican
            }

            $attributes = (array) $this->input('data.attributes', []);

            // El estado JAMAS cambia por PATCH (mismo principio que
            // Sales/Purchase/CFDI): siempre por endpoints de accion.
            if (array_key_exists('status', $attributes)
                && $this->immutabilityValueChanged($model->status, $attributes['status'])) {
                $validator->errors()->add('status', sprintf(
                    'El estado no puede cambiarse por edicion directa (actual: "%s"). Usa las acciones del documento (postear, anular, registrar pago).',
                    $model->status
                ));
            }

            if (!in_array($model->status, $this->immutableStatuses(), true)) {
                return;
            }

            foreach ($attributes as $field => $value) {
                if ($field === 'status' || in_array($field, $this->alwaysEditableFields(), true)) {
                    continue;
                }

                $column = Str::snake($field);
                $current = $model->getAttribute($column);

                if ($this->immutabilityValueChanged($current, $value, $model->getCasts()[$column] ?? null)) {
                    $validator->errors()->add($field, sprintf(
                        'Un documento en estado "%s" es inmutable: "%s" ya no puede modificarse (solo notas y metadata). Los ajustes van por nota de credito o anulacion.',
                        $model->status,
                        $field
                    ));
                }
            }
        });
    }

    /**
     * Comparacion tolerante a representacion (fechas con/sin hora, numeros
     * como string, booleanos): solo un cambio REAL de valor cuenta.
     */
    protected function immutabilityValueChanged(mixed $current, mixed $value, ?string $cast = null): bool
    {
        if ($current === null && ($value === null || $value === '')) {
            return false;
        }

        if ($current instanceof \DateTimeInterface) {
            if ($value === null || $value === '') {
                return true;
            }
            try {
                return $current->format('Y-m-d') !== Carbon::parse((string) $value)->format('Y-m-d');
            } catch (\Throwable) {
                return true;
            }
        }

        if (is_bool($current) || $cast === 'boolean') {
            return (bool) $current !== (bool) $value;
        }

        if (is_numeric($current) && is_numeric($value)) {
            return abs((float) $current - (float) $value) > 0.0001;
        }

        if (is_array($current) || is_array($value)) {
            return $current != $value;
        }

        return (string) $current !== (string) ($value ?? '');
    }
}
