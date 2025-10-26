#!/bin/bash

# Script para ejecutar test suite completo en background
# Uso: ./run_full_test_suite.sh

echo "========================================"
echo "EJECUTANDO TEST SUITE COMPLETO"
echo "========================================"
echo "Fecha inicio: $(date)"
echo ""

# Limpiar archivos de tests anteriores
rm -f /tmp/full_test_suite_*.txt

# Timestamp para el archivo
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
OUTPUT_FILE="/tmp/full_test_suite_${TIMESTAMP}.txt"

echo "Archivo de salida: $OUTPUT_FILE"
echo "Ejecutando tests..."
echo ""

# Ejecutar tests con output detallado
php artisan test --stop-on-failure=false > "$OUTPUT_FILE" 2>&1

# Extraer resumen
echo ""
echo "========================================"
echo "RESUMEN DE RESULTADOS"
echo "========================================"
grep "Tests:" "$OUTPUT_FILE"
echo ""
echo "Fecha fin: $(date)"
echo ""
echo "Archivo completo disponible en: $OUTPUT_FILE"
echo ""
echo "Para ver resultados:"
echo "  tail -100 $OUTPUT_FILE"
echo "  grep 'Tests:' $OUTPUT_FILE"
echo "  grep -E 'FAILED|Failed' $OUTPUT_FILE | head -50"
