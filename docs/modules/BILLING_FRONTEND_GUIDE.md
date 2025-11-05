# Billing Module - Frontend Integration Guide

**Module:** Billing (Mexican CFDI Electronic Invoicing)
**Entities:** 3 (CFDIInvoice, CFDIItem, CompanySetting)
**Endpoints:** 15
**Base Path:** `/api/v1`

## Overview

The Billing module provides Mexican CFDI (Comprobante Fiscal Digital por Internet) electronic invoicing functionality. It integrates with PAC (Proveedor Autorizado de Certificación) providers for official tax authority stamping and supports the complete invoicing workflow from draft to cancellation.

**IMPORTANT:** This module is specifically designed for Mexican fiscal requirements (SAT - Servicio de Administración Tributaria).

## Core Entities

### 1. CFDIInvoice

**Endpoint:** `/cfdi-invoices`
**Resource Type:** `cfdi-invoices`

#### TypeScript Interface

```typescript
type TipoComprobante = 'I' | 'E' | 'T' | 'N' | 'P'; // Ingreso, Egreso, Traslado, Nomina, Pago
type CFDIStatus = 'draft' | 'generated' | 'stamped' | 'valid' | 'cancelled' | 'error';
type MetodoPago = 'PUE' | 'PPD'; // Pago en Una Exhibicion, Pago en Parcialidades o Diferido

interface CFDIInvoice {
  id: string;
  companySettingId: number;
  contactId: number;
  arInvoiceId: number | null;

  // CFDI Identification
  series: string;
  folio: number;
  uuid: string | null;  // Assigned after stamping
  tipoComprobante: TipoComprobante;

  // Customer (Receptor) Information
  receptorRfc: string;
  receptorNombre: string;
  receptorUsoCfdi: string;  // G01, G02, G03, etc.
  receptorRegimenFiscal: string;
  receptorDomicilioFiscal: string;

  // Amounts (stored in cents)
  subtotal: number;
  total: number;
  descuento: number;
  iva: number;
  ieps: number;
  isrRetenido: number;
  ivaRetenido: number;

  // Currency
  moneda: string;  // MXN, USD, EUR, etc.
  tipoCambio: number;

  // Payment Information
  formaPago: string;  // 01, 02, 03, etc. (SAT catalog)
  metodoPago: MetodoPago;
  condicionesPago: string | null;

  // Related CFDI
  cfdiRelacionadoTipo: string | null;  // 01, 02, 03, etc.
  cfdiRelacionadoUuids: string[] | null;

  // Status
  status: CFDIStatus;

  // Dates
  fechaEmision: string;
  fechaTimbrado: string | null;
  fechaCancelacion: string | null;

  // Files
  xmlPath: string | null;
  pdfPath: string | null;

  // Error handling
  errorMessage: string | null;

  metadata: Record<string, any> | null;
  createdAt: string;
  updatedAt: string;
}
```

#### Field Mappings

| JSON:API Field | Database Column | Type | Required | Sortable | Filterable |
|---------------|-----------------|------|----------|----------|------------|
| `series` | `series` | string | Yes | Yes | Yes |
| `folio` | `folio` | number | Yes | Yes | Yes |
| `uuid` | `uuid` | string | No | Yes | Yes |
| `tipoComprobante` | `tipo_comprobante` | string | Yes | Yes | Yes |
| `receptorRfc` | `receptor_rfc` | string | Yes | Yes | Yes |
| `receptorNombre` | `receptor_nombre` | string | Yes | Yes | No |
| `status` | `status` | string | Yes | Yes | Yes |
| `metodoPago` | `metodo_pago` | string | Yes | Yes | Yes |
| `fechaEmision` | `fecha_emision` | datetime | Yes | Yes | No |

#### Relationships

- `companySetting` → CompanySetting (belongsTo)
- `contact` → Contact (belongsTo)
- `arInvoice` → ARInvoice (belongsTo)
- `items` → CFDIItem[] (hasMany)

---

### 2. CompanySetting

**Endpoint:** `/company-settings`
**Resource Type:** `company-settings`

