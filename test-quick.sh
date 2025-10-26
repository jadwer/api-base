#!/bin/bash
# Run only failing test types (Store + Update)
# This is the fastest way to verify validation fixes
# Expected time: ~4 minutes (vs 7 for all tests)

echo "🧪 Quick Test Run - Store + Update only"
echo "⏱️  Expected time: ~4 minutes"
echo ""

php artisan test Modules/Accounting/tests/Feature --filter="Store|Update"

echo ""
echo "✅ Quick tests completed"
