# Phase 5.1: Billing/CFDI Module - Implementation Plan

**Status:** 📋 Planning
**Start Date:** TBD
**Estimated Duration:** 5-7 days
**Complexity:** High (4/5)
**Priority:** 🔴 CRITICAL (for Mexico operations)
**Dependencies:** Finance Module ✅, Accounting Module ✅

---

## Objective

Implement Mexican electronic invoicing (CFDI 4.0) module for full SAT (Servicio de Administración Tributaria) compliance. Enable automated XML generation, PAC integration for digital stamping (timbrado), certificate management (CSD), and cancellation workflows. This module is MANDATORY for legal operations in Mexico.

**Business Value:**
- Legal compliance with Mexican tax law
- Automated CFDI 4.0 XML generation
- Digital stamping (timbrado) via PAC integration
- Real-time SAT validation
- Invoice cancellation workflow
- Customer portal for CFDI download
- Regulatory audit trail

---

## Regulatory Context

**Mexican CFDI Requirements:**
- **CFDI 4.0:** Current standard (since January 2022)
- **PAC Integration:** Authorized stamping provider (Proveedor Autorizado de Certificación)
- **CSD Certificates:** Digital signature certificates from SAT
- **72-hour Rule:** CFDIs must be stamped within 72 hours of issue
- **Cancellation:** Requires customer acceptance (some exceptions)
- **Retention:** 5 years minimum

---

## Architecture Decision

**Module Approach:** Create dedicated `Billing` module

**Why?**
- Isolated regulatory compliance code
- External PAC integration management
- Certificate handling and security
- CFDI-specific business rules
- Separate from general Finance module

---

## Implementation Plan

### Stage 1: CFDI Data Model & Company Setup (Day 1, 6-7 hours)

#### 1.1 Database Migrations

**Table: `billing_company_settings`**
```sql
CREATE TABLE billing_company_settings (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    rfc VARCHAR(13) NOT NULL UNIQUE,
    legal_name VARCHAR(255) NOT NULL,
    fiscal_regime VARCHAR(10) NOT NULL, -- 601, 603, etc. (SAT catalog)
    postal_code VARCHAR(5) NOT NULL,
    tax_domicile TEXT, -- Fiscal address

    -- CSD Certificates
    certificate_cer TEXT, -- Base64 encoded .cer file
    certificate_key TEXT, -- Base64 encoded .key.pem file
    certificate_password VARCHAR(255), -- Encrypted
    certificate_number VARCHAR(20), -- Número de certificado
    certificate_valid_from DATE,
    certificate_valid_until DATE,

    -- PAC Configuration
    pac_provider VARCHAR(100), -- finkok, sw, diconsa, etc.
    pac_username VARCHAR(255),
    pac_password VARCHAR(255), -- Encrypted
    pac_test_mode BOOLEAN DEFAULT TRUE,
    pac_endpoint_stamp VARCHAR(255),
    pac_endpoint_cancel VARCHAR(255),

    -- Settings
    cfdi_version VARCHAR(10) DEFAULT '4.0',
    series_prefix VARCHAR(10), -- e.g., 'F' for Facturas
    next_folio_number INTEGER DEFAULT 1,
    logo_url VARCHAR(255),

    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    INDEX idx_company_rfc (rfc)
);
```

