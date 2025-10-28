#!/bin/bash

# API Validation Script - Frontend Simulation
# Tests critical endpoints as if frontend were calling them
# Usage: ./validate-api-frontend.sh [base_url]

BASE_URL="${1:-http://localhost:8000}"
API_URL="$BASE_URL/api/v1"
AUTH_URL="$BASE_URL/api/auth"

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Counters
TOTAL=0
PASSED=0
FAILED=0

# Test results array
declare -a RESULTS

test_endpoint() {
    local name="$1"
    local method="$2"
    local url="$3"
    local expected_status="$4"
    local data="$5"
    local token="$6"

    TOTAL=$((TOTAL + 1))

    echo -n "Testing: $name... "

    # Build curl command
    local curl_cmd="curl -s -w '\n%{http_code}' -X $method '$url'"

    if [ -n "$token" ]; then
        curl_cmd="$curl_cmd -H 'Authorization: Bearer $token'"
    fi

    curl_cmd="$curl_cmd -H 'Content-Type: application/vnd.api+json' -H 'Accept: application/vnd.api+json'"

    if [ -n "$data" ]; then
        curl_cmd="$curl_cmd -d '$data'"
    fi

    # Execute request
    response=$(eval $curl_cmd)
    status_code=$(echo "$response" | tail -n1)
    body=$(echo "$response" | head -n-1)

    # Check status code
    if [ "$status_code" = "$expected_status" ]; then
        echo -e "${GREEN}PASS${NC} (Status: $status_code)"
        PASSED=$((PASSED + 1))
        RESULTS+=("✓ $name")
    else
        echo -e "${RED}FAIL${NC} (Expected: $expected_status, Got: $status_code)"
        FAILED=$((FAILED + 1))
        RESULTS+=("✗ $name (Expected: $expected_status, Got: $status_code)")

        # Show error details if available
        if echo "$body" | jq -e '.errors' > /dev/null 2>&1; then
            echo "  Error: $(echo "$body" | jq -r '.errors[0].detail // .errors[0].title')"
        fi
    fi
}

echo "========================================="
echo "API VALIDATION - Frontend Simulation"
echo "Base URL: $BASE_URL"
echo "========================================="
echo ""

# === STEP 1: Authentication ===
echo "=== 1. AUTHENTICATION ==="

# Login to get token
LOGIN_DATA='{
    "email": "admin@example.com",
    "password": "secureadmin"
}'

echo -n "Authenticating... "
LOGIN_RESPONSE=$(curl -s -X POST "$AUTH_URL/login" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -d "$LOGIN_DATA")

TOKEN=$(echo "$LOGIN_RESPONSE" | jq -r '.access_token // empty')

if [ -z "$TOKEN" ]; then
    echo -e "${RED}FAILED${NC}"
    echo "Could not authenticate. Response:"
    echo "$LOGIN_RESPONSE" | jq '.'
    exit 1
fi

echo -e "${GREEN}SUCCESS${NC}"
echo "Token: ${TOKEN:0:20}..."
echo ""

# === STEP 2: Products Module ===
echo "=== 2. PRODUCTS MODULE ==="
test_endpoint "List Products" "GET" "$API_URL/products" "200" "" "$TOKEN"
test_endpoint "Filter Products by Category" "GET" "$API_URL/products?filter%5Bcategories%5D=1" "200" "" "$TOKEN"
test_endpoint "Sort Products by Name" "GET" "$API_URL/products?sort=name" "200" "" "$TOKEN"
test_endpoint "Products Pagination" "GET" "$API_URL/products?page%5Bsize%5D=10&page%5Bnumber%5D=1" "200" "" "$TOKEN"
echo ""

# === STEP 3: Inventory Module ===
echo "=== 3. INVENTORY MODULE ==="
test_endpoint "List Warehouses" "GET" "$API_URL/warehouses" "200" "" "$TOKEN"
test_endpoint "List Stock Levels" "GET" "$API_URL/stocks" "200" "" "$TOKEN"
test_endpoint "Stock with Products" "GET" "$API_URL/stocks?include=product" "200" "" "$TOKEN"
echo ""

