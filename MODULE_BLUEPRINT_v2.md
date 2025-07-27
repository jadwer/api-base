# 🏗️ MODULE BLUEPRINT - PROCEDIMIENTO MEJORADO v2.0

**Basado en análisis de implementación exitosa del módulo INVENTORY**

---

## 📋 **PROCEDIMIENTO PASO A PASO PARA CREAR UN MÓDULO COMPLETO**

### **FASE 1: PREPARACIÓN Y ESTRUCTURA BASE**

#### **Paso 1.1: Crear módulo base**
```bash
# Generar estructura base del módulo
php artisan module:make {ModuleName}

# Verificar estructura generada
php artisan module:list
```

#### **Paso 1.2: Identificar entidades del módulo**
```
Ejemplo para INVENTORY:
- Warehouse (almacenes)
- WarehouseLocation (ubicaciones en almacenes) 
- Stock (existencias)
- ProductBatch (lotes de productos)
```

---

### **FASE 2: IMPLEMENTACIÓN POR ENTIDAD**

**⚠️ CRÍTICO: Hacer esto para CADA entidad del módulo**

#### **Paso 2.1: Crear Model + Migration**

**Model ubicación:** `Modules/{ModuleName}/app/Models/{Entity}.php`

```php
<?php

namespace Modules\{ModuleName}\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property bool $is_active
 * // ... todos los campos de la migración
 */
class {Entity} extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'id' => 'integer',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        // Agregar casts según tipos de datos
    ];

    // Definir TODAS las relaciones
    public function parentEntity(): BelongsTo
    {
        return $this->belongsTo(ParentEntity::class);
    }

    public function childEntities(): HasMany
    {
        return $this->hasMany(ChildEntity::class);
    }

    protected static function newFactory()
    {
        return \Modules\{ModuleName}\Database\Factories\{Entity}Factory::new();
    }
}
```

**Migration:**
```bash
# Crear migración
php artisan module:make-migration create_{entities}_table {ModuleName}
```

**⚠️ IMPORTANTE:** Asegurar CONSISTENCIA total entre:
- Campos de migración
- Properties del Model (@property)
- Casts del Model
- Campos del Schema JSON:API
- Atributos del Resource

#### **Paso 2.2: Crear JSON:API Schema**

**Ubicación:** `Modules/{ModuleName}/app/JsonApi/V1/{Entities}/{Entity}Schema.php`

```php
<?php

namespace Modules\{ModuleName}\JsonApi\V1\{Entities};

use LaravelJsonApi\Eloquent\Schema;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Boolean;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Relations\HasMany;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use Modules\{ModuleName}\Models\{Entity};

class {Entity}Schema extends Schema
{
    public static string $model = {Entity}::class;

    protected int $maxDepth = 3;

    public function fields(): array
    {
        return [
            ID::make(),
            
            // MAPEAR TODOS LOS CAMPOS DE LA MIGRACIÓN
            Str::make('name')->sortable(),
            Str::make('fieldName', 'database_field_name'), // camelCase -> snake_case
            Boolean::make('isActive', 'is_active')->sortable(),
            
            // Relaciones
            BelongsTo::make('parent', 'parent')->type('parents'),
            HasMany::make('children', 'children')->type('children'),
            
            // Timestamps
            DateTime::make('createdAt', 'created_at')->sortable()->readOnly(),
            DateTime::make('updatedAt', 'updated_at')->sortable()->readOnly(),
        ];
    }

    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            Where::make('name'),
            Where::make('is_active'),
        ];
    }

    public function includePaths(): array
    {
        return [
            'parent',
            'children',
        ];
    }

    public function pagination(): ?PagePagination
    {
        return PagePagination::make();
    }

    public static function type(): string
    {
        return '{entities}'; // plural, kebab-case
    }
}
```

#### **Paso 2.3: Crear Authorizer**

**⚠️ CRÍTICO: NO confundir las interfaces de Authorizer**
- ✅ USAR: `LaravelJsonApi\Contracts\Auth\Authorizer`
- ❌ NO USAR: `LaravelJsonApi\Eloquent\Contracts\Authorizer`

**Ubicación:** `Modules/{ModuleName}/app/JsonApi/V1/{Entities}/{Entity}Authorizer.php`

