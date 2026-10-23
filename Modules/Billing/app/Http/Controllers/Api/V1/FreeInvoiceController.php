<?php

namespace Modules\Billing\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Modules\Billing\Models\CFDIInvoice;
use Modules\Billing\Models\CompanySetting;
use Modules\Billing\Models\InvoiceSeries;
use Modules\Billing\Services\CFDI\CfdiFolioAllocator;
use Modules\Billing\Services\CFDI\FreeInvoiceService;
use Modules\Billing\Support\CfdiReceptor;
use Modules\Contacts\Models\Contact;
use Modules\Contacts\Support\SatCatalogs;
use Modules\SatCatalogs\Models\SatFormaPago;

/**
 * Factura libre (estilo Bind): opciones del formulario y alta del borrador.
 * Las opciones salen del backend para que el formulario no duplique catalogos
 * (regla 7).
 */
class FreeInvoiceController extends Controller
{
    public function options(Request $request): JsonResponse
    {
        if ($denied = $this->deny($request)) {
            return $denied;
        }

        $settings = CompanySetting::where('is_active', true)->first();
        $series = $settings ? InvoiceSeries::getAvailable('I', null, $settings->id) : collect();
        $toOptions = fn (array $map) => collect($map)->map(fn ($label, $code) => ['code' => (string) $code, 'label' => $label])->values();

        return response()->json(['data' => [
            'series' => $series->map(fn (InvoiceSeries $s) => [
                'code' => $s->code,
                'name' => $s->name,
                'is_default' => (bool) $s->is_default,
                // Misma regla que la asignacion real (CfdiFolioAllocator)
                'next_folio' => implode('-', app(CfdiFolioAllocator::class)->peek($settings, 'I', $s->code)),
            ])->values(),
            'metodos_pago' => $toOptions(FreeInvoiceService::METODOS_PAGO),
            'tasas_iva' => $toOptions(FreeInvoiceService::TASAS_IVA),
            'formas_pago' => SatFormaPago::orderBy('clave')->get(['clave', 'descripcion'])
                ->map(fn ($f) => ['code' => $f->clave, 'label' => "{$f->clave} - {$f->descripcion}"])->values(),
            'usos_cfdi' => SatCatalogs::toApiPayload()['usos_cfdi'],
        ]]);
    }

    /** Datos fiscales del receptor que llevara el CFDI, con lo que falta para timbrar. */
    public function receptor(Request $request, Contact $contact): JsonResponse
    {
        if ($denied = $this->deny($request)) {
            return $denied;
        }

        $settings = CompanySetting::where('is_active', true)->first();
        $contact->load('contactAddresses');
        $receptor = CfdiReceptor::fromContact($contact, $settings?->postal_code);

        $missing = [];
        if ($receptor['receptor_rfc'] === CfdiReceptor::GENERIC_RFC && trim((string) $contact->tax_id) === '') {
            $missing[] = 'RFC (se facturará a PÚBLICO EN GENERAL)';
        }
        if (empty($receptor['receptor_domicilio_fiscal'])) {
            $missing[] = 'Código postal de la dirección fiscal';
        }
        if ($receptor['receptor_rfc'] !== CfdiReceptor::GENERIC_RFC && empty($contact->regimen_fiscal)) {
            $missing[] = 'Régimen fiscal';
        }

        return response()->json(['data' => [
            'rfc' => $receptor['receptor_rfc'],
            'nombre' => $receptor['receptor_nombre'],
            'codigo_postal' => $receptor['receptor_domicilio_fiscal'],
            // Solo el capturado: el 616 por omision de CfdiReceptor se mostraba como dato real (E2E 23-10)
            'regimen_fiscal' => $receptor['receptor_rfc'] === CfdiReceptor::GENERIC_RFC
                ? $receptor['receptor_regimen_fiscal']
                : ($contact->regimen_fiscal ?: null),
            'uso_cfdi' => $receptor['receptor_uso_cfdi'],
            'email' => $contact->email,
            'payment_terms' => $contact->payment_terms,
            'faltantes' => $missing,
        ]]);
    }