**Table: `billing_cfdi_invoices`**
```sql
CREATE TABLE billing_cfdi_invoices (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,

    -- Reference to Finance Module
    ar_invoice_id BIGINT UNSIGNED,

    -- CFDI Fields (CFDI 4.0 spec)
    uuid VARCHAR(36) UNIQUE, -- Folio Fiscal (from PAC after stamping)
    serie VARCHAR(25),
    folio VARCHAR(40) NOT NULL,
    fecha_emision TIMESTAMP NOT NULL,
    fecha_timbrado TIMESTAMP,

    -- Emisor (Issuer - our company)
    emisor_rfc VARCHAR(13) NOT NULL,
    emisor_nombre VARCHAR(255) NOT NULL,
    emisor_regimen_fiscal VARCHAR(10) NOT NULL,

    -- Receptor (Receiver - customer)
    receptor_rfc VARCHAR(13) NOT NULL,
    receptor_nombre VARCHAR(255) NOT NULL,
    receptor_domicilio_fiscal VARCHAR(5), -- Postal code
    receptor_regimen_fiscal VARCHAR(10),
    receptor_uso_cfdi VARCHAR(10) NOT NULL, -- G01, G03, etc.

    -- Amounts
    subtotal DECIMAL(18,6) NOT NULL,
    descuento DECIMAL(18,6) DEFAULT 0,
    total DECIMAL(18,6) NOT NULL,
    moneda VARCHAR(3) DEFAULT 'MXN',
    tipo_cambio DECIMAL(10,6) DEFAULT 1,

    -- Payment
    forma_pago VARCHAR(2), -- 01, 02, etc. (SAT catalog)
    metodo_pago VARCHAR(3) DEFAULT 'PUE', -- PUE, PPD
    condiciones_pago VARCHAR(255),

    -- CFDI Type
    tipo_comprobante VARCHAR(1) DEFAULT 'I', -- I=Ingreso, E=Egreso, T=Traslado, P=Pago
    tipo_relacion VARCHAR(2), -- 01=Nota crédito, 02=Nota débito, etc.
    cfdi_relacionados JSON, -- Array of related UUIDs

    -- Status & Workflow
    status VARCHAR(50) DEFAULT 'draft', -- draft, stamping, stamped, cancelled, error
    stamping_error TEXT,

    -- XML & PDF
    xml_original TEXT, -- XML before stamping
    xml_timbrado TEXT, -- XML after stamping (with Timbre)
    pdf_url VARCHAR(255),

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
    INDEX idx_cfdi_uuid (uuid),
    INDEX idx_cfdi_status (status),
    INDEX idx_cfdi_receptor_rfc (receptor_rfc),
    INDEX idx_cfdi_fecha_emision (fecha_emision)
);
```

**Table: `billing_cfdi_items`**
```sql
CREATE TABLE billing_cfdi_items (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    cfdi_invoice_id BIGINT UNSIGNED NOT NULL,

    -- Product/Service
    clave_prod_serv VARCHAR(10) NOT NULL, -- SAT product/service code
    no_identificacion VARCHAR(100), -- SKU
    cantidad DECIMAL(18,6) NOT NULL,
    clave_unidad VARCHAR(10) NOT NULL, -- SAT unit code (KGM, E48, etc.)
    unidad VARCHAR(20), -- Descripción de la unidad
    descripcion TEXT NOT NULL,
    valor_unitario DECIMAL(18,6) NOT NULL,
    importe DECIMAL(18,6) NOT NULL,
    descuento DECIMAL(18,6) DEFAULT 0,

    -- Object Type (01=Not subject, 02=Subject)
    objeto_imp VARCHAR(2) DEFAULT '02',

    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (cfdi_invoice_id) REFERENCES billing_cfdi_invoices(id) ON DELETE CASCADE,
    INDEX idx_cfdi_items_invoice (cfdi_invoice_id)
);
```

**Table: `billing_cfdi_taxes`**
```sql
CREATE TABLE billing_cfdi_taxes (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    cfdi_item_id BIGINT UNSIGNED NOT NULL,

    tipo VARCHAR(10) NOT NULL, -- Traslado o Retención
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

#### 1.2 Models

**CFDIInvoice Model:**
```php
class CFDIInvoice extends Model
{
    protected $table = 'billing_cfdi_invoices';

    protected $fillable = [
        'ar_invoice_id', 'uuid', 'serie', 'folio', 'fecha_emision', 'fecha_timbrado',
        'emisor_rfc', 'emisor_nombre', 'emisor_regimen_fiscal',
        'receptor_rfc', 'receptor_nombre', 'receptor_domicilio_fiscal',
        'receptor_regimen_fiscal', 'receptor_uso_cfdi',
        'subtotal', 'descuento', 'total', 'moneda', 'tipo_cambio',
        'forma_pago', 'metodo_pago', 'condiciones_pago',
        'tipo_comprobante', 'tipo_relacion', 'cfdi_relacionados',
        'status', 'stamping_error', 'xml_original', 'xml_timbrado', 'pdf_url',
        'cancellation_status', 'cancellation_reason', 'cancellation_substitute_uuid',
        'cancelled_at', 'cancellation_response', 'metadata'
    ];

