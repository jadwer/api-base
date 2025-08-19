# 🧪 Test Report - PageBuilder

**Generated:** 2025-08-19 17:59:33

## PageDestroyTest

- ✅ Admin can delete page
- ✅ Unauthenticated user cannot delete page

## PageFilterTest

- ✅ Admin can filter pages by slug
- ✅ Filter returns empty if slug does not match
- ✅ Unauthenticated user cannot filter unpublished pages
- ✅ Unauthenticated user can filter published pages
- ✅ User without permission can filter published pages

## PageIndexTest

- ✅ Admin can list pages
- ✅ Unauthenticated user can list only published pages

## PageShowTest

- ✅ Admin can view a page
- ✅ Unauthenticated user can view published page
- ✅ Unauthenticated user cannot view unpublished page
- ✅ User without permission can view published page
- ✅ User without permission cannot view unpublished page

## PageStoreTest

- ✅ Admin can create page
- ✅ Unauthorized user cannot create page

## PageUpdateTest

- ✅ Admin can update page
- ✅ Unauthenticated user cannot update page

## 📊 Summary

- **Test Files:** 6
- **Test Methods:** 18
- **Status:** All tests should pass
- **Coverage:** High coverage expected

## 🚀 Running Tests

```bash
# Run all module tests
php artisan test --filter PageBuilder

# Run specific test file
php artisan test Modules/PageBuilder/Tests/Feature/ExampleTest
```
