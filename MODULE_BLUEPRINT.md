# 🏗️ MODULE BLUEPRINT - Guía Técnica para Creación de Módulos

**Especificación técnica completa para crear módulos en api-base usando Laravel JSON:API 5.x + nwidart/laravel-modules**

**Versión:** 1.0  
**Basado en:** Product Module (patrón establecido)  
**Arquitectura:** Laravel 11 + JSON:API 5.x + Módulos + Spatie Permissions

---

## 📋 **ESTRUCTURA COMPLETA DEL MÓDULO**

### **Anatomía de un módulo tipo:**

```
Modules/{ModuleName}/
├── app/
│   ├── Http/
│   │   ├── Controllers/Api/V1/          # Controllers JSON:API
│   │   │   ├── {Entity}Controller.php   # Un controller por entidad
│   │   │   └── ...
│   ├── JsonApi/V1/
│   │   ├── {Entities}/                  # Carpeta plural por entidad
│   │   │   ├── {Entity}Schema.php       # Schema con fields/relations/filters
│   │       ├── {Entity}Request.php      # Validaciones para crear/actualizar
│   │   │   ├── {Entity}Resource.php     # JSON:API Resource (opcional)
│   │   │   └── {Entity}Authorizer.php   # Control de acceso granular
│   │   └── ...
│   ├── Models/                          # Eloquent Models
│   │   ├── {Entity}.php                 # Modelo principal
│   │   └── ...
│   └── Providers/                       # Service Providers del módulo
│       ├── {ModuleName}ServiceProvider.php  # Provider principal del módulo
│       └── RouteServiceProvider.php     # Provider de rutas (si se necesita)
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

## 🧩 **COMPONENTES CLAVE DEL MÓDULO**

### **1. Models - Eloquent con Relaciones**

```php
<?php

namespace Modules\{ModuleName}\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class {Entity} extends Model
{
    use HasFactory;

    /**
     * The attributes that aren't mass assignable.
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast to native types.
     */
    protected $casts = [
        'id' => 'integer',
        'price' => 'float',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        // Agregar más casts según el tipo de datos
    ];

    /**
     * Relaciones BelongsTo (pertenece a)
     */
    public function relatedEntity(): BelongsTo
    {
        return $this->belongsTo(RelatedEntity::class);
    }

    /**
     * Relaciones HasMany (tiene muchos)
     */
    public function childEntities(): HasMany
    {
        return $this->hasMany(ChildEntity::class);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Modules\{ModuleName}\Database\Factories\{Entity}Factory::new();
    }
}
```

### **2. Migrations - Schema de Base de Datos**

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
            
            // Foreign keys
            $table->foreignId('related_entity_id')
                  ->constrained('related_entities')
                  ->onDelete('restrict'); // o cascade según negocio
            
            $table->timestamps();
            
            // Índices para mejorar performance
            $table->index(['name']);
            $table->index(['is_active']);
            $table->index(['related_entity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('{entities}');
    }
};
```

### **3. JSON:API Schema - Laravel JSON:API 5.x**

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
            
            // Campos básicos
            Str::make('name')->sortable(),
            Str::make('code')->sortable(),
            Str::make('description'),
            Number::make('price')->sortable(),
            Boolean::make('isActive', 'is_active'),
            
            // Relaciones BelongsTo (muchos a uno)
            BelongsTo::make('relatedEntity', 'related_entity')
                     ->type('related-entities'),
            
            // Relaciones HasMany (uno a muchos)
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
            WhereIn::make('relatedEntity', 'related_entity_id'),
            Where::make('isActive', 'is_active')->asBoolean(),
        ];
    }

    /**
     * Get the resource paginator.
     */
    public function pagination(): ?PagePagination
    {
        return PagePagination::make();
    }
}
```

### **4. Controllers - Laravel JSON:API 5.x**

**Ubicación:** `Modules/{ModuleName}/app/Http/Controllers/Api/V1/{Entity}Controller.php`

**Generación con comando oficial:**
```bash
# Generar los controladores usando el comando oficial de JSON:API
php artisan jsonapi:controller WarehouseController
php artisan jsonapi:controller WarehouseLocationController
php artisan jsonapi:controller ProductBatchController
php artisan jsonapi:controller StockController

# Los archivos se generan en app/Http/Controllers/ y deben moverse a:
# Modules/{ModuleName}/app/Http/Controllers/Api/V1/
```

**Estructura del Controller:**
```php
<?php