#### TypeScript Interface

```typescript
interface CompanySetting {
  id: string;

  // Company Fiscal Information
  companyName: string;
  rfc: string;
  taxRegime: string;  // e.g., "601", "612"
  postalCode: string;

  // Invoice Series & Folios
  invoiceSeries: string;
  creditNoteSeries: string;
  nextInvoiceFolio: number;
  nextCreditNoteFolio: number;

  // PAC Configuration
  pacProvider: string;  // "finkok", "sw", etc.
  pacUsername: string;
  // pacPassword is encrypted, never exposed
  pacProductionMode: boolean;

  // Digital Certificate (CSD)
  certificateFile: string;  // .cer file path
  keyFile: string;          // .key file path
  // keyPassword is encrypted, never exposed

  // Additional Settings
  logoPath: string | null;
  additionalSettings: Record<string, any> | null;

  isActive: boolean;
  createdAt: string;
  updatedAt: string;
}
```

---

## Complete CFDI Workflow

### 1. Create Draft CFDI Invoice

```javascript
async function createCFDIDraft(invoiceData) {
  const payload = {
    data: {
      type: "cfdi-invoices",
      attributes: {
        companySettingId: invoiceData.companySettingId,
        contactId: invoiceData.customerId,
        arInvoiceId: invoiceData.arInvoiceId, // Optional link to AR invoice

        series: "A",
        tipoComprobante: "I", // Ingreso (sales invoice)

        // Customer information
        receptorRfc: invoiceData.customerRfc,
        receptorNombre: invoiceData.customerName,
        receptorUsoCfdi: "G03", // General expenses
        receptorRegimenFiscal: "601",
        receptorDomicilioFiscal: invoiceData.customerZipCode,

        // Amounts (in cents)
        subtotal: Math.round(invoiceData.subtotal * 100),
        iva: Math.round(invoiceData.iva * 100),
        total: Math.round(invoiceData.total * 100),
        descuento: 0,

        // Currency
        moneda: "MXN",
        tipoCambio: 1.0,

        // Payment
        formaPago: "03", // Electronic transfer
        metodoPago: "PUE", // Pago en una exhibición

        status: "draft",
        fechaEmision: new Date().toISOString()
      }
    }
  };

  const response = await fetch('/api/v1/cfdi-invoices', {
    method: 'POST',
    headers,
    body: JSON.stringify(payload)
  });

  return await response.json();
}
```

### 2. Add CFDI Items (Concepts)

```javascript
async function addCFDIItems(cfdiInvoiceId, items) {
  for (const item of items) {
    const payload = {
      data: {
        type: "cfdi-items",
        attributes: {
          cfdiInvoiceId: cfdiInvoiceId,
          claveProdServ: item.productSatCode, // SAT catalog code
          noIdentificacion: item.sku,
          cantidad: item.quantity,
          claveUnidad: item.unitSatCode, // SAT unit code
          unidad: item.unitName,
          descripcion: item.description,
          valorUnitario: Math.round(item.unitPrice * 100),
          importe: Math.round(item.total * 100),
          objetoImp: "02", // Subject to tax
          trasladoImpuesto: "002", // IVA
          trasladoTasaOCuota: "0.160000",
          trasladoImporte: Math.round(item.tax * 100)
        }
      }
    };

    await fetch('/api/v1/cfdi-items', {
      method: 'POST',
      headers,
      body: JSON.stringify(payload)
    });
  }
}
```

### 3. Generate XML

```javascript
async function generateCFDIXml(cfdiInvoiceId) {
  // Call service endpoint to generate XML
  const response = await fetch(`/api/v1/cfdi-invoices/${cfdiInvoiceId}/generate-xml`, {
    method: 'POST',
    headers
  });

  if (!response.ok) {
    const error = await response.json();
    throw new Error(error.errors[0].detail);
  }

  const result = await response.json();

  return {
    cfdiId: cfdiInvoiceId,
    xmlPath: result.xmlPath,
    status: result.status // Should be 'generated'
  };
}
```

### 4. Generate PDF

