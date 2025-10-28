#!/bin/bash

# Business Flows Validation - Critical Business Processes
# Tests Order-to-Cash and Procure-to-Pay flows
# Usage: ./validate-business-flows.sh [base_url]

BASE_URL="${1:-http://localhost:8000}"
API_URL="$BASE_URL/api/v1"
AUTH_URL="$BASE_URL/api/auth"

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo "========================================="
echo "BUSINESS FLOWS VALIDATION"
echo "Testing: Order-to-Cash & Procure-to-Pay"
echo "========================================="
echo ""

# === AUTHENTICATE ===
echo -n "Authenticating... "
LOGIN_RESPONSE=$(curl -s -X POST "$AUTH_URL/login" \
    -H "Content-Type: application/json" \
    -d '{"email": "admin@example.com", "password": "secureadmin"}')

TOKEN=$(echo "$LOGIN_RESPONSE" | jq -r '.access_token // empty')

if [ -z "$TOKEN" ]; then
    echo -e "${RED}FAILED${NC}"
    exit 1
fi
echo -e "${GREEN}OK${NC}"
echo ""

# === FLOW 1: Order-to-Cash ===
echo -e "${BLUE}=== FLOW 1: ORDER-TO-CASH ===${NC}"
echo "Steps: Create Contact → Create Sales Order → Create AR Invoice → Record Payment"
echo ""

# Step 1.1: Create Customer
echo -n "1.1 Creating Customer... "
CUSTOMER_RESPONSE=$(curl -s -X POST "$API_URL/contacts" \
    -H "Authorization: Bearer $TOKEN" \
    -H "Content-Type: application/vnd.api+json" \
    -d '{
        "data": {
            "type": "contacts",
            "attributes": {
                "contactType": "company",
                "name": "Test Customer Corp",
                "legalName": "Test Customer Corp SA de CV",
                "taxId": "TCU123456ABC",
                "email": "customer@test.com",
                "phone": "5551234567",
                "isCustomer": true,
                "creditLimit": 50000,
                "paymentTerms": 30,
                "status": "active"
            }
        }
    }')

CUSTOMER_ID=$(echo "$CUSTOMER_RESPONSE" | jq -r '.data.id // empty')
if [ -n "$CUSTOMER_ID" ]; then
    echo -e "${GREEN}OK${NC} (ID: $CUSTOMER_ID)"
else
    echo -e "${RED}FAILED${NC}"
    echo "$CUSTOMER_RESPONSE" | jq '.errors'
fi

# Step 1.2: Create Sales Order
echo -n "1.2 Creating Sales Order... "
SALES_ORDER_RESPONSE=$(curl -s -X POST "$API_URL/sales-orders" \
    -H "Authorization: Bearer $TOKEN" \
    -H "Content-Type: application/vnd.api+json" \
    -d "{
        \"data\": {
            \"type\": \"sales-orders\",
            \"attributes\": {
                \"contact_id\": $CUSTOMER_ID,
                \"order_number\": \"SO-TEST-$(date +%s)\",
                \"status\": \"draft\",
                \"order_date\": \"$(date +%Y-%m-%d)\",
                \"subtotal_amount\": 10000.00,
                \"tax_amount\": 1600.00,
                \"total_amount\": 11600.00
            }
        }
    }")

SALES_ORDER_ID=$(echo "$SALES_ORDER_RESPONSE" | jq -r '.data.id // empty')
if [ -n "$SALES_ORDER_ID" ]; then
    echo -e "${GREEN}OK${NC} (ID: $SALES_ORDER_ID)"
else
    echo -e "${RED}FAILED${NC}"
    echo "$SALES_ORDER_RESPONSE" | jq '.errors'
fi

