# P2 Business Rules Implementation Plan

**Created:** 2025-11-17
**Status:** Ready for Implementation
**Total Estimated Effort:** 30-35 hours (6-7 days)

---

## Executive Summary

This document consolidates all P2 (medium-priority) business rules identified across **6 module reviews** (Finance, Accounting, Sales, Purchase, Inventory, Ecommerce). P2 tasks are high-value features that should be implemented after P1 critical issues.

### Module Summary

| Module | P2 Tasks | Estimated Effort | Priority Notes |
|--------|----------|------------------|----------------|
| **Finance** | 2 | 5-6 hours | Overdue detection + credit hold |
| **Accounting** | 2 | 4 hours | Field protection + hierarchy validation |
| **Sales** | 2 | 3.5 hours | Line calculation + ENUM fix |
| **Purchase** | 3 | 6.5-7.5 hours | Supplier validation + receiving + budget |
| **Inventory** | 3 | 7-9 hours | Approval + quality check + reorder |
| **Ecommerce** | 0 | 0 hours | ✅ No P2 tasks (best module) |
| **TOTAL** | **12** | **26-32 hours** | **5-6 days** |

---

## Finance Module (5-6 hours)

### FI-002: Implement CheckOverdueInvoices Command

**Priority:** P2 HIGH
**Effort:** 3-4 hours
**Impact:** Automate overdue detection (currently manual)
**Status:** Documented as implemented, but code doesn't exist

**Implementation:**

1. **Create Command** (1 hour)
   ```php
   // Modules/Finance/app/Console/Commands/CheckOverdueInvoices.php
   class CheckOverdueInvoices extends Command
   {
       protected $signature = 'finance:check-overdue';
       protected $description = 'Check for overdue invoices and update status';

       public function handle()
       {
           $this->info('Checking for overdue AR invoices...');

           // Update AR invoices
           $arUpdated = ARInvoice::where('status', '!=', 'paid')
               ->where('status', '!=', 'overdue')
               ->where('due_date', '<', now()->toDateString())
               ->where('is_active', true)
               ->update(['status' => 'overdue']);

           $this->info("Updated {$arUpdated} AR invoices to overdue status");

           // Update AP invoices
           $apUpdated = APInvoice::where('status', '!=', 'paid')
               ->where('status', '!=', 'overdue')
               ->where('due_date', '<', now()->toDateString())
               ->where('is_active', true)
               ->update(['status' => 'overdue']);

           $this->info("Updated {$apUpdated} AP invoices to overdue status");

           return 0;
       }
   }
   ```

2. **Register Command** (30 min)
   ```php
   // Modules/Finance/app/Providers/FinanceServiceProvider.php
   protected function registerCommands(): void
   {
       $this->commands([
           \Modules\Finance\Console\Commands\CheckOverdueInvoices::class,
       ]);
   }
   ```

3. **Schedule Daily Execution** (30 min)
   ```php
   // app/Console/Kernel.php
   protected function schedule(Schedule $schedule)
   {
       $schedule->command('finance:check-overdue')->daily();
   }
   ```

4. **Add Tests** (1-2 hours)
   - Test overdue detection for AR invoices
   - Test overdue detection for AP invoices
   - Test already paid invoices are skipped
   - Test inactive invoices are skipped

**Files to Create:**
- `Modules/Finance/app/Console/Commands/CheckOverdueInvoices.php`
- `Modules/Finance/tests/Feature/CheckOverdueInvoicesTest.php`

**Files to Modify:**
- `Modules/Finance/app/Providers/FinanceServiceProvider.php`
- `app/Console/Kernel.php`

---

### FI-M003: Implement Credit Hold Automation

**Priority:** P2 HIGH
**Effort:** 2 hours
**Impact:** Automated risk management, prevents bad debt
**Depends On:** FI-002 (CheckOverdueInvoices command)

**Implementation:**

1. **Add Migration** (30 min)
   ```php
   // Add credit_status field to contacts table
   Schema::table('contacts', function (Blueprint $table) {
       $table->enum('credit_status', ['active', 'hold', 'blocked'])
             ->default('active')
             ->after('credit_limit');
       $table->timestamp('credit_hold_at')->nullable();
       $table->text('credit_hold_reason')->nullable();
   });
   ```

