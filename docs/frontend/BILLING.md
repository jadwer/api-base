# Billing Module (CFDI)

## Overview

Mexican electronic invoicing (CFDI) with PAC integration (SW Sapien).

## Entities

| Entity | Endpoint | Description |
|--------|----------|-------------|
| CfdiInvoice | `/api/v1/cfdi-invoices` | CFDI invoices |
| CfdiItem | `/api/v1/cfdi-items` | Invoice items |
| CompanySetting | `/api/v1/company-settings` | Company tax info |

## CFDI Invoice

```typescript
type CfdiStatus = 'draft' | 'stamped' | 'cancelled' | 'cancellation_pending';
type UsoCfdi = 'G01' | 'G02' | 'G03' | 'I01' | 'I02' | 'I03' | 'I04' | 'I05' | 'I06' | 'I07' | 'I08' | 'P01';
type MetodoPago = 'PUE' | 'PPD';  // Pago en Una Exhibicion, Pago en Parcialidades
type FormaPago = '01' | '02' | '03' | '04' | '28' | '99';  // Efectivo, Cheque, Transferencia, Tarjeta, etc.

interface CfdiInvoice {
  id: string;
  arInvoiceId: number;        // Linked AR invoice
  contactId: number;
  companySettingId: number;

  // CFDI data
  uuid: string | null;        // Folio fiscal (after stamping)
  serie: string;              // A, B, C...
  folio: string;              // Sequential number
  fechaEmision: string;
  usoCfdi: UsoCfdi;
  metodoPago: MetodoPago;
  formaPago: FormaPago;

  // Amounts
  subtotal: number;
  descuento: number;
  iva: number;
  total: number;

  // Status
  status: CfdiStatus;
  stampedAt: string | null;
  cancelledAt: string | null;
  cancellationReason: string | null;

  // XML/PDF
  xmlContent: string | null;
  pdfPath: string | null;

  createdAt: string;
}

// List CFDI invoices
GET /api/v1/cfdi-invoices?filter[status]=stamped&include=contact,arInvoice

// Create CFDI from AR Invoice
POST /api/v1/cfdi-invoices
{
  "data": {
    "type": "cfdi-invoices",
    "attributes": {
      "arInvoiceId": 10,
      "contactId": 5,
      "companySettingId": 1,
      "serie": "A",
      "usoCfdi": "G03",
      "metodoPago": "PUE",
      "formaPago": "03"
    }
  }
}
```

## CFDI Operations

### Stamp Invoice (Timbrar)

```typescript
// Stamp CFDI with PAC
POST /api/v1/cfdi-invoices/{id}/stamp

// Response
{
  "success": true,
  "uuid": "ABC12345-XXXX-XXXX-XXXX-XXXXXXXXXXXX",
  "fechaTimbrado": "2026-01-08T10:30:00",
  "selloSAT": "...",
  "cadenaOriginal": "..."
}

// Error response
{
  "success": false,
  "error_code": "CFDI301",
  "message": "RFC del receptor no encontrado en SAT"
}
```

### Generate XML

```typescript
// Generate XML (before stamping)
POST /api/v1/cfdi-invoices/{id}/generate-xml

// Download stamped XML
GET /api/v1/cfdi-invoices/{id}/download-xml
Content-Type: application/xml
```

### Generate PDF

```typescript
// Generate PDF
POST /api/v1/cfdi-invoices/{id}/generate-pdf

// Preview PDF (in browser)
GET /api/v1/cfdi-invoices/{id}/preview-pdf

// Download PDF
GET /api/v1/cfdi-invoices/{id}/download-pdf
Content-Type: application/pdf
```

### Cancel Invoice

```typescript
// Request cancellation
POST /api/v1/cfdi-invoices/{id}/cancel
{
  "motivo": "02",  // 01: Error, 02: Sin efecto, 03: Operacion no realizada
  "folioSustitucion": null  // UUID of replacement (for motivo 01)
}

// Response (may be pending if receptor needs to accept)
{
  "status": "cancellation_pending",
  "message": "Awaiting receptor approval"
}

// Check cancellation status
GET /api/v1/cfdi-invoices/{id}/cancellation-status

// Response
{
  "status": "cancelled",
  "fechaCancelacion": "2026-01-09T15:00:00",
  "acuse": "..."
}
```

### Validate with SAT

```typescript
// Validate CFDI status with SAT
GET /api/v1/cfdi-invoices/{id}/validate-sat

// Response
{
  "valid": true,
  "uuid": "ABC12345-...",
  "status": "Vigente",  // or "Cancelado"
  "fechaEmision": "2026-01-08",
  "rfcEmisor": "AAA010101AAA",
  "rfcReceptor": "BBB020202BBB"
}
```

