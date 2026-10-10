<?php

namespace Modules\Product\JsonApi\Sorting;

use LaravelJsonApi\Eloquent\Contracts\SortField;

/**
 * Orden por precio con los productos sin precio (sujetos a cotizacion) al
 * final en ambas direcciones. Con orderBy simple MySQL pone los NULL primero
 * en ASC y el catalogo "menor precio primero" abria con productos sin precio.
 */
class PriceNullsLast implements SortField
{
    public function __construct(private readonly string $fieldName = 'price', private readonly string $column = 'price')
    {
    }

    public static function make(string $fieldName = 'price', string $column = 'price'): self
    {
        return new self($fieldName, $column);
    }

    public function sortField(): string
    {
        return $this->fieldName;
    }

    public function sort($query, string $direction = 'asc')
    {
        $column = $query->getModel()->qualifyColumn($this->column);

        return $query
            ->orderByRaw("CASE WHEN {$column} IS NULL THEN 1 ELSE 0 END")
            ->orderBy($column, $direction);
    }
}
