<?php

namespace Modules\Billing\Http\Controllers\Api\V1;

use LaravelJsonApi\Laravel\Http\Controllers\Actions;
use Modules\Billing\Models\InvoiceSeries;
use Modules\Billing\Models\CompanySetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * SA-M011: Invoice Series Controller
 *
 * Manages multiple invoice series (FAC, FAC-W, REFAC, N) with configurable
 * folio numbering and role-based restrictions.
 */
class InvoiceSeriesController
{
    use Actions\FetchMany;
    use Actions\FetchOne;
    use Actions\Store;
    use Actions\Update;
    use Actions\Destroy;
    use Actions\FetchRelated;
    use Actions\FetchRelationship;

    /**
     * Get available series for a CFDI type.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function available(Request $request): JsonResponse
    {
        if (Gate::denies('billing.invoice-series.index')) {
            abort(403, 'No tiene permisos para ver series de facturacion');
        }

        $validated = $request->validate([
            'cfdi_type' => ['required', 'string', 'in:I,E,P,N,T'],
            'source' => ['nullable', 'string', 'in:web,pos,manual'],
            'company_setting_id' => ['nullable', 'integer', 'exists:company_settings,id'],
        ]);

        $series = InvoiceSeries::getAvailable(
            $validated['cfdi_type'],
            $validated['source'] ?? null,
            $validated['company_setting_id'] ?? null
        );

        return response()->json([
            'data' => $series->map(fn ($s) => [
                'id' => $s->id,
                'code' => $s->code,
                'name' => $s->name,
                'description' => $s->description,
                'cfdi_type' => $s->cfdi_type,
                'cfdi_type_label' => $s->cfdi_type_label,
                'is_default' => $s->is_default,
                'next_folio_preview' => $s->previewNextFolio(),
            ]),
            'meta' => [
                'count' => $series->count(),
                'cfdi_type' => $validated['cfdi_type'],
            ],
        ]);
    }

    /**
     * Preview next folio for a series.
     *
     * @param InvoiceSeries $invoiceSeries
     * @return JsonResponse
     */
    public function previewNextFolio(InvoiceSeries $invoiceSeries): JsonResponse
    {
        if (Gate::denies('billing.invoice-series.show')) {
            abort(403, 'No tiene permisos para ver series de facturacion');
        }

        return response()->json([
            'data' => [
                'id' => $invoiceSeries->id,
                'code' => $invoiceSeries->code,
                'name' => $invoiceSeries->name,
                'current_folio' => $invoiceSeries->current_folio,
                'next_folio_preview' => $invoiceSeries->previewNextFolio(),
            ],
        ]);
    }

    /**
     * Set initial folio for a series.
     *
     * @param InvoiceSeries $invoiceSeries
     * @param Request $request
     * @return JsonResponse
     */
    public function setInitialFolio(InvoiceSeries $invoiceSeries, Request $request): JsonResponse
    {
        if (Gate::denies('billing.invoice-series.update')) {
            abort(403, 'No tiene permisos para modificar series de facturacion');
        }

        $validated = $request->validate([
            'folio' => ['required', 'integer', 'min:1'],
        ]);

        $invoiceSeries->setInitialFolio($validated['folio']);
        $invoiceSeries->refresh();

        return response()->json([
            'message' => 'Folio inicial establecido correctamente',
            'data' => [
                'id' => $invoiceSeries->id,
                'code' => $invoiceSeries->code,
                'initial_folio' => $invoiceSeries->initial_folio,
                'current_folio' => $invoiceSeries->current_folio,
                'next_folio_preview' => $invoiceSeries->previewNextFolio(),
            ],
        ]);
    }

    /**
     * Set series as default for its CFDI type.
     *
     * @param InvoiceSeries $invoiceSeries
     * @return JsonResponse
     */
    public function setAsDefault(InvoiceSeries $invoiceSeries): JsonResponse
    {
        if (Gate::denies('billing.invoice-series.update')) {
            abort(403, 'No tiene permisos para modificar series de facturacion');
        }

        // Remove default from other series of same type
        InvoiceSeries::where('company_setting_id', $invoiceSeries->company_setting_id)
            ->where('cfdi_type', $invoiceSeries->cfdi_type)
            ->where('id', '!=', $invoiceSeries->id)
            ->update(['is_default' => false]);

        // Set this as default
        $invoiceSeries->update(['is_default' => true]);

        return response()->json([
            'message' => 'Serie establecida como predeterminada',
            'data' => [
                'id' => $invoiceSeries->id,
                'code' => $invoiceSeries->code,
                'name' => $invoiceSeries->name,
                'cfdi_type' => $invoiceSeries->cfdi_type,
                'is_default' => true,
            ],
        ]);
    }

    /**
     * Initialize default series for a company setting.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function initializeDefaults(Request $request): JsonResponse
    {
        if (Gate::denies('billing.invoice-series.store')) {
            abort(403, 'No tiene permisos para crear series de facturacion');
        }

        $validated = $request->validate([
            'company_setting_id' => ['nullable', 'integer', 'exists:company_settings,id'],
        ]);

        $companySettingId = $validated['company_setting_id'] ?? null;

        if ($companySettingId) {
            $setting = CompanySetting::find($companySettingId);
        } else {
            $setting = CompanySetting::getActive();
        }

        if (!$setting) {
            return response()->json([
                'message' => 'No se encontro configuracion de empresa',
            ], 404);
        }

        InvoiceSeries::initializeDefaults($setting);

        $series = InvoiceSeries::where('company_setting_id', $setting->id)
            ->orderBy('code')
            ->get();

        return response()->json([
            'message' => 'Series inicializadas correctamente',
            'data' => $series->map(fn ($s) => [
                'id' => $s->id,
                'code' => $s->code,
                'name' => $s->name,
                'cfdi_type' => $s->cfdi_type,
                'is_default' => $s->is_default,
            ]),
            'meta' => [
                'company_setting_id' => $setting->id,
                'count' => $series->count(),
            ],
        ]);
    }

    /**
     * Get summary statistics for all series.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function summary(Request $request): JsonResponse
    {
        if (Gate::denies('billing.invoice-series.index')) {
            abort(403, 'No tiene permisos para ver series de facturacion');
        }

        $companySettingId = $request->query('company_setting_id');

        $query = InvoiceSeries::query();

        if ($companySettingId) {
            $query->where('company_setting_id', $companySettingId);
        } else {
            $setting = CompanySetting::getActive();
            if ($setting) {
                $query->where('company_setting_id', $setting->id);
            }
        }

        $series = $query->get();

        $byCfdiType = $series->groupBy('cfdi_type')->map(function ($group) {
            return [
                'count' => $group->count(),
                'active' => $group->where('is_active', true)->count(),
                'default' => $group->firstWhere('is_default', true)?->code,
            ];
        });

        return response()->json([
            'data' => [
                'total_series' => $series->count(),
                'active_series' => $series->where('is_active', true)->count(),
                'by_cfdi_type' => $byCfdiType,
                'series' => $series->map(fn ($s) => [
                    'id' => $s->id,
                    'code' => $s->code,
                    'name' => $s->name,
                    'cfdi_type' => $s->cfdi_type,
                    'current_folio' => $s->current_folio,
                    'is_active' => $s->is_active,
                    'is_default' => $s->is_default,
                ]),
            ],
        ]);
    }
}