```php
<?php

namespace Modules\{ModuleName}\JsonApi\V1\{Entities};

use Illuminate\Auth\Access\Response;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Auth\Authorizer;  // ⚠️ INTERFAZ CORRECTA

class {Entity}Authorizer implements Authorizer
{
    public function index(Request $request, string $modelClass): bool|Response
    {
        return $request->user()?->can('{entities}.index') ?? false; // ✅ PLURAL
    }

    public function store(Request $request, string $modelClass): bool|Response
    {
        return $request->user()?->can('{entities}.store') ?? false; // ✅ PLURAL
    }

    public function show(Request $request, object $model): bool|Response
    {
        return $request->user()?->can('{entities}.view') ?? false; // ✅ view, no show
    }

    public function update(Request $request, object $model): bool|Response
    {
        return $request->user()?->can('{entities}.update') ?? false; // ✅ PLURAL
    }

    public function destroy(Request $request, object $model): bool|Response
    {
        return $request->user()?->can('{entities}.destroy') ?? false; // ✅ PLURAL
    }

    // Métodos para relaciones (copiar patrón completo)
    public function showRelated(Request $request, object $model, string $fieldName): bool|Response
    {
        return $this->show($request, $model);
    }

    public function showRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return $this->show($request, $model);
    }

    public function updateRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return $this->update($request, $model);
    }

    public function attachRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return $this->update($request, $model);
    }

    public function detachRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return $this->update($request, $model);
    }
}
```

#### **Paso 2.4: Crear Request para validaciones**

**Ubicación:** `Modules/{ModuleName}/app/JsonApi/V1/{Entities}/{Entity}Request.php`

```php
<?php

namespace Modules\{ModuleName}\JsonApi\V1\{Entities};

use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

class {Entity}Request extends ResourceRequest
{
    public function rules(): array
    {
        $entity = $this->model();

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('{entities}', 'code')->ignore($entity),
            ],
            // VALIDAR TODOS LOS CAMPOS DE LA MIGRACIÓN
            'fieldName' => ['nullable', 'string', 'max:255'],
            'isActive' => ['sometimes', 'boolean'],
        ];
    }

    public function withDefaults(): array
    {
        return [
            'isActive' => true,
            // Otros defaults
        ];
    }
}
```

#### **Paso 2.5: Crear Resource (NO es opcional, si no se crea, no se generan las rutas de la API)**

**Ubicación:** `Modules/{ModuleName}/app/JsonApi/V1/{Entities}/{Entity}Resource.php`

```php
<?php

namespace Modules\{ModuleName}\JsonApi\V1\{Entities};

use LaravelJsonApi\Core\Resources\JsonApiResource;

class {Entity}Resource extends JsonApiResource
{
    public function attributes($request): iterable
    {
        return [
            // MAPEAR TODOS LOS CAMPOS DE LA MIGRACIÓN
            'name' => $this->name,
            'fieldName' => $this->database_field_name, // snake_case -> camelCase
            'isActive' => $this->is_active,
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }

    public function relationships($request): iterable
    {
        return [
            'parent' => $this->relation('parent'),
            'children' => $this->relation('children'),
        ];
    }
}
```

#### **Paso 2.6: Crear Controller**

**Ubicación:** `Modules/{ModuleName}/app/Http/Controllers/Api/V1/{Entity}Controller.php`

```php
<?php

namespace Modules\{ModuleName}\Http\Controllers\Api\V1;

use Illuminate\Routing\Controller;
use LaravelJsonApi\Laravel\Http\Controllers\Actions;

class {Entity}Controller extends Controller
{
    use Actions\FetchMany;       // GET /api/v1/{entities}
    use Actions\FetchOne;        // GET /api/v1/{entities}/{id}
    use Actions\Store;           // POST /api/v1/{entities}
    use Actions\Update;          // PATCH /api/v1/{entities}/{id}
    use Actions\Destroy;         // DELETE /api/v1/{entities}/{id}
    
    // Actions para relaciones
    use Actions\FetchRelated;        // GET /api/v1/{entities}/{id}/parent
    use Actions\FetchRelationship;   // GET /api/v1/{entities}/{id}/relationships/parent
    use Actions\UpdateRelationship;  // PATCH /api/v1/{entities}/{id}/relationships/parent
    use Actions\AttachRelationship;  // POST /api/v1/{entities}/{id}/relationships/children
    use Actions\DetachRelationship;  // DELETE /api/v1/{entities}/{id}/relationships/children
}
```

