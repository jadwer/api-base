# 🏗️ MODULE BLUEPRINT MASTER - Guía Completa v3.0

**Especificación técnica unificada para crear módulos en api-base usando Laravel JSON:API 5.x + nwidart/laravel-modules**

**Versión:** 3.0 Master  
**Basado en:** Análisis completo Product, Inventory y Purchase modules  
**Arquitectura:** Laravel 12 + JSON:API 5.x + Módulos + Spatie Permissions  
**Actualizado:** 27 de julio de 2025

---

## 📋 **INTRODUCCIÓN Y ARQUITECTURA**

### **Stack Tecnológico:**
- **Framework:** Laravel 12
- **API:** Laravel JSON:API 5.x (NO 4.x o inferior)
- **Módulos:** nwidart/laravel-modules
- **Permisos:** Spatie Laravel Permission
- **Testing:** PHPUnit con Laravel Testing utilities
- **Base de datos:** MySQL/PostgreSQL

### **Principios Arquitectónicos:**
1. **Modularidad:** Cada módulo es independiente y autocontenido
2. **Consistencia:** Todos los módulos siguen el mismo patrón
3. **JSON:API Compliance:** Estricto cumplimiento de la especificación JSON:API
4. **Security First:** Sistema granular de permisos por endpoint
5. **Testing Coverage:** Mínimo 80% de cobertura de tests

---

## 📋 **ESTRUCTURA COMPLETA DEL MÓDULO**

### **Anatomía de un módulo tipo:**

```
Modules/{ModuleName}/
├── app/
│   ├── Http/
│   │   └── Controllers/Api/V1/          # Controllers JSON:API 5.x
│   │       ├── {Entity}Controller.php   # Un controller por entidad
│   │       └── ...
│   ├── JsonApi/V1/
│   │   ├── {Entities}/                  # Carpeta plural por entidad
│   │   │   ├── {Entity}Schema.php       # Schema con fields/relations/filters
│   │   │   ├── {Entity}Request.php      # Validaciones para crear/actualizar
│   │   │   ├── {Entity}Resource.php     # JSON:API Resource (OBLIGATORIO)
│   │   │   └── {Entity}Authorizer.php   # Control de acceso granular
│   │   └── ...
│   ├── Models/                          # Eloquent Models
│   │   ├── {Entity}.php                 # Modelo principal
│   │   └── ...
│   └── Providers/                       # Service Providers del módulo
│       ├── {ModuleName}ServiceProvider.php  # Provider principal del módulo
│       └── RouteServiceProvider.php     # Provider de rutas
├── Database/
│   ├── Factories/                       # Model Factories para testing
│   │   ├── {Entity}Factory.php
│   │   └── ...
│   ├── Migrations/                      # Schema de base de datos
│   │   ├── {timestamp}_create_{entities}_table.php
│   │   └── ...
│   └── Seeders/                         # Seeders del módulo
│       ├── {ModuleName}DatabaseSeeder.php    # Seeder principal del módulo
│       ├── {ModuleName}PermissionSeeder.php  # Permisos específicos
│       ├── {ModuleName}AssignPermissionsSeeder.php  # Asignación de permisos
│       ├── {Entity}Seeder.php               # Datos de ejemplo
│       └── ...
├── Tests/
│   ├── Feature/                         # Tests de API completos
│   │   ├── {Entity}IndexTest.php        # GET /api/v1/{entities}
│   │   ├── {Entity}ShowTest.php         # GET /api/v1/{entities}/{id}
│   │   ├── {Entity}StoreTest.php        # POST /api/v1/{entities}
│   │   ├── {Entity}UpdateTest.php       # PATCH /api/v1/{entities}/{id}
│   │   ├── {Entity}DestroyTest.php      # DELETE /api/v1/{entities}/{id}
│   │   └── ...
│   └── Unit/                            # Tests unitarios (si se necesitan)
├── routes/
│   └── jsonapi.php                      # Rutas JSON:API del módulo
├── config/
│   └── config.php                       # Configuración del módulo (si se necesita)
└── module.json                          # Configuración principal del módulo
```

---

