<?php

namespace Modules\Accounting\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use LaravelJsonApi\Laravel\Http\Controllers\Actions;
use Modules\Accounting\Models\JournalEntry;
use Modules\Accounting\Services\AccountingService;

class JournalEntryController extends Controller
{
    use Actions\FetchMany;
    use Actions\FetchOne;
    use Actions\Store;
    use Actions\Update;
    use Actions\Destroy;
    use Actions\FetchRelated;
    use Actions\FetchRelationship;
    use Actions\UpdateRelationship;
    use Actions\AttachRelationship;
    use Actions\DetachRelationship;

    /**
     * Contabiliza una poliza en borrador o aprobada: valida cuadre, periodo
     * abierto que contenga la fecha y cuentas de detalle activas, y asigna
     * folio. Es el unico camino a "posted" para las polizas manuales.
     */
    public function post(Request $request, JournalEntry $journalEntry, AccountingService $accountingService): JsonResponse
    {
        $user = $request->user('sanctum');
        if (!$user) {
            return response()->json(['error' => 'No autenticado.'], 401);
        }
        if (!$user->can('journal-entries.update')) {
            return response()->json(['error' => 'No tiene permisos para esta acción.'], 403);
        }

        if (!in_array($journalEntry->status, [JournalEntry::STATUS_DRAFT, JournalEntry::STATUS_APPROVED], true)) {
            return response()->json([
                'error' => "Solo se contabilizan pólizas en borrador o aprobadas (estado actual: {$journalEntry->status}).",
            ], 422);
        }

        try {
            $accountingService->postJournalEntry($journalEntry);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        $journalEntry->refresh();

        return response()->json([
            'message' => 'Póliza contabilizada.',
            'data' => [
                'id' => (string) $journalEntry->id,
                'number' => $journalEntry->number,
                'status' => $journalEntry->status,
                'postedAt' => $journalEntry->posted_at?->toIso8601String(),
                'totalDebit' => round((float) $journalEntry->total_debit, 2),
                'totalCredit' => round((float) $journalEntry->total_credit, 2),
            ],
        ]);
    }
}
