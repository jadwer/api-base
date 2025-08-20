# 📦 Ecommerce Module

Advanced module with multiple entities and complex relationships.

**Generated:** 2025-07-31 22:47:46

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


## 📊 Métricas

- **Test Files**: 15
- **Generated**: 2025-08-20 11:02:11
- **Status**: ✅ Documentation up to date
- **API Version**: JSON:API v1.0