```javascript
async function generateCFDIPdf(cfdiInvoiceId) {
  const response = await fetch(`/api/v1/cfdi-invoices/${cfdiInvoiceId}/generate-pdf`, {
    method: 'POST',
    headers
  });

  const result = await response.json();

  return {
    cfdiId: cfdiInvoiceId,
    pdfPath: result.pdfPath
  };
}
```

### 5. Stamp with PAC (Timbrado)

```javascript
async function stampCFDI(cfdiInvoiceId) {
  // Send to PAC for official stamping
  const response = await fetch(`/api/v1/cfdi-invoices/${cfdiInvoiceId}/stamp`, {
    method: 'POST',
    headers
  });

  if (!response.ok) {
    const error = await response.json();
    throw new Error(`Stamping failed: ${error.errors[0].detail}`);
  }

  const result = await response.json();

  return {
    cfdiId: cfdiInvoiceId,
    uuid: result.uuid,
    fechaTimbrado: result.fechaTimbrado,
    status: result.status // Should be 'valid'
  };
}
```

### 6. Download Files

```javascript
async function downloadCFDIFiles(cfdiInvoiceId) {
  // Get CFDI details
  const response = await fetch(
    `/api/v1/cfdi-invoices/${cfdiInvoiceId}`,
    { headers }
  );

  const cfdi = await response.json();

  // Download XML
  if (cfdi.data.attributes.xmlPath) {
    const xmlUrl = `/api/v1/cfdi-invoices/${cfdiInvoiceId}/download-xml`;
    window.open(xmlUrl, '_blank');
  }

  // Download PDF
  if (cfdi.data.attributes.pdfPath) {
    const pdfUrl = `/api/v1/cfdi-invoices/${cfdiInvoiceId}/download-pdf`;
    window.open(pdfUrl, '_blank');
  }

  return {
    xmlPath: cfdi.data.attributes.xmlPath,
    pdfPath: cfdi.data.attributes.pdfPath,
    uuid: cfdi.data.attributes.uuid
  };
}
```

### 7. Cancel CFDI

```javascript
async function cancelCFDI(cfdiInvoiceId, motivo, uuidReemplazo = null) {
  const payload = {
    motivo: motivo, // "01", "02", "03", "04"
    uuidReemplazo: uuidReemplazo // Required for motivo "01"
  };

  const response = await fetch(`/api/v1/cfdi-invoices/${cfdiInvoiceId}/cancel`, {
    method: 'POST',
    headers,
    body: JSON.stringify(payload)
  });

  if (!response.ok) {
    const error = await response.json();
    throw new Error(`Cancellation failed: ${error.errors[0].detail}`);
  }

  const result = await response.json();

  return {
    cfdiId: cfdiInvoiceId,
    status: result.status, // Should be 'cancelled'
    fechaCancelacion: result.fechaCancelacion
  };
}
```

---

## Complete Invoice Creation Flow

```javascript
async function createAndStampCFDI(invoiceData) {
  try {
    // 1. Create draft
    console.log('Creating draft CFDI...');
    const draft = await createCFDIDraft(invoiceData);
    const cfdiId = draft.data.id;

    // 2. Add items
    console.log('Adding items...');
    await addCFDIItems(cfdiId, invoiceData.items);

    // 3. Generate XML
    console.log('Generating XML...');
    const xmlResult = await generateCFDIXml(cfdiId);

    // 4. Generate PDF
    console.log('Generating PDF...');
    const pdfResult = await generateCFDIPdf(cfdiId);

    // 5. Stamp with PAC
    console.log('Stamping with PAC...');
    const stampResult = await stampCFDI(cfdiId);

    // 6. Get final CFDI
    const finalResponse = await fetch(`/api/v1/cfdi-invoices/${cfdiId}`, { headers });
    const finalCFDI = await finalResponse.json();

    console.log('CFDI created successfully!');
    console.log('UUID:', stampResult.uuid);
    console.log('Folio:', `${finalCFDI.data.attributes.series}-${finalCFDI.data.attributes.folio}`);

    return {
      success: true,
      cfdiId: cfdiId,
      uuid: stampResult.uuid,
      folio: `${finalCFDI.data.attributes.series}-${finalCFDI.data.attributes.folio}`,
      xmlPath: finalCFDI.data.attributes.xmlPath,
      pdfPath: finalCFDI.data.attributes.pdfPath
    };

  } catch (error) {
    console.error('CFDI creation failed:', error);
    return {
      success: false,
      error: error.message
    };
  }
}
```

