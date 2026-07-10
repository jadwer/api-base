<?php

namespace Modules\Reports\Services\Export;

use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use League\Csv\Writer;
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

        $export = match ($reportType) {
            'balance-sheet' => new \Modules\Reports\Exports\BalanceSheetExport($data),
            'income-statement' => new \Modules\Reports\Exports\IncomeStatementExport($data),
            'trial-balance' => new \Modules\Reports\Exports\TrialBalanceExport($data),
            default => new \Modules\Reports\Exports\GenericReportExport($data),
        };

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

        $view = match ($reportType) {
            'balance-sheet' => 'reports::exports.balance-sheet-pdf',
            'income-statement' => 'reports::exports.income-statement-pdf',
            'cash-flow' => 'reports::exports.cash-flow-pdf',
            'trial-balance' => 'reports::exports.trial-balance-pdf',
            default => 'reports::exports.generic-report-pdf',
        };

        $pdf = Pdf::loadView($view, ['data' => $data]);
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
     * Convert balance sheet data to CSV rows
     *
     * @param array $data
     * @return array
     */
    private function balanceSheetToRows(array $data): array
    {
        $rows = [];

        // Header
        $rows[] = ['Balance Sheet'];
        $rows[] = ['As of: ' . ($data['as_of_date'] ?? '')];
        $rows[] = ['Currency: ' . ($data['currency'] ?? 'MXN')];
        $rows[] = [];

        // Assets
        $rows[] = ['ASSETS'];
        $rows[] = ['Current Assets'];

        foreach ($data['assets']['current_assets']['accounts'] ?? [] as $account) {
            $rows[] = [$account['code'], $account['name'], $account['balance']];
        }

        $rows[] = ['', 'Total Current Assets', $data['assets']['current_assets']['total']];
        $rows[] = [];

        // Liabilities
        $rows[] = ['LIABILITIES'];
        foreach ($data['liabilities']['current_liabilities']['accounts'] ?? [] as $account) {
            $rows[] = [$account['code'], $account['name'], $account['balance']];
        }

        $rows[] = ['', 'Total Liabilities', $data['liabilities']['total_liabilities']];
        $rows[] = [];

        // Equity
        $rows[] = ['EQUITY'];
        foreach ($data['equity']['accounts'] ?? [] as $account) {
            $rows[] = [$account['code'], $account['name'], $account['balance']];
        }

        $rows[] = ['', 'Total Equity', $data['equity']['total_equity']];

        return $rows;
    }

    /**
     * Convert income statement data to CSV rows
     *
     * @param array $data
     * @return array
     */
    private function incomeStatementToRows(array $data): array
    {
        $rows = [];

        $rows[] = ['Income Statement'];
        $rows[] = ['Period: ' . ($data['period']['start_date'] ?? '') . ' to ' . ($data['period']['end_date'] ?? '')];
        $rows[] = [];

        $rows[] = ['REVENUE'];
        foreach ($data['revenue']['accounts'] ?? [] as $account) {
            $rows[] = [$account['code'], $account['name'], $account['amount']];
        }
        $rows[] = ['', 'Total Revenue', $data['revenue']['total_revenue']];
        $rows[] = [];

        $rows[] = ['', 'Cost of Goods Sold', $data['cost_of_goods_sold']];
        $rows[] = ['', 'Gross Profit', $data['gross_profit']];
        $rows[] = [];

        $rows[] = ['OPERATING EXPENSES'];
        foreach ($data['operating_expenses']['accounts'] ?? [] as $account) {
            $rows[] = [$account['code'], $account['name'], $account['amount']];
        }
        $rows[] = ['', 'Total Operating Expenses', $data['operating_expenses']['total_operating_expenses']];
        $rows[] = [];

        $rows[] = ['', 'Operating Income', $data['operating_income']];
        $rows[] = ['', 'NET INCOME', $data['net_income']];

        return $rows;
    }

    /**
     * Convert trial balance data to CSV rows
     *
     * @param array $data
     * @return array
     */
    private function trialBalanceToRows(array $data): array
    {
        $rows = [];

        $rows[] = ['Trial Balance'];
        $rows[] = ['As of: ' . ($data['as_of_date'] ?? '')];
        $rows[] = [];

        $rows[] = ['Code', 'Account Name', 'Type', 'Debit Balance', 'Credit Balance'];

        foreach ($data['accounts'] ?? [] as $account) {
            $rows[] = [
                $account['code'],
                $account['name'],
                $account['account_type'],
                $account['debit_balance'],
                $account['credit_balance'],
            ];
        }

        $rows[] = [];
        $rows[] = ['', '', 'TOTALS', $data['totals']['total_debits'], $data['totals']['total_credits']];

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

        $rows[] = ['Aging Report - ' . ($data['report_type'] ?? '')];
        $rows[] = ['As of: ' . ($data['as_of_date'] ?? '')];
        $rows[] = [];

        $entityKey = isset($data['customers']) ? 'customers' : 'suppliers';
        $nameKey = isset($data['customers']) ? 'customer_name' : 'supplier_name';

        $rows[] = ['Name', 'Current', '1-30 Days', '31-60 Days', '61-90 Days', 'Over 90 Days', 'Total'];

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

        $rows[] = ['Sales by Customer'];
        $rows[] = ['Period: ' . ($data['period']['start_date'] ?? '') . ' to ' . ($data['period']['end_date'] ?? '')];
        $rows[] = [];

        $rows[] = ['Customer Name', 'Order Count', 'Total Sales'];

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

        // Just convert the data array to rows
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $rows[] = [$key];
                foreach ($value as $subKey => $subValue) {
                    if (is_scalar($subValue)) {
                        $rows[] = ['', $subKey, $subValue];
                    }
                }
            } else {
                $rows[] = [$key, $value];
            }
        }

        return $rows;
    }
}
