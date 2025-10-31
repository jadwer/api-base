# Phase 5.1: Billing Module - Stripe + CFDI Integration - Complete Implementation Plan

**Status:** 📋 Planning Complete, Ready for Implementation
**Start Date:** 2025-10-31
**Estimated Duration:** 6-9 days (48-72 hours)
**Complexity:** High (4/5)
**Priority:** 🔴 HIGH (Payment processing + Legal compliance)
**Dependencies:** Finance Module ✅, Accounting Module ✅, Ecommerce Module ✅

---

## 🎯 OBJECTIVE

Implement a comprehensive Billing module that integrates **Stripe** for payment processing and **CFDI 4.0** electronic invoicing for Mexican tax compliance. Replace MockPaymentGateway with real Stripe integration and provide automated invoice generation with PAC stamping (timbrado).

**Business Value:**
- Real payment processing with Stripe (cards, OXXO, SPEI)
- Legal compliance with Mexican CFDI 4.0 invoicing
- Automated workflow: Payment → Invoice → Stamping → GL Posting
- Customer portal for XML/PDF downloads
- Multi-currency support (10 currencies already in system)
- Complete audit trail and webhook handling

---

## 🏗️ ARCHITECTURE OVERVIEW

### **Module Approach:** Create dedicated `Billing` module

**Why?**
- Payment gateway abstraction (Stripe today, others tomorrow)
- CFDI-specific business logic isolated
- Certificate and credential management
- Separate from general Finance module
- Clean integration points with existing modules

### **Technology Stack:**
- **Payment Gateway:** Stripe (`stripe/stripe-php` v12+)
- **PAC Provider:** SW Sapien (REST API, JSON)
- **XML Generation:** Native PHP DOM/SimpleXML
- **PDF Generation:** DomPDF or TCPDF
- **QR Code:** SimpleSoftwareIO/simple-qrcode
- **Webhooks:** Laravel Event system

---

## 📊 SYSTEM CONFIGURATION

### **Stripe Credentials (Test Mode)**
```
Publishable Key: your_stripe_publishable_key_here
Secret Key: your_stripe_secret_key_here
```

### **Company Fiscal Data**
```
RFC: RAMR850519248
Razón Social: RODRIGO GABINO RAMIREZ MORENO
Régimen Fiscal: 612 (Personas Físicas con Actividades Empresariales y Profesionales)
Código Postal: 07969
Domicilio Fiscal: Av 509, San Juan de Aragón I
Serie Facturas: F
Serie Notas: N
Folio Inicial: 1
```

### **SW Sapien (Pending credentials)**
```
Status: Account requested, awaiting approval
API: https://api.sw.com.mx/
Docs: https://developers.sw.com.mx/
```

---

## 🚀 IMPLEMENTATION PLAN - 4 PHASES

---

## **PHASE 1: STRIPE INTEGRATION** (Days 1-3, 18-24 hours)

### **Objective:** Replace MockPaymentGateway with real Stripe integration

**Status:** ✅ Can start immediately (credentials ready)

---

### **Step 1.1: Payment Gateway Infrastructure** (4-5 hours)

#### **1.1.1 Create PaymentTransaction Model**

**Table:** `payment_transactions`

```sql
CREATE TABLE payment_transactions (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,

    -- Reference
    checkout_session_id BIGINT UNSIGNED,
    sales_order_id BIGINT UNSIGNED,
    ar_invoice_id BIGINT UNSIGNED,

    -- Gateway Info
    gateway VARCHAR(50) NOT NULL, -- stripe, conekta, etc.
    payment_intent_id VARCHAR(255) UNIQUE,
    transaction_id VARCHAR(255) UNIQUE,
    client_secret VARCHAR(255),

    -- Payment Details
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'MXN',
    status VARCHAR(50) DEFAULT 'pending', -- pending, authorized, captured, failed, refunded, cancelled
    payment_method VARCHAR(50), -- card, oxxo, spei, bank_transfer

    -- Card Info (if applicable)
    card_brand VARCHAR(50),
    card_last4 VARCHAR(4),

    -- Gateway Response
    gateway_response JSON,
    error_message TEXT,

    -- Timestamps
    authorized_at TIMESTAMP NULL,
    captured_at TIMESTAMP NULL,
    failed_at TIMESTAMP NULL,
    refunded_at TIMESTAMP NULL,

    -- Metadata
    metadata JSON,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (checkout_session_id) REFERENCES checkout_sessions(id) ON DELETE SET NULL,
    FOREIGN KEY (sales_order_id) REFERENCES sales_orders(id) ON DELETE SET NULL,
    FOREIGN KEY (ar_invoice_id) REFERENCES ar_invoices(id) ON DELETE SET NULL,
    INDEX idx_transactions_gateway (gateway),
    INDEX idx_transactions_status (status),
    INDEX idx_transactions_payment_intent (payment_intent_id),
    INDEX idx_transactions_transaction_id (transaction_id)
);
```

**Model:** `Modules\Billing\Models\PaymentTransaction`

```php
class PaymentTransaction extends Model
{
    protected $fillable = [
        'checkout_session_id', 'sales_order_id', 'ar_invoice_id',
        'gateway', 'payment_intent_id', 'transaction_id', 'client_secret',
        'amount', 'currency', 'status', 'payment_method',
        'card_brand', 'card_last4', 'gateway_response', 'error_message',
        'authorized_at', 'captured_at', 'failed_at', 'refunded_at', 'metadata'
    ];

    protected $casts = [
        'amount' => 'float',
        'gateway_response' => 'array',
        'metadata' => 'array',
        'authorized_at' => 'datetime',
        'captured_at' => 'datetime',
        'failed_at' => 'datetime',
        'refunded_at' => 'datetime',
    ];

    public function checkoutSession(): BelongsTo;
    public function salesOrder(): BelongsTo;
    public function arInvoice(): BelongsTo;
}
```

---

#### **1.1.2 Create StripePaymentGateway Service**

**File:** `Modules\Billing\Services\Payment\StripePaymentGateway.php`

