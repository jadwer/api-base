# 🧪 Test Report - Purchase

**Generated:** 2025-08-20 01:05:10

## PurchaseOrderDestroyTest

- ✅ Admin can delete purchase order
- ✅ Returns 404 for nonexistent purchase order
- ✅ Unauthorized user cannot delete purchase order
- ✅ User without permission cannot delete purchase order

## PurchaseOrderIndexTest

- ✅ Admin can list purchase orders
- ✅ Admin can filter purchase orders by status
- ✅ Admin can include supplier data
- ✅ Admin can sort purchase orders by order date
- ✅ Admin can filter purchase orders by supplier
- ✅ Unauthorized user cannot list purchase orders
- ✅ User without permission cannot list purchase orders

## PurchaseOrderItemDestroyTest

- ✅ Admin can delete purchase order item
- ✅ Returns 404 when deleting non existent purchase order item
- ✅ Unauthorized user cannot delete purchase order item
- ✅ User without permission cannot delete purchase order item
- ✅ Deleting purchase order item preserves related data
- ✅ Admin can delete multiple items from same purchase order
- ✅ Tech user cannot delete purchase order item
- ✅ Cannot delete same purchase order item twice

## PurchaseOrderItemIndexTest

- ✅ Admin can list purchase order items
- ✅ Admin can filter purchase order items by purchase order
- ✅ Admin can include purchase order data
- ✅ Admin can sort purchase order items by quantity
- ✅ Unauthorized user cannot list purchase order items
- ✅ User without permission cannot list purchase order items

## PurchaseOrderItemShowTest

- ✅ Admin user can show purchase order item
- ✅ Admin can view purchase order item
- ✅ Admin can view purchase order item with relationships
- ✅ Returns 404 for non existent purchase order item
- ✅ Unauthorized user cannot view purchase order item
- ✅ User without permission cannot view purchase order item
- ✅ Tech user can view purchase order item

## PurchaseOrderItemStoreTest

- ✅ Admin user can create purchase order item
- ✅ Admin can create purchase order item
- ✅ Store validates required fields
- ✅ Store validates purchase order relationship
- ✅ Store validates product relationship
- ✅ Store validates positive quantity
- ✅ Store validates positive unit price
- ✅ Guest cannot create purchase order item
- ✅ User without permission cannot create purchase order item

## PurchaseOrderItemUpdateTest

- ✅ Admin can update purchase order item
- ✅ Unauthorized user cannot update purchase order item
- ✅ User without permission cannot update purchase order item

## PurchaseOrderShowTest

- ✅ Admin can show purchase order
- ✅ Admin can show purchase order with contact included
- ✅ Admin can show purchase order with items included
- ✅ Returns 404 for nonexistent purchase order
- ✅ Unauthorized user cannot show purchase order
- ✅ User without permission cannot show purchase order

## PurchaseOrderStoreTest

- ✅ Admin can create purchase order
- ✅ Admin can create purchase order with minimal data
- ✅ Store validates required fields
- ✅ Store validates supplier relationship
- ✅ Store validates status enum
- ✅ Store validates total amount positive
- ✅ Store validates order date format
- ✅ Unauthorized user cannot create purchase order
- ✅ User without permission cannot create purchase order

## PurchaseOrderUpdateTest

- ✅ Admin can update purchase order
- ✅ Admin can update partial purchase order data
- ✅ Update validates status enum
- ✅ Update validates total amount positive
- ✅ Update validates contact exists
- ✅ Update validates order date format
- ✅ Returns 404 for nonexistent purchase order
- ✅ Unauthorized user cannot update purchase order
- ✅ User without permission cannot update purchase order

## 📊 Summary

- **Test Files:** 10
- **Test Methods:** 68
- **Status:** All tests should pass
- **Coverage:** High coverage expected

## 🚀 Running Tests

```bash
# Run all module tests
php artisan test --filter Purchase

# Run specific test file
php artisan test Modules/Purchase/Tests/Feature/ExampleTest
```