## 🚀 **PROCEDIMIENTO PASO A PASO PARA CREAR UN MÓDULO COMPLETO**

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
Ejemplo para PURCHASE:
- Supplier (proveedores)
- PurchaseOrder (órdenes de compra) 
- PurchaseOrderItem (items de órdenes de compra)

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
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * @property int $id
 * @property string $name
 * @property string|null $code
 * @property string|null $description
 * @property float|null $price
 * @property bool $is_active
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class {Entity} extends Model
{
    use HasFactory, LogsActivity;

    protected $guarded = [];

    protected $casts = [
        'id' => 'integer',
        'price' => 'float',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        // Campos JSON como ArrayHash
        'certifications' => 'array',
        'specifications' => 'array',
    ];

    // Configuración de Activity Log
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'code', 'description', 'price', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // Scopes útiles
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    // Relaciones BelongsTo (pertenece a)
    public function parentEntity(): BelongsTo
    {
        return $this->belongsTo(ParentEntity::class);
    }

    // Relaciones HasMany (tiene muchos)
    public function childEntities(): HasMany
    {
        return $this->hasMany(ChildEntity::class);
    }

    // Factory
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

**Patrón de migración:**
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('{entities}', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique()->nullable();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->boolean('is_active')->default(true);
            
            // Campos JSON para arrays asociativos
            $table->json('certifications')->nullable();
            $table->json('specifications')->nullable();
            
            // Foreign keys
            $table->foreignId('parent_entity_id')
                  ->nullable()
                  ->constrained('parent_entities')
                  ->onDelete('restrict');
            
            $table->timestamps();
            
            // Índices para mejorar performance
            $table->index(['name']);
            $table->index(['code']);
            $table->index(['is_active']);
            $table->index(['parent_entity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('{entities}');
    }
};
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
use LaravelJsonApi\Eloquent\Fields\ArrayHash;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Relations\HasMany;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Filters\WhereIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use Modules\{ModuleName}\Models\{Entity};

class {Entity}Schema extends Schema
{
    /**
     * The model the schema corresponds to.
     */
    public static string $model = {Entity}::class;

    /**
     * The maximum depth of include paths.
     */
    protected int $maxDepth = 3;

    /**
     * Get the resource fields.
     */
    public function fields(): array
    {
        return [
            ID::make(),
            
            // Campos básicos - MAPEAR TODOS LOS CAMPOS DE LA MIGRACIÓN
            Str::make('name')->sortable(),
            Str::make('code')->sortable(),
            Str::make('description'),
            Number::make('price')->sortable(),
            Boolean::make('isActive', 'is_active')->sortable(),
            
            // Campos JSON como ArrayHash (requiere arrays asociativos)
            ArrayHash::make('certifications'),
            ArrayHash::make('specifications'),
            
            // Relaciones BelongsTo (muchos a uno) - DENTRO DE fields()
            BelongsTo::make('parentEntity', 'parent_entity')
                     ->type('parent-entities'),
            
            // Relaciones HasMany (uno a muchos) - DENTRO DE fields()
            HasMany::make('childEntities', 'child_entities')
                   ->type('child-entities'),
            
            // Timestamps
            DateTime::make('createdAt', 'created_at')->readOnly()->sortable(),
            DateTime::make('updatedAt', 'updated_at')->readOnly()->sortable(),
        ];
    }

    /**
     * Get the resource filters.
     */
    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            Where::make('name'),
            Where::make('code'),
            WhereIn::make('parentEntity', 'parent_entity_id'),
            Where::make('isActive', 'is_active')->asBoolean(),
        ];
    }

    /**
     * Get the resource include paths.
     */
    public function includePaths(): array
    {
        return [
            'parentEntity',
            'childEntities',
        ];
    }

    /**
     * Get the resource paginator.
     */
    public function pagination(): ?PagePagination
    {
        return PagePagination::make();
    }

    /**
     * Get the JSON:API resource type.
     */
    public static function type(): string
    {
        return '{entities}'; // plural, kebab-case
    }

    /**
     * Get the resource relationships.
     * ⚠️ CRÍTICO: Mantener vacío - las relaciones van en fields()
     */
    public function relationships(): array
    {
        return [
            //
        ];
    }
}
```

#### **Paso 2.3: Crear Authorizer**

**⚠️ CRÍTICO: NO confundir las interfaces de Authorizer**
- ✅ USAR: `LaravelJsonApi\Contracts\Auth\Authorizer`
- ❌ NO USAR: `LaravelJsonApi\Eloquent\Contracts\Authorizer`

**Ubicación:** `Modules/{ModuleName}/app/JsonApi/V1/{Entities}/{Entity}Authorizer.php`

**🚨 ACTUALIZACIÓN CRÍTICA - PATRÓN ROLE-BASED GRANULAR:**
- ✅ **NUEVO PATRÓN:** Verificar roles específicos ANTES de permisos genéricos
- ✅ **SIN GUARD 'api':** Los permisos se verifican sin especificar guard (funciona correctamente)
- ❌ **NO MÁS:** Bypasses de testing con `app()->environment()`
- ✅ **GRANULAR:** Control específico por rol para cada operación

**🎯 PATRÓN COMPROBADO (Customer module - 100% tests pasando):**

```php
<?php

namespace Modules\{ModuleName}\JsonApi\V1\{Entities};

use Illuminate\Auth\Access\Response;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Auth\Authorizer;

class {Entity}Authorizer implements Authorizer
{
    /**
     * Authorize the index controller action (GET /api/v1/{entities})
     */
    public function index(Request $request, string $modelClass): bool|Response
    {
        $user = $request->user();
        
        // Customer users cannot list all entities (customize per business rules)
        if ($user && $user->hasRole('customer')) {
            return false; // Or implement specific customer logic
        }
        
        return $user?->can('{entities}.index') ?? false;
    }

    /**
     * Authorize the store controller action (POST /api/v1/{entities})
     */
    public function store(Request $request, string $modelClass): bool|Response
    {
        $user = $request->user();
        
        // Customer users cannot create new entities
        if ($user && $user->hasRole('customer')) {
            return false;
        }
        
        return $user?->can('{entities}.store') ?? false;
    }

    /**
     * Authorize the show controller action (GET /api/v1/{entities}/{id})
     */
    public function show(Request $request, object $model): bool|Response
    {
        $user = $request->user();
        
        // Customer users cannot view other entities
        if ($user && $user->hasRole('customer')) {
            return false; // Or implement ownership logic: $model->user_id === $user->id
        }
        
        return $user?->can('{entities}.show') ?? false;
    }

    /**
     * Authorize the update controller action (PATCH /api/v1/{entities}/{id})
     */
    public function update(Request $request, object $model): bool|Response
    {
        $user = $request->user();
        
        // Customer users cannot update other entities
        if ($user && $user->hasRole('customer')) {
            return false; // Or implement ownership logic
        }
        
        return $user?->can('{entities}.update') ?? false;
    }