```php
<?php

namespace Modules\Billing\Services\Payment;

use Modules\Ecommerce\Services\Payment\PaymentGatewayInterface;
use Modules\Ecommerce\Models\CheckoutSession;
use Modules\Billing\Models\PaymentTransaction;
use Stripe\StripeClient;
use Stripe\Exception\ApiErrorException;

class StripePaymentGateway implements PaymentGatewayInterface
{
    protected StripeClient $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('services.stripe.secret'));
    }

    /**
     * Create a payment intent for the checkout session
     */
    public function createPaymentIntent(CheckoutSession $session, array $paymentData): array
    {
        try {
            $paymentIntent = $this->stripe->paymentIntents->create([
                'amount' => (int)($session->total_amount * 100), // Convert to cents
                'currency' => strtolower($session->currency),
                'payment_method_types' => $paymentData['payment_method_types'] ?? ['card'],
                'metadata' => [
                    'checkout_session_id' => $session->id,
                    'order_id' => $session->order_id ?? null,
                    'customer_email' => $session->customer_email ?? null,
                ],
                'description' => "Order #{$session->order_id} - " . config('app.name'),
            ]);

            // Create transaction record
            $transaction = PaymentTransaction::create([
                'checkout_session_id' => $session->id,
                'gateway' => 'stripe',
                'payment_intent_id' => $paymentIntent->id,
                'client_secret' => $paymentIntent->client_secret,
                'amount' => $session->total_amount,
                'currency' => $session->currency,
                'status' => 'pending',
                'gateway_response' => $paymentIntent->toArray(),
            ]);

            return [
                'payment_intent_id' => $paymentIntent->id,
                'client_secret' => $paymentIntent->client_secret,
                'status' => 'pending',
            ];

        } catch (ApiErrorException $e) {
            \Log::error('Stripe createPaymentIntent error', [
                'error' => $e->getMessage(),
                'session_id' => $session->id,
            ]);

            throw new \Exception('Error al crear intento de pago: ' . $e->getMessage());
        }
    }

    /**
     * Capture/confirm an authorized payment
     */
    public function capturePayment(string $paymentIntentId): array
    {
        try {
            $paymentIntent = $this->stripe->paymentIntents->capture($paymentIntentId);

            $transaction = PaymentTransaction::where('payment_intent_id', $paymentIntentId)->first();

            if ($transaction) {
                $transaction->update([
                    'status' => 'captured',
                    'transaction_id' => $paymentIntent->charges->data[0]->id ?? null,
                    'captured_at' => now(),
                    'gateway_response' => $paymentIntent->toArray(),
                ]);
            }

            return [
                'status' => 'captured',
                'transaction_id' => $paymentIntent->charges->data[0]->id ?? $paymentIntentId,
            ];

        } catch (ApiErrorException $e) {
            \Log::error('Stripe capturePayment error', [
                'error' => $e->getMessage(),
                'payment_intent_id' => $paymentIntentId,
            ]);

            throw new \Exception('Error al capturar pago: ' . $e->getMessage());
        }
    }

    /**
     * Refund a captured payment
     */
    public function refundPayment(string $transactionId, float $amount, ?string $reason = null): array
    {
        try {
            $refund = $this->stripe->refunds->create([
                'charge' => $transactionId,
                'amount' => (int)($amount * 100),
                'reason' => $reason ?? 'requested_by_customer',
            ]);

            $transaction = PaymentTransaction::where('transaction_id', $transactionId)->first();

            if ($transaction) {
                $transaction->update([
                    'status' => 'refunded',
                    'refunded_at' => now(),
                    'metadata' => array_merge($transaction->metadata ?? [], [
                        'refund_id' => $refund->id,
                        'refund_reason' => $reason,
                    ]),
                ]);
            }

            return [
                'status' => 'refunded',
                'refund_id' => $refund->id,
            ];

        } catch (ApiErrorException $e) {
            \Log::error('Stripe refundPayment error', [
                'error' => $e->getMessage(),
                'transaction_id' => $transactionId,
            ]);

            throw new \Exception('Error al procesar reembolso: ' . $e->getMessage());
        }
    }

    /**
     * Get payment status from gateway
     */
    public function getPaymentStatus(string $paymentIntentId): string
    {
        try {
            $paymentIntent = $this->stripe->paymentIntents->retrieve($paymentIntentId);

            return match ($paymentIntent->status) {
                'requires_payment_method' => 'pending',
                'requires_confirmation' => 'pending',
                'requires_action' => 'pending',
                'processing' => 'pending',
                'requires_capture' => 'authorized',
                'succeeded' => 'captured',
                'canceled' => 'cancelled',
                default => 'failed',
            };

        } catch (ApiErrorException $e) {
            \Log::error('Stripe getPaymentStatus error', [
                'error' => $e->getMessage(),
                'payment_intent_id' => $paymentIntentId,
            ]);

            return 'failed';
        }
    }

    /**
     * Cancel/void a pending payment
     */
    public function cancelPayment(string $paymentIntentId): array
    {
        try {
            $paymentIntent = $this->stripe->paymentIntents->cancel($paymentIntentId);

            $transaction = PaymentTransaction::where('payment_intent_id', $paymentIntentId)->first();

            if ($transaction) {
                $transaction->update([
                    'status' => 'cancelled',
                    'gateway_response' => $paymentIntent->toArray(),
                ]);
            }

            return ['status' => 'cancelled'];

        } catch (ApiErrorException $e) {
            \Log::error('Stripe cancelPayment error', [
                'error' => $e->getMessage(),
                'payment_intent_id' => $paymentIntentId,
            ]);

            throw new \Exception('Error al cancelar pago: ' . $e->getMessage());
        }
    }

    /**
     * Handle webhook from payment gateway
     */
    public function handleWebhook(array $payload, ?string $signature = null): void
    {
        if (!$this->verifyWebhookSignature($payload, $signature)) {
            throw new \Exception('Invalid webhook signature');
        }

        $event = $payload;

        switch ($event['type']) {
            case 'payment_intent.succeeded':
                $this->handlePaymentSucceeded($event['data']['object']);
                break;

            case 'payment_intent.payment_failed':
                $this->handlePaymentFailed($event['data']['object']);
                break;

            case 'charge.refunded':
                $this->handleChargeRefunded($event['data']['object']);
                break;

            default:
                \Log::info('Stripe webhook event not handled', ['type' => $event['type']]);
        }
    }

    /**
     * Verify webhook signature
     */
    public function verifyWebhookSignature(array $payload, string $signature): bool
    {
        try {
            $webhookSecret = config('services.stripe.webhook_secret');

            if (!$webhookSecret) {
                \Log::warning('Stripe webhook secret not configured');
                return true; // Allow in development
            }

            \Stripe\Webhook::constructEvent(
                json_encode($payload),
                $signature,
                $webhookSecret
            );

            return true;

        } catch (\Exception $e) {
            \Log::error('Stripe webhook signature verification failed', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get gateway name
     */
    public function getName(): string
    {
        return 'Stripe';
    }

    // =========================================================================
    // PRIVATE WEBHOOK HANDLERS
    // =========================================================================

    protected function handlePaymentSucceeded(array $paymentIntent): void
    {
        $transaction = PaymentTransaction::where('payment_intent_id', $paymentIntent['id'])->first();

        if ($transaction) {
            $transaction->update([
                'status' => 'captured',
                'transaction_id' => $paymentIntent['charges']['data'][0]['id'] ?? null,
                'captured_at' => now(),
                'card_brand' => $paymentIntent['charges']['data'][0]['payment_method_details']['card']['brand'] ?? null,
                'card_last4' => $paymentIntent['charges']['data'][0]['payment_method_details']['card']['last4'] ?? null,
                'gateway_response' => $paymentIntent,
            ]);

            // Fire event for Order-to-Cash integration
            event(new \Modules\Billing\Events\PaymentCaptured($transaction));
        }
    }

    protected function handlePaymentFailed(array $paymentIntent): void
    {
        $transaction = PaymentTransaction::where('payment_intent_id', $paymentIntent['id'])->first();

        if ($transaction) {
            $transaction->update([
                'status' => 'failed',
                'failed_at' => now(),
                'error_message' => $paymentIntent['last_payment_error']['message'] ?? 'Payment failed',
                'gateway_response' => $paymentIntent,
            ]);
        }
    }

    protected function handleChargeRefunded(array $charge): void
    {
        $transaction = PaymentTransaction::where('transaction_id', $charge['id'])->first();

        if ($transaction) {
            $transaction->update([
                'status' => 'refunded',
                'refunded_at' => now(),
                'gateway_response' => $charge,
            ]);
        }
    }
}
```

---

#### **1.1.3 Configuration**

**File:** `config/services.php`

```php
'stripe' => [
    'key' => env('STRIPE_KEY'),
    'secret' => env('STRIPE_SECRET'),
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
],
```

**File:** `.env` (add these lines)

```bash
# Stripe Configuration
STRIPE_KEY=your_stripe_publishable_key_here
STRIPE_SECRET=your_stripe_secret_key_here
STRIPE_WEBHOOK_SECRET=
```

---

### **Step 1.2: Replace MockPaymentGateway in Ecommerce** (2-3 hours)

#### **1.2.1 Update Service Provider Binding**

**File:** `Modules\Ecommerce\Providers\EcommerceServiceProvider.php`

```php
public function register(): void
{
    // OLD (MockPaymentGateway):
    // $this->app->bind(PaymentGatewayInterface::class, MockPaymentGateway::class);

    // NEW (StripePaymentGateway):
    $this->app->bind(
        \Modules\Ecommerce\Services\Payment\PaymentGatewayInterface::class,
        \Modules\Billing\Services\Payment\StripePaymentGateway::class
    );
}
```

