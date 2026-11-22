# CLAUDE.md

This file provides guidance to Claude Code when working with this repository.

## CRITICAL RULES

### Git Commits
- **NEVER make commits automatically** - Always provide commit message text for manual execution
- NO emojis in commit messages
- NO "Generated with Claude Code" credits
- NO "Co-Authored-By: Claude" footers

### Module Policy
- **NEVER regenerate modules** unless explicitly requested
- Prefer modification over regeneration for working modules

---

## Project Status (2026-11-22)

- Plantilla Laravel 12 del ERP (LaborWasser y AlphaLab corren sobre ella). 23 modulos,
  1,002 rutas de API, 603 archivos de prueba (4,298 pruebas en verde el 18-11).
- Rama de trabajo: `feat/rediseno-cotizaciones`. `refactor/wp-style`, `master` y `lwm` son
  historicas: no se trabaja ni se despliega de ahi.
- Rumbo, estado de prod y pendientes: `../ROADMAP.md`, `../PROMPT.md` y
  `../roadmap/pendientes.yaml` (carpeta `base/` del workspace, fuera de este repo).
  Lo comercial opera; facturacion, finanzas y contabilidad existen pero nunca se usaron con
  datos reales y no se terminan aqui (pasan a AtomoPlatform).
- Es plantilla: `tests/Feature/NoHardcodedBrandTest` prohibe nombres de marca en Modules,
  app, database, resources y config, incluidos comentarios.
- Leccion de la auditoria de 2026-07: las pruebas con `Event::fake()` escondieron que el
  ciclo comercial estaba roto. Las pruebas de flujo se escriben por invariante de negocio,
  sin fingir eventos.

---

## Key Documentation

| Document | Purpose |
|----------|---------|
| `../docs/RUNBOOK_RELEASE.md` | Release, rollback y gotchas del servidor (PHP ea-php84) |
| `docs/module-generator.md` | Generador de modulos (`module:generate`) |
| `docs/architecture/BUSINESS_RULES_COMPLETE.md` | Reglas de negocio, corregidas con la auditoria |
| `tests/Feature/Contracts/` | Contratos JSON:API (el Schema es la unica fuente de la salida) |

El esquema de la base se lee de las migraciones y de `php artisan model:show`; no hay
documento de esquema vigente.

---

## Tech Stack

- **Framework:** Laravel 12
- **API:** JSON:API 5.x (laravel-json-api/laravel)
- **Modules:** nwidart/laravel-modules
- **Auth:** Laravel Sanctum + Spatie Permission
- **Testing:** PHPUnit (SQLite for speed)
- **Audit:** Spatie Activity Log
- **Payments:** Stripe
- **CFDI:** SW Sapien PAC

---

## Common Commands

```bash
# Run all tests (unos 10 min; revisar uptime y avisar a AtomoPlatform antes)
php artisan test --parallel --processes=6

# Run module tests
php artisan test Modules/{Module}/

# Run specific test file
php artisan test Modules/Ecommerce/tests/Integration/OnlineSalesE2ETest.php

# Fresh database (solo local)
php artisan migrate:fresh --seeder=CleanDatabaseSeeder

# Create module
php artisan module:generate {Name} --config=config/modules/{name}.json

# Generate API docs
php artisan scribe:generate
```

---

## Test Fix Workflow

When a test fails, check in this order:

```
1. Model      -> fillable, casts, relationships
2. Migration  -> columns match model
3. Schema     -> fields(), filters(), pagination()
4. Request    -> validation rules
5. Authorizer -> 10 methods, permissions (plural)
6. Factory    -> generates valid data
7. Seed       -> permissions created
8. Routes     -> resource registered
9. Server.php -> schema + authorizer uncommented
10. Tests     -> data format matches schema
```

---

## Naming Conventions

| Context | Convention | Example |
|---------|------------|---------|
| Models | Singular PascalCase | `SalesOrder` |
| Tables | Plural snake_case | `sales_orders` |
| Endpoints | Plural kebab-case | `/api/v1/sales-orders` |
| Permissions | `entities.action` (plural, kebab-case) | `sales-orders.store` |
| JSON:API fields | camelCase | `createdAt`, `salesOrderId` |
| Database columns | snake_case | `created_at`, `sales_order_id` |

---

## Database Conventions

- Use `float` cast for decimals (not `decimal`)
- JSON fields use `ArrayHash` with associative arrays
- Foreign keys use `onDelete('restrict')`
- Add indexes on filterable/sortable fields

---

## Testing Standards