2. **Update CheckOverdueInvoices Command** (1 hour)
   ```php
   // Add to CheckOverdueInvoices::handle()
   $this->info('Checking for customers requiring credit hold...');

   $threshold = config('finance.credit_hold_days', 60);

   $customersToHold = Contact::whereHas('arInvoices', function ($query) use ($threshold) {
           $query->where('status', 'overdue')
                 ->where('due_date', '<', now()->subDays($threshold));
       })
       ->where('credit_status', 'active')
       ->get();

   foreach ($customersToHold as $customer) {
       $overdueAmount = $this->creditManagementService->getOverdueAmount($customer);

       $customer->update([
           'credit_status' => 'hold',
           'credit_hold_at' => now(),
           'credit_hold_reason' => "Overdue invoices exceeding {$threshold} days. Amount: $" . number_format($overdueAmount, 2),
       ]);

       // Send notification email
       Mail::to($customer->email)->send(new CreditHoldNotification($customer));

       $this->warn("Credit hold placed on: {$customer->name}");
   }

   $this->info("Placed {$customersToHold->count()} customers on credit hold");
   ```

3. **Update CreditManagementService** (30 min)
   ```php
   // Modules/Finance/app/Services/CreditManagementService.php
   public function validateCustomerCredit(Contact $contact, float $orderAmount): bool
   {
       // Check credit hold status (NEW)
       if ($contact->credit_status === 'hold') {
           throw new CreditHoldException(
               "Customer is on credit hold. Reason: {$contact->credit_hold_reason}"
           );
       }

       if ($contact->credit_status === 'blocked') {
           throw new CreditBlockedException("Customer account is blocked");
       }

       // ... existing validation ...
   }
   ```

**Files to Create:**
- `Modules/Finance/Database/migrations/YYYY_MM_DD_add_credit_status_to_contacts_table.php`
- `Modules/Finance/app/Mail/CreditHoldNotification.php`

**Files to Modify:**
- `Modules/Finance/app/Console/Commands/CheckOverdueInvoices.php`
- `Modules/Finance/app/Services/CreditManagementService.php`

---

## Accounting Module (4 hours)

### AC-005: Add readOnly Markers to Posted Entry Fields

**Priority:** P2 HIGH
**Effort:** 1 hour
**Impact:** Prevents accidental modification of system-calculated fields
**Status:** Fields exposed but not protected

**Implementation:**

1. **Update JournalEntrySchema** (30 min)
   ```php
   // Modules/Accounting/app/JsonApi/V1/JournalEntries/JournalEntrySchema.php
   public function fields(): array
   {
       return [
           // ... existing fields ...
           Number::make('totalDebit', 'total_debit')->sortable()->readOnly(),  // ✅ ADD
           Number::make('totalCredit', 'total_credit')->sortable()->readOnly(), // ✅ ADD
           DateTime::make('postedAt', 'posted_at')->sortable()->readOnly(),     // ✅ ADD
           Number::make('postedById', 'posted_by_id')->readOnly(),              // ✅ ADD
           DateTime::make('approvedAt', 'approved_at')->sortable()->readOnly(), // ✅ ADD
           Number::make('approvedById', 'approved_by_id')->readOnly(),          // ✅ ADD
           Number::make('reversalOfId', 'reversal_of_id')->readOnly(),          // ✅ ADD
           Str::make('reversalReason', 'reversal_reason')->readOnly(),          // ✅ ADD
       ];
   }
   ```

2. **Add Database Trigger (Optional)** (30 min)
   ```php
   // Migration: Add trigger to prevent modification of posted entries
   DB::unprepared("
       CREATE TRIGGER tr_prevent_posted_modification
       BEFORE UPDATE ON journal_entries
       FOR EACH ROW
       BEGIN
           IF OLD.status = 'posted' AND (
               OLD.total_debit != NEW.total_debit OR
               OLD.total_credit != NEW.total_credit OR
               OLD.posted_at != NEW.posted_at OR
               OLD.posted_by_id != NEW.posted_by_id
           ) THEN
               SIGNAL SQLSTATE '45000'
               SET MESSAGE_TEXT = 'Cannot modify posted journal entry fields. Use reversal instead.';
           END IF;
       END
   ");
   ```

