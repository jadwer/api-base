# PHASE 1 - ACCOUNTING MODULE - PENDING FIXES

**CRITICAL:** Este documento contiene TODOS los problemas que deben ser resueltos antes de avanzar a Phase 3.

**Status:** Phase 1 completo al 90% - 154 tests failing (de 1,222 total)

**Última actualización:** 2025-10-24

---

## REGLA CRÍTICA

**NO AVANZAR A PHASE 3 HASTA QUE PHASE 1 Y PHASE 2 TENGAN 100% DE TESTS PASSED**

---

## Resumen Ejecutivo

### Tests Actuales
- **Total tests:** 1,222
- **Passing:** 761 (62.3%)
- **Failing:** 461 (37.7%)
- **Duration:** ~19.8 minutos (con todos los seeders)

### Distribución de Fallas por Módulo

| Módulo | Fallas | Status | Prioridad |
|--------|--------|--------|-----------|
| Accounting | 154 | ❌ NEEDS FIX | CRITICAL |
| Contacts | 105 | ✅ FIXED (seeder restored) | DONE |
| Sales | 90 | ✅ FIXED (seeder restored) | DONE |
| Ecommerce | 84 | ✅ FIXED (seeder restored) | DONE |
| Product | 72 | ✅ FIXED (seeder restored) | DONE |
| Purchase | 33 | ✅ FIXED (seeder restored) | DONE |
| Inventory | 25 | ✅ FIXED (seeder restored) | DONE |
| Audit | 8 | ✅ FIXED (seeder restored) | DONE |

### Distribución de Fallas por Tipo de Test (Accounting Module)

| Test Type | Fallas | Causa Principal |
|-----------|--------|-----------------|
| StoreTest | 160 | JSON:API field mapping (camelCase vs snake_case) |
| UpdateTest | 132 | JSON:API field mapping + validation rules |
| IndexTest | 107 | Filter implementation issues |
| ShowTest | 81 | Relationship inclusion issues |
| DestroyTest | 75 | Constraint validation issues |

---

## PROBLEMA 1: JSON:API Field Mapping (StoreTest/UpdateTest) - 292 failures

### Descripción
Los tests de Store y Update fallan porque los campos requeridos no están siendo mapeados correctamente entre JSON:API (camelCase) y la base de datos (snake_case).

### Ejemplo de Error
```json
{
    "errors": [
        {
            "detail": "El campo Company id es obligatorio.",
            "source": {"pointer": "/data"},
            "status": "422"
        },
        {
            "detail": "El campo Account type es obligatorio.",
            "source": {"pointer": "/data"},
            "status": "422"
        },
        {
            "detail": "El campo Is postable es obligatorio.",
            "source": {"pointer": "/data"},
            "status": "422"
        }
    ]
}
```

### Ubicación
Afecta a TODAS las entidades de Accounting:
- `Modules/Accounting/Tests/Feature/*StoreTest.php`
- `Modules/Accounting/Tests/Feature/*UpdateTest.php`

### Entidades Afectadas (22 entidades × 2 test types = 44 test files)
1. Account
2. AccountBalance
3. AccountMapping
4. AuditLog
5. ExchangeRate
6. ExchangeRatePolicy
7. FiscalPeriod
8. IdempotencyKey
9. Journal
10. JournalEntry
11. JournalLine
12. JournalSequence
13. AccountReconciliation
14. BankAccount
15. BankStatement
16. BankTransaction
17. CostCenter
18. Department
19. ReconciliationRule
20. TaxCode
21. TaxRate
22. Company (si existe)

### Causa Raíz
1. **Schemas no están definiendo correctamente los campos**
   - Los campos required no están en el schema `fields()` method
   - Falta mapeo explícito camelCase → snake_case

2. **Requests no tienen las reglas de validación correctas**
   - Faltan reglas `required` para campos obligatorios
   - Reglas no distinguen entre POST (required) y PATCH (sometimes)

3. **Tests no están enviando todos los campos requeridos**
   - Los test payloads no incluyen todos los campos obligatorios
   - Formato incorrecto (snake_case en vez de camelCase)

### Solución Requerida

#### PASO 1: Revisar y Fix Schemas (22 archivos)
Para cada entidad en `Modules/Accounting/app/JsonApi/V1/{Entity}/`:

**Schema.php:**
```php
public function fields(): iterable
{
    return [
        ID::make(),

        // Campos básicos con mapping explícito
        Str::make('code'),
        Str::make('name'),
        Str::make('description')->nullable(),

        // BelongsTo relationships - deben estar en el schema
        BelongsTo::make('company', 'companies')
            ->type('companies')
            ->serializeUsing(static fn($value) => $value),

        BelongsTo::make('accountType', 'accountTypes')
            ->type('account-types')
            ->serializeUsing(static fn($value) => $value),

        // Boolean fields
        Boolean::make('isPostable'),
        Boolean::make('isCashFlow'),
        Boolean::make('isActive'),

        // Enum fields
        Str::make('nature')->enum(['debit', 'credit']),
        Str::make('status')->enum(['active', 'inactive', 'closed']),

        // Numeric fields
        Number::make('level'),

        // JSON fields
        ArrayHash::make('metadata')->nullable(),

        // Timestamps
        DateTime::make('createdAt')->readOnly(),
        DateTime::make('updatedAt')->readOnly(),
    ];
}
```

**CRÍTICO:** Todos los campos de la migración deben estar en el schema.

#### PASO 2: Fix Requests (44 archivos - Store + Update por entidad)

**{Entity}Request.php (Store):**
```php
public function rules(): array
{
    return [
        'code' => ['required', 'string', 'max:50', 'unique:accounts,code'],
        'name' => ['required', 'string', 'max:255'],
        'description' => ['nullable', 'string'],
        'companyId' => ['required', 'integer', 'exists:companies,id'],
        'accountTypeId' => ['required', 'integer', 'exists:account_types,id'],
        'nature' => ['required', 'string', Rule::in(['debit', 'credit'])],
        'level' => ['required', 'integer', 'min:1', 'max:10'],
        'currency' => ['required', 'string', 'size:3'],
        'isPostable' => ['required', 'boolean'],
        'isCashFlow' => ['required', 'boolean'],
        'isActive' => ['sometimes', 'boolean'],
        'status' => ['required', 'string', Rule::in(['active', 'inactive', 'closed'])],
        'metadata' => ['nullable', 'array'],
    ];
}
```

**{Entity}Request.php (Update):**
```php
public function rules(): array
{
    return [
        'code' => ['sometimes', 'string', 'max:50', Rule::unique('accounts', 'code')->ignore($this->route('account'))],
        'name' => ['sometimes', 'string', 'max:255'],
        'description' => ['nullable', 'string'],
        'companyId' => ['sometimes', 'integer', 'exists:companies,id'],
        'accountTypeId' => ['sometimes', 'integer', 'exists:account_types,id'],
        'nature' => ['sometimes', 'string', Rule::in(['debit', 'credit'])],
        'level' => ['sometimes', 'integer', 'min:1', 'max:10'],
        'currency' => ['sometimes', 'string', 'size:3'],
        'isPostable' => ['sometimes', 'boolean'],
        'isCashFlow' => ['sometimes', 'boolean'],
        'isActive' => ['sometimes', 'boolean'],
        'status' => ['sometimes', 'string', Rule::in(['active', 'inactive', 'closed'])],
        'metadata' => ['nullable', 'array'],
    ];
}
```

**Diferencia clave:** Store usa `required`, Update usa `sometimes`.

#### PASO 3: Fix Test Payloads (44 archivos)

**{Entity}StoreTest.php:**
```php
public function test_admin_can_create_{entity}(): void
{
    $company = Company::factory()->create();
    $accountType = AccountType::factory()->create();

    $data = [
        'type' => '{entities}',
        'attributes' => [
            'code' => '1010.001',
            'name' => 'Test Account',
            'description' => 'Test description',
            'nature' => 'debit',
            'level' => 3,
            'currency' => 'MXN',
            'isPostable' => true,
            'isCashFlow' => false,
            'status' => 'active',
            'metadata' => ['key' => 'value'],
        ],
        'relationships' => [
            'company' => [
                'data' => [
                    'type' => 'companies',
                    'id' => (string) $company->id,
                ],
            ],
            'accountType' => [
                'data' => [
                    'type' => 'account-types',
                    'id' => (string) $accountType->id,
                ],
            ],
        ],
    ];

    $response = $this
        ->actingAs($this->getAdminUser(), 'sanctum')
        ->jsonApi()
        ->expects('{entities}')
        ->withData($data)
        ->post('/api/v1/{entities}');

    $response->assertCreatedWithServerId(
        url: '/api/v1/{entities}',
        data: $data
    );
}
```

