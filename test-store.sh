#!/bin/bash
# Run only Store tests for Accounting module
# Expected time: ~2 minutes (vs 7 minutes for all tests)

echo "🧪 Running Store Tests only..."
php artisan test Modules/Accounting/tests/Feature --filter=Store

echo ""
echo "✅ Store tests completed"
