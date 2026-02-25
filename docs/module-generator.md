# Module Generator v2

Generador de modulos Laravel + JSON:API con arquitectura SRP.
Produce codigo funcional para CRUD sin edicion manual, con TODOs para logica de negocio.

## Uso rapido

```bash
# 1. Crear archivo de configuracion JSON
# 2. Generar el modulo
php artisan module:generate Logistics --config=config/modules/logistics.json

# 3. Migrar
php artisan migrate

# 4. Correr tests del modulo generado
php artisan test Modules/Logistics/

# Opciones
php artisan module:generate Logistics --config=path.json --force  # Sobreescribir si existe
```

---

## Configuracion JSON

### Estructura base

```json
{
  "module": "Logistics",
  "entities": {
    "Shipment": {
      "table": "shipments",
      "fields": [...],
      "relationships": [...]
    }
  }
}
```

### Campos (fields)

Cada campo tiene las siguientes propiedades:

| Propiedad    | Requerido | Tipo    | Descripcion |
|-------------|-----------|---------|-------------|
| `name`      | Si        | string  | Nombre de columna en snake_case |
| `type`      | Si        | string  | Tipo de dato (ver tabla abajo) |
| `rules`     | No        | string  | Reglas de validacion separadas por `\|` |
| `default`   | No        | any     | Valor por defecto |
| `sortable`  | No        | boolean | Habilita ordenamiento en API |
| `filterable`| No        | boolean | Habilita filtrado en API |

### Tipos de campo soportados

| Tipo | Migration | Cast | Schema | Faker | Filtro |
|------|-----------|------|--------|-------|--------|
| `string` | `string(max)` | - | `Str::make()` | `words(3, true)` | `Where::make()` |
| `text` | `text` | - | `Str::make()` | `paragraph()` | - |
| `integer` | `integer` | `'integer'` | `Number::make()` | `numberBetween(1,100)` | `Where::make()` |
| `bigInteger` | `bigInteger` | `'integer'` | `Number::make()` | `numberBetween(1,100)` | `Where::make()` |
| `decimal` | `decimal(10,2)` | `'float'` | `Number::make()` | `randomFloat(2,0,999)` | `Where::make()` |
| `boolean` | `boolean` | `'boolean'` | `Boolean::make()` | `boolean(80)` | `Where::make()->asBoolean()` |
| `date` | `date` | `'date'` | `DateTime::make()` | `date()` | `Where::make()` |
| `datetime` | `dateTime` | `'datetime'` | `DateTime::make()` | `dateTime()` | `Where::make()` |
| `json` | `json` | `'array'` | `ArrayHash::make()` | `[]` | - |
| `enum` | `string` | - | `Str::make()` | `randomElement([...])` | `Where::make()` |

### Reglas de validacion

Formato: pipe-delimited (`"required|string|max:100"`)

| Regla | Efecto |
|-------|--------|
| `required` | Campo obligatorio en store, `sometimes` en update |
| `nullable` | Columna nullable en migration y validacion |
| `unique` | `Rule::unique()` con `->ignore($model)` en update |
| `max:N` | `max:N` en validacion, `string(N)` en migration |
| `min:N` | `min:N` en validacion |
| `in:a,b,c` | `Rule::in([...])` en validacion, faker usa `randomElement` |
| `numeric` | Validacion numerica |
| `boolean` | Validacion booleana |

### Relaciones (relationships)

| Propiedad | Requerido | Descripcion |
|-----------|-----------|-------------|
| `type`    | Si        | `belongsTo`, `hasMany`, o `hasOne` |
| `model`   | Si        | Nombre del modelo relacionado (PascalCase) |
| `entity`  | Condicional | Nombre de entidad del mismo modulo |
| `module`  | Condicional | Nombre del modulo externo |

Debe especificar **`entity`** (mismo modulo) o **`module`** (modulo externo), no ambos.

```json
// Relacion interna (mismo modulo)
{ "type": "hasMany", "model": "ShipmentItem", "entity": "ShipmentItem" }

// Relacion externa (otro modulo)
{ "type": "belongsTo", "model": "Contact", "module": "Contacts" }
```

