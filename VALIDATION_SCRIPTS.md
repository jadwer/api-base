# API Validation Scripts
## Frontend-like Testing with cURL

Este documento describe los scripts de validación de API que simulan llamadas desde un frontend.

---

## 📋 Scripts Disponibles

### 1. `validate-api-frontend.sh`
**Propósito:** Validación completa de endpoints críticos del API
**Tests:** 30+ endpoints a través de todos los módulos
**Tiempo:** ~30 segundos

**Módulos Validados:**
- ✅ Authentication (login/token)
- ✅ Products (CRUD, filters, sorting, pagination)
- ✅ Inventory (warehouses, stock, movements)
- ✅ Sales (orders, customers, filtering)
- ✅ Purchase (orders, suppliers)
- ✅ Finance (AR/AP invoices, payments, bank accounts)
- ✅ Accounting (accounts, journal entries, fiscal periods)
- ✅ Contacts (customers, suppliers, Party Pattern)
- ✅ Public Endpoints (product catalog sin auth)
- ✅ Error Handling (401, 404, 422)

**Uso:**
```bash
# Con servidor local
./validate-api-frontend.sh

# Con servidor custom
./validate-api-frontend.sh http://api.example.com
```

**Salida Esperada:**
```
=========================================
API VALIDATION - Frontend Simulation
=========================================

=== 1. AUTHENTICATION ===
Authenticating... SUCCESS

=== 2. PRODUCTS MODULE ===
Testing: List Products... PASS (Status: 200)
Testing: Filter Products by Category... PASS (Status: 200)
...

=========================================
VALIDATION SUMMARY
=========================================
Total Tests:  32
Passed:       32
Failed:       0
Success Rate: 100.0%

✓ ALL TESTS PASSED - API is working correctly!
```

---

### 2. `validate-business-flows.sh`
**Propósito:** Validar flujos de negocio end-to-end
**Tests:** Order-to-Cash & Procure-to-Pay completos
**Tiempo:** ~10 segundos

**Flujos Validados:**

#### Order-to-Cash (Orden a Cobro)
```
1. Crear Customer (Contact con is_customer=true)
2. Crear Sales Order
3. Crear AR Invoice (linked to Sales Order)
4. Verificar relaciones y datos
```

#### Procure-to-Pay (Compra a Pago)
```
1. Crear Supplier (Contact con is_supplier=true)
2. Crear Purchase Order
3. Crear AP Invoice (linked to Purchase Order)
4. Verificar relaciones y datos
```

#### Accounting Integration
```
1. Verificar Chart of Accounts existe
2. Verificar Fiscal Periods activos
3. Verificar Journal Entries creados
```

**Uso:**
```bash
# Con servidor local
./validate-business-flows.sh

# Con servidor custom
./validate-business-flows.sh http://api.example.com
```

**Salida Esperada:**
```
=========================================
BUSINESS FLOWS VALIDATION
Testing: Order-to-Cash & Procure-to-Pay
=========================================

=== FLOW 1: ORDER-TO-CASH ===
1.1 Creating Customer... OK (ID: 123)
1.2 Creating Sales Order... OK (ID: 456)
1.3 Creating AR Invoice... OK (ID: 789)
1.4 Fetching AR Invoice Details... OK (Status: draft)

=== FLOW 2: PROCURE-TO-PAY ===
2.1 Creating Supplier... OK (ID: 234)
2.2 Creating Purchase Order... OK (ID: 567)
2.3 Creating AP Invoice... OK (ID: 890)

✓ BUSINESS FLOWS VALIDATION COMPLETE
```

---

## 🚀 Cómo Usar

### Prerequisitos
```bash
# Instalar jq para parsing JSON
sudo apt-get install jq  # Debian/Ubuntu
brew install jq          # macOS

# Iniciar el servidor
composer dev
# O manualmente:
php artisan serve
```

### Ejecución Rápida
```bash
# Dar permisos de ejecución (solo primera vez)
chmod +x validate-*.sh

# Correr validación completa
./validate-api-frontend.sh

# Correr flujos de negocio
./validate-business-flows.sh
```

### Uso Avanzado

#### Testing en Staging/Production
```bash
# Staging
./validate-api-frontend.sh https://api-staging.example.com

# Production (read-only tests)
./validate-api-frontend.sh https://api.example.com
```

#### Debugging con Verbose Output
```bash
# Agregar -v para ver requests completos
bash -x ./validate-api-frontend.sh
```

#### Integración en CI/CD
```yaml
# .github/workflows/api-validation.yml
- name: Validate API
  run: |
    php artisan serve &
    sleep 5
    ./validate-api-frontend.sh
    ./validate-business-flows.sh
```

---

## 📊 Interpretación de Resultados

### Status Codes Esperados

| Code | Significado | Ejemplo |
|------|-------------|---------|
| 200  | Success - GET/LIST | List products, Get invoice |
| 201  | Created - POST | Create customer, Create order |
| 204  | No Content - DELETE | Delete product |
| 401  | Unauthorized | Sin token, token inválido |
| 403  | Forbidden | Sin permisos para acción |
| 404  | Not Found | Recurso no existe |
| 422  | Validation Error | Datos inválidos en POST/PATCH |

