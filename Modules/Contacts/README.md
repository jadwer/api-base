# 📦 Contacts Module

Advanced module with multiple entities and complex relationships.

**Generated:** 2025-08-18 16:13:29

## 📋 Entities

### Contact
- **Table:** `contacts`
- **Fields:** 16

### ContactDocument
- **Table:** `contact_documents`
- **Fields:** 11

### ContactAddress
- **Table:** `contact_addresses`
- **Fields:** 9

### ContactPerson
- **Table:** `contact_persons`
- **Fields:** 8

## 🔗 Relationships

- **ContactDocument** ↔ **Contact** (belongsTo)
- **Contact** ↔ **ContactDocument** (hasMany)
- **ContactAddress** ↔ **Contact** (belongsTo)
- **Contact** ↔ **ContactAddress** (hasMany)
- **ContactPerson** ↔ **Contact** (belongsTo)
- **Contact** ↔ **ContactPerson** (hasMany)

## 🧪 Testing

```bash
php artisan test Modules/Contacts
```