**Files to Modify:**
- `Modules/Accounting/app/JsonApi/V1/JournalEntries/JournalEntrySchema.php`

**Files to Create (Optional):**
- `Modules/Accounting/Database/migrations/YYYY_MM_DD_add_posted_entry_protection_trigger.php`

---

### AC-007: Implement Circular Reference Validation

**Priority:** P2 HIGH
**Effort:** 3 hours
**Impact:** Prevents infinite loops in account hierarchy
**Status:** Database allows circular references, no application validation

**Implementation:**

1. **Create AccountHierarchyService** (1.5 hours)
   ```php
   // Modules/Accounting/app/Services/AccountHierarchyService.php
   namespace Modules\Accounting\Services;

   use Modules\Accounting\Models\Account;

   class AccountHierarchyService
   {
       /**
        * Validate no circular reference in account hierarchy
        */
       public function validateNoCircularReference(int $accountId, ?int $newParentId): void
       {
           if (!$newParentId) {
               return; // No parent, no issue
           }

           if ($accountId === $newParentId) {
               throw new \Exception('Account cannot be its own parent');
           }

           // Traverse up the parent chain
           $currentParentId = $newParentId;
           $visited = [$accountId];

           while ($currentParentId) {
               if (in_array($currentParentId, $visited)) {
                   throw new \Exception('Circular reference detected in account hierarchy');
               }

               $visited[] = $currentParentId;
               $parent = Account::find($currentParentId);
               $currentParentId = $parent?->parent_id;
           }
       }

       /**
        * Validate hierarchy doesn't exceed max depth
        */
       public function validateMaxDepth(int $accountId, int $maxDepth = 5): void
       {
           $depth = $this->calculateDepth($accountId);

           if ($depth > $maxDepth) {
               throw new \Exception("Account hierarchy exceeds maximum depth of {$maxDepth} levels");
           }
       }

       /**
        * Calculate depth of account in hierarchy
        */
       private function calculateDepth(int $accountId): int
       {
           $account = Account::find($accountId);
           if (!$account) {
               return 0;
           }

           $depth = 1;
           $currentParentId = $account->parent_id;

           while ($currentParentId) {
               $depth++;
               $parent = Account::find($currentParentId);
               $currentParentId = $parent?->parent_id;
           }

           return $depth;
       }

       /**
        * Get full hierarchy path for account
        */
       public function getHierarchyPath(int $accountId): array
       {
           $path = [];
           $account = Account::find($accountId);

           while ($account) {
               $path[] = [
                   'id' => $account->id,
                   'code' => $account->code,
                   'name' => $account->name,
                   'level' => $account->level,
               ];
               $account = $account->account; // Parent relationship
           }

           return array_reverse($path);
       }
   }
   ```

2. **Add Validation to AccountRequest** (1 hour)
   ```php
   // Modules/Accounting/app/JsonApi/V1/Accounts/AccountRequest.php
   public function withValidator($validator): void
   {
       $validator->after(function ($validator) {
           $data = $this->validated();

           if (isset($data['parentId'])) {
               $accountId = $this->route('account') ? $this->route('account')->id : null;

               if ($accountId) {
                   $hierarchyService = app(AccountHierarchyService::class);

                   try {
                       // Validate no circular reference
                       $hierarchyService->validateNoCircularReference($accountId, $data['parentId']);

                       // Validate max depth
                       $hierarchyService->validateMaxDepth($accountId);
                   } catch (\Exception $e) {
                       $validator->errors()->add('parentId', $e->getMessage());
                   }
               }
           }
       });
   }
   ```

3. **Add Tests** (30 min)
   - Test circular reference detection (A → B → A)
   - Test deep circular reference (A → B → C → A)
   - Test max depth validation
   - Test self-parent prevention

**Files to Create:**
- `Modules/Accounting/app/Services/AccountHierarchyService.php`
- `Modules/Accounting/tests/Feature/AccountHierarchyValidationTest.php`

**Files to Modify:**
- `Modules/Accounting/app/JsonApi/V1/Accounts/AccountRequest.php`

---

## Sales Module (3.5 hours)

### SA-008: Implement Line Total Calculation

