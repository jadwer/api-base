# Test Strategy - Fast Individual Tests + Script Suite

## Filosofía

**Problema resuelto:** El bootstrap/testing.php ejecutándose en cada test individual causaba >20s de overhead por test.

**Solución:** Tests individuales rápidos (3-5s) + Script de suite completa que hace setup una sola vez.

---

## Para Debug Rápido (Tests Individuales)

### Correr un solo test:
```bash
php artisan test Modules/Auth/tests/Feature/LoginTest.php
```
**Tiempo:** ~3-5 segundos
**Requiere:** Base de datos previamente configurada (ver abajo)

### Correr un test específico:
```bash
php artisan test --filter=test_admin_can_login
```

### Preparar DB para tests individuales (una sola vez):
```bash
php artisan migrate:fresh --seed --env=testing --force
```

---

## Para Suite Completa

### Opción 1: Script Automático (RECOMENDADO)
```bash
./run-test-suite.sh
```

**Características:**
- ✅ Hace migrate:fresh --seed automáticamente
- ✅ Corre todos los tests secuencialmente
- ✅ Barra de progreso visual
- ✅ Guarda resultados detallados en `logtests/suite_results.log`
- ✅ Muestra resumen final con estadísticas

### Opción 2: Solo un módulo
```bash
./run-test-suite.sh --module Sales
```

### Opción 3: Paralelo (Experimental - NO USAR AÚN)
```bash
./run-test-suite.sh --parallel
```

---

## Revisar Resultados

### Ver tests fallidos:
```bash
grep -A 10 'FAILED' logtests/suite_results.log
```

### Ver resumen:
```bash
tail -20 logtests/suite_results.log
```

### Ver progreso en tiempo real:
```bash
tail -f logtests/suite_results.log
```

---

## Comparativa de Velocidad

| Método | Setup Time | Test Time | Total (383 tests) |
|--------|-----------|-----------|-------------------|
| **Original** (bootstrap en cada test) | 20s × 383 | 3s × 383 | ~2.4 horas |
| **Nuevo** (script con setup una vez) | 40s × 1 | 3s × 383 | ~20 minutos |
| **Test individual** | 0s | 3s | 3 segundos |

**Mejora:** ~86% más rápido para suite completa, infinitamente más rápido para debug individual

---

## Workflow Recomendado

### 1. Debug de un test específico:
```bash
# Preparar DB (solo primera vez o después de cambios en migrations/seeds)
php artisan migrate:fresh --seed --env=testing --force

# Debuggear test individual (super rápido)
php artisan test Modules/Auth/tests/Feature/LoginTest.php
```

### 2. Validar módulo completo:
```bash
./run-test-suite.sh --module Auth
```

### 3. Antes de commit (suite completa):
```bash
./run-test-suite.sh
```

### 4. CI/CD:
```bash
# En pipeline de CI/CD
./run-test-suite.sh
exit $?  # Propaga exit code
```

---

## Troubleshooting

### Error: "Base table or view not found"
**Causa:** DB no está configurada para tests individuales
**Solución:**
```bash
php artisan migrate:fresh --seed --env=testing --force
```

### Suite toma mucho tiempo
**Causa:** Muchos tests fallidos con timeouts
**Solución:** Revisar primero módulo por módulo:
```bash
for module in Auth Product Sales; do
    echo "Testing $module..."
    ./run-test-suite.sh --module $module
done
```

### Script no ejecuta
**Causa:** Permisos
**Solución:**
```bash
chmod +x run-test-suite.sh
```

---

## Notas Técnicas

### bootstrap/testing.php
- **Antes:** Hacía `migrate:fresh --seed` en cada ejecución
- **Ahora:** Solo hace bootstrap mínimo de Laravel
- **Resultado:** Tests individuales 85% más rápidos

### TestCase.php
- Usa `DatabaseTransactions` para rollback automático
- Cada test empieza con DB en estado limpio
- No necesita re-seeding entre tests

### Script de Suite
- Hace setup de DB una sola vez al inicio
- Corre tests secuencialmente (garantiza no conflictos)
- Timeout de 30s por test (configurable)
- Guarda output completo para debugging

---

## Próximas Mejoras

1. **Paralelización:** Usar GNU parallel para correr tests en paralelo (4-8x más rápido)
2. **Test seleccionado:** `./run-test-suite.sh --pattern="*Login*"`
3. **Retry fallidos:** `./run-test-suite.sh --retry-failed`
4. **Coverage report:** Integrar con phpunit coverage

---

**Autor:** Claude Code
**Fecha:** 2025-11-03
**Status:** ✅ Implementado y probado