    protected $casts = [
        'fecha_emision' => 'datetime',
        'fecha_timbrado' => 'datetime',
        'cancelled_at' => 'datetime',
        'subtotal' => 'float',
        'descuento' => 'float',
        'total' => 'float',
        'tipo_cambio' => 'float',
        'cfdi_relacionados' => 'array',
        'metadata' => 'array',
    ];

    public function arInvoice(): BelongsTo;
    public function items(): HasMany; // CFDIItem
    public function taxes(): HasManyThrough; // Through items
}
```

**CFDIItem Model:**
```php
class CFDIItem extends Model
{
    protected $table = 'billing_cfdi_items';

    protected $fillable = [
        'cfdi_invoice_id', 'clave_prod_serv', 'no_identificacion',
        'cantidad', 'clave_unidad', 'unidad', 'descripcion',
        'valor_unitario', 'importe', 'descuento', 'objeto_imp'
    ];

    protected $casts = [
        'cantidad' => 'float',
        'valor_unitario' => 'float',
        'importe' => 'float',
        'descuento' => 'float',
    ];

    public function cfdiInvoice(): BelongsTo;
    public function taxes(): HasMany; // CFDITax
}
```

#### 1.3 API Endpoints (Setup)

```
GET    /api/v1/billing/company-settings      Get company CFDI settings
PATCH  /api/v1/billing/company-settings      Update settings
POST   /api/v1/billing/certificates/upload   Upload CSD certificates
GET    /api/v1/billing/certificates/validate Validate certificate status
```

#### 1.4 Testing

Create 3 test files

**Test Scenarios:**
- Setup company settings
- Upload CSD certificates
- Validate certificate expiration
- PAC configuration

---

### Stage 2: XML Generation Engine (Day 2-3, 10-12 hours)

#### 2.1 CFDI XML Generator Service

**CFDIXMLGenerator Service:**
```php
class CFDIXMLGenerator
{
    /**
     * Generate CFDI 4.0 XML from invoice data
     */
    public function generate(CFDIInvoice $invoice): string
    {
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><cfdi:Comprobante></cfdi:Comprobante>');

        // Add namespaces
        $xml->addAttribute('xmlns:xmlns:cfdi', 'http://www.sat.gob.mx/cfd/4');
        $xml->addAttribute('xmlns:xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');

        // Add attributes
        $xml->addAttribute('Version', '4.0');
        $xml->addAttribute('Serie', $invoice->serie);
        $xml->addAttribute('Folio', $invoice->folio);
        $xml->addAttribute('Fecha', $invoice->fecha_emision->format('Y-m-d\TH:i:s'));
        $xml->addAttribute('Sello', ''); // Will be added by PAC
        $xml->addAttribute('FormaPago', $invoice->forma_pago);
        $xml->addAttribute('NoCertificado', ''); // From company settings
        $xml->addAttribute('Certificado', ''); // From company settings
        $xml->addAttribute('SubTotal', number_format($invoice->subtotal, 6, '.', ''));
        $xml->addAttribute('Moneda', $invoice->moneda);
        $xml->addAttribute('Total', number_format($invoice->total, 6, '.', ''));
        $xml->addAttribute('TipoDeComprobante', $invoice->tipo_comprobante);
        $xml->addAttribute('MetodoPago', $invoice->metodo_pago);
        $xml->addAttribute('LugarExpedicion', $this->getCompanyPostalCode());

        // Add Emisor
        $this->addEmisor($xml, $invoice);

        // Add Receptor
        $this->addReceptor($xml, $invoice);

        // Add Conceptos (items)
        $this->addConceptos($xml, $invoice);

        // Add Impuestos (taxes summary)
        $this->addImpuestos($xml, $invoice);

        return $xml->asXML();
    }

