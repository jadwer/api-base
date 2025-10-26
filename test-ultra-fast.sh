#!/bin/bash
# Ultra-fast testing: Parallel + SQLite + Store/Update only
# Expected time: ~1-2 minutes

echo "⚡ ULTRA-FAST TEST MODE"
echo "📋 Running: Store + Update tests only"
echo "🚀 Mode: Parallel execution"
echo "💾 Database: SQLite in-memory"
echo "⏱️  Expected time: 1-2 minutes"
echo "💻 Using $(nproc) CPU cores"
echo ""

# Use paratest with directory path and filter for Store and Update tests
vendor/bin/paratest --processes=$(nproc) --filter="Store|Update" Modules/Accounting/tests/Feature

echo ""
echo "✅ Ultra-fast tests completed!"
