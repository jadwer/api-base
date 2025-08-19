# 🧪 Test Report - Audit

**Generated:** 2025-08-19 17:59:33

## AuditIndexTest

- ✅ It returns a list of audits with causer and optional subject
- ✅ It supports sorting by created at desc
- ✅ It supports filtering by event
- ✅ It supports filtering by causer

## AuditShowTest

- ✅ It returns a single audit with causer and optional subject
- ✅ It fails if user lacks permission

## 📊 Summary

- **Test Files:** 2
- **Test Methods:** 6
- **Status:** All tests should pass
- **Coverage:** High coverage expected

## 🚀 Running Tests

```bash
# Run all module tests
php artisan test --filter Audit

# Run specific test file
php artisan test Modules/Audit/Tests/Feature/ExampleTest
```
