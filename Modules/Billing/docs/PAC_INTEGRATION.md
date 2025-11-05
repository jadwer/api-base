# PAC Integration - SW Sapien

Complete guide for integrating SW Sapien PAC (Proveedor Autorizado de Certificación) for CFDI stamping and cancellation.

## Overview

The Billing module now includes full integration with SW Sapien PAC for:
- ✅ CFDI Stamping (Timbrado)
- ✅ CFDI Cancellation
- ✅ SAT Validation
- ✅ Cancellation Status Query
- ✅ Webhook Support for async updates

## Configuration

### Environment Variables

Add the following to your `.env` file:

```env
# SW PAC Configuration
SW_PAC_ENABLED=true
SW_PAC_URL=https://services.test.sw.com.mx  # Production: https://services.sw.com.mx
SW_PAC_TOKEN=                                # Option 1: Pre-generated token
SW_PAC_USER=your-sw-user                     # Option 2: User/password authentication
SW_PAC_PASSWORD=your-sw-password
SW_PAC_TIMEOUT=30
SW_PAC_RETRY_ATTEMPTS=3
SW_PAC_RETRY_DELAY=1000
SW_PAC_WEBHOOK_SECRET=your-webhook-secret

# CFDI Storage Paths
CFDI_STORAGE_PATH=cfdi
CFDI_XML_PATH=cfdi/xml
CFDI_PDF_PATH=cfdi/pdf
```

### Authentication Options

SW Sapien supports two authentication methods:

**Option 1: Pre-generated Token (Recommended)**
```env
SW_PAC_TOKEN=eyJ0eXAiOiJKV1QiLCJhbGc...
```

**Option 2: User/Password (Token auto-generated)**
```env
SW_PAC_USER=your-sw-user
SW_PAC_PASSWORD=your-sw-password
```

## API Endpoints

### 1. Stamp CFDI

**POST** `/api/v1/cfdi-invoices/{id}/stamp`

Stamp a CFDI invoice with the PAC.

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Body:**
```json
{
  "regenerate_xml": false
}
```

**Response (Success):**
```json
{
  "message": "CFDI timbrado correctamente",
  "data": {
    "id": 123,
    "uuid": "A1B2C3D4-E5F6-7890-ABCD-EF1234567890",
    "fecha_timbrado": "2025-11-04T15:30:00Z",
    "status": "valid",
    "folio_completo": "F-000123"
  }
}
```

**Response (Error):**
```json
{
  "message": "Error al timbrar CFDI",
  "error": "El CFDI ya está timbrado"
}
```

### 2. Cancel CFDI

**POST** `/api/v1/cfdi-invoices/{id}/cancel`

Cancel a stamped CFDI invoice.

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Body:**
```json
{
  "motivo_cancelacion": "02",
  "uuid_sustitucion": null
}
```

**Cancellation Motives:**
- `01` - Comprobante emitido con errores con relación (requires `uuid_sustitucion`)
- `02` - Comprobante emitido con errores sin relación
- `03` - No se llevó a cabo la operación
- `04` - Operación nominativa relacionada en una factura global

**Response:**
```json
{
  "message": "CFDI cancelado correctamente",
  "data": {
    "id": 123,
    "uuid": "A1B2C3D4-E5F6-7890-ABCD-EF1234567890",
    "fecha_cancelacion": "2025-11-04T16:00:00Z",
    "status": "cancelled",
    "motivo": "02"
  }
}
```

### 3. Validate with SAT

**GET** `/api/v1/cfdi-invoices/{id}/validate-sat`

Validate CFDI status with SAT.

**Response:**
```json
{
  "message": "Validación completada",
  "data": {
    "status": "Vigente",
    "es_cancelable": "Cancelable sin aceptación",
    "estado": "Vigente",
    "validacion_efos": "200"
  }
}
```

### 4. Get Cancellation Status

**GET** `/api/v1/cfdi-invoices/{id}/cancellation-status`

Get current cancellation status from PAC.

**Response:**
```json
{
  "message": "Estatus de cancelación obtenido",
  "data": {
    "uuid": "A1B2C3D4-E5F6-7890-ABCD-EF1234567890",
    "status": "En proceso",
    "fecha_solicitud": "2025-11-04T16:00:00Z"
  }
}
```

## Webhooks

Configure SW Sapien to send webhook notifications to your application for async updates.

### Webhook Endpoints

**Stamp Notification:**
```
POST /api/v1/webhooks/pac/stamp
```

**Cancel Notification:**
```
POST /api/v1/webhooks/pac/cancel
```

### Webhook Signature Validation

Webhooks are validated using HMAC SHA256 signatures. Configure your webhook secret:

```env
SW_PAC_WEBHOOK_SECRET=your-secret-key
```

SW Sapien sends the signature in the `X-SW-Signature` header.

### Stamp Webhook Payload

```json
{
  "uuid": "A1B2C3D4-E5F6-7890-ABCD-EF1234567890",
  "folio": "F-123",
  "status": "valid",
  "fecha_timbrado": "2025-11-04T15:30:00Z",
  "xml_timbrado": "base64_encoded_xml",
  "qr_code": "base64_encoded_qr"
}
```

### Cancel Webhook Payload

```json
{
  "uuid": "A1B2C3D4-E5F6-7890-ABCD-EF1234567890",
  "status": "cancelled",
  "fecha_cancelacion": "2025-11-04T16:00:00Z",
  "acuse": "base64_encoded_acuse"
}
```

## Service Layer Usage

### Programmatic Stamping