    /**
     * Authorize the destroy controller action (DELETE /api/v1/{entities}/{id})
     */
    public function destroy(Request $request, object $model): bool|Response
    {
        $user = $request->user();
        
        // Customer users cannot delete other entities
        if ($user && $user->hasRole('customer')) {
            return false;
        }
        
        return $user?->can('{entities}.destroy') ?? false;
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
use LaravelJsonApi\Validation\Rule as JsonApiRule;

class {Entity}Request extends ResourceRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $entity = $this->model();

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('{entities}', 'code')->ignore($entity),
            ],
            'description' => ['nullable', 'string'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'isActive' => ['sometimes', 'boolean'],
            
            // Validaciones para campos JSON (ArrayHash)
            'certifications' => ['nullable', 'array'],
            'certifications.*' => ['boolean'], // Para arrays asociativos
            'specifications' => ['nullable', 'array'],
            
            // Validaciones para relaciones
            'parentEntity' => JsonApiRule::toOne(),
            'childEntities' => JsonApiRule::toMany(),
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'code.unique' => 'Este código ya está en uso.',
            'price.min' => 'El precio debe ser mayor o igual a 0.',
            'price.numeric' => 'El precio debe ser un número válido.',
            'certifications.array' => 'Las certificaciones deben ser un objeto.',
            'specifications.array' => 'Las especificaciones deben ser un objeto.',
        ];
    }

    /**
     * Get default values for the request.
     */
    public function withDefaults(): array
    {
        return [
            'isActive' => true,
            'certifications' => [],
            'specifications' => [],
        ];
    }
}
```

#### **Paso 2.5: Crear Resource (OBLIGATORIO - NO es opcional)**

**Ubicación:** `Modules/{ModuleName}/app/JsonApi/V1/{Entities}/{Entity}Resource.php`

```php
<?php

namespace Modules\{ModuleName}\JsonApi\V1\{Entities};

use LaravelJsonApi\Core\Resources\JsonApiResource;

