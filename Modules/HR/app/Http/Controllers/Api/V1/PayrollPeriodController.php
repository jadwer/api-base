<?php

namespace Modules\HR\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use LaravelJsonApi\Laravel\Http\Controllers\Actions;
use Modules\HR\Models\PayrollPeriod;
use Modules\HR\Services\PayrollService;

class PayrollPeriodController extends Controller
{
    use Actions\FetchMany;
    use Actions\FetchOne;
    use Actions\Store;
    use Actions\Update;
    use Actions\Destroy;
    use Actions\FetchRelated;
    use Actions\FetchRelationship;

    protected PayrollService $payrollService;

    public function __construct(PayrollService $payrollService)
    {
        $this->payrollService = $payrollService;
    }

    /**
     * Process a payroll period - calculate totals and update status.
     * POST /api/v1/payroll-periods/{id}/process
     */
    public function process(Request $request, PayrollPeriod $payrollPeriod)
    {
        try {
            $period = $this->payrollService->processPeriod($payrollPeriod);

            return response()->json([
                'message' => 'Período de nómina procesado exitosamente.',
                'data' => $period,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al procesar el período de nómina.',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Mark a payroll period as paid and post to GL.
     * POST /api/v1/payroll-periods/{id}/mark-as-paid
     */
    public function markAsPaid(Request $request, PayrollPeriod $payrollPeriod)
    {
        try {
            $userId = $request->user()->id;
            $result = $this->payrollService->markAsPaid($payrollPeriod, $userId);

            return response()->json([
                'message' => 'Período de nómina marcado como pagado y registrado en contabilidad.',
                'data' => [
                    'period' => $result['period'],
                    'journal_entry_id' => $result['journal_entry']->id,
                    'journal_entry_reference' => $result['journal_entry']->reference,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al marcar el período como pagado.',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Close a payroll period.
     * POST /api/v1/payroll-periods/{id}/close
     */
    public function close(Request $request, PayrollPeriod $payrollPeriod)
    {
        try {
            $period = $this->payrollService->closePeriod($payrollPeriod);

            return response()->json([
                'message' => 'Período de nómina cerrado exitosamente.',
                'data' => $period,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al cerrar el período de nómina.',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Reopen a closed payroll period.
     * POST /api/v1/payroll-periods/{id}/reopen
     */
    public function reopen(Request $request, PayrollPeriod $payrollPeriod)
    {
        try {
            $period = $this->payrollService->reopenPeriod($payrollPeriod);

            return response()->json([
                'message' => 'Período de nómina reabierto exitosamente.',
                'data' => $period,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al reabrir el período de nómina.',
                'error' => $e->getMessage(),
            ], 422);
        }
    }
}
