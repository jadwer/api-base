# PHPUnit Metadata Cleanup Roadmap

**Fecha:** 2025-12-23
**Objetivo:** Eliminar metadata deprecada en doc-comments (PHPUnit 12 compatibility)

---

## Problema

```
WARN  Metadata found in doc-comment for method ...::test_...()
Metadata in doc-comments is deprecated and will no longer be supported in PHPUnit 12.
```

**Metadata a eliminar:**
- `@test`
- `@depends`
- `@dataProvider`
- `@group`
- `@covers`

**Nota:** Los métodos que inician con `test_` no necesitan `@test`.

---

## Archivos Afectados por Módulo

| Módulo | Archivos | Estado |
|--------|----------|--------|
| Reports | 50 | ✅ Corregido |
| Ecommerce | 22 | ✅ Corregido |
| Billing | 2 | ✅ Corregido |
| Finance | 1 | ✅ Corregido |
| **Total** | **75** | **✅ COMPLETO** |

---

## Fase 1: Billing (2 archivos)

- [ ] `Modules/Billing/tests/Feature/CFDIInvoiceDownloadTest.php`
- [ ] `Modules/Billing/tests/Feature/CFDIStampingTest.php`

---

## Fase 2: Finance (1 archivo)

- [ ] `Modules/Finance/tests/Integration/Phase3ComprehensiveTest.php`

---

## Fase 3: Ecommerce (22 archivos)

### Wishlists (8)
- [ ] `WishlistIndexTest.php`
- [ ] `WishlistShowTest.php`
- [ ] `WishlistStoreTest.php`
- [ ] `WishlistUpdateTest.php`
- [ ] `WishlistDestroyTest.php`
- [ ] `WishlistItemIndexTest.php`
- [ ] `WishlistItemShowTest.php`
- [ ] `WishlistItemStoreTest.php`
- [ ] `WishlistItemUpdateTest.php`
- [ ] `WishlistItemDestroyTest.php`

### ProductComparison (10)
- [ ] `ProductComparisonIndexTest.php`
- [ ] `ProductComparisonShowTest.php`
- [ ] `ProductComparisonStoreTest.php`
- [ ] `ProductComparisonUpdateTest.php`
- [ ] `ProductComparisonDestroyTest.php`
- [ ] `ProductComparisonItemIndexTest.php`
- [ ] `ProductComparisonItemShowTest.php`
- [ ] `ProductComparisonItemStoreTest.php`
- [ ] `ProductComparisonItemUpdateTest.php`
- [ ] `ProductComparisonItemDestroyTest.php`

### Recommendations (2)
- [ ] `RecommendationEngineTest.php`
- [ ] `ProductRecommendationEndpointsTest.php`

---

## Fase 4: Reports (50 archivos)

### APAgingReports (5)
- [ ] `APAgingReportIndexTest.php`
- [ ] `APAgingReportShowTest.php`
- [ ] `APAgingReportStoreTest.php`
- [ ] `APAgingReportUpdateTest.php`
- [ ] `APAgingReportDestroyTest.php`

### ARAgingReports (5)
- [ ] `ARAgingReportIndexTest.php`
- [ ] `ARAgingReportShowTest.php`
- [ ] `ARAgingReportStoreTest.php`
- [ ] `ARAgingReportUpdateTest.php`
- [ ] `ARAgingReportDestroyTest.php`

### BalanceSheets (5)
- [ ] `BalanceSheetIndexTest.php`
- [ ] `BalanceSheetShowTest.php`
- [ ] `BalanceSheetStoreTest.php`
- [ ] `BalanceSheetUpdateTest.php`
- [ ] `BalanceSheetDestroyTest.php`

### CashFlows (5)
- [ ] `CashFlowIndexTest.php`
- [ ] `CashFlowShowTest.php`
- [ ] `CashFlowStoreTest.php`
- [ ] `CashFlowUpdateTest.php`
- [ ] `CashFlowDestroyTest.php`

### IncomeStatements (5)
- [ ] `IncomeStatementIndexTest.php`
- [ ] `IncomeStatementShowTest.php`
- [ ] `IncomeStatementStoreTest.php`
- [ ] `IncomeStatementUpdateTest.php`
- [ ] `IncomeStatementDestroyTest.php`

### PurchaseByProductReports (5)
- [ ] `PurchaseByProductReportIndexTest.php`
- [ ] `PurchaseByProductReportShowTest.php`
- [ ] `PurchaseByProductReportStoreTest.php`
- [ ] `PurchaseByProductReportUpdateTest.php`
- [ ] `PurchaseByProductReportDestroyTest.php`

### PurchaseBySupplierReports (5)
- [ ] `PurchaseBySupplierReportIndexTest.php`
- [ ] `PurchaseBySupplierReportShowTest.php`
- [ ] `PurchaseBySupplierReportStoreTest.php`
- [ ] `PurchaseBySupplierReportUpdateTest.php`
- [ ] `PurchaseBySupplierReportDestroyTest.php`

### SalesByCustomerReports (5)
- [ ] `SalesByCustomerReportIndexTest.php`
- [ ] `SalesByCustomerReportShowTest.php`
- [ ] `SalesByCustomerReportStoreTest.php`
- [ ] `SalesByCustomerReportUpdateTest.php`
- [ ] `SalesByCustomerReportDestroyTest.php`

### SalesByProductReports (5)
- [ ] `SalesByProductReportIndexTest.php`
- [ ] `SalesByProductReportShowTest.php`
- [ ] `SalesByProductReportStoreTest.php`
- [ ] `SalesByProductReportUpdateTest.php`
- [ ] `SalesByProductReportDestroyTest.php`

### TrialBalances (5)
- [ ] `TrialBalanceIndexTest.php`
- [ ] `TrialBalanceShowTest.php`
- [ ] `TrialBalanceStoreTest.php`
- [ ] `TrialBalanceUpdateTest.php`
- [ ] `TrialBalanceDestroyTest.php`

---

## Progreso

| Fase | Módulo | Archivos | Estado | Fecha |
|------|--------|----------|--------|-------|
| 1 | Billing | 2 | ✅ COMPLETO | 2025-12-23 |
| 2 | Finance | 1 | ✅ COMPLETO | 2025-12-23 |
| 3 | Ecommerce | 22 | ✅ COMPLETO | 2025-12-23 |
| 4 | Reports | 50 | ✅ COMPLETO | 2025-12-23 |

**Total: 75 archivos corregidos - 0 warnings de metadata deprecada**

---

## Notas

1. Eliminar solo las anotaciones deprecadas, no modificar la lógica del test
2. Los métodos `test_*` no necesitan `@test`
3. `@dataProvider` se convierte a atributo `#[DataProvider('providerName')]`
4. `@depends` se convierte a atributo `#[Depends('methodName')]`
5. `@group` se convierte a atributo `#[Group('groupName')]`

---

**Última Actualización:** 2025-12-23
