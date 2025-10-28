#!/bin/bash

###############################################################################
# k6 Load Testing Runner Script
#
# Usage:
#   ./run-tests.sh [smoke|load|stress|all]
#
# Examples:
#   ./run-tests.sh smoke       # Run smoke test only
#   ./run-tests.sh all         # Run all tests in sequence
###############################################################################

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
API_URL=${API_URL:-"http://localhost:8000"}
API_EMAIL=${API_EMAIL:-"admin@example.com"}
API_PASSWORD=${API_PASSWORD:-"secureadmin"}

# Results directory
RESULTS_DIR="results"
mkdir -p "$RESULTS_DIR"

###############################################################################
# Functions
###############################################################################

print_header() {
    echo -e "${BLUE}════════════════════════════════════════════════════════${NC}"
    echo -e "${BLUE}  $1${NC}"
    echo -e "${BLUE}════════════════════════════════════════════════════════${NC}"
    echo ""
}

print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

print_info() {
    echo -e "${YELLOW}ℹ️  $1${NC}"
}

# Check if k6 is installed
check_k6() {
    if ! command -v k6 &> /dev/null; then
        print_error "k6 is not installed"
        echo ""
        echo "Install k6:"
        echo "  Linux: sudo apt-get install k6"
        echo "  Mac: brew install k6"
        echo "  Docker: docker pull grafana/k6"
        echo ""
        echo "For more info: https://k6.io/docs/getting-started/installation/"
        exit 1
    fi
    print_success "k6 is installed ($(k6 version --quiet))"
}

# Check if API is running
check_api() {
    print_info "Checking API availability at $API_URL..."

    if curl -s --max-time 5 "$API_URL/up" > /dev/null; then
        print_success "API is reachable"
    else
        print_error "API is not reachable at $API_URL"
        echo ""
        echo "Start the API server:"
        echo "  php artisan serve"
        echo "  composer dev"
        exit 1
    fi
}

# Get authentication token
get_token() {
    print_info "Getting authentication token..."

    local response
    response=$(curl -s -X POST "$API_URL/api/auth/login" \
        -H "Content-Type: application/json" \
        -d "{\"email\":\"$API_EMAIL\",\"password\":\"$API_PASSWORD\"}" || echo "")

    # Try to extract token (works with both {token:"..."} and {data:{attributes:{token:"..."}}} formats)
    export API_TOKEN=$(echo "$response" | grep -o '"token":"[^"]*"' | cut -d'"' -f4)

    if [ -z "$API_TOKEN" ]; then
        print_error "Failed to get authentication token"
        echo ""
        echo "Response: $response"
        echo ""
        echo "Check credentials:"
        echo "  Email: $API_EMAIL"
        echo "  Password: $API_PASSWORD"
        exit 1
    fi

    print_success "Authentication token obtained"
}

# Run smoke test
run_smoke() {
    print_header "Running Smoke Test (1 minute)"

    k6 run \
        --out json="$RESULTS_DIR/smoke-test-$(date +%Y%m%d-%H%M%S).json" \
        smoke-test.js

    if [ $? -eq 0 ]; then
        print_success "Smoke test passed"
        return 0
    else
        print_error "Smoke test failed"
        return 1
    fi
}

# Run load test
run_load() {
    print_header "Running Load Test (9 minutes)"

    k6 run \
        --out json="$RESULTS_DIR/load-test-$(date +%Y%m%d-%H%M%S).json" \
        load-test.js

    if [ $? -eq 0 ]; then
        print_success "Load test passed"
        return 0
    else
        print_error "Load test failed"
        return 1
    fi
}

# Run stress test
run_stress() {
    print_header "Running Stress Test (14 minutes)"

    print_info "This test will push the system to its limits"
    print_info "Monitor system resources during execution"
    echo ""

    k6 run \
        --out json="$RESULTS_DIR/stress-test-$(date +%Y%m%d-%H%M%S).json" \
        stress-test.js

    if [ $? -eq 0 ]; then
        print_success "Stress test completed"
        return 0
    else
        print_error "Stress test showed system degradation"
        return 1
    fi
}

# Display results summary
show_results() {
    print_header "Test Results Summary"

    echo "Results saved in: $RESULTS_DIR/"
    echo ""

    # Find latest result files
    local latest_smoke=$(ls -t "$RESULTS_DIR"/smoke-test-*.json 2>/dev/null | head -1)
    local latest_load=$(ls -t "$RESULTS_DIR"/load-test-*.json 2>/dev/null | head -1)
    local latest_stress=$(ls -t "$RESULTS_DIR"/stress-test-*.json 2>/dev/null | head -1)

    if [ -n "$latest_smoke" ]; then
        echo "Smoke Test:  $latest_smoke"
    fi
    if [ -n "$latest_load" ]; then
        echo "Load Test:   $latest_load"
    fi
    if [ -n "$latest_stress" ]; then
        echo "Stress Test: $latest_stress"
    fi

    echo ""
    print_info "Use 'jq' to analyze JSON results:"
    echo "  jq '.metrics.http_req_duration' $RESULTS_DIR/load-test-*.json"
}

###############################################################################
# Main
###############################################################################

# Change to script directory
cd "$(dirname "$0")"

print_header "k6 Load Testing Suite"

# Pre-flight checks
check_k6
check_api
get_token

echo ""
print_info "Configuration:"
echo "  API URL: $API_URL"
echo "  Token: ${API_TOKEN:0:20}..."
echo ""

# Determine what to run
TEST_TYPE=${1:-"smoke"}

case "$TEST_TYPE" in
    smoke)
        run_smoke
        show_results
        ;;
    load)
        run_load
        show_results
        ;;
    stress)
        run_stress
        show_results
        ;;
    all)
        print_info "Running all tests in sequence..."
        echo ""

        # Run smoke first (fail fast)
        if ! run_smoke; then
            print_error "Smoke test failed. Stopping."
            exit 1
        fi

        echo ""
        sleep 5  # Brief pause between tests

        # Run load test
        if ! run_load; then
            print_error "Load test failed. Skipping stress test."
            show_results
            exit 1
        fi

        echo ""
        sleep 10  # Longer pause before stress test

        # Run stress test
        run_stress
        show_results
        ;;
    *)
        print_error "Unknown test type: $TEST_TYPE"
        echo ""
        echo "Usage: $0 [smoke|load|stress|all]"
        echo ""
        echo "Test Types:"
        echo "  smoke  - Quick sanity check (1 min)"
        echo "  load   - Normal load test (9 min)"
        echo "  stress - Find breaking point (14 min)"
        echo "  all    - Run all tests in sequence (~25 min)"
        exit 1
        ;;
esac

print_header "Testing Complete"

exit 0
