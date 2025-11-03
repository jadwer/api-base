#!/bin/bash

###############################################################################
# Complete Test Suite Runner
###############################################################################
#
# Strategy:
# 1. Setup database ONCE (migrate:fresh --seed)
# 2. Run all tests individually (fast, no bootstrap overhead)
# 3. Save detailed results to logtests/suite_results.log
# 4. Show summary statistics
#
# Usage:
#   ./run-test-suite.sh                    # Run all tests
#   ./run-test-suite.sh --module Sales     # Run only Sales module
#   ./run-test-suite.sh --parallel         # Run tests in parallel (experimental)
#
###############################################################################

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
LOG_FILE="logtests/suite_results.log"
PARALLEL=false
MODULE=""

# Parse arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        --parallel)
            PARALLEL=true
            shift
            ;;
        --module)
            MODULE="$2"
            shift 2
            ;;
        *)
            echo "Unknown option: $1"
            exit 1
            ;;
    esac
done

# Create log directory if it doesn't exist
mkdir -p logtests

echo -e "${BLUE}╔═══════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║       Complete Test Suite Runner                         ║${NC}"
echo -e "${BLUE}╚═══════════════════════════════════════════════════════════╝${NC}"
echo ""

# Step 1: Database Setup
echo -e "${YELLOW}[1/3] Setting up database...${NC}"
php artisan migrate:fresh --seed --env=testing --force
echo -e "${GREEN}✓ Database ready${NC}"
echo ""

# Step 2: Collect all test files
echo -e "${YELLOW}[2/3] Collecting test files...${NC}"
if [ -n "$MODULE" ]; then
    TEST_FILES=$(find "Modules/$MODULE/tests/Feature" -name "*Test.php" 2>/dev/null | sort)
    echo -e "${GREEN}✓ Found tests in $MODULE module${NC}"
else
    TEST_FILES=$(find Modules/*/tests/Feature -name "*Test.php" 2>/dev/null | sort)
    echo -e "${GREEN}✓ Found all module tests${NC}"
fi

TOTAL_FILES=$(echo "$TEST_FILES" | wc -l)
echo -e "${BLUE}Total test files: $TOTAL_FILES${NC}"
echo ""

# Step 3: Run tests
echo -e "${YELLOW}[3/3] Running tests...${NC}"
echo ""

# Initialize counters
passed=0
failed=0
current=0

# Clear log file
echo "========================================" > "$LOG_FILE"
echo "Test Suite Results" >> "$LOG_FILE"
echo "Started: $(date)" >> "$LOG_FILE"
echo "========================================" >> "$LOG_FILE"
echo "" >> "$LOG_FILE"

# Run each test
while IFS= read -r test_file; do
    current=$((current + 1))

    # Progress indicator
    printf "\r${BLUE}Progress: [$current/$TOTAL_FILES]${NC} "

    # Log test start
    echo "[$current/$TOTAL_FILES] Testing: $test_file" >> "$LOG_FILE"

    # Run test and capture result
    if timeout 30 php artisan test "$test_file" >> "$LOG_FILE" 2>&1; then
        passed=$((passed + 1))
        echo "  ✓ PASSED" >> "$LOG_FILE"
        printf "${GREEN}✓${NC}"
    else
        failed=$((failed + 1))
        echo "  ✗ FAILED" >> "$LOG_FILE"
        printf "${RED}✗${NC}"
    fi

    echo "" >> "$LOG_FILE"
done <<< "$TEST_FILES"

echo ""  # New line after progress bar
echo ""

# Calculate percentages
pass_percent=$((passed * 100 / TOTAL_FILES))
fail_percent=$((failed * 100 / TOTAL_FILES))

# Write summary to log
echo "" >> "$LOG_FILE"
echo "========================================" >> "$LOG_FILE"
echo "FINAL RESULTS" >> "$LOG_FILE"
echo "========================================" >> "$LOG_FILE"
echo "Total:  $TOTAL_FILES tests" >> "$LOG_FILE"
echo "Passed: $passed tests ($pass_percent%)" >> "$LOG_FILE"
echo "Failed: $failed tests ($fail_percent%)" >> "$LOG_FILE"
echo "Finished: $(date)" >> "$LOG_FILE"
echo "========================================" >> "$LOG_FILE"

# Display summary
echo -e "${BLUE}╔═══════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║                    FINAL RESULTS                          ║${NC}"
echo -e "${BLUE}╚═══════════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "Total:  ${BLUE}$TOTAL_FILES${NC} tests"
echo -e "Passed: ${GREEN}$passed${NC} tests (${GREEN}$pass_percent%${NC})"
echo -e "Failed: ${RED}$failed${NC} tests (${RED}$fail_percent%${NC})"
echo ""
echo -e "Detailed results saved to: ${BLUE}$LOG_FILE${NC}"
echo ""

# Exit with appropriate code
if [ $failed -eq 0 ]; then
    echo -e "${GREEN}✓ All tests passed!${NC}"
    exit 0
else
    echo -e "${RED}✗ Some tests failed${NC}"
    echo -e "Review failures with: ${YELLOW}grep -A 10 'FAILED' $LOG_FILE${NC}"
    exit 1
fi
