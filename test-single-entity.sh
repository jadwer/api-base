#!/bin/bash
# Run all tests for a single entity
# Usage: ./test-single-entity.sh AccountBalance
# Expected time: ~30 seconds per entity

if [ -z "$1" ]; then
    echo "❌ Error: Debe especificar una entidad"
    echo "Uso: ./test-single-entity.sh AccountBalance"
    echo ""
    echo "Entidades disponibles:"
    echo "  - Account"
    echo "  - AccountBalance"
    echo "  - AccountMapping"
    echo "  - AuditLog"
    echo "  - ExchangeRate"
    echo "  - ExchangeRatePolicy"
    echo "  - FiscalPeriod"
    echo "  - IdempotencyKey"
    echo "  - Journal"
    echo "  - JournalEntry"
    echo "  - JournalLine"
    echo "  - JournalSequence"
    exit 1
fi

ENTITY=$1

echo "🧪 Running tests for: $ENTITY"
echo "⏱️  Expected time: ~30 seconds"
echo ""

php artisan test Modules/Accounting/tests/Feature --filter="$ENTITY"

echo ""
echo "✅ Tests for $ENTITY completed"
