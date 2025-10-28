#!/bin/bash

# Performance Baseline Script
# Measures current API performance metrics
# Usage: ./performance-baseline.sh [base_url]

BASE_URL="${1:-http://localhost:8000}"
API_URL="$BASE_URL/api/v1"
AUTH_URL="$BASE_URL/api/auth"

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Output file
OUTPUT_FILE="docs/performance/BASELINE_METRICS_$(date +%Y%m%d_%H%M%S).md"

echo "========================================="
echo "PERFORMANCE BASELINE MEASUREMENT"
echo "Date: $(date)"
echo "========================================="
echo ""

# Authenticate
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

# Function to measure endpoint performance
measure_endpoint() {
    local name="$1"
    local url="$2"
    local token="$3"

    echo -n "Measuring: $name... "

    # Perform 10 requests and measure times
    local total_time=0
    local times=()

    for i in {1..10}; do
        local time_ms=$(curl -s -w "%{time_total}" -o /dev/null \
            -H "Authorization: Bearer $token" \
            -H "Accept: application/vnd.api+json" \
            "$url")

        # Convert to milliseconds
        time_ms=$(echo "$time_ms * 1000" | bc)
        times+=($time_ms)
        total_time=$(echo "$total_time + $time_ms" | bc)
    done

    # Calculate average
    local avg=$(echo "scale=2; $total_time / 10" | bc)

    # Sort times for percentile calculation
    IFS=$'\n' sorted=($(sort -n <<<"${times[*]}"))
    unset IFS

    local p50=${sorted[4]}
    local p95=${sorted[9]}

    echo -e "${GREEN}Done${NC}"
    echo "  Average: ${avg}ms | p50: ${p50}ms | p95: ${p95}ms"

    # Append to report
    echo "| $name | ${avg}ms | ${p50}ms | ${p95}ms |" >> "$OUTPUT_FILE"
}

# Create output directory
mkdir -p docs/performance

# Start report
cat > "$OUTPUT_FILE" << 'EOF'
# Performance Baseline Metrics
**Date:** $(date)
**Environment:** Development
**Server:** Laravel 12 + PHP 8.x

---

## Response Time Measurements

Each endpoint was tested 10 times. Times are in milliseconds.

| Endpoint | Average | p50 | p95 |
|----------|---------|-----|-----|
EOF

echo ""
echo -e "${BLUE}=== MEASURING API ENDPOINTS ===${NC}"
echo ""

# Product Module
echo -e "${YELLOW}Product Module:${NC}"
measure_endpoint "List Products" "$API_URL/products" "$TOKEN"
measure_endpoint "List Categories" "$API_URL/categories" "$TOKEN"
measure_endpoint "List Brands" "$API_URL/brands" "$TOKEN"

# Inventory Module
echo ""
echo -e "${YELLOW}Inventory Module:${NC}"
measure_endpoint "List Warehouses" "$API_URL/warehouses" "$TOKEN"
measure_endpoint "List Stocks" "$API_URL/stocks" "$TOKEN"

# Sales Module
echo ""
echo -e "${YELLOW}Sales Module:${NC}"
measure_endpoint "List Sales Orders" "$API_URL/sales-orders" "$TOKEN"
measure_endpoint "Sales Orders with Contact" "$API_URL/sales-orders?include=contact" "$TOKEN"

# Purchase Module
echo ""
echo -e "${YELLOW}Purchase Module:${NC}"
measure_endpoint "List Purchase Orders" "$API_URL/purchase-orders" "$TOKEN"
measure_endpoint "Purchase Orders with Contact" "$API_URL/purchase-orders?include=contact" "$TOKEN"

# Finance Module
echo ""
echo -e "${YELLOW}Finance Module:${NC}"
measure_endpoint "List AR Invoices" "$API_URL/ar-invoices" "$TOKEN"
measure_endpoint "AR Invoices with Contact" "$API_URL/ar-invoices?include=contact" "$TOKEN"
measure_endpoint "List AP Invoices" "$API_URL/ap-invoices" "$TOKEN"
measure_endpoint "List Payments" "$API_URL/payments" "$TOKEN"
measure_endpoint "List Bank Accounts" "$API_URL/bank-accounts" "$TOKEN"

# Accounting Module
echo ""
echo -e "${YELLOW}Accounting Module:${NC}"
measure_endpoint "List Accounts" "$API_URL/accounts" "$TOKEN"
measure_endpoint "List Journal Entries" "$API_URL/journal-entries" "$TOKEN"
measure_endpoint "List Fiscal Periods" "$API_URL/fiscal-periods" "$TOKEN"

# Contacts Module
echo ""
echo -e "${YELLOW}Contacts Module:${NC}"
measure_endpoint "List Contacts" "$API_URL/contacts" "$TOKEN"
measure_endpoint "Filter Customers" "$API_URL/contacts?filter%5BisCustomer%5D=true" "$TOKEN"

# Add summary to report
cat >> "$OUTPUT_FILE" << 'EOF'

---

## Database Query Analysis

Run `php artisan db:monitor` or enable Laravel Debugbar to analyze query counts.

**What to look for:**
- Endpoints with > 20 queries (likely N+1 issues)
- Slow queries (> 100ms)
- Missing indexes on foreign keys

---

## Memory Usage

Run `memory_get_peak_usage()` in controllers to measure memory consumption.

**Target:** < 128MB per request

---

## Next Steps

1. Add indexes for foreign keys and common filters
2. Implement eager loading to eliminate N+1 queries
3. Add caching for catalog endpoints (accounts, products)
4. Optimize heavy queries (aging analysis, credit checks)

---

**Baseline established:** $(date)
EOF

echo ""
echo "========================================="
echo -e "${GREEN}✓ BASELINE COMPLETE${NC}"
echo "========================================="
echo ""
echo "Report saved to: $OUTPUT_FILE"
echo ""
echo "Next steps:"
echo "1. Review the report"
echo "2. Identify slow endpoints (> 200ms p95)"
echo "3. Run database query analysis"
echo "4. Start optimization based on findings"
echo ""
