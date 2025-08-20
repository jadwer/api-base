# Finance & Accounting - Fase 2 Roadmap

**Documento de Planificación Avanzada**  
**Fecha:** 20 de Agosto, 2025  
**Status:** Planificación futura - NO implementar aún  
**Prerrequisito:** Fase 1 completa y estable  

---

## 🚀 **FILOSOFÍA FASE 2**

**Objetivo**: Expandir funcionalidad básica F1 hacia características avanzadas empresariales sin romper simplicidad existente.

**Enfoque**: Evolutivo, no revolucionario. Mantener compatibilidad con F1.

---

## 📊 **ENTIDADES COMPLEJAS - IMPLEMENTADAS PERO NO USAR**

> ⚠️ **IMPORTANTE:** Estas entidades YA ESTÁN implementadas en el backend actual, pero son **FASE 2**. Frontend NO debe usarlas hasta que se complete Fase 1.

### **🔄 Multi-Moneda (Exchange Rates)**

#### **Ya Implementado (NO USAR F1):**
```php
// ✅ Tabla: exchange_rates  
ExchangeRate: {
    base_currency: "MXN",
    quote_currency: "USD", 
    rate: 17.50,
    rate_date: "2025-08-20"
}

// ✅ Endpoint: /api/v1/exchange-rates
```

#### **Fase 2 - Funcionalidad Planeada:**
- Facturas multi-moneda (USD, EUR, MXN)
- Diferencias de cambio automáticas en GL
- Revaluación de saldos por tipo de cambio
- Reportes en moneda base vs moneda original

---

### **📝 Líneas Detalladas de Facturas**

#### **Ya Implementado (NO USAR F1):**
```php
// ✅ Tablas: ap_invoice_lines, ar_invoice_lines
APInvoiceLine: {
    ap_invoice_id,
    account_id,      // Cuenta de gasto específica
    description,
    quantity,
    unit_price,
    discount,
    subtotal,
    metadata
}

// ✅ Endpoints: /api/v1/a-p-invoice-lines
```

#### **Fase 2 - Funcionalidad Planeada:**
- Facturas multi-línea con diferentes cuentas contables
- Cálculos automáticos quantity × unit_price
- Descuentos por línea vs descuentos globales
- Análisis de gastos por cuenta/categoría detallada

---

### **🔗 Aplicaciones Complejas N:M**

#### **Ya Implementado (NO USAR F1):**
```php
// ✅ Tablas: ap_invoice_payments, ar_invoice_receipts
APInvoicePayment: {
    ap_invoice_id,
    ap_payment_id,
    applied_amount,
    applied_date
}

// ✅ Endpoints: /api/v1/a-p-invoice-payments
```

#### **Fase 2 - Funcionalidad Planeada:**
- Un pago aplicado a múltiples facturas
- Una factura pagada con múltiples pagos parciales
- Aplicaciones/desaplicaciones de pagos
- Anticipos y aplicación posterior

---

### **🏦 Conciliación Bancaria**

#### **Ya Implementado (NO USAR F1):**
```php
// ✅ Tablas: bank_statements, bank_statement_lines
BankStatement: {
    bank_account_id,
    statement_date,
    beginning_balance,
    ending_balance,
    status: "pending|reconciled"
}

BankStatementLine: {
    bank_statement_id,
    transaction_date,
    description,
    amount,
    journal_entry_id,     // Link a GL cuando se reconcilia
    status: "unreconciled|reconciled"
}

// ✅ Endpoints: /api/v1/bank-statements, /api/v1/bank-statement-lines
```

#### **Fase 2 - Funcionalidad Planeada:**
- Importación automática de estados de cuenta
- Matching automático vs movimientos contables
- Conciliación manual de diferencias
- Reportes de partidas en tránsito

---

### **📋 Diarios y Secuencias**

#### **Ya Implementado (NO USAR F1):**
```php
// ✅ Tabla: journals
Journal: {
    code: "AP",
    name: "Cuentas por Pagar",
    journal_type: "general|cash|purchase|sale",
    auto_numbering: true,
    sequence_prefix: "AP-",
    sequence_next: 1001
}

// ✅ Endpoint: /api/v1/journals
```

#### **Fase 2 - Funcionalidad Planeada:**
- Numeración automática por tipo de documento
- Secuencias por período fiscal
- Diarios especializados (Ventas, Compras, Bancos)
- Control de folios fiscales

---

### **📅 Períodos Fiscales Avanzados**

#### **Ya Implementado (NO USAR F1):**
```php
// ✅ Tabla: fiscal_periods
FiscalPeriod: {
    name: "2025",
    start_date: "2025-01-01",
    end_date: "2025-12-31", 
    status: "open|closed|locked"
}

// ✅ Endpoint: /api/v1/fiscal-periods
```

#### **Fase 2 - Funcionalidad Planeada:**
- Cierre de períodos contables
- Validación fechas vs período activo
- Estados financieros por período
- Comparativos multi-período

---

## 🔮 **FUNCIONALIDADES AVANZADAS FASE 2**

### **📊 Reportes Financieros Avanzados**

#### **Estado de Resultados**
```
- Ingresos por categoría/período
- Gastos por cuenta/centro de costo  
- Márgenes y análisis de rentabilidad
- Comparativos período anterior
```

#### **Balance General**
```
- Activos/Pasivos/Capital clasificados
- Saldos por moneda (si multi-moneda)
- Análisis de liquidez y solvencia
- Notas y explicaciones automáticas
```

#### **Flujo de Efectivo**
```
- Flujo operativo/inversión/financiamiento
- Proyección de flujo por vencimientos
- Análisis de días cartera/pago promedio
```

---

### **🏭 Centros de Costo**

