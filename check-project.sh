#!/bin/bash
# Script de verificación rápida del proyecto
# Ejecutar con: ./check-project.sh

echo "🔍 Verificando estado del proyecto..."
echo ""

echo "📂 Rama actual:"
git branch --show-current
echo ""

echo "📊 Estado de Git:"
git status --porcelain
echo ""

echo "🧪 Tests críticos:"
php artisan test --filter=ProductBatch | tail -3
echo ""

echo "📝 Archivos críticos:"
if [ -s "Modules/Inventory/app/Http/Controllers/Api/V1/ProductBatchController.php" ]; then
    echo "✅ ProductBatchController.php - OK"
else
    echo "❌ ProductBatchController.php - VACÍO O FALTANTE"
fi

if [ -s "Modules/Inventory/tests/Feature/ProductBatchIndexTest.php" ]; then
    echo "✅ ProductBatchIndexTest.php - OK"
else
    echo "❌ ProductBatchIndexTest.php - VACÍO O FALTANTE"
fi

if [ -s "Modules/Inventory/app/Models/ProductBatch.php" ]; then
    echo "✅ ProductBatch.php Model - OK"
else
    echo "❌ ProductBatch.php Model - VACÍO O FALTANTE"
fi

echo ""
echo "✅ Verificación completa"