**Efecto por tipo:**
- `belongsTo`: Agrega `foreignId` en migration, `BelongsTo` en modelo, `JsonApiRule::toOne()` en request
- `hasMany`: Solo relacion en modelo y schema, sin FK (el FK esta en la entidad hija)
- `hasOne`: Igual que hasMany pero relacion uno-a-uno

---

## Ejemplo completo

```json
{
  "module": "Logistics",
  "entities": {
    "Shipment": {
      "table": "shipments",
      "fields": [
        { "name": "tracking_number", "type": "string", "rules": "required|unique|max:100", "sortable": true, "filterable": true },
        { "name": "status", "type": "string", "rules": "required|in:pending,shipped,delivered,cancelled", "default": "pending", "filterable": true },
        { "name": "total_weight", "type": "decimal", "rules": "nullable|numeric|min:0" },
        { "name": "shipped_at", "type": "datetime", "rules": "nullable|date", "sortable": true },
        { "name": "is_fragile", "type": "boolean", "rules": "boolean", "default": false, "filterable": true },
        { "name": "notes", "type": "text", "rules": "nullable|string" },
        { "name": "metadata", "type": "json", "rules": "nullable|array" }
      ],
      "relationships": [
        { "type": "belongsTo", "model": "Contact", "module": "Contacts" },
        { "type": "hasMany", "model": "ShipmentItem", "entity": "ShipmentItem" }
      ]
    },
    "ShipmentItem": {
      "table": "shipment_items",
      "fields": [
        { "name": "quantity", "type": "integer", "rules": "required|integer|min:1" },
        { "name": "weight", "type": "decimal", "rules": "nullable|numeric|min:0" }
      ],
      "relationships": [
        { "type": "belongsTo", "model": "Shipment", "entity": "Shipment" },
        { "type": "belongsTo", "model": "Product", "module": "Product" }
      ]
    }
  }
}
```

---

## Archivos generados

### Por entidad (ej. Shipment)

| Archivo | Ruta |
|---------|------|
| Migration | `Database/migrations/{ts}_create_shipments_table.php` |
| Model | `app/Models/Shipment.php` |
| Schema | `app/JsonApi/V1/Shipments/ShipmentSchema.php` |
| Authorizer | `app/JsonApi/V1/Shipments/ShipmentAuthorizer.php` |
| Request | `app/JsonApi/V1/Shipments/ShipmentRequest.php` |
| Controller | `app/Http/Controllers/Api/V1/ShipmentController.php` |
| Factory | `Database/factories/ShipmentFactory.php` |
| IndexTest | `tests/Feature/ShipmentIndexTest.php` |
| ShowTest | `tests/Feature/ShipmentShowTest.php` |
| StoreTest | `tests/Feature/ShipmentStoreTest.php` |
| UpdateTest | `tests/Feature/ShipmentUpdateTest.php` |
| DestroyTest | `tests/Feature/ShipmentDestroyTest.php` |

### Por modulo (1 vez)

| Archivo | Ruta |
|---------|------|
| Routes | `routes/jsonapi.php` |
| RSP | `app/Providers/RouteServiceProvider.php` |
| PermissionSeeder | `Database/seeders/LogisticsPermissionSeeder.php` |
| AssignPermissionsSeeder | `Database/seeders/LogisticsAssignPermissionsSeeder.php` |
| DatabaseSeeder | `Database/seeders/LogisticsDatabaseSeeder.php` |

### Integracion automatica

El generador actualiza automaticamente:

- `app/JsonApi/V1/Server.php` - Registra schemas y authorizers
- `database/seeders/DatabaseSeeder.php` - Agrega seeder del modulo
- `database/seeders/TestDatabaseSeeder.php` - Agrega modulo a `$requiredModules`

---

## Convenciones de nombres

| Contexto | Convencion | Ejemplo |
|----------|-----------|---------|
| Modulo | PascalCase | `Logistics` |
| Entidad/Modelo | PascalCase | `ShipmentItem` |
| Tabla | snake_case plural | `shipment_items` |
| Columna DB | snake_case | `tracking_number` |
| Campo API | camelCase | `trackingNumber` |
| Tipo JSON:API | kebab-case plural | `shipment-items` |
| Foreign key | `{modelo}_id` | `shipment_id` |
| Permiso | `{tipo}.{accion}` | `shipments.store` |

---

## Permisos generados