**⚠️ IMPORTANTE:**
- **Namespace:** `Modules\{ModuleName}\Http\Controllers\Api\V1` (SIN `/app/`)
- **Ubicación:** `app/Http/Controllers/Api/V1/` (CON `/app/`)

---

### **FASE 3: CONFIGURACIÓN DEL MÓDULO**

#### **Paso 3.1: Registrar rutas JSON:API**

**Archivo:** `Modules/{ModuleName}/routes/jsonapi.php`

```php
<?php

use LaravelJsonApi\Laravel\Facades\JsonApiRoute;
use LaravelJsonApi\Laravel\Routing\ResourceRegistrar;
use Modules\{ModuleName}\Http\Controllers\Api\V1\{Entity}Controller;

JsonApiRoute::server('v1')
    ->prefix('v1')
    ->middleware('auth:sanctum')
    ->resources(function (ResourceRegistrar $server) {
        $server->resource('{entities}', {Entity}Controller::class);
        // Repetir para cada entidad del módulo
    });
```

#### **Paso 3.2: Configurar RouteServiceProvider**

**Verificar:** `Modules/{ModuleName}/app/Providers/RouteServiceProvider.php`

```php
protected function mapJsonApiRoutes(): void
{
    Route::prefix('api')
        ->middleware('api')
        ->group(module_path($this->name, '/routes/jsonapi.php')); // minúscula
}
```

#### **Paso 3.3: Registrar en Server.php**

**Archivo:** `app/JsonApi/V1/Server.php`

```php
// En imports
use Modules\{ModuleName}\JsonApi\V1\{Entities}\{Entity}Schema;

// En allSchemas()
protected function allSchemas(): array
{
    return [
        // Schemas existentes...
        
        // {ModuleName} Module
        {Entity}Schema::class,
        // Repetir para cada entidad
    ];
}

// En authorizers()
protected function authorizers(): array
{
    return [
        // Authorizers existentes...
        
        '{entities}' => \Modules\{ModuleName}\JsonApi\V1\{Entities}\{Entity}Authorizer::class,
        // Repetir para cada entidad
    ];
}
```

---

### **FASE 4: SISTEMA DE PERMISOS**

#### **Paso 4.1: Crear PermissionSeeder**

**⚠️ CRÍTICO: Nomenclatura de permisos**
- **Regla:** Usar el nombre PLURAL del resource JSON:API
- **Formato:** `{entities}.{action}` (kebab-case + plural)
- **Ejemplos:** `warehouses.index`, `warehouse-locations.store`, `product-batches.update`

```php
<?php

namespace Modules\{ModuleName}\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class {ModuleName}PermissionSeeder extends Seeder
{
    public function run()
    {
        $permissions = [
            // Para cada entidad, crear 5 permisos CRUD (usar nombre PLURAL)
            ['{entities}.index', 'guard_name' => 'api'],    // ✅ warehouses.index
            ['{entities}.view', 'guard_name' => 'api'],     // ✅ warehouses.view  
            ['{entities}.store', 'guard_name' => 'api'],    // ✅ warehouses.store
            ['{entities}.update', 'guard_name' => 'api'],   // ✅ warehouses.update
            ['{entities}.destroy', 'guard_name' => 'api'],  // ✅ warehouses.destroy
            
            // Para entidades con kebab-case
            ['warehouse-locations.index', 'guard_name' => 'api'],
            ['product-batches.index', 'guard_name' => 'api'],
            // ...
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate($permission);
        }
    }
}
```

#### **Paso 4.2: Crear AssignPermissionsSeeder**

```php
<?php

namespace Modules\{ModuleName}\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class {ModuleName}AssignPermissionsSeeder extends Seeder
{
    public function run()
    {
        $godRole = Role::where('name', 'god')->first();
        
        if ($godRole) {
            $modulePermissions = Permission::where('guard_name', 'api')
                ->where(function($query) {
                    $query->where('name', 'like', '{entity}%')
                          ->orWhere('name', 'like', '{other-entity}%');
                })
                ->get();

            foreach ($modulePermissions as $permission) {
                if (!$godRole->hasPermissionTo($permission)) {
                    $godRole->givePermissionTo($permission);
                }
            }
        }
    }
}
```

---

### **FASE 5: DATOS DE PRUEBA**