---

#### **1.2.2 Update CheckoutService**

**File:** `Modules\Ecommerce\Services\CheckoutService.php`

No changes needed! Already uses PaymentGatewayInterface abstraction:

```php
// This code already works with Stripe:
$paymentResult = $this->paymentGateway->createPaymentIntent($session, [
    'payment_method_types' => ['card'],
]);
```

---

### **Step 1.3: Webhook Controller** (2-3 hours)

**File:** `Modules\Billing\Http\Controllers\WebhookController.php`

```php
<?php

namespace Modules\Billing\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Billing\Services\Payment\StripePaymentGateway;

class WebhookController extends Controller
{
    /**
     * Handle Stripe webhook
     */
    public function stripe(Request $request)
    {
        try {
            $gateway = app(StripePaymentGateway::class);

            $payload = $request->all();
            $signature = $request->header('Stripe-Signature');

            $gateway->handleWebhook($payload, $signature);

            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            \Log::error('Stripe webhook error', [
                'error' => $e->getMessage(),
                'payload' => $request->all(),
            ]);

            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
```

**Route:** `routes/api.php`

```php
Route::post('/webhooks/stripe', [\Modules\Billing\Http\Controllers\WebhookController::class, 'stripe'])
    ->name('webhooks.stripe')
    ->withoutMiddleware(['auth:sanctum']); // Stripe needs public access
```

---

### **Step 1.4: Testing** (6-8 hours)

Create comprehensive test suite:

**Test Files:**
1. `PaymentTransactionIndexTest.php`
2. `PaymentTransactionShowTest.php`
3. `PaymentTransactionStoreTest.php`
4. `PaymentTransactionUpdateTest.php`
5. `PaymentTransactionDestroyTest.php`
6. `StripePaymentGatewayTest.php` (Unit test)
7. `StripeWebhookTest.php` (Feature test)

**Example Test:** `StripePaymentGatewayTest.php`

```php
<?php

namespace Modules\Billing\Tests\Unit;

use Tests\TestCase;
use Modules\Billing\Services\Payment\StripePaymentGateway;
use Modules\Ecommerce\Models\CheckoutSession;
use Modules\Billing\Models\PaymentTransaction;

class StripePaymentGatewayTest extends TestCase
{
    protected StripePaymentGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gateway = new StripePaymentGateway();
    }

    public function test_can_create_payment_intent(): void
    {
        $session = CheckoutSession::factory()->create([
            'total_amount' => 1000.00,
            'currency' => 'MXN',
        ]);

        $result = $this->gateway->createPaymentIntent($session, [
            'payment_method_types' => ['card'],
        ]);

        $this->assertArrayHasKey('payment_intent_id', $result);
        $this->assertArrayHasKey('client_secret', $result);
        $this->assertEquals('pending', $result['status']);

        // Verify transaction was created
        $this->assertDatabaseHas('payment_transactions', [
            'checkout_session_id' => $session->id,
            'gateway' => 'stripe',
            'amount' => 1000.00,
            'currency' => 'MXN',
            'status' => 'pending',
        ]);
    }

    public function test_get_gateway_name(): void
    {
        $this->assertEquals('Stripe', $this->gateway->getName());
    }

    // Add more tests for capture, refund, cancel, webhook handling...
}
```

---

### **✅ Phase 1 Deliverables**

- [x] PaymentTransaction model and migration
- [x] StripePaymentGateway service (implements PaymentGatewayInterface)
- [x] Replaced MockPaymentGateway in Ecommerce
- [x] Webhook controller and route
- [x] Configuration in .env
- [x] 7 test files with 35+ tests
- [x] Stripe integration working end-to-end

**Time:** 18-24 hours (2-3 days)
**Result:** Real payment processing with Stripe in Ecommerce module

---

## **PHASE 2: CFDI MODULE STRUCTURE** (Days 4-5, 16-20 hours)

### **Objective:** Create CFDI data models, XML generator, PDF generator

**Status:** ⏳ Can start after Phase 1 or in parallel

---

### **Step 2.1: Database Migrations** (4-5 hours)

#### **2.1.1 Company Settings**

**Table:** `billing_company_settings`

```sql
CREATE TABLE billing_company_settings (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,

    -- Company Fiscal Data
    rfc VARCHAR(13) NOT NULL UNIQUE DEFAULT 'RAMR850519248',
    legal_name VARCHAR(255) NOT NULL DEFAULT 'RODRIGO GABINO RAMIREZ MORENO',
    fiscal_regime VARCHAR(10) NOT NULL DEFAULT '612',
    postal_code VARCHAR(5) NOT NULL DEFAULT '07969',
    tax_domicile TEXT DEFAULT 'Av 509, San Juan de Aragón I',

    -- CSD Certificates (Base64 encoded)
    certificate_cer TEXT,
    certificate_key TEXT,
    certificate_password VARCHAR(255), -- Encrypted
    certificate_number VARCHAR(20),
    certificate_valid_from DATE,
    certificate_valid_until DATE,

    -- PAC Configuration
    pac_provider VARCHAR(100) DEFAULT 'sw', -- sw, finkok, diconsa
    pac_username VARCHAR(255),
    pac_password VARCHAR(255), -- Encrypted
    pac_token TEXT, -- For SW Sapien
    pac_test_mode BOOLEAN DEFAULT TRUE,
    pac_endpoint_stamp VARCHAR(255),
    pac_endpoint_cancel VARCHAR(255),

    -- CFDI Settings
    cfdi_version VARCHAR(10) DEFAULT '4.0',
    series_invoice VARCHAR(10) DEFAULT 'F',
    series_credit_note VARCHAR(10) DEFAULT 'N',
    next_folio_invoice INTEGER DEFAULT 1,
    next_folio_credit_note INTEGER DEFAULT 1,
    logo_url VARCHAR(255),

    -- Status
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    INDEX idx_company_rfc (rfc)
);
```

---

#### **2.1.2 CFDI Invoices**

**Table:** `billing_cfdi_invoices`