```php
use Modules\Billing\Services\CFDI\CFDIStampingService;
use Modules\Billing\Models\CFDIInvoice;

$stampingService = app(CFDIStampingService::class);
$invoice = CFDIInvoice::find(123);

try {
    $stampedInvoice = $stampingService->stamp($invoice);
    echo "UUID: " . $stampedInvoice->uuid;
} catch (\Modules\Billing\Exceptions\PacException $e) {
    echo "Error: " . $e->getMessage();
}
```

### Programmatic Cancellation

```php
$stampingService = app(CFDIStampingService::class);
$invoice = CFDIInvoice::find(123);

try {
    $cancelledInvoice = $stampingService->cancel(
        invoice: $invoice,
        motivoCancelacion: '02'
    );
    echo "Cancelled: " . $cancelledInvoice->fecha_cancelacion;
} catch (\Modules\Billing\Exceptions\PacException $e) {
    echo "Error: " . $e->getMessage();
}
```

## Events

The stamping service dispatches events for integration with other parts of your application:

### CFDIStamped Event

Fired when a CFDI is successfully stamped.

```php
namespace Modules\Billing\Events;

class CFDIStamped
{
    public CFDIInvoice $invoice;
}
```

**Example Listener:**
```php
use Modules\Billing\Events\CFDIStamped;

Event::listen(CFDIStamped::class, function (CFDIStamped $event) {
    // Send notification to customer
    Mail::to($event->invoice->contact->email)
        ->send(new CFDIStampedNotification($event->invoice));
});
```

### CFDICancelled Event

Fired when a CFDI is successfully cancelled.

```php
namespace Modules\Billing\Events;

class CFDICancelled
{
    public CFDIInvoice $invoice;
}
```

## Permissions

The following permissions control access to PAC operations:

- `billing.cfdi-invoices.stamp` - Stamp CFDI with PAC
- `billing.cfdi-invoices.cancel` - Cancel CFDI with PAC
- `billing.cfdi-invoices.validate` - Validate CFDI with SAT
- `billing.cfdi-invoices.cancellation-status` - Query cancellation status

**Role Assignments:**
- `god` - All permissions
- `admin` - All permissions
- `tech` - No PAC permissions (read-only)
- `customer` - No PAC permissions

## Testing

Run PAC integration tests:

```bash
# Test PAC integration
php artisan test Modules/Billing/tests/Feature/CFDIStampingTest.php
```

**Note:** Some tests require PAC to be enabled and properly configured:

```php
if (!config('billing.sw_pac.enabled')) {
    $this->markTestSkipped('PAC integration is not enabled');
}
```

## Error Handling

### Common Errors

**1. PAC not enabled:**
```json
{
  "message": "Error al timbrar CFDI",
  "error": "SW PAC integration is not enabled"
}
```
**Solution:** Set `SW_PAC_ENABLED=true` in `.env`

**2. Authentication failed:**
```json
{
  "message": "Error al timbrar CFDI",
  "error": "Error al autenticar con SW PAC"
}
```
**Solution:** Verify `SW_PAC_TOKEN` or `SW_PAC_USER`/`SW_PAC_PASSWORD`

**3. Already stamped:**
```json
{
  "message": "Error al timbrar CFDI",
  "error": "El CFDI ya está timbrado"
}
```
**Solution:** Invoice already has UUID, cannot stamp again

**4. Invalid cancellation motive:**
```json
{
  "message": "Error al cancelar CFDI",
  "error": "El motivo 01 requiere UUID de sustitución"
}
```
**Solution:** Provide `uuid_sustitucion` when using motive `01`

## Production Checklist

Before going live with PAC integration:

- [ ] Configure production SW PAC URL: `https://services.sw.com.mx`
- [ ] Obtain production credentials from SW Sapien
- [ ] Set `SW_PAC_ENABLED=true`
- [ ] Configure webhook secret
- [ ] Test stamping with real invoices in sandbox
- [ ] Test cancellation flow
- [ ] Verify SAT validation works
- [ ] Configure webhook URLs in SW Sapien dashboard
- [ ] Set up event listeners for notifications
- [ ] Test error handling and retries
- [ ] Monitor logs for PAC errors
- [ ] Configure storage paths for XML/PDF files

## Support

For SW Sapien API support:
- Documentation: https://developers.sw.com.mx/
- Support: soporte@sw.com.mx

## Architecture

```
┌─────────────────┐
│ CFDIInvoice     │
│ Controller      │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ CFDIStamping    │
│ Service         │ ← Orchestration Layer
└────┬───────┬────┘
     │       │
     ▼       ▼
┌────────┐ ┌──────────┐
│ SWPac  │ │ CFDI     │
│ Service│ │ Generators│
└────┬───┘ └──────────┘
     │
     ▼
┌────────────────┐
│ SW Sapien API  │
│ (External PAC) │
└────────────────┘
```

## Files Created

- `app/Services/PAC/SWPacService.php` - SW Sapien API integration
- `app/Services/CFDI/CFDIStampingService.php` - Stamping orchestration
- `app/Http/Controllers/Api/V1/PacWebhookController.php` - Webhook handler
- `app/Events/CFDIStamped.php` - Stamping event
- `app/Events/CFDICancelled.php` - Cancellation event
- `app/Exceptions/PacException.php` - PAC-specific exception
- `tests/Feature/CFDIStampingTest.php` - Integration tests
- `config/config.php` - PAC configuration
- `routes/jsonapi.php` - PAC endpoints

## Next Steps

1. Obtain SW Sapien credentials
2. Configure `.env` with credentials
3. Test in sandbox environment
4. Implement event listeners for notifications
5. Monitor production stamping