## CFDI Item

```typescript
interface CfdiItem {
  id: string;
  cfdiInvoiceId: number;
  productId: number | null;

  // SAT catalog codes
  claveProdServ: string;     // SAT product code
  claveUnidad: string;       // SAT unit code
  noIdentificacion: string;  // SKU

  cantidad: number;
  descripcion: string;
  valorUnitario: number;
  descuento: number;
  importe: number;

  // Taxes
  ivaRate: number;           // 0.16
  ivaAmount: number;

  createdAt: string;
}

// Get items for invoice
GET /api/v1/cfdi-items?filter[cfdi_invoice_id]=1&include=product
```

## Company Setting

```typescript
interface CompanySetting {
  id: string;
  name: string;              // Company name
  rfc: string;               // Tax ID
  regimenFiscal: string;     // SAT regime code
  domicilioFiscal: string;   // Postal code

  // Certificates
  cerPath: string | null;
  keyPath: string | null;
  cerPassword: string | null;  // Encrypted

  // PAC credentials (SW Sapien)
  pacToken: string | null;     // Encrypted

  // Invoice settings
  serie: string;
  nextFolio: number;

  isActive: boolean;
  createdAt: string;
}

// Get company settings
GET /api/v1/company-settings

// Update settings
PATCH /api/v1/company-settings/{id}
```

## SAT Catalogs

```typescript
// Get product codes (ClaveProdServ)
GET /api/v1/sat-catalogs/productos?search=computadora

// Get unit codes (ClaveUnidad)
GET /api/v1/sat-catalogs/unidades?search=pieza

// Get tax regimes
GET /api/v1/sat-catalogs/regimenes

// Get payment forms
GET /api/v1/sat-catalogs/formas-pago

// Get CFDI usage
GET /api/v1/sat-catalogs/uso-cfdi
```

## Uso CFDI Codes

| Code | Description |
|------|-------------|
| G01 | Adquisicion de mercancias |
| G02 | Devoluciones, descuentos o bonificaciones |
| G03 | Gastos en general |
| I01 | Construcciones |
| I02 | Mobiliario y equipo de oficina |
| I03 | Equipo de transporte |
| I04 | Equipo de computo |
| I05 | Dados, troqueles, moldes |
| I06 | Comunicaciones telefonicas |
| I07 | Comunicaciones satelitales |
| I08 | Otra maquinaria y equipo |
| P01 | Por definir |

## Complete CFDI Flow

```typescript
async function createAndStampCfdi(arInvoiceId: number, contactId: number) {
  // 1. Create CFDI record
  const cfdi = await fetch('/api/v1/cfdi-invoices', {
    method: 'POST',
    headers,
    body: JSON.stringify({
      data: {
        type: 'cfdi-invoices',
        attributes: {
          arInvoiceId,
          contactId,
          companySettingId: 1,
          serie: 'A',
          usoCfdi: 'G03',
          metodoPago: 'PUE',
          formaPago: '03'  // Transferencia
        }
      }
    })
  });
  const cfdiData = await cfdi.json();

  // 2. Generate XML
  await fetch(`/api/v1/cfdi-invoices/${cfdiData.data.id}/generate-xml`, {
    method: 'POST',
    headers
  });

  // 3. Stamp with PAC
  const stampResult = await fetch(`/api/v1/cfdi-invoices/${cfdiData.data.id}/stamp`, {
    method: 'POST',
    headers
  });
  const { uuid, fechaTimbrado } = await stampResult.json();

  // 4. Generate PDF
  await fetch(`/api/v1/cfdi-invoices/${cfdiData.data.id}/generate-pdf`, {
    method: 'POST',
    headers
  });

  return {
    cfdiId: cfdiData.data.id,
    uuid,
    fechaTimbrado,
    downloadPdf: `/api/v1/cfdi-invoices/${cfdiData.data.id}/download-pdf`,
    downloadXml: `/api/v1/cfdi-invoices/${cfdiData.data.id}/download-xml`
  };
}
```

## Business Rules

| Rule | Description | Frontend Impact |
|------|-------------|-----------------|
| RFC Validation | Must be valid RFC format | Validate before save |
| SAT Codes | Products need SAT codes | Require mapping |
| Stamp Required | Must stamp within 72 hours | Show deadline |
| Cancel Period | 24h for self-cancel | Show timer |
| Folio Sequence | Unique per serie | Auto-generate |
| XML Required | Stamp needs valid XML | Generate before stamp |

## Error Codes

| Code | Description |
|------|-------------|
| CFDI301 | RFC not found in SAT |
| CFDI302 | Certificate expired |
| CFDI303 | Invalid digital seal |
| CFDI401 | Duplicate UUID |
| CFDI402 | Cancellation rejected |