namespace Modules\{ModuleName}\Http\Controllers\Api\V1;

use Illuminate\Routing\Controller;
use LaravelJsonApi\Laravel\Http\Controllers\Actions;

class {Entity}Controller extends Controller
{
    // Actions traits para operaciones CRUD automáticas
    use Actions\FetchMany;       // GET /api/v1/{entities}
    use Actions\FetchOne;        // GET /api/v1/{entities}/{id}
    use Actions\Store;           // POST /api/v1/{entities}
    use Actions\Update;          // PATCH /api/v1/{entities}/{id}
    use Actions\Destroy;         // DELETE /api/v1/{entities}/{id}
    
    // Actions para relaciones
    use Actions\FetchRelated;        // GET /api/v1/{entities}/{id}/related-entity
    use Actions\FetchRelationship;   // GET /api/v1/{entities}/{id}/relationships/related-entity
    use Actions\UpdateRelationship;  // PATCH /api/v1/{entities}/{id}/relationships/related-entity
    use Actions\AttachRelationship;  // POST /api/v1/{entities}/{id}/relationships/child-entities
    use Actions\DetachRelationship;  // DELETE /api/v1/{entities}/{id}/relationships/child-entities
}
```

**Puntos importantes:**
- ⚠️ **Namespace correcto:** `Modules\{ModuleName}\Http\Controllers\Api\V1` (SIN `/app/`)
- 📁 **Ubicación física:** `Modules/{ModuleName}/app/Http/Controllers/Api/V1/` (CON `/app/`)
- 🎯 **Herencia:** De `Illuminate\Routing\Controller` (no de `App\Http\Controllers\Controller`)
- ⚡ **Actions:** Todos los traits de `LaravelJsonApi\Laravel\Http\Controllers\Actions`

### **5. Authorizers - Control de Acceso**

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
        return $request->user()?->can('{entities}.index') ?? false;
    }

    /**
     * Authorize the store controller action (POST /api/v1/{entities})
     */
    public function store(Request $request, string $modelClass): bool|Response
    {
        return $request->user()?->can('{entities}.store') ?? false;
    }

    /**
     * Authorize the show controller action (GET /api/v1/{entities}/{id})
     */
    public function show(Request $request, object $model): bool|Response
    {
        return $request->user()?->can('{entities}.show') ?? false;
    }

    /**
     * Authorize the update controller action (PATCH /api/v1/{entities}/{id})
     */
    public function update(Request $request, object $model): bool|Response
    {
        return $request->user()?->can('{entities}.update') ?? false;
    }

    /**
     * Authorize the destroy controller action (DELETE /api/v1/{entities}/{id})
     */
    public function destroy(Request $request, object $model): bool|Response
    {
        return $request->user()?->can('{entities}.destroy') ?? false;
    }
}
```

### **6. Form Requests - Validaciones**

```php
<?php

namespace Modules\{ModuleName}\Http\Requests;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use LaravelJsonApi\Validation\Rule as JsonApiRule;

class {Entity}Request extends ResourceRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('{entities}.store') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50', 'unique:{entities},code'],
            'description' => ['nullable', 'string'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
            'related_entity_id' => ['required', 'exists:related_entities,id'],
            'roles' => JsonApiRule::toMany(),

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
            'related_entity_id.exists' => 'La entidad relacionada no existe.',
        ];
    }
}
```


### **7. Factories - Datos de Prueba**

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
            'price' => $this->faker->randomFloat(2, 10, 1000),
            'is_active' => $this->faker->boolean(80), // 80% activos
            'related_entity_id' => \Modules\RelatedModule\Models\RelatedEntity::factory(),
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
            'price' => $this->faker->randomFloat(2, 500, 2000),
        ]);
    }
}
```

### **8. Module Service Provider**

```php
<?php

namespace Modules\{ModuleName}\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;

class {ModuleName}ServiceProvider extends ServiceProvider
{
    /**
     * Module namespace
     */
    protected string $moduleName = '{ModuleName}';

