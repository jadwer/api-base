#!/bin/bash

echo "═══════════════════════════════════════════════════"
echo "📦 MODULE 1/6: ECOMMERCE"
echo "═══════════════════════════════════════════════════"
php artisan test Modules/Ecommerce/tests/Feature/
echo ""
echo "✅ Ecommerce completed"
echo ""

echo "═══════════════════════════════════════════════════"
echo "📦 MODULE 2/6: SALES"
echo "═══════════════════════════════════════════════════"
php artisan test Modules/Sales/tests/Feature/
echo ""
echo "✅ Sales completed"
echo ""

echo "═══════════════════════════════════════════════════"
echo "📦 MODULE 3/6: HR"
echo "═══════════════════════════════════════════════════"
php artisan test Modules/HR/tests/Feature/
echo ""
echo "✅ HR completed"
echo ""

echo "═══════════════════════════════════════════════════"
echo "📦 MODULE 4/6: CRM"
echo "═══════════════════════════════════════════════════"
php artisan test Modules/CRM/tests/Feature/
echo ""
echo "✅ CRM completed"
echo ""

echo "═══════════════════════════════════════════════════"
echo "📦 MODULE 5/6: FINANCE"
echo "═══════════════════════════════════════════════════"
php artisan test Modules/Finance/tests/Feature/
echo ""
echo "✅ Finance completed"
echo ""

echo "═══════════════════════════════════════════════════"
echo "📦 MODULE 6/6: BILLING"
echo "═══════════════════════════════════════════════════"
php artisan test Modules/Billing/tests/Feature/
echo ""
echo "✅ Billing completed"
echo ""

echo "═══════════════════════════════════════════════════"
echo "🎉 ALL TESTS COMPLETED"
echo "═══════════════════════════════════════════════════"
