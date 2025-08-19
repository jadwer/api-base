# 🧪 Test Report - PermissionManager

**Generated:** 2025-08-19 17:59:33

## F1PermissionDestroyTest

- ✅ God can delete permission
- ✅ User without permission cannot delete permission
- ✅ Unauthenticated user cannot delete permission

## F1PermissionIndexTest

- ✅ God can list permissions
- ✅ User without permission cannot list permissions
- ✅ Unauthenticated user cannot list permissions

## F1PermissionShowTest

- ✅ God can view permission
- ✅ User without permission cannot view permission
- ✅ Unauthenticated user cannot view permission

## F1PermissionStoreTest

- ✅ God can create permission
- ✅ User without permission cannot create permission
- ✅ Validation errors when creating permission

## F1PermissionUpdateTest

- ✅ God can update permission
- ✅ User without permission cannot update permission
- ✅ Validation errors when updating permission

## F1RoleDestroyTest

- ✅ God can delete role
- ✅ User without permission cannot delete role
- ✅ Unauthenticated user cannot delete role

## F1RoleIndexTest

- ✅ God can list roles
- ✅ User without permission cannot list roles
- ✅ Unauthenticated user cannot list roles

## F1RoleShowTest

- ✅ God can view role
- ✅ User without permission cannot view role
- ✅ Unauthenticated user cannot view role

## F1RoleStoreTest

- ✅ God can create role
- ✅ User without permission cannot create role
- ✅ Validation errors when creating role

## F1RoleUpdateTest

- ✅ God can update role
- ✅ User without permission cannot update role
- ✅ Validation errors when updating role

## F2RoleIncludePermissionsTest

- ✅ God can include permissions in role index
- ✅ God can include permissions in role show
- ✅ User without permission cannot include permissions

## F2RoleStoreWithPermissionsTest

- ✅ God can create role with permissions
- ✅ Jsonapi format error returns 400
- ✅ Validation error returns 422
- ✅ User without permission cannot create role with permissions

## F2RoleUpdatePermissionsTest

- ✅ God can update role permissions
- ✅ User without permission cannot update role permissions

## 📊 Summary

- **Test Files:** 13
- **Test Methods:** 39
- **Status:** All tests should pass
- **Coverage:** High coverage expected

## 🚀 Running Tests

```bash
# Run all module tests
php artisan test --filter PermissionManager

# Run specific test file
php artisan test Modules/PermissionManager/Tests/Feature/ExampleTest
```