**Priority:** P2 HIGH
**Effort:** 3 hours
**Impact:** Prevents incorrect totals from frontend errors
**Status:** NOT IMPLEMENTED (documented but missing)

**Implementation:**

**Option: Model Observer (Recommended - Same as Purchase)**

```php
// Modules/Sales/app/Models/SalesOrderItem.php
protected static function boot()
{
    parent::boot();

    // Auto-calculate total before saving
    static::saving(function ($item) {
        if ($item->quantity && $item->unit_price) {
            $item->total = ($item->quantity * $item->unit_price) - ($item->discount ?? 0);
        }
    });
}
```

**Files to Modify:**
- `Modules/Sales/app/Models/SalesOrderItem.php`

**Files to Create:**
- `Modules/Sales/tests/Feature/SalesOrderItemCalculationTest.php` (copy from Purchase module)

**Effort Breakdown:**
- Add boot() method: 30 min
- Copy tests from Purchase: 30 min
- Run tests and verify: 1 hour
- Documentation update: 1 hour

---

### SA-007: Update Status ENUM in Migration

**Priority:** P2 HIGH
**Effort:** 30 minutes
**Impact:** Prevents database constraint errors
**Status:** Service has 10 states, database ENUM has only 6

**Implementation:**

```php
// Modules/Sales/Database/migrations/YYYY_MM_DD_update_sales_order_status_enum.php
Schema::table('sales_orders', function (Blueprint $table) {
    $table->dropColumn('status');
});

Schema::table('sales_orders', function (Blueprint $table) {
    $table->enum('status', [
        'draft', 'pending', 'confirmed', 'processing',
        'shipped', 'delivered', 'completed',
        'cancelled', 'returned', 'refunded'
    ])->default('draft')->after('order_number');
});
```

**Files to Create:**
- `Modules/Sales/Database/migrations/YYYY_MM_DD_update_sales_order_status_enum.php`

---

## Purchase Module (6.5-7.5 hours)

### PU-004: Add Supplier Selection Validation

**Priority:** P2 HIGH
**Effort:** 30 minutes
**Impact:** Prevents creating POs with non-supplier contacts
**Status:** Missing

**Implementation:**

```php
// Modules/Purchase/app/JsonApi/V1/PurchaseOrders/PurchaseOrderRequest.php
use Illuminate\Validation\Rule;

public function rules(): array
{
    $creating = $this->isCreatingResource();

    return [
        'contact_id' => $creating
            ? [
                'required',
                'exists:contacts,id',
                Rule::exists('contacts', 'id')->where('is_supplier', true) // ✅ ADD
            ]
            : [
                'sometimes',
                'exists:contacts,id',
                Rule::exists('contacts', 'id')->where('is_supplier', true) // ✅ ADD
            ],
        // ... other rules
    ];
}
```

**Files to Modify:**
- `Modules/Purchase/app/JsonApi/V1/PurchaseOrders/PurchaseOrderRequest.php`

---

### PU-005: Implement Receiving Validation

**Priority:** P2 HIGH
**Effort:** 2 hours
**Impact:** Prevents over-receiving beyond tolerance
**Status:** NOT IMPLEMENTED (no receive() method exists)

**Implementation:**

1. **Add Migration** (30 min)
   ```php
   Schema::table('purchase_order_items', function (Blueprint $table) {
       $table->decimal('received_quantity', 15, 2)->default(0)->after('quantity');
   });
   ```

2. **Add receive() Method to PurchaseOrder** (1 hour)
   ```php
   // Modules/Purchase/app/Models/PurchaseOrder.php
   public function receive(array $items): void
   {
       DB::transaction(function () use ($items) {
           foreach ($items as $itemData) {
               $item = $this->items()->find($itemData['id']);

               $newReceived = $item->received_quantity + $itemData['quantity'];
               $tolerance = $item->quantity * 1.05; // +5% tolerance

               if ($newReceived > $tolerance) {
                   throw new \Exception(
                       "Over-receiving beyond tolerance for item {$item->id}"
                   );
               }

               $item->update(['received_quantity' => $newReceived]);
           }

           // Check if fully received
           if ($this->isFullyReceived()) {
               $this->update(['status' => 'received']);
               event(new PurchaseOrderReceived($this));
           }
       });
   }

   public function isFullyReceived(): bool
   {
       foreach ($this->items as $item) {
           if ($item->received_quantity < $item->quantity) {
               return false;
           }
       }
       return true;
   }
   ```

