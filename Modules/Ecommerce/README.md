# 📦 Ecommerce Module

Advanced module with multiple entities and complex relationships.

**Generated:** 2025-07-30 06:18:14

## 📋 Entities

### ShoppingCart
- **Table:** `shopping_carts`
- **Fields:** 11

### CartItem
- **Table:** `cart_items`
- **Fields:** 11

### Coupon
- **Table:** `coupons`
- **Fields:** 15

## 🔗 Relationships

- **ShoppingCart** ↔ **CartItem** (one-to-many)
- **CartItem** ↔ **Product** (many-to-one)
- **ShoppingCart** ↔ **User** (many-to-one)

## 🧪 Testing

```bash
php artisan test Modules/Ecommerce
```
