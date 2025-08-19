# Roadmap de Implementación: Reglas de Negocio Financiero-Contables

**Documento de Análisis y Planificación Técnica**  
**Fecha:** 19 de Agosto, 2025  
**Versión:** 1.0  
**Proyecto:** API Base - Módulos Accounting & Finance  

---

## 🎯 **RESUMEN EJECUTIVO**

### Situación Actual
El proyecto cuenta con una **base arquitectónica sólida** para los módulos Accounting y Finance, con estructura JSON:API completa y integración establecida con el sistema de Contactos. Sin embargo, las **reglas de negocio críticas** para operación empresarial están pendientes de implementación.

### Objetivos del Roadmap
- Implementar lógica de estados y transiciones documentales
- Establecer validaciones financiero-contables empresariales
- Crear servicios de posteo automático a contabilidad general
- Habilitar funcionalidades de conciliación bancaria y aplicación de pagos

### Impacto Esperado
- **Funcionalidad empresarial completa** para AP/AR/GL
- **Trazabilidad y auditoría** de todas las transacciones financieras
- **Automatización** de procesos contables manuales
- **Cumplimiento** de mejores prácticas contables mexicanas

---

## 📊 **ANÁLISIS DEL ESTADO ACTUAL**

### ✅ Fortalezas Identificadas

#### **1. Arquitectura Base Sólida**
- ✅ **Módulos generados**: Accounting (6 entidades) + Finance (11 entidades)
- ✅ **JSON:API 5.x**: Estructura completa con schemas, controllers, tests
- ✅ **Integración Contactos**: Sistema Party con `is_customer`/`is_supplier` implementado
- ✅ **Multi-moneda**: Campos `currency` y `exchange_rate` preparados
- ✅ **Permisos**: Sistema granular Spatie implementado (543+ tests)

#### **2. Entidades Principales Establecidas**
```
ACCOUNTING (GL):
├── accounts (jerárquico con is_postable)
├── fiscal_periods (control temporal) 
├── journals (diarios contables)
├── journal_entries + journal_lines (asientos)
└── exchange_rates (tipos de cambio)

FINANCE (AP/AR/Treasury):
├── ap_invoices + ap_invoice_lines (CxP)
├── ar_invoices + ar_invoice_lines (CxC) 
├── ap_payments + ar_receipts (pagos/cobros)
├── bank_accounts + bank_statements (tesorería)
└── Aplicaciones: ap_invoice_payments, ar_invoice_receipts
```

#### **3. Integración con Contactos Funcional**
- **Contact Model**: Roles `is_customer`/`is_supplier` con validaciones
- **Business Logic**: Credit limits, RFC validation, status management  
- **Referencias**: Finance usa `contact_id` (no entidades customer/supplier separadas)

### ❌ Gaps Críticos Identificados

#### **1. Estados y Transiciones (CRÍTICO)**
```
ACTUAL: status → string básico
REQUERIDO: draft → approved → posted → paid/reconciled → void
```
- Sin lógica de transición de estados
- Sin validaciones de edición por estado
- Sin endpoints de acciones (approve/post/reverse)

#### **2. Validaciones Financiero-Contables (CRÍTICO)** 
```
FALTANTE:
├── Balance cero en asientos contables
├── Periodo abierto para posteos
├── Cuentas postables (is_postable=true)
├── Rol de contacto (supplier para AP, customer para AR)
├── Límites de crédito en AR
└── Unicidad (contact_id, invoice_number)
```

#### **3. Posteo Automático a GL (CRÍTICO)**
```
SIN IMPLEMENTAR:
├── AP Invoice → Gasto (Debit) + AP Control (Credit)
├── AR Invoice → AR Control (Debit) + Ingresos (Credit)  
├── Payments → Bank (Credit) + AP Control (Debit)
├── Receipts → Bank (Debit) + AR Control (Credit)
└── FX → Diferencias de cambio automáticas
```

#### **4. Servicios de Negocio (CRÍTICO)**
- **PostToGLService**: Generación automática de asientos
- **SequenceService**: Numeración automática
- **ReconciliationService**: Conciliación bancaria
- **FXService**: Manejo diferencias de cambio

---

## 🗺️ **ROADMAP DE IMPLEMENTACIÓN**

### **CRONOGRAMA GENERAL**
```
📅 DURACIÓN TOTAL: 10 semanas
👥 RECURSOS: 1 Developer + 0.5 QA
💰 ESFUERZO: ~400 horas desarrollo
```

---

## 🚀 **FASE 1: FUNDAMENTOS CONTABLES**
**⏱️ Duración:** 3 semanas | **🎯 Prioridad:** CRÍTICA

