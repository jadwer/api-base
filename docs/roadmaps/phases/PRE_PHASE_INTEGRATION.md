# 🔗 PRE-FASE: Integración Cross-Module
## Campos preparatorios para Sales/Purchase/Inventory

**Objetivo:** Preparar módulos existentes para integración financiera

---

## 🎯 **OBJETIVO**

Agregar campos preparatorios a los módulos Sales, Purchase e Inventory para facilitar la integración con los nuevos módulos Finance y Accounting empresariales.

## 📦 **MÓDULOS AFECTADOS**

### 1. **Sales Module**
Campos para integración con AR Invoices:

```sql
-- sales_orders table additions
ar_invoice_id BIGINT UNSIGNED NULL,
invoicing_status ENUM('pending', 'partial', 'complete') DEFAULT 'pending',
invoicing_notes TEXT NULL,

-- sales_order_items additions  
ar_invoice_line_id BIGINT UNSIGNED NULL,
invoiced_quantity DECIMAL(10,2) DEFAULT 0.00,
invoiced_amount DECIMAL(10,2) DEFAULT 0.00
```

### 2. **Purchase Module**
Campos para integración con AP Invoices:

```sql
-- purchase_orders table additions
ap_invoice_id BIGINT UNSIGNED NULL,
invoicing_status ENUM('pending', 'partial', 'complete') DEFAULT 'pending',
invoicing_notes TEXT NULL,

-- purchase_order_items additions
ap_invoice_line_id BIGINT UNSIGNED NULL,
invoiced_quantity DECIMAL(10,2) DEFAULT 0.00,
invoiced_amount DECIMAL(10,2) DEFAULT 0.00
```

### 3. **Inventory Module**
Campos para integración con GL:

```sql
-- inventory_movements table additions
gl_journal_entry_id BIGINT UNSIGNED NULL,
gl_posting_status ENUM('pending', 'posted', 'error') DEFAULT 'pending',
cost_per_unit DECIMAL(10,4) DEFAULT 0.0000,
total_cost DECIMAL(10,2) DEFAULT 0.00,
gl_posting_notes TEXT NULL
```

---

## 🛠️ **IMPLEMENTACIÓN**

### **Paso 1: Crear Migraciones**

```bash
# Sales integration fields
php artisan make:migration add_finance_integration_fields_to_sales_tables

# Purchase integration fields  
php artisan make:migration add_finance_integration_fields_to_purchase_tables

# Inventory integration fields
php artisan make:migration add_finance_integration_fields_to_inventory_tables
```

### **Paso 2: Actualizar Modelos**

Agregar campos a `$fillable` arrays y casting apropiado:

```php
// SalesOrder model additions
protected $fillable = [
    // ... existing fields
    'ar_invoice_id',
    'invoicing_status', 
    'invoicing_notes'
];

protected $casts = [
    // ... existing casts
    'invoicing_status' => 'string'
];
```

### **Paso 3: Actualizar Schemas JSON:API**

Agregar campos a schemas para exposición en API:

```php
// SalesOrderSchema additions
public function fields(): array
{
    return [
        // ... existing fields
        'arInvoiceId' => ['type' => 'integer', 'nullable' => true],
        'invoicingStatus' => ['type' => 'string'],
        'invoicingNotes' => ['type' => 'string', 'nullable' => true]
    ];
}
```

### **Paso 4: Actualizar Factory Data**

Preparar factories para testing con datos realistas:

```php
// SalesOrderFactory additions
'ar_invoice_id' => null,
'invoicing_status' => 'pending',
'invoicing_notes' => fake()->optional()->sentence()
```

### **Paso 5: Tests de Integración**

Validar que campos se exponen correctamente:

```php
public function test_sales_order_includes_finance_integration_fields()
{
    $order = SalesOrder::factory()->create();
    
    $response = $this->getJson("/api/v1/sales-orders/{$order->id}");
    
    $response->jsonApi()
        ->expects('sales-orders')
        ->hasAttribute('invoicingStatus')
        ->hasAttribute('arInvoiceId');
}
```

---

## ✅ **CRITERIOS DE ACEPTACIÓN**

### **Sales Module**
- [ ] Migración ejecutada sin errores
- [ ] Campos expuestos en JSON:API responses
- [ ] Tests pasan con nuevos campos
- [ ] Factory actualizado con datos realistas

### **Purchase Module**  
- [ ] Migración ejecutada sin errores
- [ ] Campos expuestos en JSON:API responses
- [ ] Tests pasan con nuevos campos
- [ ] Factory actualizado con datos realistas

### **Inventory Module**
- [ ] Migración ejecutada sin errores
- [ ] Campos expuestos en JSON:API responses
- [ ] Tests pasan con nuevos campos
- [ ] Factory actualizado con datos realistas

---

## 🔄 **IMPACT ASSESSMENT**

### **Breaking Changes:** ❌ NO
- Solo se agregan campos opcionales
- No se modifican endpoints existentes
- Backward compatibility mantenida

### **Performance Impact:** ✅ MÍNIMO
- Campos agregados son nullable
- No se agregan índices complejos en esta fase
- Queries existentes no afectados

### **Testing Impact:** ✅ MÍNIMO
- Tests existentes siguen funcionando
- Solo se agregan validaciones de nuevos campos

---

## 📅 **PLAN DE IMPLEMENTACIÓN**

### **Etapa 1: Sales Module**
- Crear migraciones para campos de integración AR
- Actualizar models y schemas Sales
- Tests de integración básicos

### **Etapa 2: Purchase Module**
- Crear migraciones para campos de integración AP
- Actualizar models y schemas Purchase
- Tests de integración básicos

### **Etapa 3: Inventory Module**
- Crear migraciones para campos de integración GL
- Actualizar models y schemas Inventory
- Tests de integración básicos

### **Etapa 4: Validación**
- Actualizar factories con datos realistas
- Tests de regresión completos
- Validación de estructura

---

## 🚀 **SIGUIENTE FASE**

Una vez completada la PRE-FASE, proceder con **FASE 0: Backup y eliminación** de módulos Finance/Accounting actuales para regeneración empresarial.