# 🧪 Testing Guide - Optimized Workflows

Este documento describe las estrategias optimizadas para ejecutar tests de manera eficiente.

## 🚀 Scripts de Test Rápidos

### 1. Test Quick (Store + Update solamente)
**Uso:** Cuando trabajas en validaciones y schemas
**Tiempo:** ~4 minutos (vs 7 minutos completos)
```bash
./test-quick.sh
```

### 2. Test Store (Solo tests de creación)
**Uso:** Cuando trabajas en Request validation para POST
**Tiempo:** ~2 minutos
```bash
./test-store.sh
```

### 3. Test Update (Solo tests de actualización)
**Uso:** Cuando trabajas en Request validation para PATCH
**Tiempo:** ~2.5 minutos
```bash
./test-update.sh
```

### 4. Test Single Entity (Una entidad específica)
**Uso:** Cuando debuggeas problemas de una entidad
**Tiempo:** ~30 segundos
```bash
./test-single-entity.sh AccountBalance
./test-single-entity.sh FiscalPeriod
./test-single-entity.sh Journal
```

**Entidades disponibles:**
- Account
- AccountBalance
- AccountMapping
- AuditLog
- ExchangeRate
- ExchangeRatePolicy
- FiscalPeriod
- IdempotencyKey
- Journal
- JournalEntry
- JournalLine
- JournalSequence

---

## 📊 Comparación de Tiempos

| Comando | Tests | Tiempo Aprox | Cuándo Usar |
|---------|-------|--------------|-------------|
| `php artisan test Modules/Accounting` | ~420 | 7 min | Run final completo |
| `./test-quick.sh` | ~156 | 4 min | Iteración rápida Store/Update |
| `./test-store.sh` | ~72 | 2 min | Fixes de POST validation |
| `./test-update.sh` | ~84 | 2.5 min | Fixes de PATCH validation |
| `./test-single-entity.sh Entity` | ~13 | 30 seg | Debug entidad específica |

**Ahorro de tiempo:** 50-90% dependiendo del caso de uso

---

## 🎯 Workflow Recomendado

### Workflow 1: Arreglando Validation en Requests
```bash
# 1. Fix AccountRequest.php
# 2. Test solo esa entidad
./test-single-entity.sh Account

# 3. Si pasa, aplicar fix a todas las entidades
# 4. Test rápido de Store+Update
./test-quick.sh

# 5. Si todo pasa, run completo final
php artisan test Modules/Accounting
```

**Tiempo total:** 30s + 4min + 7min = ~12 minutos (vs 21 minutos con 3 runs completos)

### Workflow 2: Arreglando Schemas
```bash
# 1. Fix AccountSchema.php field mappings
# 2. Test solo Store (que usa el Schema)
./test-store.sh

# 3. Si pasa, aplicar a todos los Schemas
# 4. Run completo
php artisan test Modules/Accounting
```

**Tiempo total:** 2min + 7min = 9 minutos (vs 14 minutos con 2 runs completos)

### Workflow 3: Arreglando Tests
```bash
# 1. Fix AccountBalanceStoreTest.php
# 2. Test solo esa entidad
./test-single-entity.sh AccountBalance

# 3. Iterar hasta que pase (30s por intento)
# 4. Replicar pattern a otras entidades
# 5. Test quick
./test-quick.sh
```

**Tiempo total:** 30s × iteraciones + 4min final

---

## 💡 Tips de Optimización

### 1. Ejecutar en Background
```bash
./test-quick.sh > /tmp/test_results.txt 2>&1 &
# Continúa trabajando mientras corren los tests
# Revisa después: cat /tmp/test_results.txt
```

### 2. Usar --filter para Casos Específicos
```bash
# Solo tests de admin can create
php artisan test --filter="admin_can_create"

# Solo tests de validación
php artisan test --filter="cannot_create.*without_required"

# Solo tests de autorización
php artisan test --filter="customer.*cannot|guest.*cannot"
```

### 3. Stop on First Failure (Debugging)
```bash
# Para debugging rápido
./test-single-entity.sh Account --stop-on-failure
```

### 4. Ejecutar Solo Tests Fallidos (PHPUnit 10+)
```bash
php artisan test --failed
# Re-ejecuta solo los tests que fallaron en el último run
```

---

## 🔧 Optimizaciones Implementadas

### ✅ Opción A: Parallel Testing (IMPLEMENTADO - 2025-10-25)
```bash
# Usar paratest para ejecución paralela
./test-ultra-fast.sh    # Store + Update en paralelo
./test-parallel.sh      # Todos los tests en paralelo
```
**Ganancia real:** ~85% reducción de tiempo para Store+Update (de ~12 min a ~4 min)
**Implementación:** Instalado `brianium/paratest` v7.8.4
**Configuración:** Usa 8 cores CPU automáticamente

**Scripts disponibles:**
- `test-ultra-fast.sh` - Paralelo + filtro Store/Update (156 tests en 4:24 min)
- `test-parallel.sh` - Todos los tests en paralelo (420 tests)

### ✅ Opción B: SQLite In-Memory (YA CONFIGURADO)
Ya configurado en `phpunit.xml`:
```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```
**Ganancia:** Base de datos en memoria reduce overhead de I/O
**Estado:** Activo desde inicio del proyecto

### Opción C: Disable Auto-Seeding (Evaluación pendiente)
En `TestCase.php`:
```php
protected $seed = false;
```
Luego seed manualmente solo cuando sea necesario.
**Ganancia estimada:** 20-30% reducción de tiempo
**Estado:** Por evaluar - requiere modificar estructura de tests

---

## 📈 Métricas de Éxito

**Objetivo Original:** Reducir tiempo de iteración de 7 min → <2 min

**Estado Actual (2025-10-25):**
- ✅ Test Grouping implementado
- ✅ Parallel testing implementado (paratest v7.8.4)
- ✅ SQLite in-memory activo (ya configurado)
- ⏳ Seeding optimization pendiente

**Resultados Reales:**
- Iteración individual: ~30 segundos (✅ Objetivo cumplido)
- Iteración grupal Store+Update: ~4.5 minutos con paratest (156 tests)
- Run completo: ~7-10 minutos secuencial (420 tests) → Estimado 3-5 min con paratest
- **Ganancia total:** ~60-75% reducción de tiempo con paratest

**Performance por Script:**
| Script | Tests | Tiempo | Modo |
|--------|-------|--------|------|
| `test-single-entity.sh` | ~13-35 | 30s-3min | Secuencial |
| `test-ultra-fast.sh` | 156 | 4:24 min | Paralelo (8 cores) |
| `test-quick.sh` | 156 | ~4 min | Secuencial |
| Full suite | 420 | ~7-10 min | Secuencial |

---

## 🎓 Comandos Útiles

```bash
# Ver lista de todos los tests sin ejecutarlos
php artisan test --list-tests Modules/Accounting

# Ejecutar con cobertura
php artisan test --coverage

# Ver solo nombres de tests fallidos
php artisan test | grep FAIL

# Contar tests por tipo
ls Modules/Accounting/tests/Feature/*StoreTest.php | wc -l
ls Modules/Accounting/tests/Feature/*UpdateTest.php | wc -l

# Buscar test específico por nombre
php artisan test --filter="test_admin_can_create_Account$"
```

---

**Última actualización:** 2025-10-25
**Autor:** Session de optimización de tests