- Minimum 5 test files per entity: Index, Show, Store, Update, Destroy
- Test all roles: admin, tech, customer, guest
- Use `getAdminUser()`, `getTechUser()`, `getCustomerUser()` helpers
- Use `->jsonApi()->expects()` for JSON:API assertions
- E2E tests in `tests/Integration/` directory

---

## External Integrations

### SW Sapien PAC (CFDI)
- Config: `Modules/Billing/config/config.php`
- Keys in `.env`: `SW_PAC_TOKEN`, `CFDI_*`
- Docs: `Modules/Billing/docs/PAC_INTEGRATION.md`

### Stripe
- Gateway: `Modules/Ecommerce/app/Services/Payment/StripePaymentGateway.php`
- Keys in `.env`: `STRIPE_PUBLISHABLE_KEY`, `STRIPE_SECRET_KEY`
- Config: `config/services.php`

---

## Key Services

| Service | Location | Purpose |
|---------|----------|---------|
| CheckoutService | Ecommerce | Cart -> Order flow |
| ARInvoiceService | Finance | Invoice creation with GL posting |
| BudgetControlService | Purchase | PO budget validation |
| DiscountRuleService | Sales | Automatic discount application |
| CycleCountService | Inventory | Inventory count scheduling |
| DuplicateDetectionService | Contacts | Find duplicate contacts |

---

## Event-Driven Architecture (actualizado post-refactor 2026-07)

### Key Events (camino real del ciclo)
- `SalesOrderDelivered` (tambien `SalesOrderCompleted`) -> listener unico
  `Finance\CreateARInvoiceForSalesOrder` crea la ARInvoice. Los dos listeners
  viejos se ELIMINARON (R3); no recrearlos.
- `PaymentCaptured` (Stripe, tienda) -> Sales marca la orden paid y Finance
  `CreateARInvoiceOnPaymentCaptured` crea la ARInvoice y le aplica el cobro EN
  EL PAGO (decision 2026-09-23; switch sales.auto_invoice_on_payment). Al
  entregar, el listener de entrega ve la AR y no duplica.
- `ARInvoiceFullyPaid` / `ARInvoicePaymentReversed` -> Sales marca la orden
  paid / unpaid (decision 2026-09-23): un cobro manual en CxC propaga a la OV.
- `SalesOrderCancelled` -> Finance anula factura y repone stock entregado.
- `PurchaseOrderReceived` -> Finance crea APInvoice; Inventory ya dio entrada
  al stock por tanda en `PurchaseOrder::receive()`.
- `PurchaseOrderCancelled` -> Inventory revierte stock si estaba recibida;
  Finance anula la APInvoice.
- `InventoryMovementCreated` -> posting a GL (cuentas 1108/5101/2101; importe
  desde `total_value`).

### Reglas duras del ciclo (no romper)
- `status` es readOnlyOnUpdate en Sales/Purchase/CFDI/Remision: los cambios de
  estado van por los endpoints de accion, nunca por PATCH directo.
- Movimientos de inventario del ciclo llevan `idempotency_key` natural (R1);
  las recepciones por tanda son la excepcion deliberada.
- Despliegues: `artisan event:clear` obligatorio o los listeners nuevos no corren
  (bootstrap/cache/events.php cachea los mapeos).

### Listeners Location
- `Modules/Finance/app/Listeners/`
- `Modules/Inventory/app/Listeners/`

---

## Convenciones Detalladas (Auditoría 2026-01-18)

### Authorizers - Permisos

**Regla:** El Authorizer de una entidad DEBE usar permisos con el nombre de SU PROPIA entidad, NO del padre.

```php
// CORRECTO - QuoteItemAuthorizer.php
public function index(Request $request, string $modelClass): bool|Response
{
    return $request->user()?->can('quote-items.index') ?? false;
}

// INCORRECTO - NO usar permisos del padre
return $request->user()?->can('quotes.index') ?? false;  // MAL
```

**Los 10 métodos obligatorios:**
1. `index` - Listar
2. `store` - Crear
3. `show` - Ver uno
4. `update` - Actualizar
5. `destroy` - Eliminar
6. `showRelated` - Ver relaciones
7. `showRelationship` - Ver enlaces de relaciones
8. `updateRelationship` - Actualizar relaciones
9. `attachRelationship` - Agregar a relaciones
10. `detachRelationship` - Quitar de relaciones

### Seeders de Permisos

**Regla:** Al crear un nuevo recurso, SIEMPRE agregar sus permisos al seeder correspondiente.

**Ubicaciones:**
- `Modules/{Module}/Database/seeders/{Module}AssignPermissionsSeeder.php`
- `Modules/{Module}/Database/seeders/PermissionsSeeder.php`