**CRÍTICO:**
- Usar camelCase en attributes: `isPostable`, `isCashFlow`, `accountType`
- Usar kebab-case en types: `account-types`, `fiscal-periods`
- Incluir TODOS los campos required del Request

---

## PROBLEMA 2: Filter Implementation (IndexTest) - 107 failures

### Descripción
Los tests de filtrado fallan porque los filtros no están correctamente implementados en los Schemas.

### Ejemplo de Error
```
Failed asserting that an array has 1 item.
Expected: 1
Actual: 0 (empty array)
```

### Causa Raíz
Los Schemas no tienen los filtros definidos correctamente en el método `filters()`.

### Solución Requerida

**En cada Schema.php:**
```php
public function filters(): array
{
    return [
        WhereIdIn::make($this),
        Where::make('code'),
        Where::make('name'),
        Where::make('status'),
        Where::make('isActive', 'is_active')->asBoolean(),
        Where::make('nature'),
        Where::make('companyId', 'company_id'),
        Where::make('accountTypeId', 'account_type_id'),
    ];
}
```

**CRÍTICO:** El segundo parámetro es el nombre del campo en la BD (snake_case).

---

## PROBLEMA 3: Sortables Implementation - Pattern Issue

### Descripción
Algunos schemas pueden tener el problema de `sortables()` method que es incompatible con Laravel JSON:API 5.x.

### Solución
Reemplazar `sortables()` por `sortables` en `pagination()`:

**INCORRECTO:**
```php
protected function sortables(): iterable
{
    return [
        'code',
        'name',
        'createdAt',
    ];
}
```

**CORRECTO:**
```php
public function pagination(): Paginator
{
    return PagePagination::make()
        ->withDefaultPerPage(15)
        ->withMaxPerPage(100);
}

// Y en el controller o schema, definir sortable fields
public function sortables(): array
{
    return [
        'code',
        'name',
        'createdAt',
        'updatedAt',
    ];
}
```

---

## PROBLEMA 4: Relationship Inclusion (ShowTest) - 81 failures

### Descripción
Los tests que intentan incluir relaciones (`?include=company,accountType`) fallan.

### Causa Raíz
1. Las relaciones no están definidas en el Schema
2. Las relaciones en el modelo no tienen el método correcto

### Solución Requerida

**En Schema.php:**
```php
public function fields(): iterable
{
    return [
        // ... otros campos ...

        BelongsTo::make('company'),
        BelongsTo::make('accountType'),
        HasMany::make('journalLines'),
    ];
}
```

**En Model.php:**
```php
public function company()
{
    return $this->belongsTo(Company::class);
}

public function accountType()
{
    return $this->belongsTo(AccountType::class, 'account_type_id');
}

public function journalLines()
{
    return $this->hasMany(JournalLine::class);
}
```

---

## PROBLEMA 5: Performance Issue - Tests 5x más lentos

### Descripción
Después de restaurar todos los seeders, los tests pasan de ~1s a ~5s por test.

### Causa
`tests/TestCase.php` ahora seedea 10 módulos en cada test:
- PermissionManager, User, Accounting, Contacts, Product, Inventory, Purchase, Sales, Ecommerce, Audit

### Solución Propuesta (Para después de arreglar los tests)

**Opción A: Module-specific Traits**
```php
trait SeedsAccountingModule
{
    protected function setUpSeedsAccounting(): void
    {
        $this->artisan('module:seed', ['module' => 'Accounting', '--quiet' => true]);
    }
}

// En AccountStoreTest.php:
class AccountStoreTest extends TestCase
{
    use SeedsAccountingModule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpSeedsAccounting();
    }
}
```

**Opción B: Lazy Seeding**
```php
// En TestCase.php:
protected static array $seededModules = [];

protected function seedModuleOnce(string $module): void
{
    if (!in_array($module, static::$seededModules)) {
        $this->artisan('module:seed', ['module' => $module, '--quiet' => true]);
        static::$seededModules[] = $module;
    }
}
```

**NOTA:** Implementar DESPUÉS de que todos los tests pasen.

---

## PLAN DE ACCIÓN - Phase 1 Fixes