    /** Edita un borrador de factura libre (sin timbrar); serie y folio no cambian. */
    public function update(Request $request, CFDIInvoice $cfdiInvoice, FreeInvoiceService $service): JsonResponse
    {
        if ($denied = $this->deny($request)) {
            return $denied;
        }

        $data = $this->validatePayload($request);

        try {
            $cfdi = $service->update($cfdiInvoice, $data);
        } catch (\DomainException|\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage(), 'error' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Borrador actualizado',
            'data' => ['id' => $cfdi->id, 'series' => $cfdi->series, 'folio' => $cfdi->folio, 'status' => $cfdi->status, 'total' => $cfdi->total],
        ]);
    }

    public function store(Request $request, FreeInvoiceService $service): JsonResponse
    {
        if ($denied = $this->deny($request)) {
            return $denied;
        }

        $data = $this->validatePayload($request);

        return $this->doStore($service, $data);
    }

    /** @return array<string, mixed> */
    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'contact_id' => ['required', 'integer', 'exists:contacts,id'],
            'series' => ['nullable', 'string', 'max:10'],
            'metodo_pago' => ['required', Rule::in(array_keys(FreeInvoiceService::METODOS_PAGO))],
            'forma_pago' => [
                'required', 'string', 'exists:sat_forma_pago,clave',
                // Regla SAT: PPD siempre 99; PUE nunca 99
                Rule::when($request->input('metodo_pago') === 'PPD', [Rule::in(['99'])]),
                Rule::when($request->input('metodo_pago') === 'PUE', [Rule::notIn(['99'])]),
            ],
            'uso_cfdi' => ['nullable', 'string', Rule::in(SatCatalogs::usoCfdiCodes())],
            'condiciones_pago' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1', 'max:200'],
            'items.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
            'items.*.descripcion' => ['required', 'string', 'max:1000'],
            'items.*.cantidad' => ['required', 'numeric', 'gt:0'],
            'items.*.valor_unitario' => ['required', 'numeric', 'gt:0'],
            'items.*.descuento' => ['nullable', 'numeric', 'min:0'],
            'items.*.clave_prod_serv' => ['required', 'string', 'exists:sat_clave_prod_serv,clave'],
            'items.*.clave_unidad' => ['required', 'string', 'exists:sat_clave_unidad,clave'],
            'items.*.unidad' => ['nullable', 'string', 'max:50'],
            'items.*.no_identificacion' => ['nullable', 'string', 'max:100'],
            'items.*.tasa_iva' => ['required', Rule::in(array_keys(FreeInvoiceService::TASAS_IVA))],
        ], [
            'forma_pago.in' => 'Con método PPD la forma de pago debe ser 99 (Por definir).',
            'forma_pago.not_in' => 'Con método PUE indica la forma de pago real; 99 (Por definir) solo aplica a PPD.',
        ], [
            'contact_id' => 'cliente',
            'series' => 'serie',
            'metodo_pago' => 'método de pago',
            'forma_pago' => 'forma de pago',
            'uso_cfdi' => 'uso del CFDI',
            'condiciones_pago' => 'condiciones de pago',
            'items' => 'conceptos',
            'items.*.product_id' => 'producto del concepto :position',
            'items.*.descripcion' => 'descripción del concepto :position',
            'items.*.cantidad' => 'cantidad del concepto :position',
            'items.*.valor_unitario' => 'precio unitario del concepto :position',
            'items.*.descuento' => 'descuento del concepto :position',
            'items.*.clave_prod_serv' => 'clave SAT de producto del concepto :position',
            'items.*.clave_unidad' => 'clave SAT de unidad del concepto :position',
            'items.*.tasa_iva' => 'impuesto del concepto :position',
        ]);

    }

    /** @param array<string, mixed> $data */
    private function doStore(FreeInvoiceService $service, array $data): JsonResponse
    {
        try {
            $cfdi = $service->create($data);
        } catch (\DomainException|\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage(), 'error' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            Log::error('Free CFDI creation failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'No se pudo crear la factura',
                'error' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'message' => 'Factura creada como borrador',
            'data' => [
                'id' => $cfdi->id,
                'series' => $cfdi->series,
                'folio' => $cfdi->folio,
                'status' => $cfdi->status,
                'total' => $cfdi->total,
                'ar_invoice_id' => $cfdi->ar_invoice_id,
            ],
        ], 201);
    }

    private function deny(Request $request): ?JsonResponse
    {
        $user = $request->user('sanctum');
        if (!$user) {
            return response()->json(['message' => 'No autenticado.'], 401);
        }
        if (!$user->can('billing.cfdi-invoices.store')) {
            return response()->json(['message' => 'No tiene permisos para crear facturas.'], 403);
        }

        return null;
    }
}
