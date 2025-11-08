#!/bin/bash

# Universal script to fix camelCase → snake_case in factory()->create() and assertDatabaseHas()
# Preserves JSON:API payloads (attributes => [...])

# Common field mappings
FIELDS=(
    "attendanceDate:date"
    "basicSalary:basic_salary"
    "checkInTime:check_in"
    "checkOutTime:check_out"
    "companySettingId:company_setting_id"
    "contactId:contact_id"
    "creditLimit:credit_limit"
    "daysAllowed:days_allowed"
    "daysRequested:days_requested"
    "discountAmount:discount_amount"
    "firstName:first_name"
    "invoiceNumber:invoice_number"
    "isActive:is_active"
    "isDefault:is_default"
    "orderNumber:order_number"
    "paymentDate:payment_date"
    "shippingMethodId:shipping_method_id"
    "shoppingCartId:shopping_cart_id"
    "startDate:start_date"
    "taxAmount:tax_amount"
    "taxId:tax_id"
    "totalAmount:total_amount"
    "unitPrice:unit_price"
)

fix_module() {
    local module="$1"
    local test_dir="Modules/$module/tests/Feature"

    if [ ! -d "$test_dir" ]; then
        echo "⊘ Skipping $module (no tests/ dir)"
        return
    fi

    echo "→ Processing $module..."
    local count=0

    for file in "$test_dir"/*.php; do
        if [ -f "$file" ]; then
            for field_pair in "${FIELDS[@]}"; do
                camel="${field_pair%%:*}"
                snake="${field_pair##*:}"

                # Fix in factory()->create([...])
                perl -i -pe "s/(->create\\(\\[.*?)\\b${camel}\\b/\$1${snake}/g" "$file" 2>/dev/null

                # Fix in assertDatabaseHas([...])
                perl -i -pe "s/(assertDatabaseHas\\([^,]+,\\s*\\[.*?)\\b${camel}\\b/\$1${snake}/g" "$file" 2>/dev/null
            done
            ((count++))
        fi
    done

    echo "  ✓ Fixed $count files in $module"
}

# Process each module
MODULES=("Billing" "Reports" "Finance" "CRM" "Accounting" "Sales" "Purchase" "Inventory" "Product")

for module in "${MODULES[@]}"; do
    fix_module "$module"
done

echo ""
echo "═══════════════════════════════════════════"
echo "✓ All modules processed"
echo "═══════════════════════════════════════════"