### JSON:API Response Format
Todas las respuestas siguen JSON:API 1.1 spec:

**Success Response:**
```json
{
  "data": {
    "id": "123",
    "type": "products",
    "attributes": {
      "name": "Product Name",
      "price": 99.99
    },
    "relationships": {
      "category": {
        "data": { "type": "categories", "id": "456" }
      }
    }
  },
  "included": [...],
  "meta": { "page": {...} }
}
```

**Error Response:**
```json
{
  "errors": [
    {
      "status": "422",
      "title": "Validation Error",
      "detail": "The name field is required.",
      "source": { "pointer": "/data/attributes/name" }
    }
  ]
}
```

---

## 🔍 Troubleshooting

### Error: "Could not authenticate"
**Causa:** Credenciales incorrectas o usuario no existe
**Solución:**
```bash
# Verificar que existe el admin user
php artisan tinker
>>> \Modules\User\Models\User::where('email', 'admin@example.com')->first()

# Si no existe, correr seeders
php artisan db:seed
```

### Error: "Server not running"
**Causa:** Laravel server no está activo
**Solución:**
```bash
# Opción 1: Usar composer dev (recomendado)
composer dev

# Opción 2: Solo servidor
php artisan serve

# Verificar que está corriendo
curl http://localhost:8000
```

### Error: "jq: command not found"
**Causa:** jq no está instalado
**Solución:**
```bash
# Ubuntu/Debian
sudo apt-get update && sudo apt-get install -y jq

# macOS
brew install jq

# Verificar instalación
jq --version
```

### Tests fallan con 500 errors
**Causa:** Error en la aplicación
**Solución:**
```bash
# Ver logs de Laravel
tail -f storage/logs/laravel.log

# O correr tests de PHPUnit primero
php artisan test

# Verificar database
php artisan migrate:fresh --seed
```

---

## 🎯 Validación por Módulo

### Solo Products
```bash
curl -X GET "http://localhost:8000/api/v1/products" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/vnd.api+json" | jq
```

### Solo Finance (AR Invoices)
```bash
curl -X GET "http://localhost:8000/api/v1/ar-invoices?include=contact,salesOrder" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/vnd.api+json" | jq
```

### Solo Accounting (Chart of Accounts)
```bash
curl -X GET "http://localhost:8000/api/v1/accounts?filter[accountType]=asset&sort=code" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/vnd.api+json" | jq
```

---

## 📈 Performance Benchmarks

**Tiempos Esperados (desarrollo):**
- Authentication: < 200ms
- List endpoint (10 items): < 300ms
- List with includes: < 500ms
- Create resource: < 400ms
- Filter/Sort: < 350ms

**Tiempos Esperados (producción con cache):**
- List endpoint: < 100ms
- List with includes: < 200ms
- Create resource: < 200ms

Si los tiempos exceden significativamente estos valores, considerar:
- Indexar foreign keys
- Agregar eager loading para relationships
- Implementar caching (Redis)
- Optimizar queries N+1

---

## 🔐 Security Notes

**⚠️ IMPORTANTE:**
- **Nunca** commitear tokens reales en los scripts
- **Nunca** correr scripts destructivos en producción
- Los scripts actuales solo hacen READ operations para producción
- Para tests de WRITE operations, usar staging environment

**Configuración Segura:**
```bash
# Usar variables de entorno
export API_URL="https://api.example.com"
export API_TOKEN="your-secure-token"

# Modificar scripts para usar env vars
./validate-api-frontend.sh $API_URL
```

---

## 📝 Extensión de Scripts

### Agregar Nuevo Endpoint Test

Editar `validate-api-frontend.sh`:
```bash
# Agregar al final de la sección apropiada
test_endpoint "Test Name" "GET" "$API_URL/your-endpoint" "200" "" "$TOKEN"
```

### Agregar Nuevo Flujo de Negocio

Editar `validate-business-flows.sh`:
```bash
# Agregar nueva sección
echo "=== FLOW 4: YOUR FLOW ==="
# ... tus tests aquí
```

---

## ✅ Checklist Pre-Deploy

Antes de deployar a producción, verificar:

- [ ] `./validate-api-frontend.sh` pasa 100%
- [ ] `./validate-business-flows.sh` crea recursos exitosamente
- [ ] Tests de PHPUnit pasan: `php artisan test`
- [ ] No hay errores en logs: `storage/logs/laravel.log`
- [ ] Database migrations actualizadas: `php artisan migrate:status`
- [ ] Seeders corrieron: datos básicos existen
- [ ] Environment configurado: `.env` correcto
- [ ] CORS configurado si frontend externo
- [ ] Rate limiting configurado en producción

---

**Última Actualización:** 2025-10-28
**Mantenedor:** Sistema de Validación Automatizado
**Documentos Relacionados:** `TESTING_GUIDE.md`, `PROJECT_ACTION_PLAN.md`