```php
// Fase 2 - Nueva entidad
CostCenter: {
    code,
    name,
    parent_id,      // Jerarquía de centros
    is_active,
    manager_id
}

// Modificación journal_lines
JournalLine: {
    // ... campos existentes
    cost_center_id   // Nuevo campo F2
}
```

#### **Funcionalidad:**
- Contabilidad analítica por departamento/proyecto
- Reportes P&L por centro de costo
- Presupuestos vs real por centro
- Asignación automática por reglas

---

### **🧾 Impuestos y Retenciones**

```php
// Fase 2 - Nuevas entidades
TaxType: {
    code: "IVA",
    name: "IVA 16%", 
    rate: 0.16,
    account_id,     // Cuenta contable del impuesto
    is_retention: false
}

InvoiceTax: {
    invoice_id,
    tax_type_id,
    base_amount,
    tax_amount
}
```

#### **Funcionalidad:**
- IVA, ISR, IEPS automáticos
- Retenciones de proveedores/clientes  
- Declaraciones fiscales automáticas
- Contabilización automática de impuestos

---

### **💸 Anticipos y Prepagos**

#### **Cuentas por Pagar**
```
1. Anticipo a proveedor → Cuenta "Anticipos Proveedores" 
2. Llegada de factura → Aplicación automática anticipo
3. Diferencia → Pago complementario o devolución
```

#### **Cuentas por Cobrar**
```  
1. Anticipo de cliente → Cuenta "Anticipos Clientes"
2. Facturación → Aplicación automática anticipo
3. Diferencia → Cobro complementario o devolución
```

---

### **🔄 Reversiones y Correcciones Automáticas**

```php
// Fase 2 - Nuevas funciones
JournalEntryService::reverse($entry, $reason)
APInvoiceService::void($invoice, $reason)  
ARInvoiceService::creditNote($invoice, $amount)
```

#### **Funcionalidad:**
- Reversión automática con asientos espejo
- Notas de crédito automáticas
- Log de auditoría completo
- Validaciones de seguridad

---

## 🎯 **ROADMAP TEMPORAL FASE 2**

### **Trimestre 1 - Fundaciones**
```
Mes 1: Multi-moneda + diferencias cambio
Mes 2: Líneas detalladas facturas + cuentas específicas  
Mes 3: Aplicaciones N:M pagos/cobros complejas
```

### **Trimestre 2 - Procesos Avanzados** 
```
Mes 4: Conciliación bancaria automática
Mes 5: Impuestos y retenciones básicas
Mes 6: Anticipos y prepagos
```

### **Trimestre 3 - Analítica**
```
Mes 7: Centros de costo + contabilidad analítica
Mes 8: Reportes financieros avanzados
Mes 9: Presupuestos vs real
```

### **Trimestre 4 - Optimización**
```
Mes 10: Diarios especializados + secuencias
Mes 11: Períodos fiscales + cierres contables
Mes 12: Auditoría + reversiones automáticas
```

---

## ⚠️ **CRITERIOS PARA INICIAR FASE 2**

### **✅ Prerrequisitos Obligatorios**
- [ ] Fase 1 funcionando estable en producción
- [ ] Tests business logic F1 implementados y pasando
- [ ] Usuarios usando sistema F1 diariamente sin problemas
- [ ] Performance acceptable con volúmenes reales
- [ ] Documentación F1 completa

### **✅ Métricas de Madurez**
- [ ] > 1000 asientos contables posteados sin errores
- [ ] > 500 facturas AP/AR procesadas correctamente
- [ ] > 200 pagos/cobros aplicados sin problemas
- [ ] < 5% tasa de errores en transacciones
- [ ] Tiempo respuesta promedio < 2 segundos

### **✅ Feedback de Usuarios**
- [ ] Equipo contable/finanzas aprobó funcionalidad F1
- [ ] Identificación clara de limitaciones F1 que requieren F2
- [ ] Consenso en prioridades de funcionalidades F2
- [ ] Recursos (desarrolladores + usuarios) disponibles para F2

---

## 🚨 **ADVERTENCIAS IMPORTANTES**

### **❌ NO Implementar F2 Si:**
- Fase 1 aún tiene bugs o funcionalidad incompleta
- Usuarios no han adoptado completamente F1  
- Team quiere "features cool" vs necesidades reales de negocio
- No hay plan de testing/QA robusto para F2

### **⚠️ Mantener Compatibilidad:**
- Todos los endpoints F1 deben seguir funcionando
- Migraciones F2 no deben romper datos F1
- UI F1 debe mantenerse simple (no complicar con features F2)
- Configuración F1 debe seguir siendo válida

### **🎯 Enfoque Evolutivo:**
- Cada feature F2 debe agregarse incrementalmente
- Testing exhaustivo antes de siguiente feature
- Rollback plan para cada cambio F2
- Documentación actualizada constantemente

---

## 📋 **ENTIDADES YA IMPLEMENTADAS - RESUMEN**

> **Frontend:** NO usar estos endpoints hasta Fase 2

```
✅ /api/v1/exchange-rates
✅ /api/v1/journals  
✅ /api/v1/fiscal-periods
✅ /api/v1/a-p-invoice-lines
✅ /api/v1/a-r-invoice-lines
✅ /api/v1/a-p-invoice-payments  
✅ /api/v1/a-r-invoice-receipts
✅ /api/v1/bank-statements
✅ /api/v1/bank-statement-lines
```

**Backend:** Entidades funcionan pero son complejidad extra para F1.

---

**🎯 OBJETIVO FASE 2: Expandir funcionalidad F1 hacia sistema ERP financiero completo manteniendo simplicidad y estabilidad del núcleo.**

---

*Documento de planificación | Implementar solo después de F1 completa*