<?php

namespace Modules\Sales\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Modules\Sales\Models\FolioSequence;

/**
 * FolioSequenceController
 *
 * Manages folio sequence configurations for documents.
 * Admin-only endpoints for configuring document numbering.
 */
class FolioSequenceController extends Controller
{
    /**
     * Get all folio sequences
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        if (Gate::denies('sales.folio-sequences.index')) {
            abort(403, 'No tiene permisos para ver configuraciones de folios');
        }

        $sequences = FolioSequence::orderBy('document_type')->get();

        return response()->json([
            'data' => $sequences->map(fn ($seq) => $this->transformSequence($seq)),
        ]);
    }

    /**
     * Get folio sequence by document type
     *
     * @param string $documentType
     * @return JsonResponse
     */
    public function show(string $documentType): JsonResponse
    {
        if (Gate::denies('sales.folio-sequences.show')) {
            abort(403, 'No tiene permisos para ver configuraciones de folios');
        }

        $sequence = FolioSequence::where('document_type', $documentType)->first();

        if (!$sequence) {
            return response()->json([
                'error' => 'Configuracion de folio no encontrada',
            ], 404);
        }

        return response()->json([
            'data' => $this->transformSequence($sequence),
        ]);
    }

    /**
     * Update folio sequence configuration
     *
     * @param Request $request
     * @param string $documentType
     * @return JsonResponse
     */
    public function update(Request $request, string $documentType): JsonResponse
    {
        if (Gate::denies('sales.folio-sequences.update')) {
            abort(403, 'No tiene permisos para modificar configuraciones de folios');
        }

        $validated = $request->validate([
            'prefix' => 'sometimes|string|max:20',
            'include_year' => 'sometimes|boolean',
            'year_format' => 'sometimes|string|in:y,Y',
            'separator' => 'sometimes|string|max:5',
            'padding' => 'sometimes|integer|min:1|max:10',
            'reset_yearly' => 'sometimes|boolean',
        ]);

        $sequence = FolioSequence::updateOrCreate(
            ['document_type' => $documentType],
            array_merge($validated, ['is_active' => true])
        );

        return response()->json([
            'data' => $this->transformSequence($sequence),
            'message' => 'Configuracion de folio actualizada exitosamente',
        ]);
    }

    /**
     * Set initial sequence number
     *
     * Useful for migrating from existing numbering system.
     *
     * @param Request $request
     * @param string $documentType
     * @return JsonResponse
     */
    public function setInitialSequence(Request $request, string $documentType): JsonResponse
    {
        if (Gate::denies('sales.folio-sequences.update')) {
            abort(403, 'No tiene permisos para modificar configuraciones de folios');
        }

        $validated = $request->validate([
            'start_from' => 'required|integer|min:1',
        ]);

        $sequence = FolioSequence::where('document_type', $documentType)->first();

        if (!$sequence) {
            return response()->json([
                'error' => 'Configuracion de folio no encontrada',
            ], 404);
        }

        // Set sequence to start_from - 1 because next call will increment
        $sequence->update([
            'current_sequence' => $validated['start_from'] - 1,
        ]);

        return response()->json([
            'data' => $this->transformSequence($sequence->fresh()),
            'message' => 'Secuencia inicial configurada. El proximo folio sera: ' . $sequence->fresh()->previewNextFolio(),
        ]);
    }

    /**
     * Preview next folio without incrementing
     *
     * @param string $documentType
     * @return JsonResponse
     */
    public function previewNext(string $documentType): JsonResponse
    {
        $sequence = FolioSequence::where('document_type', $documentType)
            ->where('is_active', true)
            ->first();

        if (!$sequence) {
            return response()->json([
                'error' => 'Configuracion de folio no encontrada',
            ], 404);
        }

        return response()->json([
            'data' => [
                'document_type' => $documentType,
                'next_folio' => $sequence->previewNextFolio(),
                'current_sequence' => $sequence->current_sequence,
            ],
        ]);
    }

    /**
     * Transform sequence model for API response
     *
     * @param FolioSequence $sequence
     * @return array
     */
    protected function transformSequence(FolioSequence $sequence): array
    {
        return [
            'id' => $sequence->id,
            'documentType' => $sequence->document_type,
            'prefix' => $sequence->prefix,
            'includeYear' => $sequence->include_year,
            'yearFormat' => $sequence->year_format,
            'separator' => $sequence->separator,
            'padding' => $sequence->padding,
            'currentSequence' => $sequence->current_sequence,
            'resetYearly' => $sequence->reset_yearly,
            'lastResetYear' => $sequence->last_reset_year,
            'isActive' => $sequence->is_active,
            'nextFolio' => $sequence->previewNextFolio(),
            'createdAt' => $sequence->created_at?->toISOString(),
            'updatedAt' => $sequence->updated_at?->toISOString(),
        ];
    }
}