**Permisos estándar por entidad:**
```php
'{resource}.index',
'{resource}.show',
'{resource}.store',
'{resource}.update',
'{resource}.destroy',
```

**Permisos custom (si hay endpoints adicionales):**
```php
'billing.company-settings.upload-certificate',
'billing.company-settings.upload-key',
'billing.company-settings.test-pac',
```

### Controllers Custom - Autorización

**Regla CRÍTICA:** Todo método que reciba un modelo por route binding DEBE verificar autorización de acceso.

```php
// CORRECTO
public function applyCoupon(Request $request, ShoppingCart $shoppingCart): JsonResponse
{
    $this->authorizeCartAccess($shoppingCart);  // PRIMERO verificar acceso

    // ... resto del código
}

// Método de autorización
private function authorizeCartAccess(ShoppingCart $shoppingCart): void
{
    $user = Auth::user();

    // Admin/tech/god bypass
    if ($user && $user->hasAnyRole(['god', 'admin', 'tech'])) {
        return;
    }

    // Verificar propiedad
    if ($shoppingCart->user_id) {
        if (!$user || $shoppingCart->user_id !== $user->id) {
            abort(403, 'You do not have access to this cart');
        }
    } else {
        $sessionId = request()->input('session_id') ?? request()->session()->getId();
        if ($shoppingCart->session_id !== $sessionId) {
            abort(403, 'You do not have access to this cart');
        }
    }
}
```

### Validación con Enums

**Regla:** Siempre validar campos tipo "status" o "type" con `Rule::in()`.

```php
// CouponRequest.php
'couponType' => [
    $isUpdate ? 'sometimes' : 'required',
    'string',
    Rule::in(['percentage', 'fixed_amount', 'free_shipping'])
],
```

**Mensaje de error correspondiente:**
```php
'couponType.in' => 'El campo Type debe ser: percentage, fixed_amount o free_shipping.',
```

### Tipos de Cupón Estándar

| Tipo | Descripción | Cálculo |
|------|-------------|---------|
| `percentage` | Porcentaje de descuento | `subtotal * (value / 100)` |
| `fixed_amount` | Monto fijo | `min(value, subtotal)` |
| `free_shipping` | Envío gratis | Manejado por separado |

**Modelo Coupon:**
```php
return match ($this->type) {
    'percentage' => $cartAmount * ($this->value / 100),
    'fixed_amount' => min($this->value, $cartAmount),
    'free_shipping' => 0.00,
    default => 0.00,
};
```

### Roles y Acceso

| Rol | Acceso |
|-----|--------|
| `god` | Todos los permisos via wildcard `like '%'` |
| `admin` | CRUD completo en recursos de negocio |
| `tech` | Similar a admin, puede tener restricciones en algunos módulos |
| `customer` | Solo lectura de SUS propios datos + crear cotizaciones |

### JSON:API Campos Ocultos

**Regla:** Campos sensibles usar `->hidden()` en Schema.

```php
Str::make('pacPassword', 'pac_password')->hidden(),
Str::make('keyPassword', 'key_password')->hidden(),
```

### Estructura de Respuesta JSON Custom

```php
// Éxito con datos
return response()->json([
    'data' => $this->transformCart($cart),
    'message' => 'Operation successful'
]);

// Éxito con validación
return response()->json([
    'valid' => true,
    'discount_amount' => $discountAmount,
    'new_total' => $cart->finalTotal,
    'message' => 'Coupon applied successfully'
]);

// Error
return response()->json([
    'valid' => false,
    'error' => 'Invalid coupon code'
], 400);
```

### Gate vs Permission en Controllers

**Para endpoints custom fuera de JSON:API:**
```php
if (Gate::denies('billing.company-settings.upload-certificate')) {
    abort(403, 'No tiene permisos para subir certificados');
}
```

**Para JSON:API estándar:** Usar Authorizer

### Imports - Limpieza

**Regla:** Eliminar imports no utilizados inmediatamente.

```php
// Mantener solo lo necesario
use Modules\Ecommerce\Models\ShoppingCart;
use Modules\Ecommerce\Models\Coupon;
// NO: use Modules\Ecommerce\Models\CartItem;  // Si no se usa, eliminar
```

---

## Estándares de Testing (Auditoría 2026-01-18)

### Estructura de Archivos de Test

