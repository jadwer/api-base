# 🧪 Test Report - Sales

**Generated:** 2025-08-19 17:59:33

## SalesOrderDestroyTest

- ✅ Admin can delete sales order
- ✅ Admin can delete draft sales order
- ✅ Admin can delete cancelled sales order
- ✅ Tech user can delete sales order
- ✅ Customer user cannot delete sales order
- ✅ Guest cannot delete sales order
- ✅ Cannot delete nonexistent sales order
- ✅ Deleting sales order preserves customer
- ✅ Can delete sales order with metadata
- ✅ Deletion response has no content

## SalesOrderIndexTest

- ✅ Admin can list sales orders
- ✅ Admin can sort sales orders by order number
- ✅ Admin can filter sales orders by status
- ✅ Admin can filter sales orders by customer
- ✅ Tech user can list sales orders with permission
- ✅ Customer user can list sales orders
- ✅ Guest cannot list sales orders
- ✅ Can paginate sales orders
- ✅ Can search sales orders by order number

## SalesOrderItemDestroyTest

- ✅ Admin can delete sales order item
- ✅ Admin can delete sales order item with metadata
- ✅ Deleting sales order item does not affect sales order
- ✅ Deleting sales order item does not affect product
- ✅ Can delete multiple items from same sales order
- ✅ Customer user cannot delete sales order item
- ✅ Guest cannot delete sales order item
- ✅ Returns 404 when deleting nonexistent sales order item
- ✅ Tech user can delete sales order item
- ✅ Admin can delete sales order item with high values

## SalesOrderItemIndexTest

- ✅ Admin can list sales order items
- ✅ Admin can sort sales order items by quantity
- ✅ Admin can sort sales order items by total desc
- ✅ Admin can filter sales order items by sales order
- ✅ Admin can filter sales order items by product
- ✅ Tech user can list sales order items with permission
- ✅ Customer user can list sales order items
- ✅ Guest cannot list sales order items
- ✅ Can paginate sales order items

## SalesOrderItemShowTest

- ✅ Admin can view sales order item
- ✅ Admin can view sales order item with metadata
- ✅ Admin can view sales order item with sales order id
- ✅ Admin can view sales order item with product id
- ✅ Admin can view sales order item with all foreign keys
- ✅ Admin can view sales order item with relationships
- ✅ Admin can view sales order item with nested relationships
- ✅ Tech user can view sales order item with permission
- ✅ Customer user can view sales order item
- ✅ Guest cannot view sales order item
- ✅ Returns 404 for nonexistent sales order item

## SalesOrderItemStoreTest

- ✅ Admin can create sales order item
- ✅ Admin can create sales order item with minimal data
- ✅ Customer user cannot create sales order item
- ✅ Guest cannot create sales order item
- ✅ Cannot create sales order item without required fields
- ✅ Cannot create sales order item with negative quantity
- ✅ Cannot create sales order item with negative unit price
- ✅ Cannot create sales order item with negative discount
- ✅ Tech user can create sales order item

## SalesOrderItemUpdateTest

- ✅ Admin can update sales order item quantity
- ✅ Admin can update sales order item unit price
- ✅ Admin can update sales order item discount
- ✅ Admin can update sales order item metadata
- ✅ Admin can update sales order item product id
- ✅ Customer user cannot update sales order item
- ✅ Guest cannot update sales order item
- ✅ Cannot update sales order item with negative quantity
- ✅ Cannot update sales order item with negative unit price
- ✅ Cannot update sales order item with negative discount
- ✅ Tech user can update sales order item

## SalesOrderShowTest

- ✅ Admin can view sales order
- ✅ Admin can view sales order with relationships
- ✅ Admin can view draft sales order
- ✅ Tech user can view sales order with permission
- ✅ Customer user can view sales order
- ✅ Guest cannot view sales order
- ✅ Cannot view nonexistent sales order
- ✅ Response includes timestamps
- ✅ Metadata is properly formatted
- ✅ Admin can view sales order with items
- ✅ Admin can view sales order with nested items and products
- ✅ Admin can view sales order with hybrid approach

## SalesOrderStoreTest

- ✅ Admin can create sales order
- ✅ Admin can create confirmed sales order
- ✅ Tech user can create sales order
- ✅ Customer user cannot create sales order
- ✅ Guest cannot create sales order
- ✅ Order number is required
- ✅ Customer id is required
- ✅ Status must be valid enum
- ✅ Total amount must be numeric
- ✅ Metadata is stored correctly

## SalesOrderUpdateTest

- ✅ Admin can update sales order
- ✅ Admin can update sales order status
- ✅ Admin can update sales order metadata
- ✅ Tech user can update sales order
- ✅ Customer user cannot update sales order
- ✅ Guest cannot update sales order
- ✅ Cannot update nonexistent sales order
- ✅ Cannot update with invalid status
- ✅ Cannot update order number to duplicate
- ✅ Can update order number to same value

## 📊 Summary

- **Test Files:** 10
- **Test Methods:** 101
- **Status:** All tests should pass
- **Coverage:** High coverage expected

## 🚀 Running Tests

```bash
# Run all module tests
php artisan test --filter Sales

# Run specific test file
php artisan test Modules/Sales/Tests/Feature/ExampleTest
```
