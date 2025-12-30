# Migration Consolidation Roadmap

**Objetivo:** Consolidar migraciones "add_*" y "update_*" en las migraciones principales para una versión 1.0 limpia.

**Estado:** ✅ COMPLETADO

**Fecha inicio:** 2025-12-30

---

## Reglas de Consolidación

1. **SIEMPRE verificar 2 veces** que los campos pertenecen al modelo correcto
2. **SIEMPRE verificar integridad** del archivo antes y después de modificar
3. **Mantener orden de columnas** lógico (FKs primero, luego campos, luego timestamps)
4. **Eliminar migración "add_*"** solo después de verificar que la consolidación es correcta
5. **NO tocar migraciones de vendor/** (spatie, etc.)

---

## Checklist por Módulo

### 1. User Module ✅
- [x] `add_status_to_users_table.php` → Integrado en `create_users_table.php`

### 2. PermissionManager Module ✅
- [x] `add_description_to_roles_table.php` → Integrado en `create_permission_tables.php`

### 3. Contacts Module ✅
- [x] `add_minimum_payment_score_to_contacts_table.php` → Integrado en `create_contacts_table.php`
- [x] `add_credit_status_to_contacts_table.php` → Integrado en `create_contacts_table.php`

### 4. Product Module ✅
- [x] `add_ecommerce_fields_to_products_table.php` → Integrado en `create_products_table.php`

### 5. Inventory Module ✅
- [x] `add_quality_check_to_inventory_movements_table.php` → Integrado en `create_inventory_movements_table.php`
- [x] `add_quality_status_to_product_batches_table.php` → Integrado en `create_product_batches_table.php`
- [x] `add_approval_fields_to_inventory_movements_table.php` → Integrado en `create_inventory_movements_table.php`
- [x] `add_gl_integration_fields_to_inventory_movements.php` (root) → Integrado en `create_inventory_movements_table.php`

### 6. Purchase Module ✅
- [x] `add_financial_status_to_purchase_orders.php` → Integrado en `create_purchase_orders_table.php`
- [x] `add_approval_fields_to_purchase_orders_table.php` → Integrado en `create_purchase_orders_table.php`
- [x] `add_received_quantity_to_purchase_order_items_table.php` → Integrado en `create_purchase_order_items_table.php`
- [x] `add_finance_integration_fields_to_purchase_tables.php` (root) → Integrado en migraciones de Purchase

### 7. Sales Module ✅
- [x] `add_integration_fields_to_sales_orders_table.php` → Integrado en `create_sales_orders_table.php`
- [x] `add_ecommerce_fields_to_sales_orders_table.php` → Integrado en `create_sales_orders_table.php`
- [x] `update_sales_order_status_enum.php` (x2) → Integrado en `create_sales_orders_table.php`
- [x] `add_finance_integration_fields_to_sales_tables.php` (root) → Integrado en migraciones de Sales

### 8. Finance Module ✅
- [x] `add_paid_date_to_ar_invoices_table.php` → Integrado en `create_ar_invoices_table.php`
- [x] `add_edge_case_fields_to_ar_invoices_table.php` → Integrado en `create_ar_invoices_table.php`
- [x] `add_edge_case_fields_to_ap_invoices_table.php` → Integrado en `create_ap_invoices_table.php`
- [x] `add_fiscal_period_to_finance_invoices_table.php` → Integrado en ambas tablas de invoices
- [x] `add_reconciliation_status_to_ap_invoices_table.php` → Integrado en `create_ap_invoices_table.php`
- [x] `fix_finance_contact_references.php` → Integrado (customer_id→contact_id, supplier_id→contact_id)

### 9. Accounting Module ✅
- [x] `add_accounting_business_constraints.php` → MANTENER (triggers/constraints separados)
- [x] `add_reversal_fields_to_journal_entries_table.php` → Eliminado (campos ya existían)
- [x] `add_accounting_date_to_journal_entries_table.php` → Integrado en `create_journal_entries_table.php`

### 10. Billing Module ✅
- [x] `add_stripe_fields_to_payment_transactions_table.php` → Integrado en `create_payment_transactions_table.php` (Ecommerce)
- [x] `add_xml_and_qr_fields_to_cfdi_invoices_table.php` → Integrado en `create_cfdi_invoices_table.php`

### 11. HR Module ✅ (N/A)
- [x] `add_manager_foreign_key_to_departments_table.php` → MANTENER (FK circular, necesita migración separada)

### 12. CRM Module ✅
- [x] `add_opportunity_id_to_activities_table.php` → Integrado en `create_activities_table.php` + FK en `create_opportunities_table.php`

### 13. Root Database Migrations ✅
- [x] `add_performance_indexes_phase35.php` → ELIMINADO (índices distribuidos a cada módulo)
- [x] `add_event_column_to_activity_log_table.php` → MANTENER (spatie package)
- [x] `add_batch_uuid_column_to_activity_log_table.php` → MANTENER (spatie package)
- [x] `add_finance_integration_fields_to_sales_tables.php` → ELIMINADO (consolidado en Sales)
- [x] `add_finance_integration_fields_to_purchase_tables.php` → ELIMINADO (consolidado en Purchase)
- [x] `add_gl_integration_fields_to_inventory_movements.php` → ELIMINADO (consolidado en Inventory)

---

## Proceso de Consolidación (Por Módulo)

```bash
# 1. Leer migración "add_*" para ver qué campos agrega
# 2. Leer migración principal "create_*"
# 3. Agregar campos en la posición correcta
# 4. Verificar integridad (campos, tipos, nullable, defaults)
# 5. Verificar que el Model tiene los campos en $fillable y $casts
# 6. Eliminar migración "add_*"
# 7. Ejecutar: php artisan migrate:fresh --seed
# 8. Ejecutar tests del módulo
```

---

## Migraciones a NO Tocar

- `vendor/**` - Paquetes externos
- `add_manager_foreign_key_to_departments_table.php` - FK circular necesario
- `add_event_column_to_activity_log_table.php` - Spatie package
- `add_batch_uuid_column_to_activity_log_table.php` - Spatie package
- `add_cross_module_foreign_keys.php` - FK circulares entre módulos

---

## Progreso

| Módulo | Migraciones | Estado | Fecha |
|--------|-------------|--------|-------|
| User | 1 | ✅ Completado | 2025-12-30 |
| PermissionManager | 1 | ✅ Completado | 2025-12-30 |
| Contacts | 2 | ✅ Completado | 2025-12-30 |
| Product | 1 | ✅ Completado | 2025-12-30 |
| Inventory | 4 | ✅ Completado | 2025-12-30 |
| Purchase | 4 | ✅ Completado | 2025-12-30 |
| Sales | 4 | ✅ Completado | 2025-12-30 |
| Finance | 6 | ✅ Completado | 2025-12-30 |
| Accounting | 2 | ✅ Completado | 2025-12-30 |
| Billing | 2 | ✅ Completado | 2025-12-30 |
| HR | 0 (mantener) | ✅ N/A | - |
| CRM | 1 | ✅ Completado | 2025-12-30 |
| Root | 3 (eliminadas) | ✅ Completado | 2025-12-30 |

**Total consolidadas:** 29 migraciones (incluyendo índices)
**Mantener separadas:** 3 migraciones (constraints, spatie, FK circular)

---

## Correcciones Adicionales (Post-Consolidación)

### Dependencias Circulares Resueltas
Se creó `database/migrations/2025_12_30_000001_add_cross_module_foreign_keys.php` para manejar FK circulares:
- `purchase_orders.ap_invoice_id` → `ap_invoices`
- `ap_invoices.purchase_order_id` → `purchase_orders`
- `sales_orders.ar_invoice_id` → `ar_invoices`
- `ar_invoices.sales_order_id` → `sales_orders`
- `sales_orders.checkout_session_id` → `checkout_sessions`

### Campos Huérfanos Eliminados
- `ap_invoice_line_id` eliminado de `purchase_order_items` (tabla `ap_invoice_lines` no existe)
- Limpieza de modelo y schema de `PurchaseOrderItem`

### Renombres de Campos Aplicados
- `payment_gateway` → `gateway` en `payment_transactions`
- `customer_id` → `contact_id` en `payments`
- Verificación de coherencia en modelos, schemas, factories y tests

### Fechas de Migraciones Ajustadas
- Finance: Movidas a `2025_10_24_*` (después de Accounting para FK válidas)

### Índices de Performance Distribuidos
La migración `add_performance_indexes_phase35.php` fue eliminada y sus índices distribuidos a:
- Accounting: fiscal_periods, journal_entries, journal_lines, accounts
- Finance: ar_invoices, ap_invoices, payments, bank_accounts, payment_applications
- Contacts: contacts, contact_persons, contact_addresses, contact_documents
- Sales: sales_orders, sales_order_items
- Purchase: purchase_orders, purchase_order_items
- Product: products
- Ecommerce: shopping_carts, cart_items, coupons
- (Inventory ya tenía sus índices)

---

## Verificación Final

Después de consolidar todos los módulos:

```bash
# 1. Limpiar base de datos
php artisan migrate:fresh --seed

# 2. Ejecutar tests completos
php artisan test

# 3. Verificar que no hay migraciones "add_*" restantes (excepto las permitidas)
find Modules -name "*add_*" -path "*/migrations/*" | grep -v "manager_foreign_key"

# 4. Verificar que no hay carpetas "Migrations" con mayúscula
find Modules -type d -name "Migrations"
```

---

**Última actualización:** 2025-12-30