    /**
     * Module namespace in lowercase
     */
    protected string $moduleNameLower = '{module_name}';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'Database/Migrations'));
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }

    /**
     * Register config.
     */
    protected function registerConfig(): void
    {
        $this->publishes([
            module_path($this->moduleName, 'config/config.php') => config_path($this->moduleNameLower . '.php'),
        ], 'config');
        $this->mergeConfigFrom(
            module_path($this->moduleName, 'config/config.php'), $this->moduleNameLower
        );
    }

    /**
     * Register views.
     */
    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/' . $this->moduleNameLower);
        $sourcePath = module_path($this->moduleName, 'resources/views');

        $this->publishes([
            $sourcePath => $viewPath
        ], ['views', $this->moduleNameLower . '-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->moduleNameLower);
    }

    /**
     * Register translations.
     */
    public function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/' . $this->moduleNameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->moduleNameLower);
            $this->loadJsonTranslationsFrom($langPath);
        } else {
            $this->loadTranslationsFrom(module_path($this->moduleName, 'resources/lang'), $this->moduleNameLower);
            $this->loadJsonTranslationsFrom(module_path($this->moduleName, 'resources/lang'));
        }
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [];
    }

    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (\Config::get('view.paths') as $path) {
            if (is_dir($path . '/modules/' . $this->moduleNameLower)) {
                $paths[] = $path . '/modules/' . $this->moduleNameLower;
            }
        }
        return $paths;
    }
}
```

### **9. Route Service Provider**

**⚠️ CRÍTICO: Separación de rutas JSON:API vs API tradicionales**

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
     * 📁 Archivo: Routes/web.php (puede estar vacío)
     */
    protected function mapWebRoutes(): void
    {
        Route::middleware('web')
            ->namespace($this->moduleNamespace)
            ->group(module_path('{ModuleName}', '/Routes/web.php'));
    }

    /**
     * Define the "api" routes for the application.
     * 📁 Archivo: Routes/api.php (debe estar VACÍO para JSON:API)
     */
    protected function mapApiRoutes(): void
    {
        Route::prefix('api')
            ->middleware('api')
            ->namespace($this->moduleNamespace)
            ->group(module_path('{ModuleName}', '/Routes/api.php'));
    }

    /**
     * 🚀 NUEVO: Define the "jsonapi" routes for the application.
     * 📁 Archivo: Routes/jsonapi.php (contiene las rutas JSON:API)
     * 
     * Ventajas:
     * - Separación clara entre API tradicional y JSON:API
     * - Middleware específico para JSON:API
     * - Organización mejorada del código
     */
    protected function mapJsonApiRoutes(): void
    {
        Route::prefix('api')
            ->middleware('api')
            ->namespace($this->moduleNamespace)
            ->group(module_path('{ModuleName}', '/Routes/jsonapi.php'));
    }
}
```

**📋 Estructura de archivos de rutas:**

```
Routes/
├── web.php      # Rutas web (generalmente vacío en módulos API)
├── api.php      # API tradicional (DEBE ESTAR VACÍO para JSON:API)
└── jsonapi.php  # Rutas JSON:API (contiene ResourceRegistrar)
```

**🎯 Puntos clave:**
- ✅ **Tres tipos de rutas:** Web, API tradicional, y JSON:API
- ✅ **Separación clara:** Cada tipo en su propio archivo
- ✅ **Namespace correcto:** Sin `/app/` en el moduleNamespace
- ✅ **Flexibilidad:** Puedes agregar middleware específico a JSON:API si necesitas

### **10. Seeders del Módulo**

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
            {Entity}Seeder::class,
            // Agregar más seeders según sea necesario
        ]);
    }
}
```

#### **{ModuleName}PermissionSeeder.php - Permisos del Módulo**

```php
<?php