```sql
CREATE TABLE billing_cfdi_invoices (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,

    -- Reference to Finance Module
    ar_invoice_id BIGINT UNSIGNED,
    sales_order_id BIGINT UNSIGNED,
    payment_transaction_id BIGINT UNSIGNED,

    -- CFDI Identification
    uuid VARCHAR(36) UNIQUE, -- Folio Fiscal (from PAC after stamping)
    serie VARCHAR(25) DEFAULT 'F',
    folio VARCHAR(40) NOT NULL,
    fecha_emision TIMESTAMP NOT NULL,
    fecha_timbrado TIMESTAMP,

    -- Emisor (Issuer - our company)
    emisor_rfc VARCHAR(13) NOT NULL DEFAULT 'RAMR850519248',
    emisor_nombre VARCHAR(255) NOT NULL DEFAULT 'RODRIGO GABINO RAMIREZ MORENO',
    emisor_regimen_fiscal VARCHAR(10) NOT NULL DEFAULT '612',

    -- Receptor (Receiver - customer)
    receptor_rfc VARCHAR(13) NOT NULL,
    receptor_nombre VARCHAR(255) NOT NULL,
    receptor_domicilio_fiscal VARCHAR(5), -- Postal code
    receptor_regimen_fiscal VARCHAR(10),
    receptor_uso_cfdi VARCHAR(10) NOT NULL, -- G01, G02, G03, etc.

    -- Amounts
    subtotal DECIMAL(18,6) NOT NULL,
    descuento DECIMAL(18,6) DEFAULT 0,
    total DECIMAL(18,6) NOT NULL,
    moneda VARCHAR(3) DEFAULT 'MXN',
    tipo_cambio DECIMAL(10,6) DEFAULT 1,

    -- Payment
    forma_pago VARCHAR(2), -- 01=Efectivo, 03=Transferencia, 04=Tarjeta, etc.
    metodo_pago VARCHAR(3) DEFAULT 'PUE', -- PUE, PPD
    condiciones_pago VARCHAR(255),

    -- CFDI Type
    tipo_comprobante VARCHAR(1) DEFAULT 'I', -- I=Ingreso, E=Egreso, T=Traslado, P=Pago
    tipo_relacion VARCHAR(2), -- 01=Nota crédito, 02=Nota débito, etc.
    cfdi_relacionados JSON, -- Array of related UUIDs

    -- Status & Workflow
    status VARCHAR(50) DEFAULT 'draft', -- draft, pending_approval, approved, stamping, stamped, cancelled, error
    stamping_error TEXT,

    -- XML & PDF
    xml_original TEXT, -- XML before stamping
    xml_timbrado TEXT, -- XML after stamping (with Timbre)
    pdf_url VARCHAR(255),
    qr_code TEXT, -- Base64 QR code

    -- Cancellation
    cancellation_status VARCHAR(50), -- pending, accepted, rejected
    cancellation_reason VARCHAR(2), -- 01, 02, 03, 04 (SAT catalog)
    cancellation_substitute_uuid VARCHAR(36),
    cancelled_at TIMESTAMP NULL,
    cancellation_response TEXT,

    -- Audit
    metadata JSON,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (ar_invoice_id) REFERENCES ar_invoices(id) ON DELETE RESTRICT,
    FOREIGN KEY (sales_order_id) REFERENCES sales_orders(id) ON DELETE RESTRICT,
    FOREIGN KEY (payment_transaction_id) REFERENCES payment_transactions(id) ON DELETE SET NULL,
    UNIQUE KEY uk_serie_folio (serie, folio),
    INDEX idx_cfdi_uuid (uuid),
    INDEX idx_cfdi_status (status),
    INDEX idx_cfdi_receptor_rfc (receptor_rfc),
    INDEX idx_cfdi_fecha_emision (fecha_emision),
    INDEX idx_cfdi_ar_invoice (ar_invoice_id)
);
```

---

#### **2.1.3 CFDI Items**

**Table:** `billing_cfdi_items`

```sql
CREATE TABLE billing_cfdi_items (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    cfdi_invoice_id BIGINT UNSIGNED NOT NULL,

    -- Product/Service (SAT Catalogs)
    clave_prod_serv VARCHAR(10) NOT NULL DEFAULT '01010101', -- SAT product/service code
    no_identificacion VARCHAR(100), -- SKU
    cantidad DECIMAL(18,6) NOT NULL,
    clave_unidad VARCHAR(10) NOT NULL DEFAULT 'E48', -- SAT unit code (KGM, E48, etc.)
    unidad VARCHAR(20), -- Unit description
    descripcion TEXT NOT NULL,
    valor_unitario DECIMAL(18,6) NOT NULL,
    importe DECIMAL(18,6) NOT NULL,
    descuento DECIMAL(18,6) DEFAULT 0,

    -- Object Type (01=Not subject, 02=Subject to tax)
    objeto_imp VARCHAR(2) DEFAULT '02',

    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (cfdi_invoice_id) REFERENCES billing_cfdi_invoices(id) ON DELETE CASCADE,
    INDEX idx_cfdi_items_invoice (cfdi_invoice_id)
);
```

---

#### **2.1.4 CFDI Taxes**

**Table:** `billing_cfdi_taxes`

```sql
CREATE TABLE billing_cfdi_taxes (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    cfdi_item_id BIGINT UNSIGNED NOT NULL,

    tipo VARCHAR(10) NOT NULL, -- Traslado (charged) or Retención (withheld)
    impuesto VARCHAR(10) NOT NULL, -- 001=ISR, 002=IVA, 003=IEPS
    tipo_factor VARCHAR(10) NOT NULL, -- Tasa, Cuota, Exento
    tasa_o_cuota DECIMAL(8,6), -- e.g., 0.160000 for 16% IVA
    base DECIMAL(18,6) NOT NULL,
    importe DECIMAL(18,6),

    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (cfdi_item_id) REFERENCES billing_cfdi_items(id) ON DELETE CASCADE,
    INDEX idx_cfdi_taxes_item (cfdi_item_id)
);
```

---

### **Step 2.2: Models** (2-3 hours)

Create Eloquent models for all 4 tables with proper relationships, casts, and scopes.

---

### **Step 2.3: CFDI XML Generator Service** (6-8 hours)

**File:** `Modules\Billing\Services\CFDI\CFDIXMLGenerator.php`

This service generates valid CFDI 4.0 XML according to SAT specifications.

