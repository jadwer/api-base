<?php

namespace App\Exports;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Excel de un listado completo a partir de su consulta. Lee por bloques, asi
 * que sirve para catalogos grandes (38 mil productos) sin cargar todo en memoria.
 */
class QueryExport implements FromQuery, WithHeadings, WithMapping, WithTitle
{
    /**
     * @param  list<string>  $headings
     * @param  Closure(mixed): list<mixed>  $map
     */
    public function __construct(private Builder $query, private array $headings, private Closure $map, private string $title)
    {
    }

    public function query(): Builder
    {
        return $this->query;
    }

    /** @return list<string> */
    public function headings(): array
    {
        return $this->headings;
    }

    /** @return list<mixed> */
    public function map($row): array
    {
        return ($this->map)($row);
    }

    public function title(): string
    {
        return mb_substr($this->title, 0, 31);
    }
}
