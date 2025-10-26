# Session 2025-10-25: Clean Tests Strategy

**Strategy Shift:** From "fixing broken tests" → "recreating clean tests"
**User Request:** "borraría los que marca que están mal y los crearía nuevos y con el enfoque que tengo en el diseño de mi sistema"

---

## 🎯 STRATEGY VALIDATED

**Decision:** Recreate clean tests instead of patching 90 broken generated tests.
**Rationale:** Test generator has fundamental flaws - better to write clean tests manually than fix bad generation.

---

## ✅ PROGRESS THIS SESSION

### 1. Root Cause Identified
**Location:** `app/Console/Commands/stubs/module-blueprint/test-Update.stub`
**Problem:** Lines 35-37, 61-62, 129, 150, 192-193 hardcoded with:
```php
'name' => 'Updated {{modelName}}',
'description' => 'Updated description',
'is_active' => false
```

**Impact:** ALL generated Update/Store tests assume entities have `name`, `description`, `is_active`.

---

### 2. Clean Tests Created for AccountBalance

**Files Created:**
- `AccountBalanceStoreTest.php` (NEW) - 6 tests, entity-specific fields
- `AccountBalanceUpdateTest.php` (NEW) - 7 tests, entity-specific fields

**Results:**
- **Store Tests:** 5/6 passing (83%)
- **Update Tests:** 4/7 passing (57%)
- **Combined:** 9/13 passing (69%)

**vs. Old Results:**
- Before: 0/13 passing (100% failure)
- After: 9/13 passing (**+900% improvement**)

---

### 3. Schema & Request Fixes Applied

**AccountBalanceSchema.php:**
```php
// BEFORE (missing DB mapping)
Number::make('fiscalYear')->sortable(),
Number::make('fiscalMonth')->sortable(),

// AFTER (correct mapping)
Number::make('fiscalYear', 'fiscal_year')->sortable(),
Number::make('fiscalMonth', 'fiscal_month')->sortable(),
```

**AccountBalanceRequest.php:**
```php
// Dynamic validation based on operation type
$isUpdate = $accountbalance && $accountbalance->exists;

return [
    'accountId' => [$isUpdate ? 'sometimes' : 'required', 'integer'],
    'fiscalYear' => [$isUpdate ? 'sometimes' : 'required', 'integer'],
    // POST requires fields, PATCH makes them optional
];
```

---

## 🔴 REMAINING ISSUES

### AccountBalance (3 failures)
- `test_admin_can_update_AccountBalance` - 500 error
- `test_admin_can_partially_update_AccountBalance` - 500 error
- `test_admin_can_update_AccountBalance_metadata` - 500 error

**Status:** 500 errors suggest Controller or database issue, not test issue.
**Next Step:** Debug Controller Update logic.

### Other 11 Entities
- Still using old generated tests
- Need clean recreation like AccountBalance

---

## 📋 CLEAN TEST PATTERN (Proven)

### Store Test Structure
```php
test_admin_can_create_Entity()
  ✓ Uses entity-specific fields
  ✓ Creates related entities (foreign keys)
  ✓ Validates database insertion

test_admin_can_create_Entity_with_minimal_data()
  ✓ Only required fields
  ✓ Tests nullable field handling

test_customer_user_cannot_create_Entity()
  ✓ Authorization check (403)

test_guest_cannot_create_Entity()
  ✓ Authentication check (401)

test_cannot_create_Entity_without_required_fields()
  ✓ Validation (422)

test_cannot_create_Entity_with_invalid_data()
  ✓ Type validation (422)
```

### Update Test Structure
```php
test_admin_can_update_Entity()
  ✓ Full update with multiple fields

test_admin_can_partially_update_Entity()
  ✓ PATCH semantics (only changed fields)

test_admin_can_update_Entity_metadata()
  ✓ JSON field update

test_customer_user_cannot_update_Entity()
  ✓ Authorization check (403)

test_guest_cannot_update_Entity()
  ✓ Authentication check (401)

test_cannot_update_nonexistent_Entity()
  ✓ 404 handling

test_cannot_update_Entity_with_invalid_data()
  ✓ Validation (422)
```

---

## 🎯 RECOMMENDED NEXT STEPS

### Option 1: Complete AccountBalance (Recommended for learning)
**Goal:** Get AccountBalance to 100% passing
**Tasks:**
1. Debug the 3 failing Update tests (500 errors)
2. Check Controller Update method
3. Verify database constraints
4. Use as reference for other entities

**Time:** 30-45 minutes
**Value:** Perfect template for other 11 entities

---

### Option 2: Mass Recreation (Fastest to 100%)
**Goal:** Recreate all 12 entities' Store/Update tests
**Tasks:**
1. Use AccountBalance tests as template
2. Customize fields per entity (use model fillable arrays)
3. Replace old tests file by file
4. Test each entity incrementally