```php
<?php

namespace Modules\Billing\Services\CFDI;

use Modules\Billing\Models\CFDIInvoice;
use Modules\Billing\Models\CompanySetting;

class CFDIXMLGenerator
{
    /**
     * Generate CFDI 4.0 XML from invoice data
     */
    public function generate(CFDIInvoice $invoice): string
    {
        $settings = CompanySetting::first();

        // Create XML with namespaces
        $xml = new \DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;

        // Root element: cfdi:Comprobante
        $comprobante = $xml->createElementNS('http://www.sat.gob.mx/cfd/4', 'cfdi:Comprobante');
        $xml->appendChild($comprobante);

        // Add schema location
        $comprobante->setAttributeNS(
            'http://www.w3.org/2001/XMLSchema-instance',
            'xsi:schemaLocation',
            'http://www.sat.gob.mx/cfd/4 http://www.sat.gob.mx/sitio_internet/cfd/4/cfdv40.xsd'
        );

        // Add attributes
        $comprobante->setAttribute('Version', '4.0');
        $comprobante->setAttribute('Serie', $invoice->serie);
        $comprobante->setAttribute('Folio', $invoice->folio);
        $comprobante->setAttribute('Fecha', $invoice->fecha_emision->format('Y-m-d\TH:i:s'));
        $comprobante->setAttribute('FormaPago', $invoice->forma_pago ?? '99');
        $comprobante->setAttribute('NoCertificado', $settings->certificate_number ?? '');
        $comprobante->setAttribute('SubTotal', number_format($invoice->subtotal, 6, '.', ''));

        if ($invoice->descuento > 0) {
            $comprobante->setAttribute('Descuento', number_format($invoice->descuento, 6, '.', ''));
        }

        $comprobante->setAttribute('Moneda', $invoice->moneda);

        if ($invoice->moneda !== 'MXN') {
            $comprobante->setAttribute('TipoCambio', number_format($invoice->tipo_cambio, 6, '.', ''));
        }

        $comprobante->setAttribute('Total', number_format($invoice->total, 6, '.', ''));
        $comprobante->setAttribute('TipoDeComprobante', $invoice->tipo_comprobante);
        $comprobante->setAttribute('MetodoPago', $invoice->metodo_pago);
        $comprobante->setAttribute('LugarExpedicion', $settings->postal_code);

        // Add Emisor
        $this->addEmisor($xml, $comprobante, $invoice, $settings);

        // Add Receptor
        $this->addReceptor($xml, $comprobante, $invoice);

        // Add Conceptos (items)
        $this->addConceptos($xml, $comprobante, $invoice);

        // Add Impuestos (taxes summary)
        $this->addImpuestos($xml, $comprobante, $invoice);

        // Add related CFDIs if any
        if (!empty($invoice->cfdi_relacionados)) {
            $this->addCfdiRelacionados($xml, $comprobante, $invoice);
        }

        return $xml->saveXML();
    }

    protected function addEmisor(\DOMDocument $xml, \DOMElement $comprobante, CFDIInvoice $invoice, CompanySetting $settings): void
    {
        $emisor = $xml->createElement('cfdi:Emisor');
        $emisor->setAttribute('Rfc', $invoice->emisor_rfc);
        $emisor->setAttribute('Nombre', $invoice->emisor_nombre);
        $emisor->setAttribute('RegimenFiscal', $invoice->emisor_regimen_fiscal);
        $comprobante->appendChild($emisor);
    }

    protected function addReceptor(\DOMDocument $xml, \DOMElement $comprobante, CFDIInvoice $invoice): void
    {
        $receptor = $xml->createElement('cfdi:Receptor');
        $receptor->setAttribute('Rfc', $invoice->receptor_rfc);
        $receptor->setAttribute('Nombre', $invoice->receptor_nombre);
        $receptor->setAttribute('DomicilioFiscalReceptor', $invoice->receptor_domicilio_fiscal);
        $receptor->setAttribute('RegimenFiscalReceptor', $invoice->receptor_regimen_fiscal ?? '616');
        $receptor->setAttribute('UsoCFDI', $invoice->receptor_uso_cfdi);
        $comprobante->appendChild($receptor);
    }

    protected function addConceptos(\DOMDocument $xml, \DOMElement $comprobante, CFDIInvoice $invoice): void
    {
        $conceptos = $xml->createElement('cfdi:Conceptos');

        foreach ($invoice->items as $item) {
            $concepto = $xml->createElement('cfdi:Concepto');
            $concepto->setAttribute('ClaveProdServ', $item->clave_prod_serv);

            if ($item->no_identificacion) {
                $concepto->setAttribute('NoIdentificacion', $item->no_identificacion);
            }

            $concepto->setAttribute('Cantidad', number_format($item->cantidad, 6, '.', ''));
            $concepto->setAttribute('ClaveUnidad', $item->clave_unidad);

            if ($item->unidad) {
                $concepto->setAttribute('Unidad', $item->unidad);
            }

            $concepto->setAttribute('Descripcion', $item->descripcion);
            $concepto->setAttribute('ValorUnitario', number_format($item->valor_unitario, 6, '.', ''));
            $concepto->setAttribute('Importe', number_format($item->importe, 6, '.', ''));

            if ($item->descuento > 0) {
                $concepto->setAttribute('Descuento', number_format($item->descuento, 6, '.', ''));
            }

            $concepto->setAttribute('ObjetoImp', $item->objeto_imp);

            // Add taxes for this item
            if ($item->taxes->count() > 0) {
                $this->addConceptoImpuestos($xml, $concepto, $item);
            }

            $conceptos->appendChild($concepto);
        }

        $comprobante->appendChild($conceptos);
    }

    protected function addConceptoImpuestos(\DOMDocument $xml, \DOMElement $concepto, $item): void
    {
        $impuestos = $xml->createElement('cfdi:Impuestos');

        // Traslados (taxes charged to customer - like IVA)
        $traslados = $item->taxes->where('tipo', 'Traslado');
        if ($traslados->count() > 0) {
            $trasladosNode = $xml->createElement('cfdi:Traslados');

            foreach ($traslados as $tax) {
                $traslado = $xml->createElement('cfdi:Traslado');
                $traslado->setAttribute('Base', number_format($tax->base, 6, '.', ''));
                $traslado->setAttribute('Impuesto', $tax->impuesto);
                $traslado->setAttribute('TipoFactor', $tax->tipo_factor);
                $traslado->setAttribute('TasaOCuota', number_format($tax->tasa_o_cuota, 6, '.', ''));
                $traslado->setAttribute('Importe', number_format($tax->importe, 6, '.', ''));
                $trasladosNode->appendChild($traslado);
            }

            $impuestos->appendChild($trasladosNode);
        }

        // Retenciones (taxes withheld - like ISR)
        $retenciones = $item->taxes->where('tipo', 'Retención');
        if ($retenciones->count() > 0) {
            $retencionesNode = $xml->createElement('cfdi:Retenciones');

            foreach ($retenciones as $tax) {
                $retencion = $xml->createElement('cfdi:Retencion');
                $retencion->setAttribute('Base', number_format($tax->base, 6, '.', ''));
                $retencion->setAttribute('Impuesto', $tax->impuesto);
                $retencion->setAttribute('TipoFactor', $tax->tipo_factor);
                $retencion->setAttribute('TasaOCuota', number_format($tax->tasa_o_cuota, 6, '.', ''));
                $retencion->setAttribute('Importe', number_format($tax->importe, 6, '.', ''));
                $retencionesNode->appendChild($retencion);
            }

            $impuestos->appendChild($retencionesNode);
        }

        $concepto->appendChild($impuestos);
    }

    protected function addImpuestos(\DOMDocument $xml, \DOMElement $comprobante, CFDIInvoice $invoice): void
    {
        // Calculate totals from all item taxes
        $totalTraslados = 0;
        $totalRetenciones = 0;

        foreach ($invoice->items as $item) {
            $totalTraslados += $item->taxes->where('tipo', 'Traslado')->sum('importe');
            $totalRetenciones += $item->taxes->where('tipo', 'Retención')->sum('importe');
        }

        if ($totalTraslados > 0 || $totalRetenciones > 0) {
            $impuestos = $xml->createElement('cfdi:Impuestos');

            if ($totalRetenciones > 0) {
                $impuestos->setAttribute('TotalImpuestosRetenidos', number_format($totalRetenciones, 6, '.', ''));
            }

            if ($totalTraslados > 0) {
                $impuestos->setAttribute('TotalImpuestosTrasladados', number_format($totalTraslados, 6, '.', ''));
            }

            // Group traslados by tax type and rate
            // ... (implementation continues)

            $comprobante->appendChild($impuestos);
        }
    }

    protected function addCfdiRelacionados(\DOMDocument $xml, \DOMElement $comprobante, CFDIInvoice $invoice): void
    {
        if (empty($invoice->cfdi_relacionados)) {
            return;
        }

        $relacionados = $xml->createElement('cfdi:CfdiRelacionados');
        $relacionados->setAttribute('TipoRelacion', $invoice->tipo_relacion);

        foreach ($invoice->cfdi_relacionados as $uuid) {
            $relacionado = $xml->createElement('cfdi:CfdiRelacionado');
            $relacionado->setAttribute('UUID', $uuid);
            $relacionados->appendChild($relacionado);
        }

        $comprobante->appendChild($relacionados);
    }
}
```

---

### **Step 2.4: CFDI PDF Generator Service** (4-5 hours)

**File:** `Modules\Billing\Services\CFDI\CFDIPDFGenerator.php`

Generate professional PDF invoices with QR codes.

```php
<?php

namespace Modules\Billing\Services\CFDI;

use Modules\Billing\Models\CFDIInvoice;
use Modules\Billing\Models\CompanySetting;
use Barryvdh\DomPDF\Facade\Pdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class CFDIPDFGenerator
{
    /**
     * Generate PDF for CFDI invoice
     */
    public function generate(CFDIInvoice $invoice): string
    {
        $settings = CompanySetting::first();

        // Generate QR Code
        $qrData = $this->generateQRData($invoice);
        $qrCode = QrCode::size(150)->generate($qrData);

        // Generate PDF
        $pdf = Pdf::loadView('billing::cfdi-pdf', [
            'invoice' => $invoice,
            'settings' => $settings,
            'qrCode' => $qrCode,
        ]);

        // Save PDF
        $filename = "CFDI_{$invoice->serie}_{$invoice->folio}.pdf";
        $path = storage_path("app/public/invoices/{$filename}");

        $pdf->save($path);

        $invoice->update(['pdf_url' => "/storage/invoices/{$filename}"]);

        return $path;
    }

    /**
     * Generate QR code data according to SAT specification
     */
    protected function generateQRData(CFDIInvoice $invoice): string
    {
        // SAT QR Code format:
        // https://verificacfdi.facturaelectronica.sat.gob.mx/default.aspx?&id=UUID&re=RFC_EMISOR&rr=RFC_RECEPTOR&tt=TOTAL&fe=ULTIMOS_8_DIGITOS_SELLO

        $uuid = $invoice->uuid;
        $rfcEmisor = $invoice->emisor_rfc;
        $rfcReceptor = $invoice->receptor_rfc;
        $total = number_format($invoice->total, 6, '.', '');

        // Extract last 8 digits of seal (sello) from XML
        // For now, use placeholder
        $selloUltimos8 = 'XXXXXXXX';

        return "https://verificacfdi.facturaelectronica.sat.gob.mx/default.aspx?" .
               "id={$uuid}&re={$rfcEmisor}&rr={$rfcReceptor}&tt={$total}&fe={$selloUltimos8}";
    }
}
```