    protected function addEmisor(\SimpleXMLElement $xml, CFDIInvoice $invoice)
    {
        $emisor = $xml->addChild('cfdi:Emisor');
        $emisor->addAttribute('Rfc', $invoice->emisor_rfc);
        $emisor->addAttribute('Nombre', $invoice->emisor_nombre);
        $emisor->addAttribute('RegimenFiscal', $invoice->emisor_regimen_fiscal);
    }

    protected function addReceptor(\SimpleXMLElement $xml, CFDIInvoice $invoice)
    {
        $receptor = $xml->addChild('cfdi:Receptor');
        $receptor->addAttribute('Rfc', $invoice->receptor_rfc);
        $receptor->addAttribute('Nombre', $invoice->receptor_nombre);
        $receptor->addAttribute('DomicilioFiscalReceptor', $invoice->receptor_domicilio_fiscal);
        $receptor->addAttribute('RegimenFiscalReceptor', $invoice->receptor_regimen_fiscal);
        $receptor->addAttribute('UsoCFDI', $invoice->receptor_uso_cfdi);
    }

    protected function addConceptos(\SimpleXMLElement $xml, CFDIInvoice $invoice)
    {
        $conceptos = $xml->addChild('cfdi:Conceptos');

        foreach ($invoice->items as $item) {
            $concepto = $conceptos->addChild('cfdi:Concepto');
            $concepto->addAttribute('ClaveProdServ', $item->clave_prod_serv);
            $concepto->addAttribute('NoIdentificacion', $item->no_identificacion);
            $concepto->addAttribute('Cantidad', number_format($item->cantidad, 6, '.', ''));
            $concepto->addAttribute('ClaveUnidad', $item->clave_unidad);
            $concepto->addAttribute('Unidad', $item->unidad);
            $concepto->addAttribute('Descripcion', $item->descripcion);
            $concepto->addAttribute('ValorUnitario', number_format($item->valor_unitario, 6, '.', ''));
            $concepto->addAttribute('Importe', number_format($item->importe, 6, '.', ''));
            $concepto->addAttribute('ObjetoImp', $item->objeto_imp);

            // Add taxes for this item
            if ($item->taxes->count() > 0) {
                $this->addConceptoImpuestos($concepto, $item);
            }
        }
    }

    protected function addConceptoImpuestos(\SimpleXMLElement $concepto, CFDIItem $item)
    {
        $impuestos = $concepto->addChild('cfdi:Impuestos');

        $traslados = $item->taxes->where('tipo', 'Traslado');
        if ($traslados->count() > 0) {
            $trasladosNode = $impuestos->addChild('cfdi:Traslados');
            foreach ($traslados as $tax) {
                $traslado = $trasladosNode->addChild('cfdi:Traslado');
                $traslado->addAttribute('Base', number_format($tax->base, 6, '.', ''));
                $traslado->addAttribute('Impuesto', $tax->impuesto);
                $traslado->addAttribute('TipoFactor', $tax->tipo_factor);
                $traslado->addAttribute('TasaOCuota', number_format($tax->tasa_o_cuota, 6, '.', ''));
                $traslado->addAttribute('Importe', number_format($tax->importe, 6, '.', ''));
            }
        }
    }

