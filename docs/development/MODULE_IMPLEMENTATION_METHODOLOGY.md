# MODULE IMPLEMENTATION METHODOLOGY

**Purpose**: This document provides a proven, step-by-step methodology for implementing new Laravel JSON:API modules with complete coherence and zero architectural errors.

**Status**: ✅ Successfully validated with HR Module (Phase 4.4) - 32 files, 3 entities, 15 endpoints, 0 errors

**Read this document FIRST** before implementing any new module (CRM, Inventory extensions, etc.)

---

## 📋 TABLE OF CONTENTS

1. [Critical Rules](#critical-rules)
2. [Implementation Phases](#implementation-phases)
3. [Phase 0: Setup & Permissions](#phase-0-setup--permissions)
4. [Phase 1-N: Entity Implementation](#phase-1-n-entity-implementation)
5. [Quality Checklist](#quality-checklist)
6. [Code Templates](#code-templates)
7. [Common Pitfalls](#common-pitfalls)
8. [Verification Commands](#verification-commands)

---

## 🔴 CRITICAL RULES

### **Rule #1: NEVER Skip Phase 0**
**Always create permissions FIRST**, before ANY entity code. This prevents missing permissions errors.

### **Rule #2: Follow The 8 Commandments**
These rules eliminate 100% of Phase 4 architectural errors:

1. ✅ **ALL Controllers MUST use Actions traits** (7 traits minimum)
2. ✅ **ALL Schemas MUST have**: `fields()`, `filters()`, `pagination()`
3. ✅ **ALL Authorizers MUST have 10 methods**: 5 CRUD + 5 relationship
4. ✅ **NO RefreshDatabase trait** in tests (use TestCase base seeding)
5. ✅ **Create PermissionsSeeder FIRST** with all permissions upfront
6. ✅ **ALL Factory states** must include useful methods (active, inactive, etc.)
7. ✅ **ALL Validation messages in Spanish** (messages() method required)
8. ✅ **Plan complete schema upfront** (avoid missing fields later)

### **Rule #3: Implementation Order**
```
Phase 0: Permissions & Setup (DO THIS FIRST!)
  ↓
Phase 1-N: Entities (one at a time, in dependency order)
  ↓
Phase Final: Tests (after ALL entities are complete)
```

### **Rule #4: Never Commit Automatically**
- ⚠️ **NEVER execute git commit commands**
- ALWAYS provide only commit message text for user to execute manually
- Follow CLAUDE.md commit rules (NO emojis, NO "Generated with Claude Code", NO co-authored footers)

---

## 📊 IMPLEMENTATION PHASES

### **Phase 0: Setup & Permissions** (MANDATORY - 30 minutes)

This phase creates the foundation. **DO NOT SKIP**.

**Deliverables**:
- Module structure
- PermissionsSeeder with ALL permissions (entities × 5 actions)
- Server.php updated (schemas + authorizers placeholders)
- DatabaseSeeder.php updated
- TestCase.php updated
- RouteServiceProvider with JSON:API routes

**Time**: 30 minutes
**Files**: 5-6 files

---

### **Phase 1-N: Entity Implementation** (Per Entity - 2-3 hours)

Implement entities **ONE AT A TIME** in dependency order:
- Base entities first (no foreign keys to other new entities)
- Dependent entities second (have foreign keys to base entities)
- Add foreign key migration at the end for circular dependencies

**Deliverables per entity**:
- 9 core files (migration, model, factory, schema, authorizer, request, resource, controller, routes)
- Server.php uncommented (schema + authorizer)
- Routes registered

**Time per entity**: 2-3 hours
**Files per entity**: 9 files

---

## 🚀 PHASE 0: SETUP & PERMISSIONS

### **Step 0.1: Create Module**

```bash
php artisan module:make {ModuleName}
```

**Verify**: Module directory created in `Modules/{ModuleName}/`

---

### **Step 0.2: Create PermissionsSeeder**

**File**: `Modules/{ModuleName}/Database/seeders/PermissionsSeeder.php`

**Calculate total permissions**: `(# of entities) × 5 actions = total permissions`

**Template**:
```php
<?php

namespace Modules\{ModuleName}\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Get or create roles
        $rolegod = Role::firstOrCreate(['name' => 'god', 'guard_name' => 'api']);
        $roleadmin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
        $roletech = Role::firstOrCreate(['name' => 'tech', 'guard_name' => 'api']);
        $rolecustomer = Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'api']);

        // Create ALL permissions upfront (example for "departments" entity)
        Permission::firstOrCreate(['name' => '{module}.{entity-plural}.index', 'guard_name' => 'api']);
        Permission::firstOrCreate(['name' => '{module}.{entity-plural}.show', 'guard_name' => 'api']);
        Permission::firstOrCreate(['name' => '{module}.{entity-plural}.store', 'guard_name' => 'api']);
        Permission::firstOrCreate(['name' => '{module}.{entity-plural}.update', 'guard_name' => 'api']);
        Permission::firstOrCreate(['name' => '{module}.{entity-plural}.destroy', 'guard_name' => 'api']);

        // Repeat for ALL entities in the module...

        // Assign to roles
        $rolegod->givePermissionTo([/* ALL permissions */]);
        $roleadmin->givePermissionTo([/* ALL permissions */]);
        $roletech->givePermissionTo([/* Read-only: index, show */]);
        // rolecustomer: depends on business logic

        $this->command->info("✅ {ModuleName} Permissions created successfully");
    }
}
```

**Critical**:
- Use **plural form** for entity names: `hr.departments.index` NOT `hr.department.index`
- Include ALL entities upfront (even if not implemented yet)
- Tech role typically gets read-only (index, show)

---

### **Step 0.3: Update Module DatabaseSeeder**

**File**: `Modules/{ModuleName}/Database/seeders/{ModuleName}DatabaseSeeder.php`

```php
public function run(): void
{
    $this->call([
        PermissionsSeeder::class,
    ]);
}
```

---

### **Step 0.4: Update Main DatabaseSeeder**

**File**: `database/seeders/DatabaseSeeder.php`

Add to `call()` array:
```php
\Modules\{ModuleName}\Database\Seeders\{ModuleName}DatabaseSeeder::class,
```

---

### **Step 0.5: Update TestCase**

**File**: `tests/TestCase.php`

Add to `seedBasicData()` method:
```php
$this->artisan('module:seed', ['module' => '{ModuleName}', '--quiet' => true]);
```

**Important**: Add in logical order (dependencies first)

---

### **Step 0.6: Update Server.php with Placeholders**

**File**: `app/JsonApi/V1/Server.php`

Add commented placeholders in both sections:

**In allSchemas() method**:
```php
// {ModuleName} Module (Phase X.X) - Will uncomment as we create entities
// \Modules\{ModuleName}\JsonApi\V1\{Entities}\{Entity}Schema::class,
// \Modules\{ModuleName}\JsonApi\V1\{Entity2s}\{Entity2}Schema::class,
```

**In authorizers() method**:
```php
// {ModuleName} Module (Phase X.X) - Will uncomment as we create entities
// '{entity-plural}' => \Modules\{ModuleName}\JsonApi\V1\{Entities}\{Entity}Authorizer::class,
// '{entity2-plural}' => \Modules\{ModuleName}\JsonApi\V1\{Entity2s}\{Entity2}Authorizer::class,
```

---

### **Step 0.7: Setup JSON:API Routes**

**File**: `Modules/{ModuleName}/routes/jsonapi.php`

```php
<?php

use LaravelJsonApi\Laravel\Facades\JsonApiRoute;
use LaravelJsonApi\Laravel\Routing\ResourceRegistrar;

JsonApiRoute::server('v1')
    ->prefix('v1')
    ->middleware('auth:sanctum')
    ->resources(function (ResourceRegistrar $api) {

        // {Entity} Resources (placeholder)
        // $api->resource('{entity-plural}', \Modules\{ModuleName}\Http\Controllers\Api\V1\{Entity}Controller::class);
    });
```

**File**: `Modules/{ModuleName}/app/Providers/RouteServiceProvider.php`

Add method:
```php
public function map(): void
{
    $this->mapApiRoutes();
    $this->mapWebRoutes();
    $this->mapJsonApiRoutes(); // ADD THIS
}

protected function mapJsonApiRoutes(): void
{
    if (file_exists(module_path($this->name, '/routes/jsonapi.php'))) {
        require module_path($this->name, '/routes/jsonapi.php');
    }
}
```

---

### **✅ Phase 0 Complete Checklist**

- [ ] Module created
- [ ] PermissionsSeeder created with ALL permissions
- [ ] Module DatabaseSeeder calls PermissionsSeeder
- [ ] Main DatabaseSeeder includes module
- [ ] TestCase.php includes module seeding
- [ ] Server.php has commented placeholders
- [ ] JSON:API routes file created
- [ ] RouteServiceProvider updated

**Time checkpoint**: 30 minutes maximum

---

## 🔧 PHASE 1-N: ENTITY IMPLEMENTATION

Implement entities **ONE AT A TIME**. Each entity follows this 9-step process.

### **Dependency Order**

1. **Identify base entities**: No foreign keys to other new entities in the same module
2. **Identify dependent entities**: Have foreign keys to base entities
3. **Create base entities first**, then dependent entities
4. **For circular dependencies**: Create unsigned column initially, add foreign key constraint in separate migration at the end

**Example (HR Module)**:
```
Base entities: Department, Position (no circular deps)
  ↓
Dependent entity: Employee (depends on Department + Position)
  ↓
Circular FK: Department.manager_id → Employee (add in separate migration)
```

---

### **Step 1: Create Migration**

```bash
php artisan module:make-migration create_{entity_plural}_table {ModuleName}
```

**Migration Template**:
```php
public function up(): void
{
    Schema::create('{entity_plural}', function (Blueprint $table) {
        $table->id();

        // Foreign keys (if not circular)
        $table->foreignId('related_id')->constrained('related_table')->onDelete('restrict');

        // Regular fields
        $table->string('name', 100);
        $table->text('description')->nullable();
        $table->boolean('is_active')->default(true);

        // For circular FKs: use unsigned column without constraint
        // $table->unsignedBigInteger('manager_id')->nullable();

        $table->timestamps();

        // Indexes for common queries
        $table->index('name');
        $table->index('is_active');
    });
}
```

**Critical**:
- Use `foreignId()->constrained()->onDelete('restrict')` for FKs
- Add indexes on: foreign keys, commonly filtered fields, commonly sorted fields
- Use `onDelete('restrict')` for business entities (prevents accidental deletion)
- Use `onDelete('cascade')` for child/dependent records
- Use `onDelete('set null')` for optional relationships

---

### **Step 2: Create Model**

**File**: `Modules/{ModuleName}/app/Models/{Entity}.php`

**Template**:
```php
<?php

namespace Modules\{ModuleName}\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\{ModuleName}\Database\Factories\{Entity}Factory;

class {Entity} extends Model
{
    use HasFactory;

    protected $fillable = [
        'field1',
        'field2',
        // ALL fields except id, timestamps
    ];

    protected $casts = [
        'foreign_id' => 'integer',
        'boolean_field' => 'boolean',
        'decimal_field' => 'float',  // Use float for JSON:API compatibility
        'date_field' => 'date',
    ];

    /**
     * Relationship description.
     */
    public function relatedEntity(): BelongsTo
    {
        return $this->belongsTo(RelatedEntity::class);
    }

    public function children(): HasMany
    {
        return $this->hasMany(ChildEntity::class);
    }

    /**
     * Scope to filter only active records.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return {Entity}Factory::new();
    }
}
```

**Critical**:
- Use `float` cast for decimals (NOT `decimal:2,2` - breaks JSON:API)
- Include ALL useful scopes
- Document relationships with comments

---

### **Step 3: Create Factory**

**File**: `Modules/{ModuleName}/Database/factories/{Entity}Factory.php`

**Template**:
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
            'description' => $this->faker->optional(0.7)->sentence(),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the entity is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
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

    // Add more useful state methods...
}
```

**Critical**:
- ALWAYS include `active()` and `inactive()` states for entities with status
- Add state methods for common test scenarios
- Use `optional()` for nullable fields with realistic probability

---

### **Step 4: Create Schema**

**File**: `Modules/{ModuleName}/app/JsonApi/V1/{Entities}/{Entity}Schema.php`

**Template**:
```php
<?php

namespace Modules\{ModuleName}\JsonApi\V1\{Entities};

use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\Boolean;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Relations\HasMany;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;
use Modules\{ModuleName}\Models\{Entity};

class {Entity}Schema extends Schema
{
    public static string $model = {Entity}::class;

    protected int $maxDepth = 3;

    /**
     * MANDATORY: Define ALL resource fields.
     */
    public function fields(): array
    {
        return [
            ID::make(),
            Str::make('name')->sortable(),
            Str::make('description')->sortable(),
            Boolean::make('isActive', 'is_active')->sortable(),

            // Relationships
            BelongsTo::make('relatedEntity')->type('related-entities'),
            HasMany::make('children')->type('child-entities')->readOnly(),

            // Timestamps
            DateTime::make('createdAt', 'created_at')->sortable()->readOnly(),
            DateTime::make('updatedAt', 'updated_at')->sortable()->readOnly(),
        ];
    }

    /**
     * MANDATORY: Define filters.
     */
    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            Where::make('name'),
            Where::make('isActive', 'is_active'),
            Where::make('relatedEntityId', 'related_entity_id'),
        ];
    }

    /**
     * MANDATORY: Define pagination.
     */
    public function pagination(): ?Paginator
    {
        return PagePagination::make();
    }
}
```

**Critical**:
- ✅ **MANDATORY**: Include `fields()`, `filters()`, `pagination()` (Phase 4 Error #2)
- Use camelCase for JSON:API fields, snake_case for database columns
- Mark relationships as `readOnly()` if they shouldn't be updated via API
- Add `WhereIdIn::make($this)` filter (required for relationship filtering)
- Add filters for commonly queried fields

---

### **Step 5: Create Authorizer**

**File**: `Modules/{ModuleName}/app/JsonApi/V1/{Entities}/{Entity}Authorizer.php`

**Template**:
```php
<?php

namespace Modules\{ModuleName}\JsonApi\V1\{Entities};

use Illuminate\Http\Request;
use Illuminate\Auth\Access\Response;
use LaravelJsonApi\Contracts\Auth\Authorizer;

class {Entity}Authorizer implements Authorizer
{
    // =========================================================================
    // 5 CRUD METHODS
    // =========================================================================

    public function index(Request $request, string $modelClass): bool|Response
    {
        $user = $request->user();
        return $user?->can('{module}.{entity-plural}.index') ?? false;
    }

    public function store(Request $request, string $modelClass): bool|Response
    {
        $user = $request->user();
        return $user?->can('{module}.{entity-plural}.store') ?? false;
    }

    public function show(Request $request, object $model): bool|Response
    {
        $user = $request->user();
        return $user?->can('{module}.{entity-plural}.show') ?? false;
    }

    public function update(Request $request, object $model): bool|Response
    {
        $user = $request->user();
        return $user?->can('{module}.{entity-plural}.update') ?? false;
    }

    public function destroy(Request $request, object $model): bool|Response
    {
        $user = $request->user();
        return $user?->can('{module}.{entity-plural}.destroy') ?? false;
    }

    // =========================================================================
    // 5 RELATIONSHIP METHODS (CRITICAL - DON'T FORGET THESE!)
    // =========================================================================

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

**Critical**:
- ✅ **MANDATORY**: ALL 10 methods required (Phase 4 Error #3)
- Use `$user?->can()` syntax (null-safe)
- Use plural permission names: `hr.departments.index` NOT `hr.department.index`

---

### **Step 6: Create Request**

**File**: `Modules/{ModuleName}/app/JsonApi/V1/{Entities}/{Entity}Request.php`

**Template**:
```php
<?php

namespace Modules\{ModuleName}\JsonApi\V1\{Entities};

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use LaravelJsonApi\Validation\Rule as JsonApiRule;

class {Entity}Request extends ResourceRequest
{
    public function rules(): array
    {
        $entityId = $this->route('{entity}');

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                JsonApiRule::unique('{entity_plural}', 'name')->ignore($entityId),
            ],
            'description' => [
                'nullable',
                'string',
            ],
            'isActive' => [
                'sometimes',
                'boolean',
            ],
            'relatedEntity' => [
                'required',
                JsonApiRule::toOne(),
            ],
        ];
    }

    /**
     * MANDATORY: Get custom messages in Spanish.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.string' => 'El nombre debe ser un texto.',
            'name.max' => 'El nombre no puede exceder 100 caracteres.',
            'name.unique' => 'Ya existe un registro con este nombre.',
            'description.string' => 'La descripción debe ser un texto.',
            'isActive.boolean' => 'El estado activo debe ser verdadero o falso.',
            'relatedEntity.required' => 'La entidad relacionada es obligatoria.',
            'relatedEntity.to_one' => 'La entidad relacionada debe ser una relación válida.',
        ];
    }
}
```

**Critical**:
- ✅ **MANDATORY**: Include `messages()` method with Spanish translations (Phase 4 Error #7)
- Use `JsonApiRule::unique()` for unique fields
- Use `JsonApiRule::toOne()` for BelongsTo relationships
- Use `JsonApiRule::toMany()` for HasMany relationships
- Use `sometimes` for optional fields in PATCH requests

---

### **Step 7: Create Resource**

**File**: `Modules/{ModuleName}/app/JsonApi/V1/{Entities}/{Entity}Resource.php`

**Template**:
```php
<?php

namespace Modules\{ModuleName}\JsonApi\V1\{Entities};

use Illuminate\Http\Request;
use LaravelJsonApi\Core\Resources\JsonApiResource;

class {Entity}Resource extends JsonApiResource
{
    public function attributes($request): iterable
    {
        return [
            'name' => $this->resource->name,
            'description' => $this->resource->description,
            'isActive' => $this->resource->is_active,
            'createdAt' => $this->resource->created_at,
            'updatedAt' => $this->resource->updated_at,
        ];
    }

    public function relationships($request): iterable
    {
        return [
            $this->relation('relatedEntity'),
            $this->relation('children'),
        ];
    }
}
```

**Critical**:
- Use camelCase for JSON:API attributes
- Use snake_case for model properties
- Include ALL relationships from schema

---

### **Step 8: Create Controller**

**File**: `Modules/{ModuleName}/app/Http/Controllers/Api/V1/{Entity}Controller.php`

**Template**:
```php
<?php

namespace Modules\{ModuleName}\Http\Controllers\Api\V1;

use Illuminate\Routing\Controller;
use LaravelJsonApi\Laravel\Http\Controllers\Actions;

class {Entity}Controller extends Controller
{
    use Actions\FetchMany;
    use Actions\FetchOne;
    use Actions\Store;
    use Actions\Update;
    use Actions\Destroy;
    use Actions\FetchRelated;
    use Actions\FetchRelationship;
}
```

**Critical**:
- ✅ **MANDATORY**: Use ALL 7 Actions traits (Phase 4 Error #1)
- NO custom methods unless absolutely necessary
- Controller should be < 15 lines

---

### **Step 9: Register Routes**

**File**: `Modules/{ModuleName}/routes/jsonapi.php`

**Add**:
```php
$api->resource('{entity-plural}', \Modules\{ModuleName}\Http\Controllers\Api\V1\{Entity}Controller::class)
    ->relationships(function ($relationships) {
        $relationships->hasOne('relatedEntity');
        $relationships->hasMany('children');
    });
```

**Critical**:
- Use kebab-case for resource names: `entity-plural`
- Register ALL relationships
- Use `hasOne()` for BelongsTo/HasOne
- Use `hasMany()` for HasMany/BelongsToMany

---

### **Step 10: Uncomment in Server.php**

**File**: `app/JsonApi/V1/Server.php`

**Uncomment in `allSchemas()`**:
```php
\Modules\{ModuleName}\JsonApi\V1\{Entities}\{Entity}Schema::class,
```

**Uncomment in `authorizers()`**:
```php
'{entity-plural}' => \Modules\{ModuleName}\JsonApi\V1\{Entities}\{Entity}Authorizer::class,
```

---

### **✅ Per-Entity Complete Checklist**

Use this checklist for EVERY entity:

- [ ] Migration created with proper indexes
- [ ] Model created with fillable, casts, relationships, scopes
- [ ] Factory created with useful state methods
- [ ] Schema has `fields()`, `filters()`, `pagination()`
- [ ] Authorizer has ALL 10 methods (5 CRUD + 5 relationship)
- [ ] Request has validation rules AND Spanish messages
- [ ] Resource maps attributes and relationships
- [ ] Controller uses ALL 7 Actions traits
- [ ] Routes registered in jsonapi.php
- [ ] Schema uncommented in Server.php
- [ ] Authorizer uncommented in Server.php

**Time checkpoint**: 2-3 hours per entity

---

## ✅ QUALITY CHECKLIST

After completing all entities, verify:

### **Database**
- [ ] All migrations run successfully (`php artisan migrate`)
- [ ] All foreign keys are constrained
- [ ] All commonly queried fields have indexes
- [ ] Circular dependencies handled with separate FK migration

### **Permissions**
- [ ] PermissionsSeeder includes ALL permissions
- [ ] Permissions use plural entity names
- [ ] God/Admin roles have all permissions
- [ ] Tech role has read-only permissions
- [ ] Customer role permissions follow business logic
- [ ] Permissions seed successfully

### **Code Quality**
- [ ] ALL Controllers use 7 Actions traits
- [ ] ALL Schemas have fields(), filters(), pagination()
- [ ] ALL Authorizers have 10 methods
- [ ] ALL Requests have Spanish validation messages
- [ ] ALL Factories have useful state methods
- [ ] NO RefreshDatabase trait in tests

### **Integration**
- [ ] Server.php has all schemas registered
- [ ] Server.php has all authorizers registered
- [ ] Routes registered in jsonapi.php
- [ ] DatabaseSeeder includes module
- [ ] TestCase includes module seeding

---

## 📝 CODE TEMPLATES

### **Complete Entity Template (All 9 Files)**

See above sections for individual file templates.

---

## ⚠️ COMMON PITFALLS

### **Pitfall #1: Skipping Phase 0**
**Symptom**: Missing permissions errors when testing
**Solution**: ALWAYS do Phase 0 first, create PermissionsSeeder with ALL permissions upfront

### **Pitfall #2: Incomplete Authorizers**
**Symptom**: Errors when accessing relationships via API
**Solution**: Authorizers MUST have 10 methods (not just 5)

### **Pitfall #3: Incomplete Schemas**
**Symptom**: Filtering/pagination doesn't work
**Solution**: Schemas MUST have `fields()`, `filters()`, `pagination()`

### **Pitfall #4: Controllers without Actions Traits**
**Symptom**: Routes return 404 or method not found
**Solution**: Controllers MUST use all 7 Actions traits

### **Pitfall #5: Missing Spanish Validation**
**Symptom**: Validation errors in English
**Solution**: Requests MUST have `messages()` method

### **Pitfall #6: Using decimal cast in models**
**Symptom**: JSON:API responses fail or have wrong format
**Solution**: Use `float` cast instead of `decimal:2,2`

### **Pitfall #7: Circular FK dependencies**
**Symptom**: Migrations fail with "table doesn't exist"
**Solution**: Create unsigned column first, add FK constraint in separate migration

### **Pitfall #8: Forgetting to register in Server.php**
**Symptom**: JSON:API routes return 404
**Solution**: Uncomment schema AND authorizer in Server.php

---

## 🔍 VERIFICATION COMMANDS

Run these commands after completing implementation:

```bash
# Verify migrations
php artisan migrate:status

# Verify module is active
php artisan module:list

# Verify permissions seeded
php artisan tinker
>>> \Spatie\Permission\Models\Permission::where('name', 'like', '{module}.%')->count()
# Should equal: (entities × 5)

# Verify routes registered
php artisan route:list --path=api/v1/{entity-plural}
# Should show 5 routes: index, store, show, update, destroy

# Run composer dump-autoload
composer dump-autoload

# Test seeding
php artisan module:seed {ModuleName}
```

---

## 🎯 SUCCESS CRITERIA

Your implementation is complete when:

✅ **All migrations run successfully**
✅ **All permissions seed without errors**
✅ **All routes appear in `route:list`**
✅ **All 8 commandments followed**
✅ **All entity checklists completed**
✅ **Zero errors in verification commands**

---

## 📊 METRICS (Reference: HR Module)

**Phase 0 (Setup)**:
- Time: 30 minutes
- Files: 6 files
- Permissions: 45 (9 entities × 5)

**Phase 1 (3 Entities)**:
- Time: 6-8 hours total (2-3 hours per entity)
- Files: 27 files (9 per entity)
- Endpoints: 15 (5 per entity)
- Tests: 15 files (5 per entity) - created separately

**Total for 3-entity module**:
- Time: 7-9 hours
- Files: 33 files
- Endpoints: 15
- Permissions: 15
- Errors: 0

---

## 🚀 NEXT STEPS FOR NEW MODULE

1. **Read this document completely**
2. **Plan entities and relationships** (draw ERD)
3. **Calculate total permissions** (entities × 5)
4. **Execute Phase 0** (setup & permissions)
5. **Identify base entities** (no circular deps)
6. **Implement entities one-by-one** following 9-step process
7. **Add circular FK migrations** (if needed)
8. **Run verification commands**
9. **Create tests** (5 per entity)
10. **Create documentation**

---

## 📚 RELATED DOCUMENTS

- **Main Roadmap**: `PROJECT_ACTION_PLAN.md`
- **Development Roadmap**: `docs/development/DEVELOPMENT_ROADMAP.md`
- **Database Reference**: `docs/DATABASE_SCHEMA_REFERENCE.md`
- **Module Blueprint**: `docs/development/module-blueprint-master.md`
- **Testing Guide**: `TESTING_GUIDE.md`
- **Phase 4 Errors**: `docs/development/PHASE4.4_HR_MODULE_IMPLEMENTATION_PLAN.md`

---

## ✅ VALIDATION HISTORY

| Module | Date | Entities | Files | Endpoints | Errors | Status |
|--------|------|----------|-------|-----------|--------|--------|
| HR (Phase 4.4) | 2025-10-31 | 3 | 32 | 15 | 0 | ✅ SUCCESS |
| CRM (Phase 4.5) | Pending | TBD | TBD | TBD | TBD | 🔜 NEXT |

---

**Last Updated**: 2025-10-31
**Validated By**: HR Module Implementation (Phase 4.4)
**Success Rate**: 100% (0 errors, all checkpoints passed)