class {Entity}Resource extends JsonApiResource
{
    /**
     * Get the resource attributes.
     */
    public function attributes($request): iterable
    {
        return [
            // MAPEAR TODOS LOS CAMPOS DE LA MIGRACIÓN
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'price' => $this->price,
            'isActive' => $this->is_active,
            
            // Campos JSON
            'certifications' => $this->certifications,
            'specifications' => $this->specifications,
            
            // Timestamps
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }

    /**
     * Get the resource relationships.
     */
    public function relationships($request): iterable
    {
        return [
            'parentEntity' => $this->relation('parentEntity'),
            'childEntities' => $this->relation('childEntities'),
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
    // Actions traits para operaciones CRUD automáticas - JSON:API 5.x
    use Actions\FetchMany;       // GET /api/v1/{entities}
    use Actions\FetchOne;        // GET /api/v1/{entities}/{id}
    use Actions\Store;           // POST /api/v1/{entities}
    use Actions\Update;          // PATCH /api/v1/{entities}/{id}
    use Actions\Destroy;         // DELETE /api/v1/{entities}/{id}
    
    // Actions para relaciones
    use Actions\FetchRelated;        // GET /api/v1/{entities}/{id}/parent-entity
    use Actions\FetchRelationship;   // GET /api/v1/{entities}/{id}/relationships/parent-entity
    use Actions\UpdateRelationship;  // PATCH /api/v1/{entities}/{id}/relationships/parent-entity
    use Actions\AttachRelationship;  // POST /api/v1/{entities}/{id}/relationships/child-entities
    use Actions\DetachRelationship;  // DELETE /api/v1/{entities}/{id}/relationships/child-entities
}
```

**⚠️ IMPORTANTE:**
- **Namespace:** `Modules\{ModuleName}\Http\Controllers\Api\V1` (SIN `/app/`)
- **Ubicación:** `app/Http/Controllers/Api/V1/` (CON `/app/`)
- **Herencia:** De `Illuminate\Routing\Controller` (no de `App\Http\Controllers\Controller`)
- **Actions:** Todos los traits de `LaravelJsonApi\Laravel\Http\Controllers\Actions`

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
<?php

namespace Modules\{ModuleName}\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The module namespace to assume when generating URLs to actions.
     * ⚠️ IMPORTANTE: Sin /app/ en el namespace
     */
    protected string $moduleNamespace = 'Modules\{ModuleName}\Http\Controllers';

    /**
     * Called before routes are registered.
     */
    public function boot(): void
    {
        parent::boot();
    }

    /**
     * Define the routes for the application.
     * 🔥 NUEVO: Agregamos mapJsonApiRoutes() para separar rutas JSON:API
     */
    public function map(): void
    {
        $this->mapApiRoutes();
        $this->mapWebRoutes();
        $this->mapJsonApiRoutes(); // ← NUEVO método para JSON:API
    }

    /**
     * Define the "web" routes for the application.
     * 📁 Archivo: routes/web.php (puede estar vacío)
     */
    protected function mapWebRoutes(): void
    {
        Route::middleware('web')
            ->namespace($this->moduleNamespace)
            ->group(module_path('{ModuleName}', '/routes/web.php'));
    }

    /**
     * Define the "api" routes for the application.
     * 📁 Archivo: routes/api.php (debe estar VACÍO para JSON:API)
     */
    protected function mapApiRoutes(): void
    {
        Route::prefix('api')
            ->middleware('api')
            ->namespace($this->moduleNamespace)
            ->group(module_path('{ModuleName}', '/routes/api.php'));
    }

    /**
     * 🚀 NUEVO: Define the "jsonapi" routes for the application.
     * 📁 Archivo: routes/jsonapi.php (contiene las rutas JSON:API)
     */
    protected function mapJsonApiRoutes(): void
    {
        Route::prefix('api')
            ->middleware('api')
            ->namespace($this->moduleNamespace)
            ->group(module_path('{ModuleName}', '/routes/jsonapi.php'));
    }
}
```

#### **Paso 3.3: Registrar en Server.php**

**Archivo:** `app/JsonApi/V1/Server.php`

```php
// En imports
use Modules\{ModuleName}\JsonApi\V1\{Entities}\{Entity}Schema;
use Modules\{ModuleName}\JsonApi\V1\{Entities}\{Entity}Authorizer;

// En allSchemas()
protected function allSchemas(): array
{
    return [
        // Schemas existentes...
        
        // {ModuleName} Module
        {Entity}Schema::class,
        // Repetir para cada entidad del módulo
    ];
}

// En authorizers()
protected function authorizers(): array
{
    return [
        // Authorizers existentes...
        
        '{entities}' => {Entity}Authorizer::class,
        // Repetir para cada entidad del módulo
    ];
}
```

---

### **FASE 4: SISTEMA DE PERMISOS**

#### **Paso 4.1: Crear PermissionSeeder**

**⚠️ CRÍTICO: Nomenclatura de permisos**
- **Regla:** Usar el nombre PLURAL del resource JSON:API
- **Formato:** `{entities}.{action}` (kebab-case + plural)
- **Ejemplos:** `suppliers.index`, `purchase-orders.store`, `purchase-order-items.update`

```php
<?php

namespace Modules\{ModuleName}\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class {ModuleName}PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Crear permisos para cada entidad del módulo
        $entities = ['{entities}', 'other-entities']; // Agregar todas las entidades
        $actions = ['index', 'view', 'store', 'update', 'destroy'];

        foreach ($entities as $entity) {
            foreach ($actions as $action) {
                Permission::firstOrCreate([
                    'name' => "{$entity}.{$action}",
                    'guard_name' => 'api'
                ]);
            }
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
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Asignar permisos a roles
        $godRole = Role::where('name', 'god')->first();
        $adminRole = Role::where('name', 'admin')->first();
        $techRole = Role::where('name', 'tech')->first();

        if ($godRole) {
            // God tiene todos los permisos
            $allPermissions = Permission::where('name', 'like', '{entities}.%')
                                      ->orWhere('name', 'like', 'other-entities.%')
                                      ->get();
            $godRole->givePermissionTo($allPermissions);
        }

        if ($adminRole) {
            // Admin tiene permisos CRUD completos
            $adminPermissions = Permission::whereIn('name', [
                '{entities}.index', '{entities}.view', '{entities}.store', 
                '{entities}.update', '{entities}.destroy',
                // Agregar permisos para otras entidades
            ])->get();
            $adminRole->givePermissionTo($adminPermissions);
        }

        if ($techRole) {
            // Tech solo lectura
            $techPermissions = Permission::whereIn('name', [
                '{entities}.index', '{entities}.view',
                // Agregar permisos de solo lectura para otras entidades
            ])->get();
            $techRole->givePermissionTo($techPermissions);
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
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = {Entity}::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'code' => $this->faker->unique()->regexify('[A-Z]{3}[0-9]{3}'),
            'description' => $this->faker->optional()->paragraph(),
            'price' => round($this->faker->randomFloat(2, 10, 1000), 2),
            'is_active' => $this->faker->boolean(80), // 80% activos
            
            // Campos JSON como arrays asociativos
            'certifications' => [
                'ISO9001' => $this->faker->boolean(),
                'HACCP' => $this->faker->boolean(),
                'Organic' => $this->faker->boolean(),
            ],
            'specifications' => [
                'weight' => $this->faker->randomFloat(2, 0.1, 10),
                'dimensions' => [
                    'length' => $this->faker->randomFloat(2, 1, 100),
                    'width' => $this->faker->randomFloat(2, 1, 100),
                    'height' => $this->faker->randomFloat(2, 1, 100),
                ],
            ],
            
            // Relaciones (opcional, se puede hacer en el seeder)
            'parent_entity_id' => null, // Se establecerá en el seeder si es necesario
        ];
    }

    /**
     * Indicate that the entity is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the entity has a specific price range.
     */
    public function expensive(): static
    {
        return $this->state(fn (array $attributes) => [
            'price' => round($this->faker->randomFloat(2, 500, 2000), 2),
        ]);
    }
}
```

#### **Paso 5.2: Crear Seeders de datos**

#### **{ModuleName}DatabaseSeeder.php - Seeder Principal**

```php
<?php

namespace Modules\{ModuleName}\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class {ModuleName}DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Model::unguard();

        // Ejecutar seeders en orden de dependencias
        $this->call([
            {ModuleName}PermissionSeeder::class,
            {ModuleName}AssignPermissionsSeeder::class,
            {Entity}Seeder::class,
            // Agregar más seeders según sea necesario
        ]);
    }
}
```

#### **{Entity}Seeder.php - Datos de Ejemplo**

```php
<?php

namespace Modules\{ModuleName}\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\{ModuleName}\Models\{Entity};

class {Entity}Seeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear datos específicos del negocio
        $entities = [
            [
                'name' => 'Entidad por Defecto',
                'code' => 'DEFAULT',
                'description' => 'Entidad creada automáticamente por el seeder',
                'is_active' => true,
                'certifications' => [
                    'ISO9001' => true,
                    'HACCP' => false,
                ],
                'specifications' => [
                    'priority' => 'high',
                    'category' => 'standard',
                ],
            ],
            // Más datos específicos...
        ];

        foreach ($entities as $entityData) {
            {Entity}::firstOrCreate(
                ['code' => $entityData['code']],
                $entityData
            );
        }

        // Crear datos de ejemplo usando factory
        {Entity}::factory(15)->create();
    }
}
```

---

### **FASE 6: TESTING (CRÍTICO)**

#### **Paso 6.1: Configuración base para tests**

**⚠️ PATRÓN ACTUALIZADO - CLEAN TESTING (Customer module probado):**
- ✅ **Helper methods** para usuarios pre-configurados (más eficiente)
- ✅ **TestCase automático** con module seeding (elimina setup manual)
- ✅ **Patrón snake_case** en todos los campos API
- ✅ **JSON API testing** con `expects()`, `assertOk()`, `assertForbidden()`

#### **Paso 6.2: Crear tests CRUD para cada entidad**

**Ubicación:** `Modules/{ModuleName}/tests/Feature/{Entity}*Test.php`

**🎯 PATRÓN CLEAN TESTING COMPROBADO (49/49 tests pasando):**

```php
<?php

namespace Modules\{ModuleName}\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\{ModuleName}\Models\{Entity};

class {Entity}IndexTest extends TestCase
{
    private function getAdminUser(): User
    {
        return User::where('email', 'admin@example.com')->firstOrFail();
    }

    private function getTechUser(): User
    {
        return User::where('email', 'tech@example.com')->firstOrFail();
    }

    private function getCustomerUser(): User
    {
        return User::where('email', 'customer@example.com')->firstOrFail();
    }

    public function test_admin_can_list_{entities}(): void
    {
        $admin = $this->getAdminUser();
        
        {Entity}::factory()->count(3)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('{entities}')
            ->get('/api/v1/{entities}');

        $response->assertOk();
        $response->assertJsonCount(3, 'data');
    }

    public function test_admin_can_sort_{entities}_by_name(): void
    {
        $admin = $this->getAdminUser();
        
        {Entity}::factory()->create(['name' => 'Z Entity']);
        {Entity}::factory()->create(['name' => 'A Entity']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('{entities}')
            ->get('/api/v1/{entities}?sort=name');

        $response->assertOk();
        $names = collect($response->json('data'))->pluck('attributes.name');
        $this->assertEquals(['A Entity', 'Z Entity'], $names->toArray());
    }

    public function test_admin_can_filter_{entities}_by_classification(): void
    {
        $admin = $this->getAdminUser();
        
        {Entity}::factory()->count(2)->create(['classification' => 'premium']);
        {Entity}::factory()->count(1)->create(['classification' => 'basic']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('{entities}')
            ->get('/api/v1/{entities}?filter[classification]=premium');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
    }

    public function test_admin_can_filter_{entities}_by_active_status(): void
    {
        $admin = $this->getAdminUser();
        
        {Entity}::factory()->count(2)->create(['is_active' => true]);
        {Entity}::factory()->count(1)->create(['is_active' => false]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('{entities}')
            ->get('/api/v1/{entities}?filter[is_active]=true');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
    }

    public function test_tech_user_can_list_{entities}_with_permission(): void
    {
        $tech = $this->getTechUser();
        
        {Entity}::factory()->count(2)->create();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('{entities}')
            ->get('/api/v1/{entities}');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
    }

    public function test_customer_user_cannot_list_{entities}(): void
    {
        $customer = $this->getCustomerUser();
        
        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('{entities}')
            ->get('/api/v1/{entities}');

        $response->assertForbidden();
    }

    public function test_guest_cannot_list_{entities}(): void
    {
        $response = $this->jsonApi()
            ->expects('{entities}')
            ->get('/api/v1/{entities}');

        $response->assertStatus(401);
    }

    public function test_can_paginate_{entities}(): void
    {
        $admin = $this->getAdminUser();
        
        {Entity}::factory()->count(25)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('{entities}')
            ->get('/api/v1/{entities}?page[size]=10');

        $response->assertOk();
        $response->assertJsonCount(10, 'data');
        $response->assertJsonStructure([
            'links' => ['first', 'last', 'prev', 'next'],
            'meta' => ['current_page', 'last_page', 'per_page', 'total']
        ]);
    }

    public function test_can_search_{entities}_by_name(): void
    {
        $admin = $this->getAdminUser();
        
        {Entity}::factory()->create(['name' => 'Searchable Entity']);
        {Entity}::factory()->create(['name' => 'Other Entity']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('{entities}')
            ->get('/api/v1/{entities}?filter[name]=Searchable');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
    }
}
```

**🎯 PATRÓN STORE TEST:**

```php
<?php

namespace Modules\{ModuleName}\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\{ModuleName}\Models\{Entity};

class {Entity}StoreTest extends TestCase
{
    private function getAdminUser(): User
    {
        return User::where('email', 'admin@example.com')->firstOrFail();
    }

    private function getTechUser(): User
    {
        return User::where('email', 'tech@example.com')->firstOrFail();
    }

    private function getCustomerUser(): User
    {
        return User::where('email', 'customer@example.com')->firstOrFail();
    }

    public function test_admin_can_create_{entity}(): void
    {
        $admin = $this->getAdminUser();
        
        $data = [
            'type' => '{entities}',
            'attributes' => [
                'name' => 'Test Entity',
                'email' => 'test@example.com',
                'classification' => 'premium',
                'credit_limit' => 5000.00,
                'is_active' => true
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('{entities}')
            ->withData($data)
            ->post('/api/v1/{entities}');

        $response->assertCreated();
        
        $id = $response->json('data.id');
        $this->assertDatabaseHas('{entities}', [
            'id' => $id,
            'name' => 'Test Entity',
            'email' => 'test@example.com'
        ]);
    }

    public function test_customer_user_cannot_create_{entity}(): void
    {
        $customer = $this->getCustomerUser();
        
        $data = [
            'type' => '{entities}',
            'attributes' => [
                'name' => 'Forbidden Entity'
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('{entities}')
            ->withData($data)
            ->post('/api/v1/{entities}');

        $response->assertForbidden();
    }

    public function test_cannot_create_{entity}_without_required_fields(): void
    {
        $admin = $this->getAdminUser();
        
        $data = [
            'type' => '{entities}',
            'attributes' => [] // Sin campos requeridos
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('{entities}')
            ->withData($data)
            ->post('/api/v1/{entities}');

        $response->assertErrorStatus();
        $this->assertJsonApiValidationErrors($response, ['name', 'email']);
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
            'attributes' => (object) [ // ⚠️ IMPORTANTE: usar (object) para array vacío
                // Campos requeridos faltantes
            ]
        ]
    ];

    $response = $this->actingAs($admin, 'sanctum')
        ->jsonApi()
        ->expects('{entities}')
        ->withData($invalidData['data'])
        ->post('/api/v1/{entities}');

    $response->assertStatus(422);
    $this->assertJsonApiValidationErrors([
        '/data/attributes/name',
    ], $response);
}

/** @test */
public function admin_can_create_entity_with_json_fields()
{
    $admin = $this->createUserWithPermissions(['{entities}.store']);
    
    $data = [
        'data' => [
            'type' => '{entities}',
            'attributes' => [
                'name' => 'Test Entity',
                'code' => 'TEST001',
                'price' => 100.0, // ⚠️ IMPORTANTE: usar float, no strings
                'isActive' => true,
                'certifications' => [ // ⚠️ IMPORTANTE: array asociativo para ArrayHash
                    'ISO9001' => true,
                    'HACCP' => false,
                ],
                'specifications' => [
                    'weight' => 5.5,
                    'priority' => 'high',
                ]
            ]
        ]
    ];

    $response = $this->actingAs($admin, 'sanctum')
        ->jsonApi()
        ->expects('{entities}')
        ->withData($data['data'])
        ->post('/api/v1/{entities}');

    $response->assertStatus(201);
    $response->assertJsonStructure([
        'data' => [
            'attributes' => [
                'certifications',
                'specifications',
                'price'
            ]
        ]
    ]);
    
    $this->assertDatabaseHas('{entities}', [
        'name' => 'Test Entity',
        'code' => 'TEST001',
    ]);
}
```

#### **Paso 6.3: Tests completos requeridos por entidad**

**Crear estos 5 tests mínimo por entidad:**
- `{Entity}IndexTest.php` - Listado y filtros
- `{Entity}ShowTest.php` - Mostrar individual
- `{Entity}StoreTest.php` - Crear con validaciones
- `{Entity}UpdateTest.php` - Actualizar con validaciones
- `{Entity}DestroyTest.php` - Eliminar

---

### **FASE 7: FINALIZACIÓN**

#### **Paso 7.1: Registrar en DatabaseSeeder principal**

**⚠️ CRÍTICO: Este paso es OBLIGATORIO y frecuentemente olvidado**

**Archivo:** `database/seeders/DatabaseSeeder.php`

```php
public function run(): void
{
    // Seeders base del sistema
    $this->call([
        UserSeeder::class,
        RolePermissionSeeder::class,
    ]);

    // Seeders de módulos (en orden de dependencias)
    $this->call([
        \Modules\PermissionManager\Database\Seeders\PermissionManagerDatabaseSeeder::class,
        \Modules\Audit\Database\Seeders\AuditDatabaseSeeder::class,
        \Modules\PageBuilder\Database\Seeders\PageBuilderDatabaseSeeder::class,
        \Modules\User\Database\Seeders\UserDatabaseSeeder::class,
        \Modules\Product\Database\Seeders\ProductDatabaseSeeder::class,
        \Modules\Inventory\Database\Seeders\InventoryDatabaseSeeder::class,
        \Modules\Purchase\Database\Seeders\PurchaseDatabaseSeeder::class, // ⚠️ AGREGAR AQUÍ
        \Modules\{ModuleName}\Database\Seeders\{ModuleName}DatabaseSeeder::class, // ⚠️ NUEVO MÓDULO
    ]);
}
```

#### **Paso 7.2: Configurar module.json**

```json
{
    "name": "{ModuleName}",
    "alias": "{module_name}",
    "description": "Módulo para gestión de {descripción del módulo}",
    "keywords": ["{keywords}", "json-api", "laravel"],
    "priority": 0,
    "providers": [
        "Modules\\{ModuleName}\\Providers\\{ModuleName}ServiceProvider"
    ],
    "aliases": {},
    "files": [],
    "requires": []
}
```

#### **Paso 7.3: Ejecutar migraciones y seeders**

```bash
# Migrar y poblar base de datos
php artisan migrate:fresh --seed

# Verificar que no hay errores
echo $?
```

#### **Paso 7.4: Verificar rutas**

```bash
# Verificar rutas del módulo
php artisan route:list --path=api/v1/{entities}

# Verificar que las rutas están registradas
php artisan route:list | grep {entities}
```

#### **Paso 7.5: Ejecutar tests**

```bash
# Ejecutar tests del módulo
php artisan test Modules/{ModuleName}/Tests/Feature/

# Verificar cobertura mínima
php artisan test --coverage
```

---

## 🎯 **LECCIONES APRENDIDAS - CUSTOMER MODULE STUDY CASE**

**Basado en implementación exitosa de Customer entity (Sales module - 49/49 tests pasando)**

### **✅ PATRONES QUE FUNCIONAN PERFECTAMENTE:**

#### **1. Clean Testing Pattern (Token-efficient)**
```yaml
helper_methods:
  - getAdminUser(): "User::where('email', 'admin@example.com')->firstOrFail()"
  - getTechUser(): "User::where('email', 'tech@example.com')->firstOrFail()"
  - getCustomerUser(): "User::where('email', 'customer@example.com')->firstOrFail()"

advantages:
  - no_manual_setup: "TestCase automático con module seeding"
  - consistent_users: "Mismos usuarios across all tests"
  - token_efficient: "Clean rewrite > debugging complex tests"
```

#### **2. Authorization Pattern (Role-based Granular)**
```php
// ✅ PATRÓN COMPROBADO
public function store(Request $request, string $modelClass): bool|Response
{
    $user = $request->user();
    
    // Customer users cannot create entities (business rule)
    if ($user && $user->hasRole('customer')) {
        return false;
    }
    
    return $user?->can('entities.store') ?? false; // Sin guard 'api'
}
```

#### **3. JSON API Testing Pattern**
```php
// ✅ PATRÓN EXITOSO
$response = $this->actingAs($admin, 'sanctum')
    ->jsonApi()
    ->expects('customers')  // Resource type
    ->withData($data)       // Para POST/PATCH
    ->get('/api/v1/customers');

$response->assertOk();           // 200
$response->assertCreated();      // 201  
$response->assertForbidden();    // 403
```

#### **4. Field Naming Consistency**
```yaml
api_fields: "snake_case throughout (credit_limit, is_active, created_at)"
validation_rules: "snake_case keys"
database_columns: "snake_case names"
json_response: "snake_case attributes"
```

### **❌ ANTI-PATTERNS QUE CAUSAN PROBLEMAS:**

#### **1. Testing Environment Bypasses**
```php
// ❌ PROBLEMÁTICO - Permite TODO en testing
if (app()->environment(['testing', 'local'])) {
    return $request->user() !== null;
}
```

#### **2. Naming Inconsistencies**
```yaml
incorrect_authorizer: "CustomersAuthorizer (plural)"
correct_authorizer: "CustomerAuthorizer (singular)"
reason: "JSON API convention - singular for Schema/Authorizer"
```

#### **3. Complex Test Setup**
```php
// ❌ COMPLEJO Y PROPENSO A ERRORES
protected function setUp(): void {
    // Manual permission creation
    // Manual role assignment  
    // Complex user creation
}

// ✅ SIMPLE Y EFECTIVO
private function getAdminUser(): User {
    return User::where('email', 'admin@example.com')->firstOrFail();
}
```

#### **4. Guard API Confusion**
```yaml
previous_belief: "MUST use guard 'api' everywhere"
actual_reality: "Works fine without explicit guard"
lesson: "Spatie permissions work correctly with default guard handling"
```

### **🔧 DEBUGGING LESSONS:**

#### **1. Authorization Issues:**
```yaml
symptom: "Customer user can do everything when should be forbidden"
cause: "Testing environment bypass in Authorizer"
solution: "Remove app()->environment() checks, implement role-based logic"
```

#### **2. Test Failures:**
```yaml
approach_failed: "Fix existing complex tests"
approach_success: "Clean rewrite with established patterns"
efficiency: "More token-efficient, cleaner code"
```

#### **3. Permission Problems:**
```yaml
issue: "403 errors despite user having permissions"
causes:
  - wrong_guard: "Using 'api' when not needed"
  - bypass_logic: "Environment-based bypasses interfering"
solution: "Role-based granular checks before generic permissions"
```

### **📈 SUCCESS METRICS - CUSTOMER MODULE:**

#### **Test Results:**
```yaml
CustomerIndexTest: "9/9 tests (100%)"
CustomerShowTest: "9/9 tests (100%)"  
CustomerStoreTest: "10/10 tests (100%)"
CustomerUpdateTest: "11/11 tests (100%)"
CustomerDestroyTest: "10/10 tests (100%)"
total: "49/49 tests (100%)"
```

#### **Feature Coverage:**
```yaml
crud_operations: "Complete (Create, Read, Update, Delete)"
authorization: "Granular by role (god, admin, tech, customer, guest)"
validation: "Comprehensive edge cases and business rules"
filtering: "name, email, classification, is_active"
sorting: "Multiple fields with direction support"
pagination: "JSON API compliant pagination"
relationships: "HasMany salesOrders working"
```

#### **API Compliance:**
```yaml
json_api_version: "1.1 strict compliance"
endpoint_structure: "RESTful with proper status codes"
error_handling: "Standardized JSON API error format"
documentation: "Auto-generated and complete"
```

---

## 🎯 **CHECKLIST DE COMPLETITUD POR ENTIDAD**

**Para CADA entidad del módulo, verificar:**

### **Estructura Base:**
- [ ] ✅ Model con todas las propiedades @property
- [ ] ✅ Migration ejecutada correctamente sin errores
- [ ] ✅ Factory con datos realistas y estados

### **JSON:API Components:**
- [ ] ✅ Schema con TODOS los campos de la migración mapeados
- [ ] ✅ Schema registrado en Server.php (allSchemas)
- [ ] ✅ Authorizer con TODOS los métodos implementados
- [ ] ✅ Authorizer registrado en Server.php (authorizers)
- [ ] ✅ Request con validaciones completas y mensajes
- [ ] ✅ Resource con TODOS los atributos y relaciones
- [ ] ✅ Controller con Actions traits correctos

### **Rutas y Configuración:**
- [ ] ✅ Rutas registradas en jsonapi.php
- [ ] ✅ RouteServiceProvider configurado correctamente
- [ ] ✅ Rutas funcionando (`php artisan route:list`)

### **Permisos y Seguridad:**
- [ ] ✅ Permisos creados con nomenclatura correcta (plural)
- [ ] ✅ Permisos asignados a roles apropiados
- [ ] ✅ Authorizer usando permisos correctos

### **Testing:**
- [ ] ✅ Test Index (listado, filtros, sorting, permisos)
- [ ] ✅ Test Show (mostrar individual, permisos)
- [ ] ✅ Test Store (crear, validaciones, permisos)
- [ ] ✅ Test Update (actualizar, validaciones, permisos)
- [ ] ✅ Test Destroy (eliminar, constraints, permisos)
- [ ] ✅ Tests pasando al 100%

### **Integración:**
- [ ] ✅ Seeder registrado en DatabaseSeeder principal
- [ ] ✅ API respondiendo correctamente (aunque requiera auth)
- [ ] ✅ Datos de ejemplo insertados correctamente

---

## 🚨 **ERRORES COMUNES IDENTIFICADOS**

### **1. Inconsistencia de campos:**
- ❌ Campo en migración pero no en Schema
- ❌ Campo en Schema pero no en Resource
- ❌ Campo en Resource pero no en Model @property
- ❌ Mapeo incorrecto camelCase ↔ snake_case

### **2. Namespace incorrecto:**
- ❌ `Modules\{ModuleName}\app\Http\Controllers` (incluir /app/)
- ✅ `Modules\{ModuleName}\Http\Controllers` (sin /app/)

### **3. Ubicación física incorrecta:**
- ❌ `JsonApi/V1/` (fuera de app/)
- ✅ `app/JsonApi/V1/` (dentro de app/)

### **4. JSON:API 5.x vs 4.x:**
- ❌ Usar sintaxis de JSON:API 4.x o inferior
- ✅ Usar sintaxis específica de JSON:API 5.x
- ❌ `relationships()` method con definiciones
- ✅ `relationships()` method vacío, relaciones en `fields()`

### **5. Nomenclatura de permisos (CRÍTICO):**
- ❌ `'warehouse.index'` (singular)
- ✅ `'warehouses.index'` (plural)
- ❌ `'product-batch.store'` (no plural)
- ✅ `'product-batches.store'` (plural + kebab-case)

### **6. Campos JSON y tipos de datos:**
- ❌ `'certifications' => ['ISO9001', 'HACCP']` (array indexado)
- ✅ `'certifications' => ['ISO9001' => true, 'HACCP' => true]` (array asociativo)
- ❌ `ArrayList::make('certifications')` (obsoleto)
- ✅ `ArrayHash::make('certifications')` (requiere arrays asociativos)

### **7. Campos decimales:**
- ❌ `'price' => 'decimal:4'` (puede causar string en JSON)
- ✅ `'price' => 'float'` (garantiza numeric en JSON)

### **8. User model integration:**
- ❌ User model sin HasRoles trait
- ✅ `use Spatie\Permission\Traits\HasRoles;` en User model

### **9. Rutas no registradas:**
- ❌ Olvidar registrar rutas en jsonapi.php
- ❌ RouteServiceProvider sin mapJsonApiRoutes()
- ❌ Middleware incorrecto en rutas

### **10. Seeder no registrado:**
- ❌ Crear seeders pero no registrarlos en DatabaseSeeder
- ❌ Orden incorrecto de seeders (dependencias)

---

## 🚀 **COMANDOS DE GENERACIÓN AUTOMATIZADA**

### **Comandos para crear un módulo completo:**

```bash
# 1. Crear el módulo base
php artisan module:make {ModuleName}

# 2. Crear migraciones
php artisan module:make-migration create_{entities}_table {ModuleName}

# 3. Crear modelos
php artisan module:make-model {Entity} {ModuleName}

# 4. Crear factories
php artisan module:make-factory {Entity}Factory {ModuleName}

# 5. Crear seeders
php artisan module:make-seeder {ModuleName}DatabaseSeeder {ModuleName}
php artisan module:make-seeder {ModuleName}PermissionSeeder {ModuleName}
php artisan module:make-seeder {Entity}Seeder {ModuleName}

# 6. Crear tests
php artisan module:make-test Feature/{Entity}IndexTest {ModuleName}
php artisan module:make-test Feature/{Entity}ShowTest {ModuleName}
php artisan module:make-test Feature/{Entity}StoreTest {ModuleName}
php artisan module:make-test Feature/{Entity}UpdateTest {ModuleName}
php artisan module:make-test Feature/{Entity}DestroyTest {ModuleName}

# 7. ⚠️ IMPORTANTE: Componentes JSON:API se crean MANUALMENTE
# No hay comandos de generación, seguir los patrones del blueprint

# 8. Ejecutar migraciones y seeders
php artisan migrate
php artisan db:seed --class="Modules\{ModuleName}\Database\Seeders\{ModuleName}DatabaseSeeder"

# 9. Verificar rutas
php artisan route:list --path=api/v1

# 10. Ejecutar tests
php artisan test Modules/{ModuleName}/Tests/Feature/
```

---

## 📊 **MÉTRICAS DE ÉXITO**

### **Por módulo completado:**
- [ ] ✅ Todas las entidades implementadas
- [ ] ✅ Rutas funcionando (`php artisan route:list`)
- [ ] ✅ API respondiendo correctamente
- [ ] ✅ Tests pasando (mínimo 20 tests por módulo de 4 entidades)
- [ ] ✅ Seeders ejecutándose sin error
- [ ] ✅ Permisos asignados correctamente
- [ ] ✅ Cobertura de tests > 80%

### **Indicadores de calidad:**
- **Consistencia:** Todos los campos mapeados en todos los componentes
- **Seguridad:** Todos los endpoints protegidos con permisos
- **Testing:** Casos de éxito, error y permisos cubiertos
- **Performance:** Índices en BD, paginación configurada
- **Mantenibilidad:** Código claro, comentado y documentado

---

## 🎓 **APRENDIZAJES DE IMPLEMENTACIONES ANTERIORES**

### **Del módulo INVENTORY (exitoso):**
- ✅ Usar ArrayHash para campos JSON con arrays asociativos
- ✅ Casts 'float' para decimales garantiza números en JSON:API
- ✅ Permisos SIEMPRE en plural (warehouses.index, no warehouse.index)
- ✅ Tests exhaustivos previenen regresiones
- ✅ Factory con estados (active/inactive) es muy útil

### **Del módulo PURCHASE (en desarrollo):**
- ✅ Namespace consistency crítico para evitar errores
- ✅ JSON:API 5.x requiere relaciones en fields(), no relationships()
- ✅ Rutas simples funcionan mejor que relaciones complejas en routes
- ✅ Importancia de corregir errores inmediatamente

### **Patrones generales identificados:**
- 🔄 Iteración rápida con validación constante
- 📝 Documentación durante desarrollo, no después
- 🧪 Tests desde el primer momento
- 🎯 Un error namespace puede bloquear todo el módulo
- 📊 Métricas claras permiten medir progreso

---

**¡Con esta guía técnica unificada puedes crear cualquier módulo siguiendo el patrón establecido y mantener la consistencia arquitectónica perfecta!** 🚀

**Versión 3.0 Master - Actualizada con todos los aprendizajes y patrones validados en producción.**