    protected function addImpuestos(\SimpleXMLElement $xml, CFDIInvoice $invoice)
    {
        // Calculate totals from all item taxes
        $totalTraslados = $invoice->taxes()->where('tipo', 'Traslado')->sum('importe');
        $totalRetenciones = $invoice->taxes()->where('tipo', 'Retención')->sum('importe');

        if ($totalTraslados > 0 || $totalRetenciones > 0) {
            $impuestos = $xml->addChild('cfdi:Impuestos');

            if ($totalRetenciones > 0) {
                $impuestos->addAttribute('TotalImpuestosRetenidos', number_format($totalRetenciones, 6, '.', ''));
            }
            if ($totalTraslados > 0) {
                $impuestos->addAttribute('TotalImpuestosTrasladados', number_format($totalTraslados, 6, '.', ''));
            }

            // Add detailed tax summary
            // ... (group by tax type and rate)
        }
    }
}
```

#### 2.2 SAT Catalog Integration

**SAT Catalogs (reference data):**
- `c_FormaPago` (payment methods: 01, 02, 03, etc.)
- `c_MetodoPago` (PUE, PPD)
- `c_UsoCFDI` (G01, G02, G03, etc.)
- `c_RegimenFiscal` (601, 603, 605, etc.)
- `c_ClaveProdServ` (product/service codes)
- `c_ClaveUnidad` (unit codes: KGM, E48, etc.)
- `c_Impuesto` (001=ISR, 002=IVA, 003=IEPS)

**Note:** These catalogs can be seeded from SAT XML files or stored as reference tables.

#### 2.3 API Endpoints

```
POST   /api/v1/billing/cfdi/generate-from-ar-invoice  Generate CFDI from AR Invoice
GET    /api/v1/billing/cfdi/preview/{id}               Preview XML before stamping
```

#### 2.4 Testing

Create 5 test files

**Test Scenarios:**
- Generate XML from invoice data
- Validate XML structure
- Validate against XSD schema
- Tax calculation accuracy
- Multi-currency invoices

---

### Stage 3: PAC Integration & Stamping (Day 4, 6-8 hours)

#### 3.1 PAC Service

**PACService (using Finkok as example):**
```php
class PACService
{
    protected $client; // SOAP client

    public function __construct()
    {
        $settings = CompanySettings::first();
        $wsdl = $settings->pac_test_mode
            ? 'https://demo-facturacion.finkok.com/servicios/soap/stamp.wsdl'
            : 'https://facturacion.finkok.com/servicios/soap/stamp.wsdl';

        $this->client = new \SoapClient($wsdl);
    }