### **Objetivos**
Establecer la base contable sólida para que Finance pueda postear automáticamente a General Ledger.

### **Entregables**

#### **1.1 Estados y Validaciones GL** *(Semana 1)*
```php
// Estados JournalEntry
enum JournalEntryStatus: string {
    case DRAFT = 'draft';
    case APPROVED = 'approved'; 
    case POSTED = 'posted';
    case REVERSED = 'reversed';
    case VOID = 'void';
}

// Validaciones críticas
- Balance cero: Σ(debit) = Σ(credit)
- Periodo abierto: entry.date ∈ fiscal_period[open]
- Cuentas postables: account.is_postable = true
- No edición post-posteo: inmutable después de 'posted'
```

#### **1.2 Servicios Core** *(Semana 2)*
```php
// JournalEntryService
class JournalEntryService {
    public function approve(JournalEntry $entry): bool
    public function post(JournalEntry $entry): bool  
    public function reverse(JournalEntry $entry): JournalEntry
    public function void(JournalEntry $entry): bool
}

// SequenceService  
class SequenceService {
    public function getNext(Journal $journal): string
    public function reserve(Journal $journal): string
}

// PeriodService
class PeriodService {
    public function isOpen(Carbon $date): bool
    public function canPost(Carbon $date): bool
}
```

#### **1.3 Endpoints de Acciones** *(Semana 3)*
```bash
POST /api/v1/journal-entries/{id}/approve
POST /api/v1/journal-entries/{id}/post
POST /api/v1/journal-entries/{id}/reverse  
POST /api/v1/fiscal-periods/{id}/close
POST /api/v1/fiscal-periods/{id}/reopen
```

### **Testing**
- [ ] 15 test cases para transiciones de estado
- [ ] 10 test cases para validaciones GL
- [ ] 8 test cases para servicios core
- [ ] 12 test cases para endpoints de acciones

### **Criterios de Aceptación**
- ✅ Journal entries siguen flujo completo draft→posted
- ✅ Validaciones bloquean asientos desbalanceados
- ✅ Numeración automática funcional
- ✅ Periodos controlan posteos por fecha

---

## 💰 **FASE 2: FLUJOS DOCUMENTALES FINANCE**
**⏱️ Duración:** 4 semanas | **🎯 Prioridad:** CRÍTICA

### **Objetivos**
Implementar lógica empresarial completa para Cuentas por Pagar, Cuentas por Cobrar y automatizar el posteo a GL.

### **Entregables**

#### **2.1 Estados Documentales AP/AR** *(Semana 4)*
```php
// Estados documentos AP/AR
enum InvoiceStatus: string {
    case DRAFT = 'draft';
    case APPROVED = 'approved';
    case POSTED = 'posted'; 
    case PARTIALLY_PAID = 'partially_paid';
    case PAID = 'paid';
    case VOID = 'void';
}

// Servicios documentales
class APInvoiceService {
    public function approve(APInvoice $invoice): bool
    public function post(APInvoice $invoice): bool
    public function void(APInvoice $invoice): bool
}
```

#### **2.2 Sistema de Aplicaciones** *(Semana 5)*
```php
// Aplicación parcial de pagos
class PaymentApplicationService {
    public function applyToInvoice(
        APPayment $payment, 
        APInvoice $invoice, 
        float $amount,
        ?float $exchangeRate = null
    ): APInvoicePayment
    
    public function calculateFXDifference(
        float $originalAmount,
        float $appliedAmount, 
        float $originalRate,
        float $applicationRate
    ): float
}
```

#### **2.3 Posteo Automático a GL** *(Semana 6-7)*
```php
// PostToGLService - Motor de integración contable
class PostToGLService {
    public function postAPInvoice(APInvoice $invoice): JournalEntry
    public function postARInvoice(ARInvoice $invoice): JournalEntry
    public function postAPPayment(APPayment $payment): JournalEntry
    public function postARReceipt(ARReceipt $receipt): JournalEntry
    public function postFXDifference(float $amount, string $type): JournalEntry
}

// Mapeos contables automáticos
AP Invoice: Expense (Debit) + AP Control (Credit)
AR Invoice: AR Control (Debit) + Revenue (Credit)
AP Payment: AP Control (Debit) + Bank (Credit)  
AR Receipt: Bank (Debit) + AR Control (Credit)
```

### **Testing**
- [ ] 25 test cases para estados documentales
- [ ] 20 test cases para aplicaciones parciales
- [ ] 30 test cases para posteo automático GL
- [ ] 15 test cases para diferencias de cambio