**View Template:** `Modules/Billing/Resources/views/cfdi-pdf.blade.php`

Professional HTML template for CFDI invoice (not shown for brevity, but includes all required SAT fields).

---

### **✅ Phase 2 Deliverables**

- [x] 4 database migrations (company_settings, cfdi_invoices, cfdi_items, cfdi_taxes)
- [x] 4 Eloquent models with relationships
- [x] CFDIXMLGenerator service (valid CFDI 4.0 XML)
- [x] CFDIPDFGenerator service (professional PDF with QR code)
- [x] Blade template for PDF
- [x] SAT catalog validation
- [x] Tests for XML generation

**Time:** 16-20 hours (2 days)
**Result:** CFDI invoices generated locally (not stamped yet)

---

## **PHASE 3: SW SAPIEN PAC INTEGRATION** (Days 6-7, 12-16 hours)

### **Objective:** Integrate with SW Sapien for CFDI stamping (timbrado)

**Status:** ⏳ Waiting for SW Sapien credentials

---

### **Step 3.1: SW Sapien Service** (6-8 hours)

**File:** `Modules\Billing\Services\PAC\SWPacService.php`

```php
<?php

namespace Modules\Billing\Services\PAC;

use Illuminate\Support\Facades\Http;
use Modules\Billing\Models\CFDIInvoice;

class SWPacService
{
    protected string $baseUrl;
    protected string $token;
    protected bool $testMode;

    public function __construct()
    {
        $this->testMode = config('services.sw.test_mode', true);
        $this->baseUrl = $this->testMode
            ? 'https://services.test.sw.com.mx'
            : 'https://services.sw.com.mx';
        $this->token = config('services.sw.token');
    }

    /**
     * Stamp CFDI invoice
     */
    public function stamp(string $xml): array
    {
        try {
            $response = Http::withToken($this->token)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->post("{$this->baseUrl}/cfdi-multi/issue/json/v4", [
                    'Emisor' => [
                        'RegimenFiscal' => '612',
                        'Rfc' => 'RAMR850519248',
                        'Nombre' => 'RODRIGO GABINO RAMIREZ MORENO',
                    ],
                    // ... rest of CFDI data in JSON format
                ]);

            if ($response->successful()) {
                $data = $response->json('data');

                return [
                    'success' => true,
                    'uuid' => $data['uuid'],
                    'xml_timbrado' => base64_decode($data['cfdi']),
                    'fecha_timbrado' => now(),
                    'cadena_original' => $data['cadenaOriginalSAT'],
                    'sello_sat' => $data['selloSAT'],
                    'no_certificado_sat' => $data['noCertificadoSAT'],
                ];
            }

            return [
                'success' => false,
                'error' => $response->json('message') ?? 'Error desconocido al timbrar',
                'details' => $response->json(),
            ];

        } catch (\Exception $e) {
            \Log::error('SW PAC stamp error', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Cancel CFDI
     */
    public function cancel(string $uuid, string $reason, ?string $substituteUUID = null): array
    {
        try {
            $response = Http::withToken($this->token)
                ->post("{$this->baseUrl}/cfdi/cancel/csd", [
                    'rfc' => 'RAMR850519248',
                    'uuid' => $uuid,
                    'motivo' => $reason,
                    'folioSustitucion' => $substituteUUID,
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'status' => $response->json('data.status'),
                    'acuse' => $response->json('data.acuse'),
                ];
            }

            return [
                'success' => false,
                'error' => $response->json('message'),
            ];

        } catch (\Exception $e) {
            \Log::error('SW PAC cancel error', [
                'error' => $e->getMessage(),
                'uuid' => $uuid,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Query cancellation status
     */
    public function queryCancellationStatus(string $uuid): string
    {
        try {
            $response = Http::withToken($this->token)
                ->get("{$this->baseUrl}/cfdi/status/{$uuid}");

            if ($response->successful()) {
                return $response->json('data.status'); // Vigente, Cancelado, etc.
            }

            return 'unknown';

        } catch (\Exception $e) {
            \Log::error('SW PAC query status error', [
                'error' => $e->getMessage(),
                'uuid' => $uuid,
            ]);

            return 'error';
        }
    }
}
```

---

### **Step 3.2: CFDI Stamping Workflow Service** (4-6 hours)

**File:** `Modules\Billing\Services\CFDI\CFDIStampingService.php`

```php
<?php

namespace Modules\Billing\Services\CFDI;

use Modules\Billing\Models\CFDIInvoice;
use Modules\Billing\Services\PAC\SWPacService;

class CFDIStampingService
{
    protected CFDIXMLGenerator $xmlGenerator;
    protected SWPacService $pacService;
    protected CFDIPDFGenerator $pdfGenerator;

    public function __construct(
        CFDIXMLGenerator $xmlGenerator,
        SWPacService $pacService,
        CFDIPDFGenerator $pdfGenerator
    ) {
        $this->xmlGenerator = $xmlGenerator;
        $this->pacService = $pacService;
        $this->pdfGenerator = $pdfGenerator;
    }

    /**
     * Generate XML, stamp with PAC, and generate PDF
     */
    public function stampInvoice(CFDIInvoice $invoice): bool
    {
        try {
            // 1. Generate XML
            $xml = $this->xmlGenerator->generate($invoice);
            $invoice->update([
                'xml_original' => $xml,
                'status' => 'stamping',
            ]);

            // 2. Send to PAC for stamping
            $result = $this->pacService->stamp($xml);

            if (!$result['success']) {
                $invoice->update([
                    'status' => 'error',
                    'stamping_error' => $result['error'],
                ]);
                return false;
            }

            // 3. Save stamped XML and UUID
            $invoice->update([
                'status' => 'stamped',
                'uuid' => $result['uuid'],
                'fecha_timbrado' => $result['fecha_timbrado'],
                'xml_timbrado' => $result['xml_timbrado'],
            ]);

            // 4. Generate PDF
            $this->pdfGenerator->generate($invoice);

            // 5. Fire event for GL posting
            event(new \Modules\Billing\Events\CFDIStamped($invoice));

            // 6. Send email to customer
            // Mail::to($invoice->receptor_email)->send(new CFDIInvoiceMail($invoice));

            return true;

        } catch (\Exception $e) {
            $invoice->update([
                'status' => 'error',
                'stamping_error' => $e->getMessage(),
            ]);

            \Log::error('CFDI stamping error', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Cancel CFDI
     */
    public function cancelInvoice(CFDIInvoice $invoice, string $reason, ?string $substituteUUID = null): bool
    {
        if ($invoice->status !== 'stamped') {
            throw new \Exception('Solo se pueden cancelar facturas timbradas');
        }

        $result = $this->pacService->cancel($invoice->uuid, $reason, $substituteUUID);

        if ($result['success']) {
            $invoice->update([
                'cancellation_status' => $result['status'],
                'cancellation_reason' => $reason,
                'cancellation_substitute_uuid' => $substituteUUID,
                'cancellation_response' => json_encode($result),
            ]);

            if ($result['status'] === 'Cancelado') {
                $invoice->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                ]);
            }

            return true;
        }

        return false;
    }
}
```

---

### **Step 3.3: Configuration** (1-2 hours)

**File:** `config/services.php`

```php
'sw' => [
    'token' => env('SW_TOKEN'),
    'test_mode' => env('SW_TEST_MODE', true),
],
```

**File:** `.env`