**Por cada entidad JSON:API crear 5 archivos mínimo:**
```
Modules/{Module}/tests/Feature/
├── {Entity}IndexTest.php      # GET /api/v1/{resources}
├── {Entity}ShowTest.php       # GET /api/v1/{resources}/{id}
├── {Entity}StoreTest.php      # POST /api/v1/{resources}
├── {Entity}UpdateTest.php     # PATCH /api/v1/{resources}/{id}
└── {Entity}DestroyTest.php    # DELETE /api/v1/{resources}/{id}
```

**Para endpoints custom, crear archivo dedicado:**
```
{Entity}{Action}Test.php       # Ej: ShoppingCartApplyCouponTest.php
```

### Patrón de Test Base

```php
<?php

namespace Modules\{Module}\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\{Module}\Tests\Traits\Seeds{Module}Module;
use Tests\TestCase;

class {Entity}IndexTest extends TestCase
{
    use RefreshDatabase, Seeds{Module}Module;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed{Module}Module();
    }

    // Tests aquí...
}
```

### Tests Mínimos por Tipo de Endpoint

#### Index (GET collection)
```php
public function test_admin_can_list_{resources}(): void
public function test_tech_can_list_{resources}(): void
public function test_customer_can_list_{resources}(): void  // si aplica
public function test_guest_cannot_list_{resources}(): void
public function test_can_paginate_{resources}(): void
public function test_can_filter_by_{field}(): void         // por cada filtro
public function test_can_sort_by_{field}(): void           // por cada sort
```

#### Show (GET single)
```php
public function test_admin_can_view_{resource}(): void
public function test_returns_404_for_nonexistent_{resource}(): void
public function test_includes_{relationship}_relationship(): void  // si aplica
public function test_sensitive_fields_excluded(): void             // si aplica
```

#### Store (POST)
```php
public function test_admin_can_create_{resource}(): void
public function test_guest_cannot_create_{resource}(): void
public function test_{field}_is_required(): void           // por cada campo required
public function test_{field}_must_be_{type}(): void        // por validaciones especiales
public function test_{field}_must_be_unique(): void        // si aplica
```

#### Update (PATCH)
```php
public function test_admin_can_update_{resource}(): void
public function test_guest_cannot_update_{resource}(): void
public function test_can_update_{field}(): void            // campos editables
public function test_uniqueness_ignores_current_record(): void  // si aplica
```

#### Destroy (DELETE)
```php
public function test_admin_can_delete_{resource}(): void
public function test_guest_cannot_delete_{resource}(): void
public function test_returns_404_for_nonexistent_{resource}(): void
```

### Tests para Endpoints Custom

```php
// Para acciones que modifican estado
public function test_admin_can_{action}_{resource}(): void
public function test_validates_{precondition}(): void      // precondiciones
public function test_changes_status_to_{new_status}(): void
public function test_sets_{timestamp}_timestamp(): void

// Para acciones con ownership
public function test_user_can_{action}_own_{resource}(): void
public function test_user_cannot_{action}_others_{resource}(): void
public function test_admin_can_{action}_any_{resource}(): void
```

### Helpers Disponibles

```php
// Obtener usuarios por rol
$admin = $this->getAdminUser();
$tech = $this->getTechUser();
$customer = $this->getCustomerUser();

// JSON:API assertions
$response->assertJsonApiError('validation');
$this->jsonApi()->expects('quotes')->get('/api/v1/quotes');

// Factories con estados
Quote::factory()->draft()->create();
Quote::factory()->sent()->create();
Quote::factory()->accepted()->create();
```

### Mocking de Servicios Externos

```php
// Stripe
$mockStripe = Mockery::mock(StripeService::class);
$mockStripe->shouldReceive('createPaymentIntent')
    ->once()
    ->andReturn($fakePaymentIntent);
$this->app->instance(StripeService::class, $mockStripe);

// PAC
$mockPac = Mockery::mock(SWPacService::class);
$mockPac->shouldReceive('isEnabled')->andReturn(true);
$mockPac->shouldReceive('getBalance')->andReturn([
    'balance' => 1000,
    'stamps_used' => 50,
    'stamps_available' => 950,
]);
$this->app->instance(SWPacService::class, $mockPac);
```

### Matriz de Cobertura Requerida

| Tipo | Tests Mínimos | Roles a Probar |
|------|---------------|----------------|
| CRUD Index | 4-8 | admin, tech, customer, guest |
| CRUD Show | 3-5 | admin, guest + 404 |
| CRUD Store | 5-10 | admin, guest + validaciones |
| CRUD Update | 4-8 | admin, guest + validaciones |
| CRUD Destroy | 3-4 | admin, guest + 404 |
| Custom Action | 4-8 | según lógica de negocio |
