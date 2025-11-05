<?php

namespace Modules\Billing\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Modules\Billing\Models\CFDIInvoice;
use Modules\Billing\Events\CFDIStamped;
use Modules\Billing\Events\CFDICancelled;

/**
 * PAC Webhook Handler
 *
 * Handles async notifications from SW Sapien PAC
 */
class PacWebhookController extends Controller
{
    /**
     * Handle stamping webhook notification
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function stampNotification(Request $request): JsonResponse
    {
        Log::info('PAC stamp webhook received', [
            'payload' => $request->all(),
        ]);

        try {
            // Validate webhook signature if configured
            if (!$this->validateWebhookSignature($request)) {
                Log::warning('Invalid webhook signature');
                return response()->json(['message' => 'Invalid signature'], 401);
            }

            $data = $request->validate([
                'uuid' => 'required|string',
                'folio' => 'nullable|string',
                'status' => 'required|string|in:valid,error',
                'fecha_timbrado' => 'nullable|date',
                'xml_timbrado' => 'nullable|string',
                'qr_code' => 'nullable|string',
                'error_message' => 'nullable|string',
            ]);

            // Find invoice by folio or UUID
            $invoice = null;
            if (!empty($data['folio'])) {
                // Parse folio (format: SERIE-FOLIO, e.g., F-000123)
                [$series, $folio] = explode('-', $data['folio']);
                $invoice = CFDIInvoice::where('series', $series)
                    ->where('folio', (int)$folio)
                    ->first();
            }

            if (!$invoice && !empty($data['uuid'])) {
                $invoice = CFDIInvoice::where('uuid', $data['uuid'])->first();
            }

            if (!$invoice) {
                Log::warning('Invoice not found for webhook', [
                    'folio' => $data['folio'] ?? null,
                    'uuid' => $data['uuid'] ?? null,
                ]);
                return response()->json(['message' => 'Invoice not found'], 404);
            }

            // Update invoice based on status
            if ($data['status'] === 'valid') {
                $invoice->update([
                    'uuid' => $data['uuid'],
                    'fecha_timbrado' => $data['fecha_timbrado'] ?? now(),
                    'xml_timbrado' => $data['xml_timbrado'] ?? null,
                    'qr_code' => $data['qr_code'] ?? null,
                    'status' => 'valid',
                ]);

                // Dispatch event
                event(new CFDIStamped($invoice));

                Log::info('Invoice stamped via webhook', [
                    'invoice_id' => $invoice->id,
                    'uuid' => $data['uuid'],
                ]);
            } else {
                $invoice->update([
                    'error_message' => $data['error_message'] ?? 'Error al timbrar',
                ]);

                Log::error('Invoice stamping failed via webhook', [
                    'invoice_id' => $invoice->id,
                    'error' => $data['error_message'] ?? 'Unknown error',
                ]);
            }

            return response()->json([
                'message' => 'Webhook processed successfully',
                'invoice_id' => $invoice->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Error processing stamp webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Error processing webhook',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Handle cancellation webhook notification
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function cancelNotification(Request $request): JsonResponse
    {
        Log::info('PAC cancel webhook received', [
            'payload' => $request->all(),
        ]);

        try {
            // Validate webhook signature if configured
            if (!$this->validateWebhookSignature($request)) {
                Log::warning('Invalid webhook signature');
                return response()->json(['message' => 'Invalid signature'], 401);
            }

            $data = $request->validate([
                'uuid' => 'required|string',
                'status' => 'required|string|in:cancelled,error',
                'fecha_cancelacion' => 'nullable|date',
                'acuse' => 'nullable|string',
                'error_message' => 'nullable|string',
            ]);

            // Find invoice by UUID
            $invoice = CFDIInvoice::where('uuid', $data['uuid'])->first();

            if (!$invoice) {
                Log::warning('Invoice not found for cancel webhook', [
                    'uuid' => $data['uuid'],
                ]);
                return response()->json(['message' => 'Invoice not found'], 404);
            }

            // Update invoice based on status
            if ($data['status'] === 'cancelled') {
                $invoice->update([
                    'status' => 'cancelled',
                    'fecha_cancelacion' => $data['fecha_cancelacion'] ?? now(),
                ]);

                // Dispatch event
                event(new CFDICancelled($invoice));

                Log::info('Invoice cancelled via webhook', [
                    'invoice_id' => $invoice->id,
                    'uuid' => $data['uuid'],
                ]);
            } else {
                $invoice->update([
                    'error_message' => $data['error_message'] ?? 'Error al cancelar',
                ]);

                Log::error('Invoice cancellation failed via webhook', [
                    'invoice_id' => $invoice->id,
                    'error' => $data['error_message'] ?? 'Unknown error',
                ]);
            }

            return response()->json([
                'message' => 'Webhook processed successfully',
                'invoice_id' => $invoice->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Error processing cancel webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Error processing webhook',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Validate webhook signature
     *
     * @param Request $request
     * @return bool
     */
    protected function validateWebhookSignature(Request $request): bool
    {
        $secret = config('billing.sw_pac.webhook_secret');

        // If no secret is configured, skip validation
        if (empty($secret)) {
            return true;
        }

        $signature = $request->header('X-SW-Signature');

        if (empty($signature)) {
            return false;
        }

        // Compute expected signature
        $payload = $request->getContent();
        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expectedSignature, $signature);
    }
}