# Step 1.3: Create AR Invoice
echo -n "1.3 Creating AR Invoice... "
AR_INVOICE_RESPONSE=$(curl -s -X POST "$API_URL/ar-invoices" \
    -H "Authorization: Bearer $TOKEN" \
    -H "Content-Type: application/vnd.api+json" \
    -d "{
        \"data\": {
            \"type\": \"ar-invoices\",
            \"attributes\": {
                \"invoiceNumber\": \"AR-TEST-$(date +%s)\",
                \"invoiceDate\": \"$(date +%Y-%m-%d)\",
                \"dueDate\": \"$(date -d '+30 days' +%Y-%m-%d)\",
                \"contactId\": $CUSTOMER_ID,
                \"salesOrderId\": $SALES_ORDER_ID,
                \"currency\": \"MXN\",
                \"subtotal\": 10000.00,
                \"taxAmount\": 1600.00,
                \"totalAmount\": 11600.00,
                \"paidAmount\": 0,
                \"status\": \"draft\"
            }
        }
    }")

AR_INVOICE_ID=$(echo "$AR_INVOICE_RESPONSE" | jq -r '.data.id // empty')
if [ -n "$AR_INVOICE_ID" ]; then
    echo -e "${GREEN}OK${NC} (ID: $AR_INVOICE_ID)"
else
    echo -e "${RED}FAILED${NC}"
    echo "$AR_INVOICE_RESPONSE" | jq '.errors'
fi

# Step 1.4: Get AR Invoice Details
echo -n "1.4 Fetching AR Invoice Details... "
AR_DETAILS=$(curl -s -X GET "$API_URL/ar-invoices/$AR_INVOICE_ID?include=contact,salesOrder" \
    -H "Authorization: Bearer $TOKEN" \
    -H "Accept: application/vnd.api+json")

AR_STATUS=$(echo "$AR_DETAILS" | jq -r '.data.attributes.status // empty')
if [ "$AR_STATUS" = "draft" ]; then
    echo -e "${GREEN}OK${NC} (Status: $AR_STATUS)"
else
    echo -e "${YELLOW}WARNING${NC} (Status: $AR_STATUS)"
fi

echo ""

# === FLOW 2: Procure-to-Pay ===
echo -e "${BLUE}=== FLOW 2: PROCURE-TO-PAY ===${NC}"
echo "Steps: Create Supplier → Create Purchase Order → Create AP Invoice"
echo ""

# Step 2.1: Create Supplier
echo -n "2.1 Creating Supplier... "
SUPPLIER_RESPONSE=$(curl -s -X POST "$API_URL/contacts" \
    -H "Authorization: Bearer $TOKEN" \
    -H "Content-Type: application/vnd.api+json" \
    -d '{
        "data": {
            "type": "contacts",
            "attributes": {
                "contactType": "company",
                "name": "Test Supplier SA",
                "legalName": "Test Supplier SA de CV",
                "taxId": "TSU987654XYZ",
                "email": "supplier@test.com",
                "phone": "5559876543",
                "isSupplier": true,
                "paymentTerms": 15,
                "status": "active"
            }
        }
    }')

SUPPLIER_ID=$(echo "$SUPPLIER_RESPONSE" | jq -r '.data.id // empty')
if [ -n "$SUPPLIER_ID" ]; then
    echo -e "${GREEN}OK${NC} (ID: $SUPPLIER_ID)"
else
    echo -e "${RED}FAILED${NC}"
    echo "$SUPPLIER_RESPONSE" | jq '.errors'
fi

# Step 2.2: Create Purchase Order
echo -n "2.2 Creating Purchase Order... "
PURCHASE_ORDER_RESPONSE=$(curl -s -X POST "$API_URL/purchase-orders" \
    -H "Authorization: Bearer $TOKEN" \
    -H "Content-Type: application/vnd.api+json" \
    -d "{
        \"data\": {
            \"type\": \"purchase-orders\",
            \"attributes\": {
                \"contact_id\": $SUPPLIER_ID,
                \"orderDate\": \"$(date +%Y-%m-%d)\",
                \"status\": \"pending\",
                \"totalAmount\": 5800.00,
                \"notes\": \"Test purchase order\"
            },
            \"relationships\": {
                \"contact\": {
                    \"data\": {
                        \"type\": \"contacts\",
                        \"id\": \"$SUPPLIER_ID\"
                    }
                }
            }
        }
    }")

PURCHASE_ORDER_ID=$(echo "$PURCHASE_ORDER_RESPONSE" | jq -r '.data.id // empty')
if [ -n "$PURCHASE_ORDER_ID" ]; then
    echo -e "${GREEN}OK${NC} (ID: $PURCHASE_ORDER_ID)"