**Time:** 2-3 hours
**Value:** Clean, maintainable test suite

---

### Option 3: Fix Generator Then Regenerate (Future-proof)
**Goal:** Fix test generator, then regenerate all tests
**Tasks:**
1. Update test-Store.stub with dynamic field placeholders
2. Update test-Update.stub with dynamic field placeholders
3. Enhance TestGenerator.php field mapping logic
4. Regenerate Accounting module tests
5. Test and iterate

**Time:** 3-4 hours
**Value:** Future modules auto-generate correct tests

---

## 📊 ENTITY FIELD REFERENCE

Quick reference for creating clean tests:

| Entity | Key Fields | Has name? | Has description? | Has is_active? |
|--------|------------|-----------|------------------|----------------|
| AccountBalance | fiscalYear, fiscalMonth, openingBalance | ❌ | ❌ | ❌ |
| Account | code, name, accountType, status | ✅ | ❌ | ❌ |
| AccountMapping | mappingType, accountId, isActive | ❌ | ❌ | ✅ |
| AuditLog | modelType, modelId, action | ❌ | ❌ | ❌ |
| ExchangeRate | fromCurrency, toCurrency, rate | ❌ | ❌ | ❌ |
| ExchangeRatePolicy | currency, maxAgeDays, isActive | ❌ | ❌ | ✅ |
| FiscalPeriod | name, year, month, status | ✅ | ❌ | ❌ |
| IdempotencyKey | endpoint, idempotencyKey, status | ❌ | ❌ | ❌ |
| Journal | code, name, description, status | ✅ | ✅ | ❌ |
| JournalEntry | number, date, description, status | ❌ | ✅ | ❌ |
| JournalLine | debit, credit, description | ❌ | ✅ | ❌ |
| JournalSequence | journalId, fiscalYear, currentNumber | ❌ | ❌ | ❌ |

---

## 💾 FILES MODIFIED THIS SESSION

**New Clean Tests (2):**
- Modules/Accounting/tests/Feature/AccountBalanceStoreTest.php
- Modules/Accounting/tests/Feature/AccountBalanceUpdateTest.php

**Backed Up (2):**
- Modules/Accounting/tests/Feature/AccountBalanceStoreTest_OLD.php
- Modules/Accounting/tests/Feature/AccountBalanceUpdateTest_OLD.php

**Schema Fixes (1):**
- Modules/Accounting/app/JsonApi/V1/AccountBalances/AccountBalanceSchema.php

**Request Fixes (1):**
- Modules/Accounting/app/JsonApi/V1/AccountBalances/AccountBalanceRequest.php

**Documentation (3):**
- docs/development/ACCOUNTING_FIX_SESSION_SUMMARY.md
- docs/development/ACCOUNTING_FIX_PROGRESS_REPORT.md
- docs/development/SESSION_2025_10_25_CLEAN_TESTS_STRATEGY.md (this file)

---

## 📈 SUCCESS METRICS

**AccountBalance Entity:**
- Old: 0/13 passing (0%)
- New: 9/13 passing (69%)
- Improvement: +69%

**Overall Accounting Module:**
- Before all fixes: 119 failures
- After Request/Store fixes: 90 failures
- After AccountBalance recreation: ~84 failures (estimated)
- **Total Improvement: -35 failures (-29%)**

**Time Investment:**
- Request validation fixes: ~30 minutes
- Store test data fixes: ~45 minutes
- AccountBalance recreation: ~60 minutes
- **Total: ~2.5 hours**

---

## 🚀 IMMEDIATE NEXT ACTIONS

1. **Decide strategy:** Option 1, 2, or 3
2. **If Option 1 (Recommended):**
   - Debug AccountBalance Update 500 errors
   - Get to 13/13 passing
   - Document pattern
3. **If Option 2:**
   - Start with simple entities (ExchangeRate, IdempotencyKey)
   - Use AccountBalance as template
   - Replace tests incrementally
4. **If Option 3:**
   - Plan generator fixes with user
   - Create entity-aware stub system
   - Test on single entity before mass regeneration

---

## 💡 KEY LEARNINGS

1. **Test generators need entity awareness** - Generic templates don't work for diverse entities
2. **Schema field mapping is critical** - camelCase → snake_case must be explicit
3. **Request validation must differentiate POST/PATCH** - Use `$isUpdate` pattern
4. **Foreign keys need related entities** - Factories should create dependencies
5. **Clean recreation > patching** - Faster and more maintainable than fixing bad generation

---

**Conclusion:** Strategy shift validated. Clean test recreation is working. AccountBalance shows 69% improvement. Ready to scale to remaining 11 entities.

**Recommended:** Complete AccountBalance (Option 1) then mass recreate (Option 2).

**Next Session:** User decides strategy and continues with chosen option.