    /**
     * Send XML to PAC for stamping (timbrado)
     */
    public function stamp(string $xml): array
    {
        try {
            $settings = CompanySettings::first();

            $response = $this->client->stamp([
                'username' => $settings->pac_username,
                'password' => $settings->pac_password,
                'xml' => base64_encode($xml),
            ]);

            if ($response->Incidencias) {
                throw new PACException("PAC Error: " . $response->Incidencias->Incidencia);
            }

            return [
                'success' => true,
                'uuid' => $response->UUID,
                'fecha_timbrado' => $response->Fecha,
                'xml_timbrado' => base64_decode($response->xml),
                'sello_sat' => $response->SatSeal,
                'no_certificado_sat' => $response->SatCertNumber,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Cancel CFDI via PAC
     */
    public function cancel(string $uuid, string $reason, ?string $substituteUUID = null): array
    {
        try {
            $settings = CompanySettings::first();

            $response = $this->client->cancel([
                'username' => $settings->pac_username,
                'password' => $settings->pac_password,
                'rfc' => $settings->rfc,
                'uuid' => $uuid,
                'motivo' => $reason,
                'folioSustitucion' => $substituteUUID,
            ]);

            return [
                'success' => true,
                'acuse' => $response->Acuse,
                'estado' => $response->EstatusCancelacion, // Cancelado, Rechazado, En proceso
            ];

        } catch (\Exception $e) {
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
        $response = $this->client->get_sat_status([
            'uuid' => $uuid,
        ]);

        return $response->estado; // Vigente, Cancelado, etc.
    }
}
```

#### 3.2 Stamping Workflow Service

**CFDIStampingService:**
```php
class CFDIStampingService
{
    protected $xmlGenerator;
    protected $pacService;

    public function __construct(CFDIXMLGenerator $xmlGenerator, PACService $pacService)
    {
        $this->xmlGenerator = $xmlGenerator;
        $this->pacService = $pacService;
    }

    /**
     * Generate XML and send to PAC for stamping
     */
    public function stampInvoice(CFDIInvoice $invoice): bool
    {
        try {
            // 1. Generate XML
            $xml = $this->xmlGenerator->generate($invoice);
            $invoice->update(['xml_original' => $xml, 'status' => 'stamping']);

            // 2. Send to PAC
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
            $this->generatePDF($invoice);

            // 5. Send email to customer
            // Mail::to($invoice->receptor_email)->send(new CFDIInvoiceMail($invoice));

            return true;

        } catch (\Exception $e) {
            $invoice->update([
                'status' => 'error',
                'stamping_error' => $e->getMessage(),
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
            throw new \Exception("Only stamped invoices can be cancelled");
        }

        $result = $this->pacService->cancel($invoice->uuid, $reason, $substituteUUID);

        if ($result['success']) {
            $invoice->update([
                'cancellation_status' => $result['estado'], // pending, accepted, rejected
                'cancellation_reason' => $reason,
                'cancellation_substitute_uuid' => $substituteUUID,
                'cancellation_response' => json_encode($result),
            ]);

            if ($result['estado'] === 'Cancelado') {
                $invoice->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                ]);
            }

            return true;
        }

        return false;
    }

    protected function generatePDF(CFDIInvoice $invoice)
    {
        // Use a PDF library like DomPDF or TCPDF
        // Generate professional invoice PDF with QR code
        // QR should contain: RFC emisor, RFC receptor, Total, UUID
        // Save PDF and update invoice->pdf_url
    }
}
```

#### 3.3 API Endpoints

```
POST   /api/v1/billing/cfdi/{id}/stamp       Stamp CFDI (send to PAC)
POST   /api/v1/billing/cfdi/{id}/cancel      Cancel CFDI
GET    /api/v1/billing/cfdi/{id}/status      Query SAT status
GET    /api/v1/billing/cfdi/{id}/xml         Download XML
GET    /api/v1/billing/cfdi/{id}/pdf         Download PDF
```

#### 3.4 Testing

Create 4 test files

**Test Scenarios:**
- Stamp invoice successfully
- Handle stamping errors
- Cancel CFDI workflow
- Query cancellation status
- Generate PDF with QR code

---

### Stage 4: Integration with Finance Module (Day 5, 4-5 hours)

#### 4.1 AR Invoice → CFDI Automation

**Workflow:**
1. AR Invoice created and approved
2. Automatically generate CFDI draft
3. Admin reviews and stamps CFDI
4. CFDI sent to customer via email
5. Link back to AR Invoice

**ARInvoice Model Enhancement:**
```php
class ARInvoice extends Model
{
    public function cfdi(): HasOne
    {
        return $this->hasOne(\Modules\Billing\Models\CFDIInvoice::class);
    }

    // Auto-generate CFDI on invoice approval
    protected static function booted()
    {
        static::updated(function ($invoice) {
            if ($invoice->status === 'approved' && !$invoice->cfdi) {
                app(CFDIAutomationService::class)->generateFromARInvoice($invoice);
            }
        });
    }
}
```

**CFDIAutomationService:**
```php
class CFDIAutomationService
{
    public function generateFromARInvoice(ARInvoice $arInvoice): CFDIInvoice
    {
        $settings = CompanySettings::first();
        $customer = $arInvoice->contact;

        // Create CFDI from AR Invoice
        $cfdi = CFDIInvoice::create([
            'ar_invoice_id' => $arInvoice->id,
            'serie' => $settings->series_prefix,
            'folio' => $settings->next_folio_number,
            'fecha_emision' => now(),

            // Emisor
            'emisor_rfc' => $settings->rfc,
            'emisor_nombre' => $settings->legal_name,
            'emisor_regimen_fiscal' => $settings->fiscal_regime,

            // Receptor
            'receptor_rfc' => $customer->rfc ?? 'XAXX010101000', // Generic RFC
            'receptor_nombre' => $customer->name,
            'receptor_domicilio_fiscal' => $customer->postal_code,
            'receptor_regimen_fiscal' => '616', // Default: Sin obligaciones fiscales
            'receptor_uso_cfdi' => 'G03', // Default: Gastos en general

            // Amounts
            'subtotal' => $arInvoice->subtotal,
            'descuento' => 0,
            'total' => $arInvoice->total_amount,
            'moneda' => $arInvoice->currency,

            // Payment
            'forma_pago' => '99', // Por definir
            'metodo_pago' => 'PUE', // Pago en una sola exhibición
            'tipo_comprobante' => 'I', // Ingreso

            'status' => 'draft',
        ]);

        // Copy items
        foreach ($arInvoice->items as $item) {
            CFDIItem::create([
                'cfdi_invoice_id' => $cfdi->id,
                'clave_prod_serv' => '01010101', // Default: Generic
                'no_identificacion' => $item->product->sku ?? '',
                'cantidad' => $item->quantity,
                'clave_unidad' => 'E48', // Unidad de servicio
                'unidad' => 'Pieza',
                'descripcion' => $item->description,
                'valor_unitario' => $item->unit_price,
                'importe' => $item->total,
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
                'base' => $item->subtotal,
                'importe' => $item->tax_amount,
            ]);
        }

        // Increment folio
        $settings->increment('next_folio_number');

        return $cfdi;
    }
}
```

#### 4.2 Customer Portal

**Public Endpoints:**
```
GET /api/v1/public/cfdi/{uuid}           Get CFDI by UUID (customer access)
GET /api/v1/public/cfdi/{uuid}/xml       Download XML (authenticated)
GET /api/v1/public/cfdi/{uuid}/pdf       Download PDF
```

#### 4.3 Testing

Create 3 test files

**Test Scenarios:**
- Auto-generate CFDI from AR Invoice
- Customer can download XML/PDF
- Verify UUID uniqueness
- Integration workflow end-to-end

---

### Stage 5: SAT Validation & Compliance (Day 6-7, 6-8 hours)

#### 5.1 XSD Schema Validation

**Pre-stamping validation:**
- Validate XML against official SAT XSD 4.0 schema
- Check required fields
- Verify SAT catalog codes
- Amount calculations

**ValidationService:**
```php
class CFDIValidationService
{
    public function validateBeforeStamping(CFDIInvoice $invoice): array
    {
        $errors = [];

        // 1. Required fields
        if (!$invoice->receptor_rfc || !$invoice->receptor_nombre) {
            $errors[] = "RFC y nombre del receptor son obligatorios";
        }

        // 2. RFC format validation
        if (!$this->isValidRFC($invoice->receptor_rfc)) {
            $errors[] = "RFC del receptor inválido";
        }

        // 3. Amount calculations
        $calculatedTotal = $invoice->subtotal - $invoice->descuento;
        $totalTaxes = $invoice->taxes->sum('importe');
        $calculatedTotal += $totalTaxes;

        if (abs($calculatedTotal - $invoice->total) > 0.01) {
            $errors[] = "El total calculado no coincide";
        }

        // 4. 72-hour rule (must stamp within 72 hours of emission date)
        if ($invoice->fecha_emision->diffInHours(now()) > 72) {
            $errors[] = "Han pasado más de 72 horas desde la fecha de emisión";
        }

        // 5. Validate XML against XSD
        if ($invoice->xml_original) {
            $xsdErrors = $this->validateAgainstXSD($invoice->xml_original);
            $errors = array_merge($errors, $xsdErrors);
        }

        return $errors;
    }

    protected function validateAgainstXSD(string $xml): array
    {
        $dom = new \DOMDocument();
        $dom->loadXML($xml);

        libxml_use_internal_errors(true);
        $xsdPath = storage_path('sat/cfdv40.xsd');

        if (!$dom->schemaValidate($xsdPath)) {
            $errors = [];
            foreach (libxml_get_errors() as $error) {
                $errors[] = $error->message;
            }
            libxml_clear_errors();
            return $errors;
        }

        return [];
    }

    protected function isValidRFC(string $rfc): bool
    {
        // Validate RFC format (13 chars for legal entities, 12 for individuals)
        return preg_match('/^[A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3}$/', $rfc);
    }
}
```

#### 5.2 Audit Trail & Retention

**Compliance requirements:**
- Store all CFDIs for 5+ years
- Maintain immutable audit log
- Track all cancellations
- SAT query responses logged

**CFDIAuditLog Model:**
```php
class CFDIAuditLog extends Model
{
    protected $table = 'billing_cfdi_audit_logs';

    protected $fillable = [
        'cfdi_invoice_id', 'action', 'user_id',
        'details', 'ip_address', 'user_agent'
    ];

    // Actions: created, stamped, cancelled, downloaded, emailed
}
```

#### 5.3 Reports & Dashboards

**Compliance Reports:**
- Monthly stamping report
- Cancellation report
- Error log report
- Certificate expiration alert

**Endpoints:**
```
GET /api/v1/billing/reports/monthly-summary      Monthly stamping stats
GET /api/v1/billing/reports/errors               Stamping errors
GET /api/v1/billing/reports/cancellations        Cancellation log
```

#### 5.4 Testing

Create 4 test files

**Test Scenarios:**
- XSD validation
- RFC format validation
- 72-hour rule enforcement
- Audit log creation
- Compliance reports

---

## Database Schema Summary

**New Tables:** 7
- `billing_company_settings` (22 columns, 1 index)
- `billing_cfdi_invoices` (39 columns, 5 indexes)
- `billing_cfdi_items` (13 columns, 1 index)
- `billing_cfdi_taxes` (9 columns, 1 index)
- `billing_cfdi_audit_logs` (9 columns, 2 indexes)

**Integration:** Finance module (ar_invoices), Accounting module (journal_entries)

---

## API Endpoints Summary

| Category | Endpoints |
|----------|-----------|
| Company Setup | 4 |
| CFDI Management | 8 |
| Stamping/Cancellation | 4 |
| Customer Portal | 3 |
| Reports | 3 |
| **TOTAL** | **22** |

---

## Testing Summary

| Category | Test Files | Est. Tests |
|----------|-----------|------------|
| Setup | 3 | 15+ |
| XML Generation | 5 | 25+ |
| PAC Integration | 4 | 20+ |
| Finance Integration | 3 | 15+ |
| Validation & Compliance | 4 | 20+ |
| **TOTAL** | **19** | **95+** |

---

## Success Criteria

**Functional:**
- [ ] Company CSD certificates uploaded and validated
- [ ] PAC integration working (test mode)
- [ ] CFDI XML generation compliant with 4.0 spec
- [ ] Stamping workflow functional
- [ ] Cancellation workflow functional
- [ ] Customer can download XML/PDF
- [ ] Auto-generation from AR Invoices
- [ ] Email notifications working

**Compliance:**
- [ ] XML validates against SAT XSD schema
- [ ] 72-hour rule enforced
- [ ] RFC validation working
- [ ] Audit trail complete
- [ ] 5-year retention configured

**Technical:**
- [ ] 19+ test files, 95+ tests passing
- [ ] PAC test mode passing
- [ ] Error handling robust
- [ ] Certificate expiration monitoring

---

## PAC Provider Options

**Recommended PACs for Mexico:**
1. **Finkok** - Popular, reliable, good API
2. **SW Sapien** - Modern API, competitive pricing
3. **Diconsa** - Government-backed
4. **PAC Comercio Digital** - Enterprise focus

**Integration Note:** Code structure allows easy PAC switching via adapter pattern.

---

## Effort Breakdown

| Stage | Duration | Complexity |
|-------|----------|------------|
| Data Model & Setup | 6-7 hours | Medium |
| XML Generation | 10-12 hours | High |
| PAC Integration | 6-8 hours | High |
| Finance Integration | 4-5 hours | Medium |
| Validation & Compliance | 6-8 hours | High |
| Testing & Debugging | 6-8 hours | High |
| **TOTAL** | **38-48 hours** | **5-7 days** |

---

**Document Status:** Planning Complete
**Last Updated:** 2025-10-29
**Next Action:** Review and approve - CRITICAL for Mexico operations