### **Criterios de Aceptación**
- ✅ Documentos AP/AR siguen flujo empresarial completo
- ✅ Aplicación parcial de pagos funcional
- ✅ Posteo automático a GL sin intervención manual
- ✅ Diferencias de cambio calculadas correctamente

---

## 🏦 **FASE 3: TESORERÍA Y CONCILIACIÓN**
**⏱️ Duración:** 2 semanas | **🎯 Prioridad:** ALTA

### **Objetivos**
Completar funcionalidad bancaria con conciliación automática y importación de estados de cuenta.

### **Entregables**

#### **3.1 Conciliación Bancaria** *(Semana 8)*
```php
// Estados líneas bancarias
enum BankStatementLineStatus: string {
    case UNRECONCILED = 'unreconciled';
    case MATCHED = 'matched';
    case RECONCILED = 'reconciled';
}

// ReconciliationService
class ReconciliationService {
    public function findMatches(
        BankStatementLine $line,
        float $tolerance = 0.01
    ): Collection
    
    public function autoMatch(BankStatement $statement): array
    public function confirmMatch(BankStatementLine $line, $document): bool
}
```

#### **3.2 Importadores y Endpoints** *(Semana 9)*
```bash
# Endpoints tesorería
POST /api/v1/bank-statements/import     # CSV/OFX/MT940
POST /api/v1/bank-statements/{id}/auto-match
POST /api/v1/bank-statement-lines/{id}/match
POST /api/v1/bank-statement-lines/{id}/reconcile
```

### **Testing**
- [ ] 12 test cases para matching automático
- [ ] 8 test cases para importadores
- [ ] 10 test cases para endpoints tesorería

### **Criterios de Aceptación**  
- ✅ Matching automático por monto/fecha/referencia
- ✅ Importación CSV básica funcional
- ✅ Estados de conciliación controlados

---

## 🔒 **FASE 4: VALIDACIONES Y CONTROLES**
**⏱️ Duración:** 1 semana | **🎯 Prioridad:** ALTA

### **Objetivos**
Asegurar integridad de datos y cumplimiento de reglas de negocio empresariales.

### **Entregables**

#### **4.1 Validaciones de Negocio** *(Semana 10)*
```php
// Request Validators
class APInvoiceRequest extends FormRequest {
    public function rules(): array {
        return [
            'contact_id' => ['required', 'exists:contacts,id', new IsSupplier],
            'invoice_number' => ['required', new UniqueInvoiceNumber],
            'total' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['required', new RequiresExchangeRate],
        ];
    }
}

class ARInvoiceRequest extends FormRequest {
    public function rules(): array {
        return [
            'contact_id' => ['required', 'exists:contacts,id', new IsCustomer],
            'total' => ['required', new CreditLimitCheck],
        ];
    }
}
```

#### **4.2 Auditoría y Trazabilidad**
```php
// Activity Logging en todas las transiciones
Activity::log('journal_entry_posted')
    ->performedOn($journalEntry)
    ->causedBy(auth()->user())
    ->withProperties([
        'previous_status' => 'approved',
        'new_status' => 'posted',
        'posted_at' => now(),
        'sequence_number' => $entry->number
    ]);
```

### **Testing**
- [ ] 15 test cases para validaciones de negocio
- [ ] 8 test cases para auditoría completa

### **Criterios de Aceptación**
- ✅ Todas las reglas de negocio validadas
- ✅ Trazabilidad completa de transacciones
- ✅ Logs de auditoría para compliance

---

## 🔮 **MEJORAS FUTURAS (Post-MVP)**

### **Funcionalidades Contempladas para V2**
```
📈 ANALYTICS & REPORTING:
├── Dashboard financiero ejecutivo
├── Reportes de antigüedad de saldos  
├── Estados financieros automáticos
└── Indicadores KPI financieros

🌐 INTEGRACIONES AVANZADAS:
├── SAT (México) - CFDI automático
├── Bancos (APIs Bancarias México)
├── ERP externos (SAP, Oracle) 
└── Plataformas de pago (Stripe, PayPal)

🔄 AUTOMATIZACIÓN AVANZADA:
├── Workflow de aprobaciones
├── OCR para facturas escaneadas
├── Matching inteligente ML
└── Proyecciones de flujo de efectivo

📊 CONTABILIDAD AVANZADA:
├── Centros de costo  
├── Proyectos y jobs
├── Multi-compañía
└── Consolidación financiera
```

### **Consideraciones Técnicas Futuras**
- **Performance**: Implementar caching Redis para consultas frecuentes
- **Escalabilidad**: Queue system para posteos masivos  
- **Seguridad**: Firma digital para documentos críticos
- **Mobile**: APIs optimizadas para apps móviles

---

## ⚠️ **RIESGOS Y MITIGACIONES**