#### **Paso 5.1: Crear Factories**

```php
<?php

namespace Modules\{ModuleName}\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\{ModuleName}\Models\{Entity};

class {Entity}Factory extends Factory
{
    protected $model = {Entity}::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'code' => $this->faker->unique()->regexify('[A-Z]{3}[0-9]{3}'),
            'is_active' => $this->faker->boolean(80),
            // Mapear TODOS los campos
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
```

#### **Paso 5.2: Crear Seeders de datos**

```php
<?php

namespace Modules\{ModuleName}\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\{ModuleName}\Models\{Entity};

class {Entity}Seeder extends Seeder
{
    public function run()
    {
        $entities = [
            [
                'name' => 'Entidad 1',
                'code' => 'ENT-001',
                // Datos específicos del negocio
            ],
            // Más datos...
        ];

        foreach ($entities as $entity) {
            {Entity}::firstOrCreate(
                ['code' => $entity['code']],
                $entity
            );
        }
    }
}
```

---

### **FASE 6: TESTING (CRÍTICO)**

#### **Paso 6.1: Crear tests CRUD para cada entidad**

**Ubicación:** `Modules/{ModuleName}/tests/Feature/{Entity}*Test.php`

**⚠️ IMPORTANTE: Configuración para PHPUnit anterior a v12**
- No usar `#[Test]` attributes (solo para PHPUnit 12+)
- Usar `/** @test */` docblocks O métodos con prefijo `test_`
- Usar `use RefreshDatabase;` para aislamiento de datos
- Usar `firstOrCreate` para permisos/roles para evitar duplicados

```php
// {Entity}IndexTest.php
// {Entity}ShowTest.php  
// {Entity}StoreTest.php
// {Entity}UpdateTest.php
// {Entity}DestroyTest.php
```

**Patrón de test completo:**
```php
<?php

namespace Modules\{ModuleName}\Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Modules\{ModuleName}\Models\{Entity};
use Illuminate\Foundation\Testing\RefreshDatabase;

class {Entity}IndexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Crear permisos necesarios (con firstOrCreate)
        Permission::firstOrCreate(['name' => '{entities}.index']);
        Permission::firstOrCreate(['name' => '{entities}.view']);
        
        // Crear roles (con firstOrCreate)
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'tech']);
        Role::firstOrCreate(['name' => 'customer']);
    }

    private function createUserWithPermissions(array $permissions = []): User
    {
        $user = User::factory()->create();
        $role = Role::create(['name' => 'test_role_' . uniqid()]);
        
        foreach ($permissions as $permission) {
            $role->givePermissionTo($permission);
        }
        
        $user->assignRole($role);
        return $user;
    }

    /** @test */
    public function admin_can_list_{entities}()
    {
        $admin = $this->createUserWithPermissions(['{entities}.index']);
        {Entity}::factory()->count(3)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/{entities}');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'type',
                    'id',
                    'attributes' => [
                        // verificar atributos clave
                    ]
                ]
            ]
        ]);
    }

    /** @test */
    public function unauthorized_user_cannot_list_{entities}()
    {
        $response = $this->getJson('/api/v1/{entities}');
        $response->assertStatus(401);
    }

    /** @test */
    public function user_without_permission_cannot_list_{entities}()
    {
        $user = $this->createUserWithPermissions([]); // Sin permisos
        
        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/{entities}');
            
        $response->assertStatus(403);
    }
}
```

**Para tests de validación JSON:API:**
```php
/** @test */
public function store_validates_required_fields()
{
    $admin = $this->createUserWithPermissions(['{entities}.store']);
    
    $invalidData = [
        'data' => [
            'type' => '{entities}',
            'attributes' => [
                'name' => '', // Campo requerido vacío
            ]
        ]
    ];

    $response = $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/{entities}', $invalidData);

    $response->assertStatus(422);
    $response->assertJsonFragment([
        'source' => ['pointer' => '/data/attributes/name']
    ]);
}
```

---

### **FASE 7: FINALIZACIÓN**

#### **Paso 7.1: Registrar en DatabaseSeeder principal**

**⚠️ CRÍTICO: Este paso es OBLIGATORIO y frecuentemente olvidado**

**Archivo:** `database/seeders/DatabaseSeeder.php`