5 permisos por entidad: `{tipo}.index`, `{tipo}.show`, `{tipo}.store`, `{tipo}.update`, `{tipo}.destroy`

Asignacion por rol:

| Rol | Permisos |
|-----|----------|
| god | Todos (via `Permission::where('name', 'like', '{tipo}.%')`) |
| admin | Todos (explicito) |
| tech | Todos (explicito) |
| customer | Solo `.index` + `.show` (read-only) |

---

## Tests generados

Cada entidad genera 5 archivos de test con los siguientes casos:

**IndexTest** (7 tests):
- Admin puede listar recursos
- Admin puede ordenar por campo sortable
- Admin puede filtrar por campo filterable
- Tech puede listar con permisos
- Customer puede listar (read-only)
- Guest recibe 401
- Paginacion funciona

**ShowTest** (3 tests):
- Admin puede ver recurso
- Guest recibe 401
- ID inexistente recibe 404

**StoreTest** (4 tests):
- Admin puede crear recurso
- Customer recibe 403
- Guest recibe 401
- Campos requeridos vacios recibe 422

**UpdateTest** (3 tests):
- Admin puede actualizar recurso
- Guest recibe 401
- ID inexistente recibe 404

**DestroyTest** (3 tests):
- Admin puede eliminar recurso
- Guest recibe 401
- ID inexistente recibe 404

---

## Patrones del codigo generado

### Model
- Traits: `HasFactory`, `LogsActivity`
- `$table` explicito
- `$fillable` con todos los campos (sin foreign keys)
- `$casts` con mapeo correcto por tipo
- `getActivitylogOptions()` con `logOnly($this->fillable)`
- `newFactory()` apuntando a factory del modulo
- Metodos de relacion con FQN para modulos externos

### Authorizer
- Implementa interfaz `Authorizer` (no extiende clase base)
- 10 metodos con `$request->user()?->can('{tipo}.{accion}') ?? false`
- Sin bypass para god/admin
- showRelated/showRelationship delegan a show
- updateRelationship/attach/detach delegan a update

### Request
- `$this->model()` para detectar update vs create
- Campos `required` cambian a `sometimes` en update
- `Rule::unique()->ignore($model)` para campos unique
- `JsonApiRule::toOne()` para relaciones belongsTo
- `withDefaults()` si hay campos con valor default

### Controller
- Extiende `Controller` (no JsonApiController)
- Usa Action traits: FetchMany, FetchOne, Store, Update, Destroy, FetchRelated, FetchRelationship, UpdateRelationship, AttachRelationship, DetachRelationship

### Migration
- Clase anonima `return new class extends Migration`
- Foreign keys con `->constrained()->onDelete('restrict')`
- Indexes en campos sortable/filterable
- Timestamps incrementales para evitar colision entre entidades

---

## Limpiar un modulo generado

```bash
# 1. Eliminar modulo
php artisan module:delete NombreModulo

# 2. Limpiar manualmente:
#    - app/JsonApi/V1/Server.php (import, schema, authorizer)
#    - database/seeders/DatabaseSeeder.php (linea del seeder)
#    - database/seeders/TestDatabaseSeeder.php (entrada en $requiredModules)

# 3. Restaurar base de datos
php artisan migrate:fresh --seeder=CleanDatabaseSeeder
```

---

## Arquitectura interna

```
app/Console/Commands/
  GenerateModuleCommand.php              Orquestador
  ModuleGeneration/
    Contracts/GeneratorInterface.php     Contrato comun
    FieldConfig.php                      Value object campo
    EntityConfig.php                     Value object entidad
    ModuleConfig.php                     Value object modulo
    NamingHelper.php                     Conversiones de nombres
    ConfigurationParser.php              Parseo y validacion JSON
    ModuleValidator.php                  Validacion de conflictos (conservado de v1)
    IntegrationManager.php              Integracion Server.php/Seeders (conservado de v1)
    Generators/
      ModelGenerator.php
      MigrationGenerator.php
      SchemaGenerator.php
      AuthorizerGenerator.php
      RequestGenerator.php
      ControllerGenerator.php
      FactoryGenerator.php
      TestGenerator.php
      RouteGenerator.php
      RouteServiceProviderGenerator.php
      PermissionSeederGenerator.php
      AssignPermissionSeederGenerator.php
      DatabaseSeederGenerator.php
```

