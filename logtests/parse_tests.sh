#!/bin/bash

LOG_FILE="logtests/tests.log"
OUTPUT_FILE="logtests/tests_status.log"

echo "=====================================" > "$OUTPUT_FILE"
echo "RESUMEN DE TESTS - $(date)" >> "$OUTPUT_FILE"
echo "=====================================" >> "$OUTPUT_FILE"
echo "" >> "$OUTPUT_FILE"

# Extraer archivos de test únicos
echo "=== ARCHIVOS DE TEST EJECUTADOS ===" >> "$OUTPUT_FILE"
grep -E "^   (PASS|FAIL)" "$LOG_FILE" | awk '{print $2}' | sort -u > /tmp/test_files.txt
TEST_FILES_COUNT=$(wc -l < /tmp/test_files.txt)
echo "Total de archivos de test: $TEST_FILES_COUNT" >> "$OUTPUT_FILE"
echo "" >> "$OUTPUT_FILE"
cat /tmp/test_files.txt >> "$OUTPUT_FILE"
echo "" >> "$OUTPUT_FILE"

# Contar tests pasados y fallados
echo "=== ESTADÍSTICAS GENERALES ===" >> "$OUTPUT_FILE"
PASSED_COUNT=$(grep -c "✓" "$LOG_FILE" 2>/dev/null || echo "0")
FAILED_COUNT=$(grep -c "⨯" "$LOG_FILE" 2>/dev/null || echo "0")
TOTAL_TESTS=$((PASSED_COUNT + FAILED_COUNT))

echo "Tests Pasados: $PASSED_COUNT" >> "$OUTPUT_FILE"
echo "Tests Fallados: $FAILED_COUNT" >> "$OUTPUT_FILE"
echo "Total de Tests: $TOTAL_TESTS" >> "$OUTPUT_FILE"

if [ $TOTAL_TESTS -gt 0 ]; then
    SUCCESS_RATE=$(awk "BEGIN {printf \"%.2f\", ($PASSED_COUNT/$TOTAL_TESTS)*100}")
    echo "Tasa de Éxito: ${SUCCESS_RATE}%" >> "$OUTPUT_FILE"
fi
echo "" >> "$OUTPUT_FILE"

# Extraer tests pasados
echo "=== TESTS PASADOS ($PASSED_COUNT) ===" >> "$OUTPUT_FILE"
grep "✓" "$LOG_FILE" | head -100 >> "$OUTPUT_FILE"
if [ $PASSED_COUNT -gt 100 ]; then
    echo "... y $((PASSED_COUNT - 100)) tests más" >> "$OUTPUT_FILE"
fi
echo "" >> "$OUTPUT_FILE"

# Extraer tests fallados
echo "=== TESTS FALLADOS ($FAILED_COUNT) ===" >> "$OUTPUT_FILE"
grep "⨯" "$LOG_FILE" >> "$OUTPUT_FILE"
echo "" >> "$OUTPUT_FILE"

# Extraer resumen final
echo "=== RESUMEN FINAL ===" >> "$OUTPUT_FILE"
grep -A 5 "Tests:" "$LOG_FILE" | tail -20 >> "$OUTPUT_FILE"

echo "Reporte generado exitosamente en: $OUTPUT_FILE"