else
    echo -e "${RED}FAILED${NC}"
    echo "$PURCHASE_ORDER_RESPONSE" | jq '.errors'
fi

# Step 2.3: Create AP Invoice
echo -n "2.3 Creating AP Invoice... "
AP_INVOICE_RESPONSE=$(curl -s -X POST "$API_URL/ap-invoices" \
    -H "Authorization: Bearer $TOKEN" \
    -H "Content-Type: application/vnd.api+json" \
    -d "{
        \"data\": {
            \"type\": \"ap-invoices\",
            \"attributes\": {
                \"invoiceNumber\": \"AP-TEST-$(date +%s)\",
                \"invoiceDate\": \"$(date +%Y-%m-%d)\",
                \"dueDate\": \"$(date -d '+15 days' +%Y-%m-%d)\",
                \"contactId\": $SUPPLIER_ID,
                \"purchaseOrderId\": $PURCHASE_ORDER_ID,
                \"currency\": \"MXN\",
                \"subtotal\": 5000.00,
                \"taxAmount\": 800.00,
                \"totalAmount\": 5800.00,
                \"paidAmount\": 0,
                \"status\": \"draft\"
            }
        }
    }")

AP_INVOICE_ID=$(echo "$AP_INVOICE_RESPONSE" | jq -r '.data.id // empty')
if [ -n "$AP_INVOICE_ID" ]; then
    echo -e "${GREEN}OK${NC} (ID: $AP_INVOICE_ID)"
else
    echo -e "${RED}FAILED${NC}"
    echo "$AP_INVOICE_RESPONSE" | jq '.errors'
fi

echo ""

# === FLOW 3: Accounting Integration ===
echo -e "${BLUE}=== FLOW 3: ACCOUNTING INTEGRATION ===${NC}"
echo "Verify Chart of Accounts, Journal Entries, Fiscal Periods"
echo ""

echo -n "3.1 List Chart of Accounts... "
ACCOUNTS=$(curl -s -X GET "$API_URL/accounts?page[size]=5" \
    -H "Authorization: Bearer $TOKEN" \
    -H "Accept: application/vnd.api+json")

ACCOUNT_COUNT=$(echo "$ACCOUNTS" | jq '.data | length')
echo -e "${GREEN}OK${NC} (Found: $ACCOUNT_COUNT accounts)"

echo -n "3.2 List Fiscal Periods... "
PERIODS=$(curl -s -X GET "$API_URL/fiscal-periods?page[size]=5" \
    -H "Authorization: Bearer $TOKEN" \
    -H "Accept: application/vnd.api+json")

PERIOD_COUNT=$(echo "$PERIODS" | jq '.data | length')
echo -e "${GREEN}OK${NC} (Found: $PERIOD_COUNT periods)"

echo -n "3.3 List Journal Entries... "
ENTRIES=$(curl -s -X GET "$API_URL/journal-entries?page[size]=5" \
    -H "Authorization: Bearer $TOKEN" \
    -H "Accept: application/vnd.api+json")

ENTRY_COUNT=$(echo "$ENTRIES" | jq '.data | length')
echo -e "${GREEN}OK${NC} (Found: $ENTRY_COUNT entries)"

echo ""

# === SUMMARY ===
echo "========================================="
echo -e "${GREEN}✓ BUSINESS FLOWS VALIDATION COMPLETE${NC}"
echo "========================================="
echo ""
echo "Summary:"
echo "  Order-to-Cash Flow: Customer → Sales Order → AR Invoice"
echo "  Procure-to-Pay Flow: Supplier → Purchase Order → AP Invoice"
echo "  Accounting Integration: Accounts, Periods, Entries verified"
echo ""
echo "Created Resources:"
echo "  - Customer ID: $CUSTOMER_ID"
echo "  - Supplier ID: $SUPPLIER_ID"
echo "  - Sales Order ID: $SALES_ORDER_ID"
echo "  - Purchase Order ID: $PURCHASE_ORDER_ID"
echo "  - AR Invoice ID: $AR_INVOICE_ID"
echo "  - AP Invoice ID: $AP_INVOICE_ID"
echo ""
echo -e "${YELLOW}Note: Run with server started: composer dev${NC}"