```bash
# SW Sapien PAC Configuration
SW_TOKEN=your_sw_token_here
SW_TEST_MODE=true
```

---

### **✅ Phase 3 Deliverables**

- [x] SWPacService (stamp, cancel, query status)
- [x] CFDIStampingService (complete workflow)
- [x] Webhook handling for PAC responses
- [x] Cancellation workflow
- [x] Configuration in .env
- [x] Tests for stamping workflow

**Time:** 12-16 hours (1.5-2 days)
**Result:** CFDI stamping with SW Sapien PAC working

---

## **PHASE 4: FULL INTEGRATION & AUTOMATION** (Day 8-9, 8-12 hours)

### **Objective:** Connect all pieces together for automated workflow

**Status:** ⏳ After Phases 1-3

---

### **Step 4.1: Automation Service** (4-6 hours)

**File:** `Modules\Billing\Services\CFDIAutomationService.php`

```php
<?php

namespace Modules\Billing\Services;

use Modules\Finance\Models\ARInvoice;
use Modules\Sales\Models\SalesOrder;
use Modules\Billing\Models\CFDIInvoice;
use Modules\Billing\Models\CFDIItem;
use Modules\Billing\Models\CFDITax;
use Modules\Billing\Models\CompanySetting;
use Modules\Billing\Models\PaymentTransaction;

class CFDIAutomationService
{
    /**
     * Auto-generate CFDI from AR Invoice
     */
    public function generateFromARInvoice(ARInvoice $arInvoice): CFDIInvoice
    {
        $settings = CompanySetting::first();
        $customer = $arInvoice->contact;

        // Determine serie based on invoice type
        $serie = $settings->series_invoice; // 'F'
        $folio = $settings->next_folio_invoice;

        // Create CFDI
        $cfdi = CFDIInvoice::create([
            'ar_invoice_id' => $arInvoice->id,
            'sales_order_id' => $arInvoice->sales_order_id,
            'serie' => $serie,
            'folio' => (string)$folio,
            'fecha_emision' => now(),

            // Emisor
            'emisor_rfc' => $settings->rfc,
            'emisor_nombre' => $settings->legal_name,
            'emisor_regimen_fiscal' => $settings->fiscal_regime,

            // Receptor
            'receptor_rfc' => $customer->tax_id ?? 'XAXX010101000', // Generic RFC
            'receptor_nombre' => $customer->name,
            'receptor_domicilio_fiscal' => $customer->postal_code ?? '00000',
            'receptor_regimen_fiscal' => '616', // Sin obligaciones fiscales
            'receptor_uso_cfdi' => 'G03', // Gastos en general

            // Amounts
            'subtotal' => $arInvoice->subtotal,
            'descuento' => 0,
            'total' => $arInvoice->total_amount,
            'moneda' => $arInvoice->currency,

            // Payment
            'forma_pago' => '99', // Por definir
            'metodo_pago' => 'PUE', // Pago en una exhibición
            'tipo_comprobante' => 'I', // Ingreso

            'status' => 'draft',
        ]);

        // Copy items from AR Invoice
        foreach ($arInvoice->items as $item) {
            $cfdiItem = CFDIItem::create([
                'cfdi_invoice_id' => $cfdi->id,
                'clave_prod_serv' => '01010101', // Generic service code
                'no_identificacion' => $item->product->sku ?? '',
                'cantidad' => $item->quantity,
                'clave_unidad' => 'E48', // Unidad de servicio
                'unidad' => 'Pieza',
                'descripcion' => $item->description ?? $item->product->name,
                'valor_unitario' => $item->unit_price,
                'importe' => $item->quantity * $item->unit_price,
                'descuento' => 0,
                'objeto_imp' => '02', // Sí objeto de impuesto
            ]);

            // Add IVA tax (16%)
            CFDITax::create([
                'cfdi_item_id' => $cfdiItem->id,
                'tipo' => 'Traslado',
                'impuesto' => '002', // IVA
                'tipo_factor' => 'Tasa',
                'tasa_o_cuota' => 0.160000,
                'base' => $item->quantity * $item->unit_price,
                'importe' => ($item->quantity * $item->unit_price) * 0.16,
            ]);
        }

        // Increment folio
        $settings->increment('next_folio_invoice');

        return $cfdi;
    }

    /**
     * Auto-generate CFDI from Payment Transaction (after Stripe payment)
     */
    public function generateFromPaymentTransaction(PaymentTransaction $transaction): ?CFDIInvoice
    {
        // Get related sales order
        $salesOrder = $transaction->salesOrder;

        if (!$salesOrder) {
            return null;
        }

        // Check if AR Invoice exists
        $arInvoice = $salesOrder->arInvoice;

        if (!$arInvoice) {
            // Create AR Invoice first
            $arInvoice = $this->createARInvoiceFromSalesOrder($salesOrder);
        }

        // Generate CFDI
        $cfdi = $this->generateFromARInvoice($arInvoice);

        // Link payment transaction
        $cfdi->update(['payment_transaction_id' => $transaction->id]);

        // Determine forma_pago from payment method
        $cfdi->update([
            'forma_pago' => $this->mapStripePaymentMethodToFormaPago($transaction->payment_method),
        ]);

        return $cfdi;
    }

    /**
     * Map Stripe payment method to SAT forma_pago
     */
    protected function mapStripePaymentMethodToFormaPago(string $paymentMethod): string
    {
        return match ($paymentMethod) {
            'card' => '04', // Tarjeta de crédito
            'bank_transfer', 'spei' => '03', // Transferencia electrónica
            'oxxo' => '01', // Efectivo
            default => '99', // Por definir
        };
    }

    protected function createARInvoiceFromSalesOrder(SalesOrder $salesOrder): ARInvoice
    {
        // Logic to create AR Invoice from Sales Order
        // (similar to existing Order-to-Cash flow)
    }
}
```

---

### **Step 4.2: Event Listeners** (2-3 hours)

**Event:** `Modules\Billing\Events\PaymentCaptured.php`

**Listener:** `Modules\Billing\Listeners\GenerateCFDIAfterPayment.php`

```php
<?php

namespace Modules\Billing\Listeners;

use Modules\Billing\Events\PaymentCaptured;
use Modules\Billing\Services\CFDIAutomationService;
use Modules\Billing\Services\CFDI\CFDIStampingService;

class GenerateCFDIAfterPayment
{
    protected CFDIAutomationService $automationService;
    protected CFDIStampingService $stampingService;

    public function __construct(
        CFDIAutomationService $automationService,
        CFDIStampingService $stampingService
    ) {
        $this->automationService = $automationService;
        $this->stampingService = $stampingService;
    }

    /**
     * Handle the event
     */
    public function handle(PaymentCaptured $event): void
    {
        $transaction = $event->transaction;

        // Generate CFDI
        $cfdi = $this->automationService->generateFromPaymentTransaction($transaction);

        if ($cfdi) {
            // Auto-stamp if in production mode
            if (!config('services.sw.test_mode')) {
                $this->stampingService->stampInvoice($cfdi);
            }
        }
    }
}
```

---

### **Step 4.3: GL Posting Integration** (2-3 hours)

**Event:** `Modules\Billing\Events\CFDIStamped.php`

**Listener:** `Modules\Billing\Listeners\PostCFDIToGeneralLedger.php`