3. **Add Tests** (30 min)

**Files to Create:**
- `Modules/Purchase/Database/migrations/YYYY_MM_DD_add_received_quantity_to_purchase_order_items.php`
- `Modules/Purchase/tests/Feature/PurchaseOrderReceivingTest.php`

**Files to Modify:**
- `Modules/Purchase/app/Models/PurchaseOrder.php`

---

### PU-M003: Implement Budget Control

**Priority:** P2 MEDIUM
**Effort:** 4-5 hours
**Impact:** Spending control, budget tracking
**Status:** NOT IMPLEMENTED

**Implementation:** (Deferred to Phase 4 - lower priority)

---

## Inventory Module (7-9 hours)

### IV-007: Implement Adjustment Approval

**Priority:** P2 HIGH
**Effort:** 3-4 hours
**Impact:** Prevents fraud via unauthorized adjustments
**Status:** NOT IMPLEMENTED

**Implementation:**

1. **Add Migration** (30 min)
   ```php
   Schema::table('inventory_movements', function (Blueprint $table) {
       $table->string('approval_status')->default('pending')->after('status');
       $table->foreignId('approved_by')->nullable()->after('approval_status');
       $table->timestamp('approved_at')->nullable()->after('approved_by');
   });
   ```

2. **Create Policy** (1.5 hours)
   ```php
   // Modules/Inventory/app/Policies/InventoryMovementPolicy.php
   namespace Modules\Inventory\Policies;

   use Modules\User\Models\User;
   use Modules\Inventory\Models\InventoryMovement;

   class InventoryMovementPolicy
   {
       public function create(User $user, array $data): bool
       {
           // Adjustments require special permission
           if ($data['movement_type'] === 'adjustment') {
               return $user->can('inventory.create-adjustments');
           }

           return $user->can('inventory-movements.store');
       }

       public function approve(User $user, InventoryMovement $movement): bool
       {
           // Only Finance Manager can approve adjustments
           return $user->can('inventory.approve-adjustments');
       }
   }
   ```

3. **Add approve() Method to Model** (1 hour)
   ```php
   // Modules/Inventory/app/Models/InventoryMovement.php
   public function approve(int $userId): bool
   {
       if ($this->movement_type !== 'adjustment') {
           throw new \Exception('Only adjustments require approval');
       }

       if ($this->approval_status === 'approved') {
           return true; // Already approved
       }

       $this->update([
           'approval_status' => 'approved',
           'approved_by' => $userId,
           'approved_at' => now(),
       ]);

       // Update stock only after approval
       $this->applyAdjustment();

       return true;
   }

   private function applyAdjustment(): void
   {
       DB::transaction(function () {
           $stock = Stock::where('product_id', $this->product_id)
               ->where('warehouse_id', $this->warehouse_id)
               ->lockForUpdate()
               ->first();

           if (!$stock) {
               throw new \Exception('Stock record not found');
           }

           $stock->quantity += $this->quantity;
           $stock->save();
       });
   }
   ```

4. **Add Tests** (1 hour)

**Files to Create:**
- `Modules/Inventory/Database/migrations/YYYY_MM_DD_add_approval_fields_to_inventory_movements.php`
- `Modules/Inventory/app/Policies/InventoryMovementPolicy.php`
- `Modules/Inventory/tests/Feature/InventoryAdjustmentApprovalTest.php`

**Files to Modify:**
- `Modules/Inventory/app/Models/InventoryMovement.php`

---

### IV-009: Implement Quality Check Before Exit