---

## PAC Webhook Handling

```javascript
// Handle PAC webhook for async stamping
app.post('/api/webhooks/pac-response', async (req, res) => {
  const { cfdiId, uuid, status, fechaTimbrado, errorMessage } = req.body;

  // Update CFDI with PAC response
  const payload = {
    data: {
      type: "cfdi-invoices",
      id: cfdiId,
      attributes: {
        uuid: uuid,
        status: status,
        fechaTimbrado: fechaTimbrado,
        errorMessage: errorMessage
      }
    }
  };

  await fetch(`/api/v1/cfdi-invoices/${cfdiId}`, {
    method: 'PATCH',
    headers,
    body: JSON.stringify(payload)
  });

  // Notify user (via websocket, email, etc.)
  notifyUser(cfdiId, status);

  res.status(200).json({ received: true });
});
```

---

## Common Use Cases

### 1. Create Credit Note (Nota de Crédito)

```javascript
async function createCreditNote(originalCfdiUuid, amount, reason) {
  const payload = {
    data: {
      type: "cfdi-invoices",
      attributes: {
        tipoComprobante: "E", // Egreso (credit note)
        cfdiRelacionadoTipo: "01", // Note of credit for returned goods
        cfdiRelacionadoUuids: [originalCfdiUuid],
        // ... other fields
      }
    }
  };

  // Follow same workflow as invoice
}
```

### 2. Query CFDI by Customer

```javascript
async function getCustomerCFDIs(customerRfc) {
  const response = await fetch(
    `/api/v1/cfdi-invoices?filter[receptorRfc]=${customerRfc}&filter[status]=valid&sort=-fechaEmision`,
    { headers }
  );

  return await response.json();
}
```

---

## Permissions

### Role-Based Access

| Role | Read | Create | Update | Delete | Stamp | Cancel |
|------|------|--------|--------|--------|-------|--------|
| **God** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| **Admin** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| **Tech** | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **Customer** | ✅ (own) | ❌ | ❌ | ❌ | ❌ | ❌ |

---

## Quick Reference

**Available Endpoints:**
- `GET /api/v1/cfdi-invoices` - List invoices
- `POST /api/v1/cfdi-invoices` - Create draft
- `GET /api/v1/cfdi-invoices/{id}` - Get invoice
- `PATCH /api/v1/cfdi-invoices/{id}` - Update draft
- `POST /api/v1/cfdi-invoices/{id}/generate-xml` - Generate XML
- `POST /api/v1/cfdi-invoices/{id}/generate-pdf` - Generate PDF
- `POST /api/v1/cfdi-invoices/{id}/stamp` - Stamp with PAC
- `POST /api/v1/cfdi-invoices/{id}/cancel` - Cancel invoice
- `GET /api/v1/cfdi-invoices/{id}/download-xml` - Download XML
- `GET /api/v1/cfdi-invoices/{id}/download-pdf` - Download PDF

**CFDI Status Flow:**
1. `draft` → Create invoice
2. `generated` → XML generated
3. `stamped` → PAC stamping in progress
4. `valid` → Successfully stamped (has UUID)
5. `cancelled` → Cancelled with SAT

**Important Notes:**
- Amounts are stored in **cents** (multiply by 100)
- UUID is assigned only after successful PAC stamping
- XMLs and PDFs are generated separately
- Cancellation requires valid UUID and correct motivo code

**Related Modules:**
- [Finance Module](FINANCE_FRONTEND_GUIDE.md) - AR invoices link to CFDI
- [Contacts Module](CONTACTS_FRONTEND_GUIDE.md) - Customer RFC information
- [Sales Module](SALES_FRONTEND_GUIDE.md) - Sales orders generate CFDI