namespace Modules\{ModuleName}\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

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
        $entities = ['{entities}', 'other_entities']; // Agregar todas las entidades
        $actions = ['index', 'show', 'store', 'update', 'destroy'];

        foreach ($entities as $entity) {
            foreach ($actions as $action) {
                Permission::firstOrCreate([
                    'name' => "{$entity}.{$action}",
                    'guard_name' => 'sanctum'
                ]);
            }
        }

        // Asignar permisos a roles
        $godRole = Role::where('name', 'god')->first();
        $adminRole = Role::where('name', 'admin')->first();
        $techRole = Role::where('name', 'tech')->first();

        if ($godRole) {
            // God tiene todos los permisos
            $allPermissions = Permission::where('name', 'like', '{entities}.%')
                                      ->orWhere('name', 'like', 'other_entities.%')
                                      ->get();
            $godRole->givePermissionTo($allPermissions);
        }

        if ($adminRole) {
            // Admin tiene permisos CRUD completos
            $adminPermissions = Permission::whereIn('name', [
                '{entities}.index', '{entities}.show', '{entities}.store', 
                '{entities}.update', '{entities}.destroy',
                // Agregar permisos para otras entidades
            ])->get();
            $adminRole->givePermissionTo($adminPermissions);
        }

        if ($techRole) {
            // Tech solo lectura
            $techPermissions = Permission::whereIn('name', [
                '{entities}.index', '{entities}.show',
                // Agregar permisos de solo lectura para otras entidades
            ])->get();
            $techRole->givePermissionTo($techPermissions);
        }
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
        // Crear datos de ejemplo usando factory
        {Entity}::factory(20)->create();

        // O crear datos específicos
        {Entity}::firstOrCreate([
            'code' => 'DEFAULT'
        ], [
            'name' => 'Entidad por Defecto',
            'description' => 'Entidad creada automáticamente por el seeder',
            'is_active' => true,
        ]);
    }
}
```

### **11. DatabaseSeeder General - Registro de Módulos**

#### **app/Database/Seeders/DatabaseSeeder.php**

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seeders base del sistema
        $this->call([
            UserSeeder::class,
            RolePermissionSeeder::class,
        ]);

        // Seeders de módulos (en orden de dependencias)
        $this->call([
            \Modules\Product\Database\Seeders\ProductDatabaseSeeder::class,
            \Modules\Inventory\Database\Seeders\InventoryDatabaseSeeder::class,
            \Modules\Purchase\Database\Seeders\PurchaseDatabaseSeeder::class,
            \Modules\Sales\Database\Seeders\SalesDatabaseSeeder::class,
            // Agregar nuevos módulos aquí
        ]);
    }
}
```

### **12. TestCase Base - Helpers para Testing**

#### **tests/TestCase.php - Extendido con Helpers**

```php
<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use LaravelJsonApi\Testing\MakesJsonApiRequests;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use MakesJsonApiRequests;

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Configurar testing environment
        $this->withoutExceptionHandling();
    }

    /**
     * Helper: Crear usuario admin autenticado
     */
    protected function loginAsAdmin(): \Modules\User\Models\User
    {
        $admin = \Modules\User\Models\User::where('email', 'admin@example.com')->first();
        $this->actingAs($admin, 'sanctum');
        return $admin;
    }

    /**
     * Helper: Crear usuario god autenticado
     */
    protected function loginAsGod(): \Modules\User\Models\User
    {
        $god = \Modules\User\Models\User::where('email', 'god@example.com')->first();
        $this->actingAs($god, 'sanctum');
        return $god;
    }

    /**
     * Helper: Crear usuario tech autenticado
     */
    protected function loginAsTech(): \Modules\User\Models\User
    {
        $tech = \Modules\User\Models\User::where('email', 'tech@example.com')->first();
        $this->actingAs($tech, 'sanctum');
        return $tech;
    }

    /**
     * Helper: Crear usuario cliente autenticado
     */
    protected function loginAsCustomer(): \Modules\User\Models\User
    {
        $customer = \Modules\User\Models\User::where('email', 'cliente1@example.com')->first();
        $this->actingAs($customer, 'sanctum');
        return $customer;
    }

    /**
     * Helper: Crear estructura JSON:API para tests
     */
    protected function jsonApiData(string $type, array $attributes, array $relationships = []): array
    {
        $data = [
            'type' => $type,
            'attributes' => $attributes,
        ];

        if (!empty($relationships)) {
            $data['relationships'] = $relationships;
        }

        return $data;
    }

    /**
     * Helper: Crear relación JSON:API
     */
    protected function jsonApiRelationship(string $type, int|string $id): array
    {
        return [
            'data' => [
                'type' => $type,
                'id' => (string) $id
            ]
        ];
    }

    /**
     * Helper: Verificar estructura de respuesta JSON:API
     */
    protected function assertJsonApiResource($response, string $type, $model = null): void
    {
        $response->assertJsonStructure([
            'data' => [
                'type',
                'id',
                'attributes',
                'relationships',
                'links' => [
                    'self'
                ]
            ]
        ]);

        $response->assertJson([
            'data' => [
                'type' => $type
            ]
        ]);

        if ($model) {
            $response->assertJson([
                'data' => [
                    'id' => (string) $model->id
                ]
            ]);
        }
    }

    /**
     * Helper: Verificar estructura de colección JSON:API
     */
    protected function assertJsonApiCollection($response, string $type): void
    {
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'type',
                    'id',
                    'attributes',
                    'relationships',
                    'links' => [
                        'self'
                    ]
                ]
            ],
            'links' => [
                'first',
                'last',
                'prev',
                'next'
            ],
            'meta' => [
                'current_page',
                'from',
                'last_page',
                'per_page',
                'to',
                'total'
            ]
        ]);

        // Verificar que todos los items tengan el type correcto
        $data = $response->json('data');
        foreach ($data as $item) {
            $this->assertEquals($type, $item['type']);
        }
    }

    /**
     * Helper: Crear datos de factory con relaciones
     */
    protected function createWithRelations(string $factoryClass, array $relations = []): mixed
    {
        $factory = $factoryClass::new();
        
        foreach ($relations as $relation => $relatedFactory) {
            $factory = $factory->for($relatedFactory, $relation);
        }

        return $factory->create();
    }
}
```