### Prioridad 1: Fix Store/Update Tests (292 failures)

**Tiempo estimado:** 8-10 horas

1. **Crear lista de todas las entidades Accounting** (checklist)
2. **Para cada entidad (22 total):**
   - [ ] Revisar migración y listar todos los campos
   - [ ] Actualizar Schema con todos los campos + relationships
   - [ ] Actualizar StoreRequest con reglas `required`
   - [ ] Actualizar UpdateRequest con reglas `sometimes`
   - [ ] Actualizar StoreTest con payload completo
   - [ ] Actualizar UpdateTest con payload completo
   - [ ] Ejecutar tests y verificar ✅

3. **Entities checklist:**
   - [ ] Account
   - [ ] AccountBalance
   - [ ] AccountMapping
   - [ ] AuditLog
   - [ ] ExchangeRate
   - [ ] ExchangeRatePolicy
   - [ ] FiscalPeriod
   - [ ] IdempotencyKey
   - [ ] Journal
   - [ ] JournalEntry
   - [ ] JournalLine
   - [ ] JournalSequence
   - [ ] AccountReconciliation
   - [ ] BankAccount
   - [ ] BankStatement
   - [ ] BankTransaction
   - [ ] CostCenter
   - [ ] Department
   - [ ] ReconciliationRule
   - [ ] TaxCode
   - [ ] TaxRate
   - [ ] Company (si existe)

### Prioridad 2: Fix Index Tests (107 failures)

**Tiempo estimado:** 3-4 horas

Para cada Schema.php:
- [ ] Implementar método `filters()` con todos los campos filterables
- [ ] Usar `Where::make()` con mapeo correcto (camelCase → snake_case)
- [ ] Agregar `WhereIdIn::make($this)` para batch filtering
- [ ] Ejecutar IndexTests y verificar ✅

### Prioridad 3: Fix Show Tests (81 failures)

**Tiempo estimado:** 2-3 horas

Para cada entidad:
- [ ] Verificar que todas las relaciones están en Schema `fields()`
- [ ] Verificar que el modelo tiene los métodos de relación
- [ ] Ejecutar ShowTests con `?include=` y verificar ✅

### Prioridad 4: Fix Destroy Tests (75 failures)

**Tiempo estimado:** 2-3 horas

- [ ] Revisar constraints de FK en migraciones
- [ ] Implementar soft deletes si es necesario
- [ ] Actualizar Authorizers con permisos correctos
- [ ] Ejecutar DestroyTests y verificar ✅

### Prioridad 5: Performance Optimization

**Tiempo estimado:** 2-3 horas

- [ ] Implementar module-specific seeding traits
- [ ] Refactor TestCase.php para lazy seeding
- [ ] Verificar que tests siguen pasando
- [ ] Medir mejora de performance (target: <2s por test)

---

## VERIFICACIÓN FINAL

Antes de marcar Phase 1 como COMPLETO:

```bash
# Ejecutar TODOS los tests
php artisan test

# Resultado esperado:
# Tests: 0 failed, 1,222 passed
# Duration: <15 minutes (con optimización de performance)
```

**CRITERIOS DE ACEPTACIÓN:**
- ✅ 100% tests passing (1,222/1,222)
- ✅ 0 warnings o deprecations
- ✅ Tiempo de ejecución < 15 minutos
- ✅ Cobertura de código > 80%
- ✅ Documentación actualizada

---

## NOTAS IMPORTANTES

1. **NO avanzar a Phase 3 hasta cumplir los criterios de aceptación**
2. **Documentar cualquier issue nuevo que se encuentre**
3. **Mantener este documento actualizado con el progreso**
4. **Usar git commits frecuentes para cada entidad arreglada**

---

## REFERENCIAS

- **Module Generator Issues Fixed:** [CLAUDE.md:386-392](CLAUDE.md#L386-L392)
- **TestCase Optimization:** [tests/TestCase.php](tests/TestCase.php)
- **Accounting Services:** `Modules/Accounting/app/Services/`
- **Original Phase 1 Report:** `docs/development/FINANCE_ACCOUNTING_PHASE1_FRONTEND_REPORT.md`

---

**Documento creado:** 2025-10-24
**Última actualización:** 2025-10-24
**Status:** PENDING - Phase 1 al 90%
**Next Step:** Continuar con Phase 2, regresar a Phase 1 fixes después
