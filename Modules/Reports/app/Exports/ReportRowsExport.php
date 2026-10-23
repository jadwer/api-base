<?php

namespace Modules\Reports\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Excel de cualquier reporte a partir de las mismas filas que el CSV
 * (ExportService::convertDataToRows). Antes ExportService apuntaba a clases
 * por reporte que nunca existieron y la exportacion a Excel daba 500.
 */
class ReportRowsExport implements FromArray, WithTitle, ShouldAutoSize
{
    /**
     * @param  list<list<mixed>>  $rows
     */
    public function __construct(private array $rows, private string $title)
    {
    }

    /** @return list<list<mixed>> */
    public function array(): array
    {
        return $this->rows;
    }

    public function title(): string
    {
        // Excel limita el nombre de la hoja a 31 caracteres
        return mb_substr($this->title, 0, 31);
    }
}