**Priority:** P2 HIGH
**Effort:** 2-3 hours
**Impact:** Compliance (FDA, ISO), customer safety
**Status:** NOT IMPLEMENTED (quality_status field doesn't exist)

**Implementation:**

1. **Add Migration** (30 min)
   ```php
   Schema::table('product_batches', function (Blueprint $table) {
       $table->enum('quality_status', [
           'pending',
           'in_testing',
           'passed',
           'failed',
           'quarantine'
       ])->default('pending')->after('status');
   });
   ```

2. **Update BatchSelectionService** (1.5 hours - combines with IV-002 implementation)
   ```php
   // Modules/Inventory/app/Services/BatchSelectionService.php
   public function selectBatchesForExit(
       int $productId,
       int $warehouseId,
       float $quantityNeeded
   ): array {
       $batches = ProductBatch::where('product_id', $productId)
           ->where('warehouse_id', $warehouseId)
           ->where('status', 'active')
           ->where('quality_status', 'passed')  // ✅ Quality check
           ->where('available_quantity', '>', 0)
           ->orderBy('expiration_date', 'ASC')  // FEFO
           ->get();

       // ... selection logic
   }
   ```

3. **Add Tests** (1 hour)

**Files to Create:**
- `Modules/Inventory/Database/migrations/YYYY_MM_DD_add_quality_status_to_product_batches.php`
- `Modules/Inventory/tests/Feature/QualityCheckValidationTest.php`

**Files to Modify:**
- `Modules/Inventory/app/Services/BatchSelectionService.php` (if exists from P1)

---

### IV-M002: Implement Stock Reorder Alerts

**Priority:** P2 MEDIUM
**Effort:** 2 hours
**Impact:** Prevents stock-outs, automated purchasing alerts
**Status:** NOT IMPLEMENTED (field exists but no alerts)

**Implementation:**

1. **Create Command** (1 hour)
   ```php
   // Modules/Inventory/app/Console/Commands/CheckReorderPoints.php
   class CheckReorderPoints extends Command
   {
       protected $signature = 'inventory:check-reorder';
       protected $description = 'Check stock levels and send reorder alerts';

       public function handle()
       {
           $lowStockItems = Stock::where('quantity_on_hand', '<=', DB::raw('reorder_point'))
               ->where('reorder_point', '>', 0)
               ->with('product')
               ->get();

           foreach ($lowStockItems as $stock) {
               $quantityNeeded = $stock->minimum_stock - $stock->quantity_on_hand;

               // Send notification to purchasing
               Notification::send(
                   User::permission('purchase.manage'),
                   new StockReorderNotification($stock, $quantityNeeded)
               );

               // Log the alert
               Log::info('Reorder alert sent', [
                   'product_id' => $stock->product_id,
                   'current_quantity' => $stock->quantity_on_hand,
                   'reorder_point' => $stock->reorder_point,
                   'quantity_needed' => $quantityNeeded,
               ]);
           }

           $this->info("Sent {$lowStockItems->count()} reorder alerts");
           return 0;
       }
   }
   ```

2. **Schedule Daily Execution** (30 min)
   ```php
   // app/Console/Kernel.php
   protected function schedule(Schedule $schedule)
   {
       $schedule->command('inventory:check-reorder')->daily();
   }
   ```

3. **Create Notification** (30 min)
   ```php
   // Modules/Inventory/app/Notifications/StockReorderNotification.php
   class StockReorderNotification extends Notification
   {
       public function toMail($notifiable)
       {
           return (new MailMessage)
               ->subject('Stock Reorder Alert: ' . $this->stock->product->name)
               ->line("Stock for {$this->stock->product->name} is below reorder point.")
               ->line("Current quantity: {$this->stock->quantity_on_hand}")
               ->line("Reorder point: {$this->stock->reorder_point}")
               ->line("Suggested order quantity: {$this->quantityNeeded}")
               ->action('Create Purchase Order', url('/purchase/orders/create'));
       }
   }
   ```

**Files to Create:**
- `Modules/Inventory/app/Console/Commands/CheckReorderPoints.php`
- `Modules/Inventory/app/Notifications/StockReorderNotification.php`

**Files to Modify:**
- `app/Console/Kernel.php`

---

## Ecommerce Module

**No P2 tasks** ✅

The Ecommerce module only has 1 P1 task (Sales Order integration) and is otherwise complete.

---

## Implementation Priority Order

### Sprint 1 (Week 1): Quick Wins (8-10 hours)

1. **SA-007:** Update Status ENUM (30 min)
2. **PU-004:** Supplier Validation (30 min)
3. **AC-005:** Add readOnly Markers (1 hour)
4. **SA-008:** Line Total Calculation (3 hours)
5. **FI-002:** CheckOverdueInvoices Command (3-4 hours)

### Sprint 2 (Week 2): High-Value Features (10-12 hours)

6. **FI-M003:** Credit Hold Automation (2 hours)
7. **AC-007:** Circular Reference Validation (3 hours)
8. **PU-005:** Receiving Validation (2 hours)
9. **IV-M002:** Stock Reorder Alerts (2 hours)
10. **IV-009:** Quality Check (2-3 hours)

### Sprint 3 (Week 3): Advanced Features (8-10 hours)

11. **IV-007:** Adjustment Approval (3-4 hours)
12. **PU-M003:** Budget Control (4-5 hours) *(Optional - can be deferred)*

---

## Testing Strategy

### Test Coverage Requirements

For each P2 implementation:
1. **Unit Tests:** Service methods, model methods
2. **Feature Tests:** API endpoints, validation
3. **Integration Tests:** Cross-module interactions
4. **Minimum:** 5 tests per feature

### Test Execution

```bash
# Run tests for specific module
php artisan test Modules/Finance/Tests/Feature/

# Run all P2 tests after completion
php artisan test --filter P2

# Run with coverage
php artisan test --coverage
```

---

## Success Metrics

### Module Grades After P2 Completion

| Module | Current Grade | After P2 | Improvement |
|--------|---------------|----------|-------------|
| Finance | B (80%) | A- (90%) | +10% |
| Accounting | B+ (85%) | A (93%) | +8% |
| Sales | A- (90%) | A (96%) | +6% |
| Purchase | C+ (65%) | B (75%) | +10% |
| Inventory | B (70%) | B+ (82%) | +12% |
| Ecommerce | A (95%) | A (95%) | 0% |

**Overall System Grade:**
- **Before P2:** B+ (81%)
- **After P2:** A- (88%)
- **Target:** A (90%+)

---

## Dependencies & Blockers

### P1 Dependencies

These P2 tasks should be implemented AFTER P1 critical issues:

- **FI-M003** depends on **FI-002**
- **IV-009** depends on **IV-002** (FEFO strategy - P1)
- **IV-007** should be done after **IV-004** (Stock availability - P1)

### No External Blockers

All P2 tasks can be implemented independently without external dependencies.

---

## Rollout Plan

### Phase 1: Core Fixes (Week 1)
- Finance overdue detection
- Sales line calculation
- Accounting field protection
- Purchase supplier validation

### Phase 2: Automation (Week 2)
- Credit hold automation
- Stock reorder alerts
- Quality check enforcement

### Phase 3: Advanced (Week 3)
- Adjustment approval workflow
- Circular reference validation
- Receiving validation
- Budget control (optional)

---

## Risk Assessment

### Low Risk (Easy Implementation)
- ✅ SA-007: Status ENUM update (database change)
- ✅ PU-004: Supplier validation (simple rule)
- ✅ AC-005: readOnly markers (schema update)

### Medium Risk (Service Layer Changes)
- ⚠️ FI-002: Scheduled command (new cron job)
- ⚠️ SA-008: Line calculation (model observer)
- ⚠️ IV-M002: Reorder alerts (notification system)

### High Risk (Complex Business Logic)
- 🔴 AC-007: Circular reference (complex validation)
- 🔴 IV-007: Adjustment approval (multi-step workflow)
- 🔴 PU-005: Receiving validation (transaction logic)

**Mitigation:**
- Start with low-risk items
- Extensive testing for high-risk features
- Feature flags for rollback capability

---

## Documentation Updates

After P2 completion, update:

1. **Business Rules Documents**
   - Mark P2 tasks as implemented
   - Update implementation status

2. **API Documentation**
   - Document new endpoints
   - Update field descriptions

3. **Developer Guide**
   - Add new service usage examples
   - Update validation rules

4. **DEVELOPMENT_ROADMAP.md**
   - Update progress percentages
   - Mark P2 phase complete

---

## Next Steps

1. **Review this plan** with team
2. **Allocate resources** (1 developer, 6-7 days)
3. **Create feature branches** for each module
4. **Implement in priority order**
5. **Test thoroughly** before merging
6. **Update documentation**
7. **Deploy to staging**
8. **Move to P3** implementation (optional enhancements)

---

**Document Owner:** Claude Code AI Assistant
**Last Updated:** 2025-11-17
**Next Review:** After P2 completion