### **Riesgos Técnicos**

#### **🔴 ALTO - Complejidad de Integración GL**
- **Riesgo**: Posteo automático genere asientos incorrectos
- **Mitigación**: Testing exhaustivo + rollback automático
- **Plan B**: Modo manual con revisión previa

#### **🟡 MEDIO - Performance en Volumen Alto** 
- **Riesgo**: Lentitud con miles de documentos/día
- **Mitigación**: Indexación DB + caching estratégico
- **Plan B**: Procesamiento asíncrono con queues

#### **🟡 MEDIO - Complejidad Multi-Moneda**
- **Riesgo**: Errores en diferencias de cambio
- **Mitigación**: Testing con datos reales + validación contable
- **Plan B**: Restricción a moneda única inicialmente

### **Riesgos de Negocio**

#### **🔴 ALTO - Migración de Datos Existentes**
- **Riesgo**: Documentos actuales sin estado definido
- **Mitigación**: Script de migración + estado inicial 'posted'
- **Plan B**: Cleanup manual pre-producción

#### **🟡 MEDIO - Curva de Aprendizaje Usuario**
- **Riesgo**: Resistencia a nuevos flujos de trabajo
- **Mitigación**: Documentación + capacitación + soporte
- **Plan B**: Modo compatibility temporal

### **Riesgos de Proyecto**

#### **🟡 MEDIO - Dependencias Entre Fases**
- **Riesgo**: Retraso Fase 1 impacta todo el cronograma
- **Mitigación**: Buffer de 1 semana + desarrollo paralelo parcial
- **Plan B**: Reducir scope Fase 3/4 si necesario

---

## 📋 **RECURSOS Y ESTIMACIONES**

### **Team Structure**
```
👨‍💻 Senior Developer (Full-time): 10 semanas
🧪 QA Engineer (Part-time 50%): 5 semanas  
📋 Project Manager (Part-time 25%): 2.5 semanas
💼 Business Analyst (Consultoría): 1 semana
```

### **Breakdown por Fase**
```
FASE 1 (3 sem): 120 horas dev + 40 horas QA = 160 horas
FASE 2 (4 sem): 160 horas dev + 60 horas QA = 220 horas  
FASE 3 (2 sem): 80 horas dev + 30 horas QA = 110 horas
FASE 4 (1 sem): 40 horas dev + 20 horas QA = 60 horas
──────────────────────────────────────────────────────
TOTAL: 400 horas dev + 150 horas QA = 550 horas
```

### **Budget Estimate** *(Referencial)*
```
💰 Desarrollo: 400h × $50/h = $20,000 USD
🧪 Testing: 150h × $35/h = $5,250 USD  
📊 PM/BA: 50h × $45/h = $2,250 USD
──────────────────────────────────────
💵 TOTAL ESTIMADO: $27,500 USD
```

---

## ✅ **PLAN DE ACCIÓN INMEDIATO**

### **Próximos 5 días**
1. **Día 1**: Aprobación roadmap + setup branch `feature/business-rules`
2. **Día 2**: Design review estados y transiciones con stakeholders  
3. **Día 3**: Configuración testing environment + data de prueba
4. **Día 4**: Inicio desarrollo Fase 1.1 (Estados GL)
5. **Día 5**: Review técnico + ajustes iniciales

### **Semana 1 Goals**
- [ ] Estados JournalEntry implementados y testeados
- [ ] Validaciones balance cero funcionando  
- [ ] Endpoints approve/post/reverse básicos
- [ ] 15 test cases pasando correctamente

### **Entregables para Review**
- **Semanal**: Demo funcional + test report
- **Mensual**: Arquitectura review + performance metrics
- **Final**: Documentación técnica + user guide

---

## 📞 **CONTACTO Y SEGUIMIENTO**

### **Stakeholders**
- **Product Owner**: [Definir]
- **Tech Lead**: [Definir]  
- **Finance Team**: [Definir]
- **QA Lead**: [Definir]

### **Comunicación**
- **Daily Standups**: 9:00 AM (15 min)
- **Weekly Reviews**: Viernes 4:00 PM (60 min)  
- **Milestone Demos**: Final de cada fase (90 min)

### **Success Metrics**
- **Funcional**: 100% test cases passing
- **Performance**: <500ms response tiempo promedio  
- **Business**: Reducción 80% tiempo procesos manuales
- **Quality**: 0 bugs críticos en producción

---

**🎯 OBJETIVO: Transformar la base técnica actual en un sistema financiero-contable empresarial completo, robusto y escalable que cumpla con los estándares más exigentes de la industria.**

---
*Documento generado por Claude Code | Última actualización: 19 Agosto 2025*