```php
public function run(): void
{
    // Seeders existentes...
    
    $this->call([
        \Modules\PermissionManager\Database\Seeders\PermissionManagerDatabaseSeeder::class,
        \Modules\Audit\Database\Seeders\AuditDatabaseSeeder::class,
        \Modules\PageBuilder\Database\Seeders\PageBuilderDatabaseSeeder::class,
        \Modules\User\Database\Seeders\UserDatabaseSeeder::class,
        \Modules\Product\Database\Seeders\ProductDatabaseSeeder::class,
        \Modules\Inventory\Database\Seeders\InventoryDatabaseSeeder::class, // ⚠️ AGREGAR AQUÍ
        \Modules\{ModuleName}\Database\Seeders\{ModuleName}DatabaseSeeder::class, // ⚠️ NUEVO MÓDULO
    ]);
}
```

**Si no se registra el seeder del módulo:**
- ❌ Los permisos no se crean automáticamente
- ❌ Los datos de ejemplo no se insertan  
- ❌ Los tests fallan por falta de permisos
- ❌ El módulo parece "vacío" al usuario

#### **Paso 7.2: Ejecutar migraciones y seeders**

```bash
php artisan migrate:fresh --seed
```

#### **Paso 7.3: Verificar rutas**

```bash
php artisan route:list --path=api/v1/{entities}
```

#### **Paso 7.4: Ejecutar tests**

```bash
php artisan test Modules/{ModuleName}/Tests/Feature/
```

---

## 🎯 **CHECKLIST DE COMPLETITUD POR ENTIDAD**

**Para CADA entidad del módulo, verificar:**

- [ ] ✅ Model con relaciones completas
- [ ] ✅ Migration ejecutada correctamente  
- [ ] ✅ Schema JSON:API con todos los campos
- [ ] ✅ Authorizer con todos los métodos
- [ ] ✅ Request con validaciones completas
- [ ] ✅ Resource con todos los atributos
- [ ] ✅ Controller con Actions traits
- [ ] ✅ Rutas registradas en jsonapi.php
- [ ] ✅ Schema registrado en Server.php
- [ ] ✅ Authorizer registrado en Server.php
- [ ] ✅ Permisos creados y asignados
- [ ] ✅ Factory para testing
- [ ] ✅ Seeder con datos de ejemplo
- [ ] ✅ Tests CRUD completos (5 tests mínimo)

---

## 🚨 **ERRORES COMUNES IDENTIFICADOS**

### **1. Inconsistencia de campos:**
- ❌ Campo en migración pero no en Schema
- ❌ Campo en Schema pero no en Resource
- ❌ Campo en Resource pero no en Model @property

### **2. Namespace incorrecto:**
- ❌ `Modules\{ModuleName}\app\Http\Controllers` (incluir /app/)
- ✅ `Modules\{ModuleName}\Http\Controllers` (sin /app/)

### **3. Ubicación física incorrecta:**
- ❌ `JsonApi/V1/` (fuera de app/)
- ✅ `app/JsonApi/V1/` (dentro de app/)

### **4. Mapeo camelCase ↔ snake_case:**
- ❌ `Str::make('warehouse_type')` 
- ✅ `Str::make('warehouseType', 'warehouse_type')`

### **5. Rutas no registradas:**
- ❌ `Routes/jsonapi.php` (mayúscula)
- ✅ `routes/jsonapi.php` (minúscula)

### **6. Nomenclatura de permisos (CRÍTICO):**
- ❌ `'warehouse.index'` (singular)
- ✅ `'warehouses.index'` (plural)
- ❌ `'product-batch.store'` (kebab-case)
- ✅ `'product-batches.store'` (plural + kebab-case)

**Regla: Los permisos deben usar el nombre PLURAL del tipo de resource JSON:API**

---

## 📊 **MÉTRICAS DE ÉXITO**

**Por módulo completado:**
- [ ] ✅ Todas las entidades implementadas (4/4 para INVENTORY)
- [ ] ✅ Rutas funcionando (`php artisan route:list`)
- [ ] ✅ API respondiendo (aunque requiera auth)
- [ ] ✅ Tests pasando (mínimo 20 tests por módulo)
- [ ] ✅ Seeders ejecutándose sin error
- [ ] ✅ Permisos asignados correctamente

**Este procedimiento garantiza un módulo 100% funcional y consistente con la arquitectura JSON:API 5.x + Laravel Modules.**
