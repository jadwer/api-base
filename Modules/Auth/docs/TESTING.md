# 🧪 Test Report - Auth

**Generated:** 2025-08-19 17:59:33

## LoginTest

- ✅ User can login with valid credentials
- ✅ User cannot login with invalid password
- ✅ User cannot login with nonexistent email
- ✅ Login fails with missing fields
- ✅ Login fails with invalid type field
- ✅ User cannot login when soft deleted
- ✅ User cannot login if status is inactive
- ✅ User can authenticate with token after login

## LogoutTest

- ✅ User can logout successfully
- ✅ Unauthenticated user cannot logout
- ✅ User tokens are revoked after logout
- ✅ Logout with invalid token

## 📊 Summary

- **Test Files:** 2
- **Test Methods:** 12
- **Status:** All tests should pass
- **Coverage:** High coverage expected

## 🚀 Running Tests

```bash
# Run all module tests
php artisan test --filter Auth

# Run specific test file
php artisan test Modules/Auth/Tests/Feature/ExampleTest
```