# === STEP 4: Sales Module ===
echo "=== 4. SALES MODULE ==="
test_endpoint "List Sales Orders" "GET" "$API_URL/sales-orders" "200" "" "$TOKEN"
test_endpoint "Filter Sales Orders by Status" "GET" "$API_URL/sales-orders?filter%5Bstatus%5D=draft" "200" "" "$TOKEN"
test_endpoint "Sales Orders with Customer" "GET" "$API_URL/sales-orders?include=contact" "200" "" "$TOKEN"
echo ""

# === STEP 5: Purchase Module ===
echo "=== 5. PURCHASE MODULE ==="
test_endpoint "List Purchase Orders" "GET" "$API_URL/purchase-orders" "200" "" "$TOKEN"
test_endpoint "Purchase Orders with Supplier" "GET" "$API_URL/purchase-orders?include=contact" "200" "" "$TOKEN"
echo ""

# === STEP 6: Finance Module ===
echo "=== 6. FINANCE MODULE ==="
test_endpoint "List AR Invoices" "GET" "$API_URL/ar-invoices" "200" "" "$TOKEN"
test_endpoint "List AP Invoices" "GET" "$API_URL/ap-invoices" "200" "" "$TOKEN"
test_endpoint "List Payments" "GET" "$API_URL/payments" "200" "" "$TOKEN"
test_endpoint "List Payment Methods" "GET" "$API_URL/payment-methods" "200" "" "$TOKEN"
test_endpoint "List Bank Accounts" "GET" "$API_URL/bank-accounts" "200" "" "$TOKEN"
echo ""

# === STEP 7: Accounting Module ===
echo "=== 7. ACCOUNTING MODULE ==="
test_endpoint "List Accounts" "GET" "$API_URL/accounts" "200" "" "$TOKEN"
test_endpoint "List Journal Entries" "GET" "$API_URL/journal-entries" "200" "" "$TOKEN"
test_endpoint "List Fiscal Periods" "GET" "$API_URL/fiscal-periods" "200" "" "$TOKEN"
test_endpoint "Filter Accounts by Type" "GET" "$API_URL/accounts?filter%5BaccountType%5D=asset" "200" "" "$TOKEN"
echo ""

# === STEP 8: Contacts Module ===
echo "=== 8. CONTACTS MODULE ==="
test_endpoint "List Contacts" "GET" "$API_URL/contacts" "200" "" "$TOKEN"
test_endpoint "Filter Customers" "GET" "$API_URL/contacts?filter%5BisCustomer%5D=true" "200" "" "$TOKEN"
test_endpoint "Filter Suppliers" "GET" "$API_URL/contacts?filter%5BisSupplier%5D=true" "200" "" "$TOKEN"
echo ""

# === STEP 9: Public Endpoints (No Auth - should return 401) ===
echo "=== 9. AUTHENTICATION REQUIRED ==="
test_endpoint "Products Require Auth" "GET" "$API_URL/products" "401" "" ""
test_endpoint "Search Requires Auth" "GET" "$API_URL/contacts?filter%5Bsearch%5D=test" "401" "" ""
echo ""

# === STEP 10: Error Handling ===
echo "=== 10. ERROR HANDLING ==="
test_endpoint "401 Unauthorized" "GET" "$API_URL/products" "401" "" ""
test_endpoint "404 Not Found" "GET" "$API_URL/products/999999" "404" "" "$TOKEN"
test_endpoint "422 Invalid Data" "POST" "$API_URL/products" "422" '{"data":{"type":"products","attributes":{}}}' "$TOKEN"
echo ""

# === SUMMARY ===
echo "========================================="
echo "VALIDATION SUMMARY"
echo "========================================="
echo -e "Total Tests:  ${TOTAL}"
echo -e "Passed:       ${GREEN}${PASSED}${NC}"
echo -e "Failed:       ${RED}${FAILED}${NC}"
echo -e "Success Rate: $(awk "BEGIN {printf \"%.1f%%\", ($PASSED/$TOTAL)*100}")"
echo ""

echo "Detailed Results:"
for result in "${RESULTS[@]}"; do
    echo "  $result"
done
echo ""

if [ $FAILED -eq 0 ]; then
    echo -e "${GREEN}✓ ALL TESTS PASSED - API is working correctly!${NC}"
    exit 0
else
    echo -e "${RED}✗ Some tests failed. Please check the logs above.${NC}"
    exit 1
fi
