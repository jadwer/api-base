#!/usr/bin/env python3
"""
Fix camelCase to snake_case in test files
Only in: factory()->create([...]) and assertDatabaseHas([...])
NOT in: JSON:API payloads (attributes => [...]) or assertJson*
"""

import re
import sys
from pathlib import Path

# Mappings
FIELD_MAPPINGS = {
    'totalAmount': 'total_amount',
    'unitPrice': 'unit_price',
    'discountAmount': 'discount_amount',
    'taxAmount': 'tax_amount',
    'shoppingCartId': 'shopping_cart_id',
    'shippingMethodId': 'shipping_method_id',
    'contactId': 'contact_id',
    'companySettingId': 'company_setting_id',
    'attendanceDate': 'date',
    'checkInTime': 'check_in',
    'checkOutTime': 'check_out',
    'basicSalary': 'basic_salary',
    'startDate': 'start_date',
    'endDate': 'end_date',
    'invoiceNumber': 'invoice_number',
    'invoiceDate': 'invoice_date',
    'paymentDate': 'payment_date',
    'dueDate': 'due_date',
    'paidAmount': 'paid_amount',
    'orderNumber': 'order_number',
    'isActive': 'is_active',
    'isDefault': 'is_default',
    'creditLimit': 'credit_limit',
    'firstName': 'first_name',
    'lastName': 'last_name',
    'daysRequested': 'days_requested',
    'daysAllowed': 'days_allowed',
}

def fix_file(filepath):
    """Fix a single test file"""
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    original = content

    # Pattern 1: assertDatabaseHas with multiline array
    # Match: assertDatabaseHas('table', [... multiple lines ...])
    def fix_assert_db(match):
        full = match.group(0)
        for camel, snake in FIELD_MAPPINGS.items():
            # Fix 'camelCase' => in array context
            full = re.sub(
                rf"'([\s]*){camel}([\s]*)'(\s*=>)",
                rf"'\1{snake}\2'\3",
                full
            )
        return full

    content = re.sub(
        r'assertDatabaseHas\([^,]+,\s*\[[^\]]+\]',
        fix_assert_db,
        content,
        flags=re.DOTALL
    )

    # Pattern 2: factory()->create([... multiple lines ...])
    def fix_factory(match):
        full = match.group(0)
        for camel, snake in FIELD_MAPPINGS.items():
            # Fix 'camelCase' => in array context
            full = re.sub(
                rf"'([\s]*){camel}([\s]*)'(\s*=>)",
                rf"'\1{snake}\2'\3",
                full
            )
        return full

    content = re.sub(
        r'factory\(\)->create\(\[[^\]]+\]\)',
        fix_factory,
        content,
        flags=re.DOTALL
    )

    # Only write if changed
    if content != original:
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(content)
        return True
    return False

def main():
    if len(sys.argv) < 2:
        print("Usage: python fix_multiline_tests.py <module_path>")
        sys.exit(1)

    module_path = Path(sys.argv[1])
    test_files = list(module_path.rglob("*Test.php"))

    fixed_count = 0
    for test_file in test_files:
        if fix_file(test_file):
            print(f"✓ Fixed: {test_file.relative_to(module_path)}")
            fixed_count += 1

    print(f"\n✓ Fixed {fixed_count}/{len(test_files)} files in {module_path.name}")

if __name__ == "__main__":
    main()
