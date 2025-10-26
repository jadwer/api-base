#!/bin/bash
# Run only Update tests for Accounting module
# Expected time: ~2.5 minutes

echo "🧪 Running Update Tests only..."
php artisan test Modules/Accounting/tests/Feature --filter=Update

echo ""
echo "✅ Update tests completed"