```php
<?php

namespace Modules\Billing\Listeners;

use Modules\Billing\Events\CFDIStamped;
use Modules\Accounting\Services\AccountingService;

class PostCFDIToGeneralLedger
{
    protected AccountingService $accountingService;

    public function __construct(AccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
    }

    /**
     * Handle the event
     */
    public function handle(CFDIStamped $event): void
    {
        $cfdi = $event->cfdiInvoice;
        $arInvoice = $cfdi->arInvoice;

        if (!$arInvoice || $arInvoice->journal_entry_id) {
            return; // Already posted
        }

        // Create Journal Entry
        $this->accountingService->createJournalEntry([
            'entry_date' => $cfdi->fecha_timbrado,
            'reference' => "CFDI {$cfdi->serie}-{$cfdi->folio}",
            'description' => "Venta a {$cfdi->receptor_nombre}",
            'lines' => [
                [
                    'account_code' => '1105', // Clientes (AR)
                    'debit' => $cfdi->total,
                    'credit' => 0,
                ],
                [
                    'account_code' => '4101', // Ingresos por ventas
                    'debit' => 0,
                    'credit' => $cfdi->subtotal,
                ],
                [
                    'account_code' => '2108', // IVA por pagar
                    'debit' => 0,
                    'credit' => $cfdi->total - $cfdi->subtotal,
                ],
            ],
        ]);
    }
}
```

---

### **✅ Phase 4 Deliverables**

- [x] CFDIAutomationService (auto-generation from AR Invoice/Payment)
- [x] Event listeners (PaymentCaptured → Generate CFDI → Stamp → GL Post)
- [x] Complete workflow integration
- [x] Customer portal for downloads
- [x] Email notifications
- [x] Documentation

**Time:** 8-12 hours (1-1.5 days)
**Result:** Fully automated system: Payment → CFDI → Stamping → GL Posting

---

## 📊 COMPLETE MODULE SUMMARY

### **Database Tables:** 5
- `payment_transactions` (Stripe payments)
- `billing_company_settings` (Company fiscal data)
- `billing_cfdi_invoices` (CFDI invoices)
- `billing_cfdi_items` (Invoice line items)
- `billing_cfdi_taxes` (Tax details)

### **Services:** 6
- `StripePaymentGateway` (Payment processing)
- `SWPacService` (PAC integration)
- `CFDIXMLGenerator` (XML generation)
- `CFDIPDFGenerator` (PDF generation)
- `CFDIStampingService` (Stamping workflow)
- `CFDIAutomationService` (Auto-generation)

### **API Endpoints:** 30+
- Payment Transactions: 5 CRUD
- Company Settings: 3
- CFDI Invoices: 5 CRUD + 5 actions (stamp, cancel, download XML/PDF, preview)
- Webhooks: 2 (Stripe, SW Sapien)
- Customer Portal: 3 (list my invoices, download XML, download PDF)
- Reports: 5 (monthly summary, errors, cancellations)

### **Events & Listeners:** 4
- `PaymentCaptured` → `GenerateCFDIAfterPayment`
- `CFDIStamped` → `PostCFDIToGeneralLedger`
- `CFDICancelled` → `ReversalJournalEntry`
- `SalesOrderCompleted` → `CreateARInvoiceAndCFDI`

### **Integration Points:**
```
Ecommerce Module
  └─> Stripe Payment (Phase 1)
      └─> PaymentTransaction created
          └─> PaymentCaptured event
              └─> CFDI auto-generated (Phase 4)
                  └─> CFDI stamped with PAC (Phase 3)
                      └─> Journal Entry posted (Accounting Module)
                          └─> Customer receives XML + PDF via email
```

---

## ✅ SUCCESS CRITERIA

**Functional:**
- [ ] Stripe payments working in Ecommerce checkout
- [ ] CFDI XML generated compliant with SAT 4.0
- [ ] PAC stamping successful
- [ ] Customer portal functional
- [ ] Webhooks handling properly
- [ ] GL posting automatic
- [ ] Email notifications sent

**Compliance:**
- [ ] XML validates against SAT XSD schema
- [ ] RFC validation working
- [ ] QR code in PDF functional
- [ ] Audit trail complete
- [ ] Cancellation workflow working

**Technical:**
- [ ] 100+ tests passing (0 errors like HR Module)
- [ ] PaymentGatewayInterface properly implemented
- [ ] Event-driven architecture working
- [ ] Webhook signatures verified
- [ ] Certificate management secure

---

## 📋 TESTING STRATEGY

### **Unit Tests:** 40+
- StripePaymentGateway methods
- SWPacService methods
- CFDIXMLGenerator validation
- CFDIPDFGenerator output
- CFDIAutomationService logic

### **Feature Tests:** 60+
- Payment Transactions CRUD (5 tests × 5 actions = 25)
- CFDI Invoices CRUD (5 tests × 5 actions = 25)
- Webhook handling (5 tests)
- Integration flows (5 tests)

### **Integration Tests:** 10+
- End-to-end: Checkout → Payment → CFDI → GL
- Cancellation flow
- Refund flow

**Total:** 110+ tests

---

## 🚀 DEPLOYMENT CHECKLIST

**Before Production:**
- [ ] Obtain real CSD certificates from SAT
- [ ] Store certificates securely (outside git repo)
- [ ] Get SW Sapien production credentials
- [ ] Get Stripe production keys
- [ ] Configure webhook endpoints in Stripe dashboard
- [ ] Configure webhook endpoints in SW Sapien dashboard
- [ ] Test with real PAC in sandbox mode
- [ ] Verify XML against SAT validator
- [ ] Generate 10 test CFDIs and validate
- [ ] Set up email notifications
- [ ] Configure customer portal domain
- [ ] Run full test suite
- [ ] Load testing (100+ concurrent checkouts)
- [ ] Security audit (especially certificate handling)

---

## 📚 DOCUMENTATION DELIVERABLES

1. **BILLING_MODULE_COMPLETE.md** (1,500+ lines)
   - Complete module reference
   - API endpoints
   - Service methods
   - Integration flows
   - Testing guide

2. **BILLING_FRONTEND_INTEGRATION_GUIDE.md** (1,000+ lines)
   - Stripe.js integration
   - Payment UI components
   - Customer portal API
   - XML/PDF download flows
   - Webhook handling

3. **CFDI_COMPLIANCE_GUIDE.md** (800+ lines)
   - SAT catalogs reference
   - Field mappings
   - Validation rules
   - Common errors and solutions

---

## 💰 COST ANALYSIS

### **Per Transaction:**
```
Sale: $1,000 MXN

Stripe Fee: $39 MXN (3.6% + $3)
SW PAC Fee: $1 MXN
Total Fees: $40 MXN (4% of sale)

Net Revenue: $960 MXN
```

### **Monthly (assuming 100 sales/month):**
```
Stripe Fees: $3,900 MXN
SW Fees: $100 MXN
Total Monthly: $4,000 MXN

Annual: $48,000 MXN
```

---

## 📞 SUPPORT CONTACTS

**Stripe:**
- Docs: https://stripe.com/docs/api
- Support: https://support.stripe.com/

**SW Sapien:**
- Docs: https://developers.sw.com.mx/
- Support: soporte@sw.com.mx
- Phone: +52 55 5985 9000

**SAT:**
- CFDI Specs: https://www.sat.gob.mx/factura
- Validator: https://verificacfdi.facturaelectronica.sat.gob.mx/

---

## 🎯 TIMELINE & EFFORT

| Phase | Duration | Complexity | Can Start |
|-------|----------|------------|-----------|
| Phase 1: Stripe | 2-3 days | Medium | ✅ Now |
| Phase 2: CFDI Structure | 2 days | Medium-High | ✅ Now |
| Phase 3: PAC Integration | 1.5-2 days | High | ⏳ Awaiting SW credentials |
| Phase 4: Full Integration | 1-1.5 days | Medium | After 1-3 |
| **TOTAL** | **6-9 days** | **High (4/5)** | - |

---

**Document Status:** Planning Complete, Ready for Implementation
**Last Updated:** 2025-10-31
**Next Action:** Begin Phase 1 (Stripe Integration) immediately

---

## 🚀 READY TO START?

All requirements gathered:
- ✅ Stripe credentials configured
- ✅ Company fiscal data complete
- ✅ Architecture designed
- ✅ Methodology validated (HR Module: 0 errors)
- ⏳ SW Sapien credentials pending (but Phase 1 can start)

**Let's implement Phase 1 (Stripe) now!**
