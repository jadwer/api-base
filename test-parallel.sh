#!/bin/bash
# Run Accounting tests with parallel execution using paratest
# Expected time: ~10 min → 3-5 min (50-70% faster)

echo "🚀 Running Accounting tests in PARALLEL mode"
echo "⏱️  Expected time: 3-5 minutes (vs 28-30 min serial)"
echo "💻 Using $(nproc) CPU cores"
echo ""

vendor/bin/paratest --processes=$(nproc) --path=Modules/Accounting/tests/Feature

echo ""
echo "✅ Parallel tests completed!"