### **13. Routes - JSON:API del Módulo**

#### **routes/jsonapi.php**

```php
<?php

use LaravelJsonApi\Laravel\Facades\JsonApiRoute;
use Modules\{ModuleName}\Http\Controllers\Api\V1\{Entity}Controller;

/*
|--------------------------------------------------------------------------
| JSON:API Routes
|--------------------------------------------------------------------------
|
| Aquí se registran las rutas JSON:API para el módulo {ModuleName}.
| Todas las rutas siguen el estándar JSON:API specification.
|
*/

JsonApiRoute::server('v1')
    ->prefix('v1')
    ->resources(function ($server) {
        
        // Rutas para {entities}
        $server->resource('{entities}', {Entity}Controller::class)
               ->relationships(function ($relationships) {
                   $relationships->hasOne('relatedEntity')->readOnly();
                   $relationships->hasMany('childEntities');
               });

        // Agregar más entidades del módulo aquí
        
    });
```

### **14. Configuración del Módulo**

#### **module.json**

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

---

## 🧪 **PATRONES DE TESTING**

### **Tests Index - Listado de Entidades**

```php
<?php

namespace Modules\{ModuleName}\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\{ModuleName}\Models\{Entity};
use Illuminate\Foundation\Testing\RefreshDatabase;

class {Entity}IndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_{entities}(): void
    {
        $admin = $this->loginAsAdmin();
        
        {Entity}::factory(3)->create();

        $response = $this->jsonApi()->get('/api/v1/{entities}');

        $response->assertOk();
        $this->assertJsonApiCollection($response, '{entities}');
        $response->assertJsonCount(3, 'data');
    }

    public function test_admin_can_sort_{entities}_by_name(): void
    {
        $admin = $this->loginAsAdmin();
        
        {Entity}::factory()->create(['name' => 'Z Entity']);
        {Entity}::factory()->create(['name' => 'A Entity']);

        $response = $this->jsonApi()->get('/api/v1/{entities}?sort=name');

        $response->assertOk();
        $names = collect($response->json('data'))->pluck('attributes.name');
        $this->assertEquals(['A Entity', 'Z Entity'], $names->toArray());
    }

    public function test_unauthenticated_user_cannot_list_{entities}(): void
    {
        $response = $this->jsonApi()->get('/api/v1/{entities}');

        $response->assertStatus(401);
    }

    public function test_customer_cannot_list_{entities}(): void
    {
        $customer = $this->loginAsCustomer();

        $response = $this->jsonApi()->get('/api/v1/{entities}');

        $response->assertStatus(403);
    }
}
```

### **Tests Store - Crear Entidades**

```php
<?php

namespace Modules\{ModuleName}\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\{ModuleName}\Models\{Entity};
use Illuminate\Foundation\Testing\RefreshDatabase;

class {Entity}StoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_{entity}(): void
    {
        $admin = $this->loginAsAdmin();
        
        $relatedEntity = \Modules\RelatedModule\Models\RelatedEntity::factory()->create();

        $data = $this->jsonApiData('{entities}', [
            'name' => 'Test Entity',
            'code' => 'TEST001',
            'price' => 100.50,
            'isActive' => true,
        ], [
            'relatedEntity' => $this->jsonApiRelationship('related-entities', $relatedEntity->id)
        ]);

        $response = $this->jsonApi()
                        ->withData($data)
                        ->post('/api/v1/{entities}');

        $response->assertCreated();
        $this->assertJsonApiResource($response, '{entities}');
        
        $this->assertDatabaseHas('{entities}', [
            'name' => 'Test Entity',
            'code' => 'TEST001',
            'price' => 100.50,
            'related_entity_id' => $relatedEntity->id,
        ]);
    }

    public function test_customer_cannot_create_{entity}(): void
    {
        $customer = $this->loginAsCustomer();

        $data = $this->jsonApiData('{entities}', [
            'name' => 'Test Entity',
        ]);

        $response = $this->jsonApi()
                        ->withData($data)
                        ->post('/api/v1/{entities}');

        $response->assertStatus(403);
    }

    public function test_{entity}_creation_fails_with_missing_fields(): void
    {
        $admin = $this->loginAsAdmin();

        $data = $this->jsonApiData('{entities}', [
            // name es requerido pero no se envía
        ]);

        $response = $this->jsonApi()
                        ->withData($data)
                        ->post('/api/v1/{entities}');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    public function test_{entity}_creation_fails_with_duplicate_code(): void
    {
        $admin = $this->loginAsAdmin();
        
        {Entity}::factory()->create(['code' => 'DUPLICATE']);

        $data = $this->jsonApiData('{entities}', [
            'name' => 'Test Entity',
            'code' => 'DUPLICATE',
        ]);

        $response = $this->jsonApi()
                        ->withData($data)
                        ->post('/api/v1/{entities}');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['code']);
    }
}
```

