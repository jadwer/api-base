<?php

namespace Modules\Reports\Services\Export;

use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use League\Csv\Writer;
use Modules\Reports\Exports\ReportRowsExport;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class ExportService
{
    /**
     * Export report data to Excel
     *
     * @param string $reportType
     * @param array $data
     * @param string|null $filename
     * @return string Path to exported file
     */
    public function exportToExcel(string $reportType, array $data, ?string $filename = null): string
    {
        $filename = $filename ?? $this->generateFilename($reportType, 'xlsx');

        // Mismas filas que el CSV (antes apuntaba a clases que nunca existieron: 500)
        $export = new ReportRowsExport($this->convertDataToRows($reportType, $data), $this->titleFor($reportType));

        $path = 'exports/' . $filename;
        Excel::store($export, $path, 'local');

        return Storage::path($path);
    }

    /**
     * Export report data to PDF
     *
     * @param string $reportType
     * @param array $data
     * @param string|null $filename
     * @return string Path to exported file
     */
    public function exportToPDF(string $reportType, array $data, ?string $filename = null): string
    {
        $filename = $filename ?? $this->generateFilename($reportType, 'pdf');

        // Una vista generica con las mismas filas que el CSV (las vistas por
        // reporte nunca existieron y el PDF daba 500)
        $pdf = Pdf::loadView('reports::exports.report-pdf', $this->pdfViewData($reportType, $data))
            ->setPaper('letter', 'landscape');
        $path = storage_path('app/exports/' . $filename);

        // Ensure directory exists
        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $pdf->save($path);

        return $path;
    }

    /**
     * Export report data to CSV
     *
     * @param string $reportType
     * @param array $data
     * @param string|null $filename
     * @return string Path to exported file
     */
    public function exportToCSV(string $reportType, array $data, ?string $filename = null): string
    {
        $filename = $filename ?? $this->generateFilename($reportType, 'csv');
        $path = storage_path('app/exports/' . $filename);

        // Ensure directory exists
        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $csv = Writer::createFromPath($path, 'w+');

        // Convert data to CSV format
        $rows = $this->convertDataToRows($reportType, $data);

        foreach ($rows as $row) {
            $csv->insertOne($row);
        }

        return $path;
    }

    /** Titulo en espanol del reporte (hoja de Excel y encabezado del PDF). */
    private function titleFor(string $reportType): string
    {
        return match ($reportType) {
            'balance-sheet' => 'Balance general',
            'income-statement' => 'Estado de resultados',
            'cash-flow' => 'Flujo de efectivo',
            'trial-balance' => 'Balanza de comprobacion',
            'aging-ar' => 'Antiguedad de saldos por cobrar',
            'aging-ap' => 'Antiguedad de saldos por pagar',
            'sales-by-customer' => 'Ventas por cliente',
            'sales-by-employee' => 'Ventas por vendedor',
            'sales-by-batch' => 'Ventas por lote',
            'sales-profitability' => 'Rentabilidad de ventas',
            'sales-history' => 'Historico de ventas',
            default => 'Reporte',
        };
    }

    /**
     * Separa las filas del CSV en titulo, lineas de periodo y tabla para la
     * vista PDF.
     *
     * @return array{title: string, meta: list<string>, rows: list<list<mixed>>, headerIndex: int|null, columns: int}
     */
    private function pdfViewData(string $reportType, array $data): array
    {
        $rows = $this->convertDataToRows($reportType, $data);

        // Encabezado: filas de una sola celda hasta la primera fila vacia
        $meta = [];
        while ($rows !== [] && count($rows[0]) <= 1) {
            $line = (string) ($rows[0][0] ?? '');
            array_shift($rows);
            if ($line === '') {
                break;
            }
            $meta[] = $line;
        }
        array_shift($meta); // la primera es el titulo

        $headerIndex = null;
        foreach ($rows as $i => $row) {
            $cells = array_filter($row, fn ($c) => $c !== '' && $c !== null);
            if (count($cells) >= 2 && count(array_filter($cells, 'is_string')) === count($cells)) {
                $headerIndex = $i;
                break;
            }
        }

        return [
            'title' => $this->titleFor($reportType),
            'meta' => $meta,
            'rows' => array_values($rows),
            'headerIndex' => $headerIndex,
            'columns' => max(1, ...array_map('count', $rows ?: [[]])),
        ];
    }

    /**
     * Generate filename for export
     *
     * @param string $reportType
     * @param string $extension
     * @return string
     */
    private function generateFilename(string $reportType, string $extension): string
    {
        $timestamp = Carbon::now()->format('Y-m-d_His');
        return "{$reportType}_{$timestamp}.{$extension}";
    }

    /**
     * Convert report data to rows for CSV export
     *
     * @param string $reportType
     * @param array $data
     * @return array
     */
    private function convertDataToRows(string $reportType, array $data): array
    {
        return match ($reportType) {
            'balance-sheet' => $this->balanceSheetToRows($data),
            'income-statement' => $this->incomeStatementToRows($data),
            'trial-balance' => $this->trialBalanceToRows($data),
            'aging-ar', 'aging-ap' => $this->agingReportToRows($data),
            'sales-by-customer' => $this->salesByCustomerToRows($data),
            'sales-history' => $this->salesHistoryToRows($data),
            default => $this->genericDataToRows($data),
        };
    }

    /**
     * Convert sales history report data to CSV rows
     *
     * Columns follow the Bind ERP "Historico de Ventas" layout:
     * Folio, Fecha, Cliente, Vendedor, Costo, Utilidad, Subtotal,
     * Descuento, IVA, Total, Estatus (plus a totals row).
     *
     * @param array $data
     * @return array
     */
    private function salesHistoryToRows(array $data): array
    {
        $rows = [];

        $rows[] = ['Historico de Ventas'];
        $rows[] = ['Periodo: ' . ($data['period']['start_date'] ?? '') . ' a ' . ($data['period']['end_date'] ?? '')];
        $rows[] = ['Moneda: ' . ($data['currency'] ?? 'MXN')];
        $rows[] = [];

        $rows[] = [
            'Folio', 'Fecha', 'Cliente', 'Vendedor', 'Costo', 'Utilidad',
            'Subtotal', 'Descuento', 'IVA', 'Total', 'Estatus',
        ];

        foreach ($data['data'] ?? [] as $row) {
            $rows[] = [
                $row['order_number'] ?? '',
                $row['date'] ?? '',
                $row['customer_name'] ?? '',
                $row['salesperson_name'] ?? '',
                $row['cost'] ?? 0,
                $row['profit'] ?? 0,
                $row['subtotal'] ?? 0,
                $row['discount'] ?? 0,
                $row['iva'] ?? 0,
                $row['total'] ?? 0,
                $row['status'] ?? '',
            ];
        }

        $totals = $data['totals'] ?? [];
        $rows[] = [];
        $rows[] = [
            'TOTALES',
            '',
            'Ordenes: ' . ($totals['count'] ?? 0),
            '',
            $totals['cost'] ?? 0,
            $totals['profit'] ?? 0,
            $totals['subtotal'] ?? 0,
            $totals['discount'] ?? 0,
            $totals['iva'] ?? 0,
            $totals['total'] ?? 0,
            '',
        ];

        if (!empty($data['grouped'])) {
            $rows[] = [];
            $rows[] = ['Agrupado por: ' . ($data['group_by'] ?? '')];
            $rows[] = ['Grupo', 'Costo', 'Utilidad', 'Subtotal', 'Descuento', 'IVA', 'Total', 'Ordenes'];

            foreach ($data['grouped'] as $group) {
                $rows[] = [
                    $group['group_label'] ?? '',
                    $group['cost'] ?? 0,
                    $group['profit'] ?? 0,
                    $group['subtotal'] ?? 0,
                    $group['discount'] ?? 0,
                    $group['iva'] ?? 0,
                    $group['total'] ?? 0,
                    $group['count'] ?? 0,
                ];
            }
        }

        return $rows;
    }

    /**
     * Balance general (estructura de BalanceSheetService::generate: secciones
     * con categorias, cuentas y subtotales). La version anterior leia llaves
     * que ya no existen (current_assets) y fallaba en los tres formatos.
     */
    private function balanceSheetToRows(array $data): array
    {
        $rows = [
            ['Balance general'],
            ['Al: ' . ($data['asOfDate'] ?? '')],
            ['Moneda: ' . ($data['currency'] ?? 'MXN')],
            [],
            ['Cuenta', 'Nombre', 'Saldo'],
        ];

        foreach (['assets' => ['ACTIVO', 'totalAssets'], 'liabilities' => ['PASIVO', 'totalLiabilities'], 'equity' => ['CAPITAL', 'totalEquity']] as $key => [$label, $totalKey]) {
            $rows[] = [$label];
            $rows = array_merge($rows, $this->hierarchyRows($data[$key] ?? [], 'balance'));
            $rows[] = ['', 'Total ' . mb_strtolower($label), $data[$totalKey] ?? 0];
            $rows[] = [];
        }

        $rows[] = ['', 'Cuadra (activo = pasivo + capital)', ($data['balanced'] ?? false) ? 'Si' : 'No'];

        return $rows;
    }

    /**
     * Estado de resultados (estructura de IncomeStatementService::generate).
     */
    private function incomeStatementToRows(array $data): array
    {
        $rows = [
            ['Estado de resultados'],
            ['Periodo: ' . ($data['startDate'] ?? '') . ' a ' . ($data['endDate'] ?? '')],
            ['Moneda: ' . ($data['currency'] ?? 'MXN')],
            [],
            ['Cuenta', 'Nombre', 'Importe'],
            ['INGRESOS'],
        ];
        $rows = array_merge($rows, $this->hierarchyRows($data['revenues'] ?? [], 'amount'));
        $rows[] = ['', 'Total ingresos', $data['totalRevenues'] ?? 0];
        $rows[] = [];
        $rows[] = ['EGRESOS'];
        $rows = array_merge($rows, $this->hierarchyRows($data['expenses'] ?? [], 'amount'));
        $rows[] = ['', 'Total egresos', $data['totalExpenses'] ?? 0];
        $rows[] = [];
        $rows[] = ['', 'UTILIDAD NETA', $data['netIncome'] ?? 0];

        return $rows;
    }

    /**
     * Filas de una seccion jerarquica (categoria -> cuentas -> subtotal).
     *
     * @param  array<int, array<string, mixed>>  $categories
     * @return list<list<mixed>>
     */
    private function hierarchyRows(array $categories, string $amountKey): array
    {
        $rows = [];
        foreach ($categories as $category) {
            $rows[] = [$category['category'] ?? ''];
            foreach ($category['accounts'] ?? [] as $account) {
                $rows[] = [$account['code'] ?? '', $account['name'] ?? '', $account[$amountKey] ?? 0];
            }
            $rows[] = ['', 'Subtotal ' . ($category['category'] ?? ''), $category['subtotal'] ?? 0];
        }

        return $rows;
    }

    /**
     * Balanza de comprobacion (estructura de TrialBalanceService::generate).
     */
    private function trialBalanceToRows(array $data): array
    {
        $rows = [
            ['Balanza de comprobacion'],
            ['Al: ' . ($data['asOfDate'] ?? '')],
            ['Moneda: ' . ($data['currency'] ?? 'MXN')],
            [],
            ['Cuenta', 'Nombre', 'Tipo', 'Cargos', 'Abonos'],
        ];

        foreach ($data['accounts'] ?? [] as $account) {
            $rows[] = [$account['code'] ?? '', $account['name'] ?? '', $account['type'] ?? '', $account['debit'] ?? 0, $account['credit'] ?? 0];
        }

        $rows[] = [];
        $rows[] = ['', '', 'TOTALES', $data['totals']['debit'] ?? 0, $data['totals']['credit'] ?? 0];

        return $rows;
    }

    /**
     * Convert aging report data to CSV rows
     *
     * @param array $data
     * @return array
     */
    private function agingReportToRows(array $data): array
    {
        $rows = [];

        $rows[] = ['Antiguedad de saldos ' . ($data['report_type'] ?? '')];
        $rows[] = ['Al: ' . ($data['as_of_date'] ?? '')];
        $rows[] = [];

        $entityKey = isset($data['customers']) ? 'customers' : 'suppliers';
        $nameKey = isset($data['customers']) ? 'customer_name' : 'supplier_name';

        $rows[] = ['Nombre', 'Vigente', '1-30 dias', '31-60 dias', '61-90 dias', 'Mas de 90 dias', 'Total'];

        foreach ($data[$entityKey] ?? [] as $entity) {
            $rows[] = [
                $entity[$nameKey],
                $entity['current'],
                $entity['days_1_30'],
                $entity['days_31_60'],
                $entity['days_61_90'],
                $entity['days_over_90'],
                $entity['total'],
            ];
        }

        $rows[] = [];
        $rows[] = [
            'TOTAL',
            $data['summary']['current'],
            $data['summary']['days_1_30'],
            $data['summary']['days_31_60'],
            $data['summary']['days_61_90'],
            $data['summary']['days_over_90'],
            $data['summary']['total'],
        ];

        return $rows;
    }

    /**
     * Convert sales by customer data to CSV rows
     *
     * @param array $data
     * @return array
     */
    private function salesByCustomerToRows(array $data): array
    {
        $rows = [];

        $rows[] = ['Ventas por cliente'];
        $rows[] = ['Periodo: ' . ($data['period']['start_date'] ?? '') . ' a ' . ($data['period']['end_date'] ?? '')];
        $rows[] = [];

        $rows[] = ['Cliente', 'Pedidos', 'Ventas'];

        foreach ($data['customers'] ?? [] as $customer) {
            $rows[] = [
                $customer['customer_name'],
                $customer['order_count'],
                $customer['total_sales'],
            ];
        }

        $rows[] = [];
        $rows[] = ['TOTALS', $data['summary']['total_orders'], $data['summary']['total_sales']];

        return $rows;
    }

    /**
     * Convert generic data to CSV rows
     *
     * @param array $data
     * @return array
     */
    private function genericDataToRows(array $data): array
    {
        $rows = [];

        // Escalares del reporte (periodo, moneda, totales planos) arriba
        foreach ($data as $key => $value) {
            if (is_scalar($value)) {
                $rows[] = [$this->label((string) $key), $value];
            }
        }

        // Cada lista de registros se vuelve una tabla con encabezados
        foreach ($data as $key => $value) {
            if (!is_array($value) || $value === []) {
                continue;
            }

            if (array_is_list($value) && is_array($value[0])) {
                $columns = array_keys(array_filter($value[0], fn ($v) => is_scalar($v) || $v === null));
                $rows[] = [];
                $rows[] = [$this->label((string) $key)];
                $rows[] = array_map(fn ($c) => $this->label((string) $c), $columns);
                foreach ($value as $record) {
                    $rows[] = array_map(fn ($c) => is_scalar($record[$c] ?? null) ? $record[$c] : '', $columns);
                }
            } elseif (!array_is_list($value)) {
                $scalars = array_filter($value, fn ($v) => is_scalar($v));
                if ($scalars !== []) {
                    $rows[] = [];
                    $rows[] = [$this->label((string) $key)];
                    foreach ($scalars as $subKey => $subValue) {
                        $rows[] = [$this->label((string) $subKey), $subValue];
                    }
                }
            }
        }

        return $rows;
    }

    /** Etiquetas en espanol de las llaves de los reportes (regla 9). */
    private const LABELS = [
        'average_cost' => 'Costo promedio', 'average_margin' => 'Margen promedio',
        'average_order_value' => 'Ticket promedio', 'average_per_period' => 'Promedio por periodo',
        'average_price' => 'Precio promedio', 'batch_id' => 'Id de lote', 'batch_number' => 'Lote',
        'batches' => 'Lotes', 'category_name' => 'Categoria', 'cost' => 'Costo', 'currency' => 'Moneda',
        'employee_id' => 'Id de vendedor', 'employee_name' => 'Vendedor', 'employees' => 'Vendedores',
        'end_date' => 'Hasta', 'estimated_revenue' => 'Ingreso estimado', 'expiration_date' => 'Caducidad',
        'gross_profit' => 'Utilidad bruta', 'group_by' => 'Agrupado por', 'is_expiring_soon' => 'Por caducar',
        'lot_number' => 'Numero de lote', 'margin_percentage' => 'Margen %', 'month' => 'Mes',
        'movement_count' => 'Movimientos', 'order_count' => 'Pedidos', 'period' => 'Periodo',
        'product_code' => 'Codigo', 'product_id' => 'Id de producto', 'product_name' => 'Producto',
        'product_sku' => 'SKU', 'products' => 'Productos', 'quantity_sold' => 'Cantidad vendida',
        'report_type' => 'Tipo de reporte', 'revenue' => 'Ingresos', 'start_date' => 'Desde',
        'subtotal' => 'Subtotal', 'summary' => 'Resumen', 'tax_total' => 'IVA', 'total_batches' => 'Total de lotes',
        'total_cost' => 'Costo total', 'total_employees' => 'Total de vendedores', 'total_orders' => 'Total de pedidos',
        'total_periods' => 'Periodos', 'total_products' => 'Total de productos', 'total_profit' => 'Utilidad total',
        'total_quantity' => 'Cantidad total', 'total_quantity_sold' => 'Cantidad vendida total',
        'total_revenue' => 'Ingresos totales', 'total_sales' => 'Ventas totales', 'trends' => 'Tendencia',
        'unit_cost' => 'Costo unitario', 'week' => 'Semana', 'data' => 'Detalle', 'total' => 'Total',
        'beginningCash' => 'Efectivo inicial', 'endingCash' => 'Efectivo final', 'netCashFlow' => 'Flujo neto',
        'operatingActivities' => 'Actividades de operacion', 'investingActivities' => 'Actividades de inversion',
        'financingActivities' => 'Actividades de financiamiento', 'startDate' => 'Desde', 'endDate' => 'Hasta',
        'generatedAt' => 'Generado', 'name' => 'Nombre', 'code' => 'Codigo', 'amount' => 'Importe',
        'description' => 'Descripcion', 'type' => 'Tipo', 'balance' => 'Saldo',
    ];

    /** Etiqueta en espanol de una llave; sin traduccion conocida, la llave legible. */
    private function label(string $key): string
    {
        return self::LABELS[$key] ?? ucfirst(str_replace('_', ' ', $key));
    }
}
