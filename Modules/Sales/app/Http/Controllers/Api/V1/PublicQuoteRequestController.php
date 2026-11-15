<?php

namespace Modules\Sales\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Contacts\Models\Contact;
use Modules\MailerManager\Models\SystemEmail;
use Modules\Sales\Mail\QuoteRequestedMail;
use Modules\Sales\Models\Quote;
use Modules\Sales\Models\QuoteItem;
use Modules\Sales\Services\CartQuotePdfService;
use Modules\Sales\Services\QuotePDFGenerator;
use Modules\Sales\Support\SalesNotificationEmail;
use Symfony\Component\HttpFoundation\Response;

/**
 * Solicitud de cotizacion desde la tienda sin registrarse (junta con el cliente
 * 2026-11-12, J2 y J3). El visitante deja nombre, correo, empresa y a quien
 * va dirigida; la cotizacion nace en el sistema con folio, en borrador, con
 * los precios del catalogo publico, y llega a ventas por correo para que
 * asigne al vendedor. El visitante descarga el PDF de esa cotizacion.
 *
 * Sin auth y con efectos: va con throttle, campo trampa contra bots y
 * limite de partidas. El contacto se reusa por correo.
 */
class PublicQuoteRequestController extends Controller
{
    public function store(Request $request, CartQuotePdfService $catalog): Response|JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:190'],
            'company' => ['required', 'string', 'max:190'],
            'attention_to' => ['required', 'string', 'max:190'],
            'phone' => ['nullable', 'string', 'max:40'],
            'website' => ['nullable', 'max:0'], // campo trampa: una persona lo deja vacio
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.product_id' => ['required', 'integer', 'min:1'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0', 'max:1000000'],
        ], [
            'website.max' => 'No se pudo enviar la solicitud.',
        ], [
            'name' => 'nombre',
            'email' => 'correo',
            'company' => 'empresa',
            'attention_to' => 'a quién va dirigida',
            'phone' => 'teléfono',
            'items' => 'productos',
        ]);

        $quantities = [];
        foreach ($data['items'] as $item) {
            $quantities[(int) $item['product_id']] = ($quantities[(int) $item['product_id']] ?? 0) + (float) $item['quantity'];
        }
        $products = $catalog->publicProducts(array_keys($quantities));
        if ($products->isEmpty()) {
            return response()->json(['message' => 'Ninguno de los productos del carrito está disponible en el catálogo.'], 422);
        }

        $requester = [
            'name' => trim($data['name']),
            'email' => mb_strtolower(trim($data['email'])),
            'company' => trim($data['company']),
            'attention_to' => trim($data['attention_to']),
            'phone' => isset($data['phone']) ? trim((string) $data['phone']) : null,
        ];

        $quote = DB::transaction(function () use ($requester, $products, $quantities): Quote {
            $contact = Contact::query()->whereRaw('LOWER(email) = ?', [$requester['email']])->first()
                ?? Contact::create([
                    'contact_type' => 'company',
                    'name' => $requester['company'],
                    'email' => $requester['email'],
                    'phone' => $requester['phone'],
                    'status' => 'active',
                    'is_customer' => true,
                    'is_supplier' => false,
                    'notes' => "Alta desde la tienda en línea. Contacto: {$requester['name']}.",
                ]);

            $quote = Quote::create([
                'contact_id' => $contact->id,
                'quote_number' => Quote::generateQuoteNumber(),
                'status' => 'draft',
                'quote_date' => now(),
                'valid_until' => now()->addDays(30),
                'currency' => 'MXN',
                'notes' => "Solicitud desde la tienda en línea. Dirigida a: {$requester['attention_to']}. Contacto: {$requester['name']}"
                    .($requester['phone'] ? ", tel. {$requester['phone']}" : '').'.',
                'metadata' => ['source' => 'tienda', 'store_request' => $requester],
            ]);

            foreach ($products as $product) {
                $leadTime = $product->brand?->getAttribute('default_lead_time');
                $notes = $leadTime ? 'ETA: '.$leadTime : null;
                // Granel: precio por confirmar (0 + price_pending); el vendedor lo captura.
                $unitPrice = $product->requiresQuote() ? 0 : (float) $product->price;
                QuoteItem::create([
                    'quote_id' => $quote->id,
                    'product_id' => $product->id,
                    'quantity' => $quantities[$product->id],
                    'unit_price' => $unitPrice,
                    'quoted_price' => $unitPrice,
                    'discount_percentage' => 0,
                    'tax_rate' => $product->effective_tax_rate ?? 0,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                    'notes' => $notes,
                ]);
            }
            $quote->recalculateTotals();

            return $quote;
        });

        activity('quote_request')
            ->performedOn($quote)
            ->withProperties(['source' => 'tienda', 'email' => $requester['email'], 'company' => $requester['company']])
            ->log('Solicitud de cotización desde la tienda');

        // Aviso a ventas (J3); un correo que falla nunca tumba la solicitud.
        try {
            if (SystemEmail::isEnabled('sales.quote_requested.admin')) {
                Mail::to(SalesNotificationEmail::address())->send(new QuoteRequestedMail($quote, true));
            }
        } catch (\Throwable $e) {
            Log::error('No se pudo avisar a ventas de la cotización de la tienda', ['quote_id' => $quote->id, 'error' => $e->getMessage()]);
        }

        $quote->refresh();
        $pdf = app(QuotePDFGenerator::class)->stream($quote);
        $pdf->headers->set('Content-Disposition', 'attachment; filename="cotizacion-'.$quote->quote_number.'.pdf"');
        $pdf->headers->set('X-Quote-Number', (string) $quote->quote_number);
        $pdf->headers->set('Access-Control-Expose-Headers', 'X-Quote-Number');

        return $pdf;
    }
}