---

## 🚀 **COMANDOS DE GENERACIÓN**

### **Comandos para crear un módulo completo:**

```bash
# 1. Crear el módulo base
php artisan module:make {ModuleName}

# 2. Generar componentes JSON:API
php artisan jsonapi:schema {entities} --module={ModuleName}
php artisan jsonapi:authorizer {entities} --module={ModuleName}

# 3. Crear migraciones
php artisan module:make-migration create_{entities}_table {ModuleName}

# 4. Crear modelos
php artisan module:make-model {Entity} {ModuleName}

# 5. Crear factories
php artisan module:make-factory {Entity}Factory {ModuleName}

# 6. Crear seeders
php artisan module:make-seeder {ModuleName}DatabaseSeeder {ModuleName}
php artisan module:make-seeder {ModuleName}PermissionSeeder {ModuleName}

# 7. Crear tests
php artisan module:make-test Feature/{Entity}IndexTest {ModuleName}
php artisan module:make-test Feature/{Entity}StoreTest {ModuleName}

# 8. Crear controllers
# ⚠️ IMPORTANTE: Los controllers se generan con jsonapi:controller
php artisan jsonapi:controller {Entity}Controller

# Luego mover desde app/Http/Controllers/ a:
# Modules/{ModuleName}/app/Http/Controllers/Api/V1/{Entity}Controller.php
# Y actualizar el namespace a: Modules\{ModuleName}\Http\Controllers\Api\V1

# 9. Crear requests
php artisan module:make-request {Entity}StoreRequest {ModuleName}
php artisan module:make-request {Entity}UpdateRequest {ModuleName}
```

---

## ✅ **CHECKLIST DE MÓDULO COMPLETO**

### **Estructura Base:**
- [ ] ✅ Módulo creado con `php artisan module:make`
- [ ] ✅ Configuración en `module.json`
- [ ] ✅ Service Providers configurados

### **Modelos y Base de Datos:**
- [ ] ✅ Models con relaciones definidas
- [ ] ✅ Migraciones con foreign keys y índices
- [ ] ✅ Factories con estados útiles
- [ ] ✅ Seeders con datos de ejemplo y permisos

### **JSON:API Components:**
- [ ] ✅ Schemas con fields, relations y filters
- [ ] ✅ Controllers con Actions traits
- [ ] ✅ Authorizers con todos los métodos
- [ ] ✅ Routes registradas correctamente

### **Validaciones:**
- [ ] ✅ Form Requests para Store y Update
- [ ] ✅ Validaciones de negocio apropiadas
- [ ] ✅ Mensajes de error personalizados

### **Testing:**
- [ ] ✅ Tests Index (listado, filtros, sorting)
- [ ] ✅ Tests Show (mostrar individual)
- [ ] ✅ Tests Store (crear, validaciones)
- [ ] ✅ Tests Update (actualizar, validaciones)
- [ ] ✅ Tests Destroy (eliminar, constraints)
- [ ] ✅ Tests de autorización para todos los roles

### **Integración:**
- [ ] ✅ Registrado en DatabaseSeeder principal
- [ ] ✅ Permisos asignados a roles apropiados
- [ ] ✅ Relaciones con otros módulos funcionando
- [ ] ✅ Tests pasando al 100%

---

**¡Con esta guía técnica puedes crear cualquier módulo siguiendo el patrón establecido y mantener la consistencia arquitectónica!** 🚀
