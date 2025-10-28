#!/bin/bash

# Simple API Validation (no jq required)
# Uses only bash and curl

BASE_URL="${1:-http://127.0.0.1:8000}"
API_URL="$BASE_URL/api/v1"
AUTH_URL="$BASE_URL/api/auth"

GREEN='\033[0;32m'
RED='\033[0;31m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m'

PASSED=0
FAILED=0
TOTAL=0

test_endpoint() {
    local name="$1"
    local method="$2"
    local url="$3"
    local expected="$4"
    local token="$5"

    TOTAL=$((TOTAL + 1))
    echo -n "Testing: $name... "

    if [ -n "$token" ]; then
        status=$(curl -s -o /dev/null -w "%{http_code}" -X $method "$url" \
            -H "Authorization: Bearer $token" \
            -H "Accept: application/vnd.api+json")
    else
        status=$(curl -s -o /dev/null -w "%{http_code}" -X $method "$url" \
            -H "Accept: application/vnd.api+json")
    fi

    if [ "$status" = "$expected" ]; then
        echo -e "${GREEN}PASS${NC} ($status)"
        PASSED=$((PASSED + 1))
    else
        echo -e "${RED}FAIL${NC} (Expected: $expected, Got: $status)"
        FAILED=$((FAILED + 1))
    fi
}

echo "========================================="
echo "API VALIDATION - Simple Mode"
echo "Base URL: $BASE_URL"
echo "========================================="
echo ""

# Get token
echo -n "Authenticating... "
LOGIN_RESPONSE=$(curl -s -X POST "$AUTH_URL/login" \
    -H "Content-Type: application/json" \
    -d '{"email":"admin@example.com","password":"secureadmin"}')

# Extract token manually (bash string manipulation)
TOKEN=$(echo "$LOGIN_RESPONSE" | grep -o '"access_token":"[^"]*"' | cut -d'"' -f4)

if [ -z "$TOKEN" ]; then
    echo -e "${RED}FAILED${NC}"
    echo "Response: $LOGIN_RESPONSE"
    exit 1
fi

echo -e "${GREEN}OK${NC}"
echo "Token: ${TOKEN:0:20}..."
echo ""

# Products Module
echo -e "${BLUE}=== PRODUCTS MODULE ===${NC}"
test_endpoint "List Products" "GET" "$API_URL/products" "200" "$TOKEN"
test_endpoint "Filter Products" "GET" "$API_URL/products?filter[name]=test" "200" "$TOKEN"
test_endpoint "Sort Products" "GET" "$API_URL/products?sort=name" "200" "$TOKEN"
test_endpoint "Paginate Products" "GET" "$API_URL/products?page[size]=5" "200" "$TOKEN"
echo ""

# Inventory Module
echo -e "${BLUE}=== INVENTORY MODULE ===${NC}"
test_endpoint "List Warehouses" "GET" "$API_URL/warehouses" "200" "$TOKEN"
test_endpoint "List Stock" "GET" "$API_URL/stock" "200" "$TOKEN"
echo ""

# Sales Module
echo -e "${BLUE}=== SALES MODULE ===${NC}"
test_endpoint "List Sales Orders" "GET" "$API_URL/sales-orders" "200" "$TOKEN"
test_endpoint "Filter by Status" "GET" "$API_URL/sales-orders?filter[status]=draft" "200" "$TOKEN"
echo ""

# Purchase Module
echo -e "${BLUE}=== PURCHASE MODULE ===${NC}"
test_endpoint "List Purchase Orders" "GET" "$API_URL/purchase-orders" "200" "$TOKEN"
echo ""

# Finance Module
echo -e "${BLUE}=== FINANCE MODULE ===${NC}"
test_endpoint "List AR Invoices" "GET" "$API_URL/ar-invoices" "200" "$TOKEN"
test_endpoint "List AP Invoices" "GET" "$API_URL/ap-invoices" "200" "$TOKEN"
test_endpoint "List Payments" "GET" "$API_URL/payments" "200" "$TOKEN"
test_endpoint "List Bank Accounts" "GET" "$API_URL/bank-accounts" "200" "$TOKEN"
test_endpoint "List Payment Methods" "GET" "$API_URL/payment-methods" "200" "$TOKEN"
echo ""

# Accounting Module
echo -e "${BLUE}=== ACCOUNTING MODULE ===${NC}"
test_endpoint "List Accounts" "GET" "$API_URL/accounts" "200" "$TOKEN"
test_endpoint "List Journal Entries" "GET" "$API_URL/journal-entries" "200" "$TOKEN"
test_endpoint "List Fiscal Periods" "GET" "$API_URL/fiscal-periods" "200" "$TOKEN"
test_endpoint "Filter Accounts" "GET" "$API_URL/accounts?filter[accountType]=asset" "200" "$TOKEN"
echo ""

# Contacts Module
echo -e "${BLUE}=== CONTACTS MODULE ===${NC}"
test_endpoint "List Contacts" "GET" "$API_URL/contacts" "200" "$TOKEN"
test_endpoint "Filter Customers" "GET" "$API_URL/contacts?filter[isCustomer]=true" "200" "$TOKEN"
test_endpoint "Filter Suppliers" "GET" "$API_URL/contacts?filter[isSupplier]=true" "200" "$TOKEN"
echo ""

# Public Endpoints
echo -e "${BLUE}=== PUBLIC ENDPOINTS ===${NC}"
test_endpoint "Public Products" "GET" "$API_URL/public/products" "200" ""
test_endpoint "Public Search" "GET" "$API_URL/public/products?filter[search]=test" "200" ""
echo ""

# Error Handling
echo -e "${BLUE}=== ERROR HANDLING ===${NC}"
test_endpoint "401 Unauthorized" "GET" "$API_URL/products" "401" ""
test_endpoint "404 Not Found" "GET" "$API_URL/products/999999" "404" "$TOKEN"
echo ""

# Summary
echo "========================================="
echo "VALIDATION SUMMARY"
echo "========================================="
echo "Total:  $TOTAL"
echo -e "Passed: ${GREEN}$PASSED${NC}"
echo -e "Failed: ${RED}$FAILED${NC}"
echo "Rate:   $(awk "BEGIN {printf \"%.1f%%\", ($PASSED/$TOTAL)*100}")"
echo ""

if [ $FAILED -eq 0 ]; then
    echo -e "${GREEN}✓ ALL TESTS PASSED!${NC}"
    exit 0
else
    echo -e "${RED}✗ Some tests failed${NC}"
    exit 1
fi
