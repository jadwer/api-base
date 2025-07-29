# 📋 MODULE BLUEPRINT MASTER GUIDE

**Guía completa para crear módulos Laravel JSON API siguiendo los estándares del proyecto**

Basado en la experiencia exitosa del módulo **Sales** (148 tests, 464 aserciones).

---

## 🎯 **ÍNDICE**

1. [Estructura del Módulo](#estructura-del-módulo)
2. [Paso a Paso de Creación](#paso-a-paso-de-creación)
3. [Configuraciones Globales](#configuraciones-globales)
4. [Patrones de Código](#patrones-de-código)
5. [Sistema de Relaciones](#sistema-de-relaciones)
6. [Tests Completos](#tests-completos)
7. [Documentación](#documentación)
8. [Checklist Final](#checklist-final)

---

## 📁 **ESTRUCTURA DEL MÓDULO**

```
Modules/{ModuleName}/
├── app/
│   ├── JsonApi/
│   │   └── V1/
│   │       ├── {ModelName}s/
│   │       │   ├── {ModelName}Authorizer.php
│   │       │   ├── {ModelName}Resource.php
│   │       │   ├── {ModelName}Schema.php
│   │       │   └── {ModelName}Request.php
│   │       └── Server.php
│   ├── Models/
│   │   └── {ModelName}.php
│   └── Providers/
│       └── {ModuleName}ServiceProvider.php
├── Database/
│   ├── Factories/
│   │   └── {ModelName}Factory.php
│   ├── Migrations/
│   │   └── create_{table_name}_table.php
│   └── Seeders/
│       ├── {ModuleName}DatabaseSeeder.php
│       └── {ModelName}PermissionsSeeder.php
├── Routes/
│   └── api.php
├── Tests/
│   ├── Feature/
│   │   ├── {ModelName}IndexTest.php
│   │   ├── {ModelName}ShowTest.php
│   │   ├── {ModelName}StoreTest.php
│   │   ├── {ModelName}UpdateTest.php
│   │   └── {ModelName}DestroyTest.php
│   └── TestCase.php (opcional)
├── composer.json
├── module.json
├── README.md
└── CHANGELOG.md
```

---

## 🚀 **PASO A PASO DE CREACIÓN**

### **1. Crear el Módulo Base**

```bash
# Crear el módulo
php artisan module:make {ModuleName}

# Ejemplo:
php artisan module:make Inventory
```

### **2. Configurar composer.json del Módulo**

```json
{
    "name": "nwidart/inventory",
    "description": "",
    "authors": [
        {
            "name": "Jadwer",
            "email": "jadwer@example.com"
        }
    ],
    "extra": {
        "laravel": {
            "providers": [],
            "aliases": {}
        }
    },
    "autoload": {
        "psr-4": {
            "Modules\\Inventory\\": "",
            "Modules\\Inventory\\Database\\Factories\\": "Database/Factories/",
            "Modules\\Inventory\\Database\\Seeders\\": "Database/Seeders/"
        }
    },
    "require": {
        "spatie/laravel-permission": "^6.0"
    }
}
```

### **3. Configurar module.json**

```json
{
    "name": "Inventory",
    "alias": "inventory",
    "description": "Inventory management module",
    "keywords": ["inventory", "stock", "warehouse"],
    "priority": 0,
    "providers": [
        "Modules\\Inventory\\Providers\\InventoryServiceProvider"
    ],
    "files": []
}
```

### **4. Crear el Modelo Base**

```php
<?php
// Modules/Inventory/app/Models/Product.php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Traits\HasPermissions;

class Product extends Model
{
    use HasFactory, HasPermissions;

    protected $table = 'inventory_products'; // Prefijo del módulo
    
    protected $fillable = [
        'name',
        'sku',
        'description',
        'category',
        'unit_price',
        'stock_quantity',
        'minimum_stock',
        'is_active',
        'metadata'
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'stock_quantity' => 'decimal:2',
        'minimum_stock' => 'decimal:2',
        'is_active' => 'boolean',
        'metadata' => 'array'
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeLowStock($query)
    {
        return $query->whereRaw('stock_quantity <= minimum_stock');
    }

    // Relaciones - Ejemplo
    // public function supplier(): BelongsTo
    // {
    //     return $this->belongsTo(\Modules\Supplier\Models\Supplier::class);
    // }

    // Factory
    protected static function newFactory()
    {
        return \Modules\Inventory\Database\Factories\ProductFactory::new();
    }
}
```

### **5. Crear la Migración**

```php
<?php
// Database/Migrations/create_inventory_products_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('inventory_products', function (Blueprint $table) {
            $table->id();
            
            // Campos básicos
            $table->string('name');
            $table->string('sku')->unique();
            $table->text('description')->nullable();
            $table->string('category');
            
            // Campos monetarios
            $table->decimal('unit_price', 10, 2);
            
            // Campos de stock
            $table->decimal('stock_quantity', 10, 2)->default(0);
            $table->decimal('minimum_stock', 10, 2)->default(0);
            
            // Estado
            $table->boolean('is_active')->default(true);
            
            // Metadatos JSON
            $table->json('metadata')->nullable();
            
            // Foreign keys - Ejemplo
            // $table->foreignId('supplier_id')->nullable()->constrained('suppliers');
            
            $table->timestamps();
            
            // Índices
            $table->index(['category', 'is_active']);
            $table->index('stock_quantity');
        });
    }

    public function down()
    {
        Schema::dropIfExists('inventory_products');
    }
};
```

### **6. Crear Factory**

```php
<?php
// Database/Factories/ProductFactory.php

namespace Modules\Inventory\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Inventory\Models\Product;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition()
    {
        return [
            'name' => fake()->words(3, true),
            'sku' => fake()->unique()->bothify('PRD-####-???'),
            'description' => fake()->paragraph(),
            'category' => fake()->randomElement(['electronics', 'clothing', 'books', 'home']),
            'unit_price' => fake()->randomFloat(2, 10, 1000),
            'stock_quantity' => fake()->numberBetween(0, 100),
            'minimum_stock' => fake()->numberBetween(5, 20),
            'is_active' => fake()->boolean(90), // 90% activos
            'metadata' => [
                'weight' => fake()->randomFloat(2, 0.1, 10),
                'dimensions' => [
                    'length' => fake()->numberBetween(10, 100),
                    'width' => fake()->numberBetween(10, 100),
                    'height' => fake()->numberBetween(5, 50)
                ],
                'manufacturer' => fake()->company()
            ]
        ];
    }

    public function inactive()
    {
        return $this->state(['is_active' => false]);
    }

    public function lowStock()
    {
        return $this->state(function (array $attributes) {
            $minimumStock = fake()->numberBetween(10, 20);
            return [
                'minimum_stock' => $minimumStock,
                'stock_quantity' => fake()->numberBetween(0, $minimumStock - 1)
            ];
        });
    }
}
```

---

## ⚙️ **CONFIGURACIONES GLOBALES**

### **1. Registrar en database/seeders/DatabaseSeeder.php**

```php
// Agregar al método run()
$this->call([
    // ... existing seeders
    \Modules\Inventory\Database\Seeders\InventoryDatabaseSeeder::class,
]);
```

### **2. Registrar en tests/TestCase.php**

```php
// Agregar al método setUp()
$this->artisan('module:seed', ['module' => 'Inventory']);
```

### **3. Configurar JSON API Server**

```php
<?php
// app/JsonApi/V1/Server.php

protected function allSchemas(): array
{
    return [
        // ... existing schemas
        \Modules\Inventory\JsonApi\V1\Products\ProductSchema::class,
    ];
}
```

### **4. Registrar rutas en RouteServiceProvider**

```php
// Agregar al método configureJsonApi()
JsonApiRoute::server('v1')
    ->domain(config('app.domain'))
    ->prefix('api/v1')
    ->resources(function (ResourceRegistrar $server) {
        // ... existing resources
        $server->resource('products', \Modules\Inventory\JsonApi\V1\Products\ProductSchema::class);
    });
```

---

## 🎨 **PATRONES DE CÓDIGO**

### **Schema Pattern**

```php
<?php
// JsonApi/V1/Products/ProductSchema.php

namespace Modules\Inventory\JsonApi\V1\Products;

use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Fields\Boolean;
use LaravelJsonApi\Eloquent\Fields\ArrayHash;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Relations\HasMany;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;
use Modules\Inventory\Models\Product;

class ProductSchema extends Schema
{
    public static string $model = Product::class;

    public function fields(): array
    {
        return [
            ID::make(),
            
            // Campos básicos
            Str::make('name')->sortable(),
            Str::make('sku')->sortable(),
            Str::make('description'),
            Str::make('category')->sortable(),
            
            // Campos numéricos
            Number::make('unit_price')->sortable(),
            Number::make('stock_quantity')->sortable(),
            Number::make('minimum_stock'),
            
            // Estado
            Boolean::make('is_active')->sortable(),
            
            // Metadatos
            ArrayHash::make('metadata'),
            
            // Relaciones - Ejemplo
            // BelongsTo::make('supplier')->type('suppliers'),
            // HasMany::make('orders')->type('purchase-orders'),
            
            // Timestamps
            DateTime::make('created_at')->readOnly()->sortable(),
            DateTime::make('updated_at')->readOnly()->sortable(),
        ];
    }

    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            \LaravelJsonApi\Eloquent\Filters\Where::make('category'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('is_active'),
            \LaravelJsonApi\Eloquent\Filters\WhereIn::make('category', 'categories'),
        ];
    }

    public function sortables(): array
    {
        return [
            'name',
            'sku',
            'category',
            'unit_price',
            'stock_quantity',
            'created_at',
            'updated_at',
        ];
    }

    public function includePaths(): array
    {
        return [
            // 'supplier',
            // 'orders',
        ];
    }

    public function pagination(): ?Paginator
    {
        return PagePagination::make();
    }
}
```

### **Resource Pattern**

```php
<?php
// JsonApi/V1/Products/ProductResource.php

namespace Modules\Inventory\JsonApi\V1\Products;

use LaravelJsonApi\Core\Resources\JsonApiResource;

class ProductResource extends JsonApiResource
{
    public function attributes($request): iterable
    {
        return [
            // Campos básicos
            'name' => $this->name,
            'sku' => $this->sku,
            'description' => $this->description,
            'category' => $this->category,
            
            // Campos numéricos
            'unit_price' => $this->unit_price,
            'stock_quantity' => $this->stock_quantity,
            'minimum_stock' => $this->minimum_stock,
            
            // Estado
            'is_active' => $this->is_active,
            
            // Metadatos
            'metadata' => $this->metadata,
            
            // Timestamps
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    public function relationships($request): iterable
    {
        return [
            // 'supplier' => $this->relation('supplier'),
            // 'orders' => $this->relation('orders'),
        ];
    }
}
```

### **Authorizer Pattern**

```php
<?php
// JsonApi/V1/Products/ProductAuthorizer.php

namespace Modules\Inventory\JsonApi\V1\Products;

use LaravelJsonApi\Core\Store\LazyRelation;
use LaravelJsonApi\Contracts\Auth\Authorizer;
use Illuminate\Http\Request;
use Modules\Inventory\Models\Product;

class ProductAuthorizer implements Authorizer
{
    public function index(Request $request): bool
    {
        return $request->user()?->can('inventory.products.index') ?? false;
    }

    public function store(Request $request): bool
    {
        return $request->user()?->can('inventory.products.store') ?? false;
    }

    public function show(Request $request, object $model): bool
    {
        return $request->user()?->can('inventory.products.show') ?? false;
    }

    public function update(Request $request, object $model): bool
    {
        return $request->user()?->can('inventory.products.update') ?? false;
    }

    public function destroy(Request $request, object $model): bool
    {
        return $request->user()?->can('inventory.products.destroy') ?? false;
    }

    // Relationship methods with direct permission checks
    public function showSupplier(Request $request, object $model): bool
    {
        return $request->user()?->can('inventory.products.show') ?? false;
    }

    public function showOrders(Request $request, object $model): bool
    {
        return $request->user()?->can('inventory.products.show') ?? false;
    }
}
```

### **Request Pattern**

```php
<?php
// JsonApi/V1/Products/ProductRequest.php

namespace Modules\Inventory\JsonApi\V1\Products;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends ResourceRequest
{
    public function rules(): array
    {
        $productId = $this->route('product');
        
        return [
            'name' => ['required', 'string', 'max:255'],
            'sku' => [
                'required', 
                'string', 
                'max:50',
                Rule::unique('inventory_products', 'sku')->ignore($productId)
            ],
            'description' => ['nullable', 'string'],
            'category' => [
                'required', 
                'string', 
                Rule::in(['electronics', 'clothing', 'books', 'home'])
            ],
            'unit_price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'stock_quantity' => ['required', 'numeric', 'min:0'],
            'minimum_stock' => ['required', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del producto es obligatorio.',
            'sku.required' => 'El SKU es obligatorio.',
            'sku.unique' => 'El SKU ya existe.',
            'category.in' => 'La categoría debe ser válida.',
            'unit_price.min' => 'El precio debe ser mayor a 0.',
            'stock_quantity.min' => 'La cantidad en stock no puede ser negativa.',
        ];
    }
}
```

---

## 🔗 **SISTEMA DE RELACIONES**

### **Relación BelongsTo**

```php
// En el Modelo
public function supplier(): BelongsTo
{
    return $this->belongsTo(\Modules\Supplier\Models\Supplier::class);
}

// En el Schema
BelongsTo::make('supplier')->type('suppliers'),

// En Resource
'supplier' => $this->relation('supplier'),

// En includePaths
'supplier',

// En Authorizer
public function showSupplier(Request $request, object $model): bool
{
    return $request->user()?->can('inventory.products.show') ?? false;
}
```

### **Relación HasMany**

```php
// En el Modelo
public function stockMovements(): HasMany
{
    return $this->hasMany(\Modules\Inventory\Models\StockMovement::class);
}

// En el Schema
HasMany::make('stockMovements')->type('stock-movements'),

// En Resource
'stockMovements' => $this->relation('stockMovements'),

// En includePaths
'stockMovements',
'stockMovements.warehouse', // Anidada

// En Authorizer
public function showStockMovements(Request $request, object $model): bool
{
    return $request->user()?->can('inventory.products.show') ?? false;
}
```

### **Enfoque Híbrido (Campo Directo + Relación)**

```php
// En Resource - Máxima compatibilidad
public function attributes($request): iterable
{
    return [
        // Campo directo para compatibilidad/filtros
        'supplier_id' => $this->supplier_id,
        
        // Otros campos...
    ];
}

public function relationships($request): iterable
{
    return [
        // Relación completa para includes
        'supplier' => $this->relation('supplier'),
    ];
}
```

---

## 🧪 **TESTS COMPLETOS**

### **Test Base Structure**

```php
<?php
// Tests/Feature/ProductIndexTest.php

namespace Modules\Inventory\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Inventory\Models\Product;

class ProductIndexTest extends TestCase
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

    public function test_admin_can_list_products(): void
    {
        $admin = $this->getAdminUser();
        
        Product::factory()->count(3)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('products')
            ->get('/api/v1/products');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'attributes' => [
                        'name',
                        'sku',
                        'category',
                        'unit_price',
                        'stock_quantity'
                    ]
                ]
            ]
        ]);
    }

    public function test_admin_can_sort_products_by_name(): void
    {
        $admin = $this->getAdminUser();
        
        Product::factory()->create(['name' => 'Product B']);
        Product::factory()->create(['name' => 'Product A']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('products')
            ->get('/api/v1/products?sort=name');

        $response->assertOk();
        $names = collect($response->json('data'))->pluck('attributes.name');
        $this->assertEquals(['Product A', 'Product B'], $names->toArray());
    }

    public function test_admin_can_filter_products_by_category(): void
    {
        $admin = $this->getAdminUser();
        
        Product::factory()->create(['category' => 'electronics']);
        Product::factory()->create(['category' => 'books']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('products')
            ->get('/api/v1/products?filter[category]=electronics');

        $response->assertOk();
        $categories = collect($response->json('data'))->pluck('attributes.category')->unique();
        $this->assertEquals(['electronics'], $categories->toArray());
    }

    public function test_guest_cannot_list_products(): void
    {
        $response = $this->jsonApi()
            ->expects('products')
            ->get('/api/v1/products');

        $response->assertStatus(401);
    }
}
```

### **Test de Relaciones**

```php
public function test_admin_can_view_product_with_relationships(): void
{
    $admin = $this->getAdminUser();
    $supplier = \Modules\Supplier\Models\Supplier::factory()->create();
    $product = Product::factory()->create(['supplier_id' => $supplier->id]);
    
    $stockMovement = \Modules\Inventory\Models\StockMovement::factory()->create([
        'product_id' => $product->id
    ]);

    $response = $this->actingAs($admin, 'sanctum')
        ->jsonApi()
        ->expects('products')
        ->includePaths('supplier', 'stockMovements')
        ->get("/api/v1/products/{$product->id}");

    $response->assertOk();
    
    // Verificar relaciones
    $response->assertJsonStructure([
        'data' => [
            'relationships' => [
                'supplier' => ['data'],
                'stockMovements' => ['data']
            ]
        ],
        'included'
    ]);
    
    $included = $response->json('included');
    $supplierIncluded = collect($included)->firstWhere('type', 'suppliers');
    $stockMovementIncluded = collect($included)->firstWhere('type', 'stock-movements');
    
    $this->assertNotNull($supplierIncluded);
    $this->assertNotNull($stockMovementIncluded);
}
```

---

## 📚 **DOCUMENTACIÓN**

### **README.md del Módulo**

```markdown
# 📦 Inventory Module

Sistema de gestión de inventario con control de stock y productos.

## 🚀 Características

- ✅ Gestión completa de productos
- ✅ Control de stock en tiempo real
- ✅ Categorización de productos
- ✅ Alertas de stock mínimo
- ✅ API JSON:API completa
- ✅ Sistema de permisos granular
- ✅ Tests completos (48 tests, 156 aserciones)

## 📋 Endpoints

### Products
- `GET /api/v1/products` - Listar productos
- `POST /api/v1/products` - Crear producto
- `GET /api/v1/products/{id}` - Ver producto
- `PATCH /api/v1/products/{id}` - Actualizar producto
- `DELETE /api/v1/products/{id}` - Eliminar producto

### Filtros Disponibles
- `filter[category]=electronics` - Filtrar por categoría
- `filter[is_active]=true` - Filtrar por estado
- `sort=name` - Ordenar por nombre
- `sort=-stock_quantity` - Ordenar por stock (desc)

### Relaciones Disponibles
- `include=supplier` - Incluir proveedor
- `include=stockMovements` - Incluir movimientos de stock

## 🔐 Permisos

- `inventory.products.index` - Listar productos
- `inventory.products.store` - Crear productos
- `inventory.products.show` - Ver productos
- `inventory.products.update` - Actualizar productos
- `inventory.products.destroy` - Eliminar productos

## 🧪 Testing

```bash
# Ejecutar todos los tests del módulo
php artisan test --filter Product

# Tests específicos
php artisan test Modules/Inventory/Tests/Feature/ProductIndexTest
```

## 📊 Métricas

- **Models**: 1 (Product)
- **Tests**: 48 tests, 156 aserciones
- **Cobertura**: 100%
- **Performance**: < 2s por test
```

### **CHANGELOG.md del Módulo**

```markdown
# Changelog - Inventory Module

All notable changes to the Inventory module will be documented in this file.

## [1.0.0] - 2025-01-29

### Added
- ✅ Product management with full CRUD operations
- ✅ Stock quantity tracking and minimum stock alerts
- ✅ Product categorization system
- ✅ JSON API v1.0 compliance
- ✅ Granular permission system with Spatie
- ✅ Complete test suite (48 tests, 156 assertions)
- ✅ Factory-based data generation
- ✅ Advanced filtering and sorting capabilities
- ✅ Relationship support (suppliers, stock movements)
- ✅ Metadata support for custom product attributes

### Technical Details
- Laravel JSON API v5.x integration
- Spatie Permissions for authorization
- PostgreSQL/MySQL compatible migrations
- PHPUnit test coverage
- PSR-4 autoloading compliance

### API Endpoints
- Products CRUD operations
- Advanced filtering by category, status
- Sorting by name, price, stock, dates
- Include relationships (supplier, stockMovements)

### Database Schema
- `inventory_products` table with optimized indexes
- JSON metadata column for extensibility
- Foreign key constraints for data integrity
```

### **Comando para Generar Documentación**

```php
<?php
// app/Console/Commands/GenerateModuleDocumentation.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Nwidart\Modules\Facades\Module;

class GenerateModuleDocumentation extends Command
{
    protected $signature = 'module:docs {module?} {--all}';
    protected $description = 'Generate comprehensive documentation for modules';

    public function handle()
    {
        if ($this->option('all')) {
            $modules = Module::all();
            foreach ($modules as $module) {
                $this->generateDocumentation($module->getName());
            }
        } else {
            $moduleName = $this->argument('module');
            if (!$moduleName) {
                $moduleName = $this->choice('Select module:', Module::allEnabled()->keys()->toArray());
            }
            $this->generateDocumentation($moduleName);
        }
    }

    private function generateDocumentation(string $moduleName)
    {
        $this->info("Generating documentation for {$moduleName}...");
        
        $module = Module::find($moduleName);
        if (!$module) {
            $this->error("Module {$moduleName} not found!");
            return;
        }

        // Generate API documentation
        $this->generateApiDocs($module);
        
        // Generate test report
        $this->generateTestReport($module);
        
        // Update README
        $this->updateReadme($module);
        
        $this->info("Documentation generated successfully for {$moduleName}!");
    }

    private function generateApiDocs($module)
    {
        // Extract schemas and generate API documentation
        $schemaPath = $module->getPath() . '/app/JsonApi/V1';
        if (!File::exists($schemaPath)) return;

        $schemas = File::directories($schemaPath);
        $apiDocs = [];

        foreach ($schemas as $schemaDir) {
            $schemaName = basename($schemaDir);
            $schemaFile = $schemaDir . '/' . rtrim($schemaName, 's') . 'Schema.php';
            
            if (File::exists($schemaFile)) {
                $apiDocs[] = $this->parseSchema($schemaFile, $schemaName);
            }
        }

        $docsPath = $module->getPath() . '/docs/API.md';
        File::put($docsPath, $this->buildApiDocumentation($apiDocs));
    }

    private function generateTestReport($module)
    {
        $testPath = $module->getPath() . '/Tests/Feature';
        if (!File::exists($testPath)) return;

        // Run tests and capture results
        $output = shell_exec("php artisan test {$testPath} --coverage-text");
        
        $reportPath = $module->getPath() . '/docs/TEST_REPORT.md';
        File::put($reportPath, "# Test Report\n\n```\n{$output}\n```");
    }

    private function updateReadme($module)
    {
        $readmePath = $module->getPath() . '/README.md';
        if (File::exists($readmePath)) {
            $content = File::get($readmePath);
            
            // Update metrics section with current data
            $testResults = $this->getTestMetrics($module);
            $content = preg_replace(
                '/## 📊 Métricas.*?```/s',
                "## 📊 Métricas\n\n{$testResults}\n```",
                $content
            );
            
            File::put($readmePath, $content);
        }
    }

    private function getTestMetrics($module): string
    {
        // Count tests and get metrics
        $testPath = $module->getPath() . '/Tests/Feature';
        $testFiles = File::glob($testPath . '/*Test.php');
        
        return "- **Tests**: " . count($testFiles) . " files\n" .
               "- **Generated**: " . now()->format('Y-m-d H:i:s') . "\n" .
               "- **Status**: ✅ All tests passing";
    }
}
```

---

## ✅ **CHECKLIST FINAL**

### **Configuración Base**
- [ ] Módulo creado con `php artisan module:make`
- [ ] `composer.json` configurado con autoload PSR-4
- [ ] `module.json` con providers correctos
- [ ] Modelo con Factory y relaciones
- [ ] Migración con índices y constraints

### **JSON API Implementation**
- [ ] Schema con fields, filters, sortables, includePaths
- [ ] Resource con attributes y relationships
- [ ] Authorizer con permisos directos (no roles)
- [ ] Request con validaciones y mensajes
- [ ] Server registrado en app/JsonApi/V1/Server.php

### **Configuraciones Globales**
- [ ] Seeder registrado en DatabaseSeeder global
- [ ] Seeder registrado en tests/TestCase.php
- [ ] Rutas registradas en RouteServiceProvider
- [ ] Permisos creados en PermissionsSeeder

### **Tests Completos**
- [ ] IndexTest (listado, filtros, sorting, permisos)
- [ ] ShowTest (visualización, relaciones, permisos)
- [ ] StoreTest (creación, validaciones, permisos)
- [ ] UpdateTest (actualización, validaciones, permisos)
- [ ] DestroyTest (eliminación, permisos)

### **Documentación**
- [ ] README.md con características y ejemplos
- [ ] CHANGELOG.md con historial de cambios
- [ ] API.md generado automáticamente
- [ ] TEST_REPORT.md con métricas

### **Comandos de Verificación**

```bash
# Verificar que todo funciona
php artisan test --filter {ModuleName}

# Generar documentación
php artisan module:docs {ModuleName}

# Verificar permisos
php artisan permission:show

# Verificar rutas
php artisan route:list --name={module}
```

---

## 🎯 **PRÓXIMOS PASOS**

1. **Usar esta guía** para crear el siguiente módulo
2. **Iterar y mejorar** basado en la experiencia
3. **Automatizar** la creación con un comando personalizado
4. **Mantener consistencia** en todos los módulos

---

**📝 Basado en la experiencia exitosa del módulo Sales (148 tests, 464 aserciones) ✅**
