# 🏗️ Guía para Módulo Personas con Relaciones Complejas

## 📋 Análisis del Escenario

### Entidades Requeridas:
1. **Persona** - Entidad principal
2. **Direccion** - Puede ser compartida por múltiples personas  
3. **DatosPerfil** - Información adicional 1:1 con persona

### Relaciones:
- **Persona ↔ Direccion** (Many-to-Many)
  - Requiere tabla pivot: `persona_direccion`
  - Una persona puede tener: casa, trabajo, temporal
  - Una dirección puede ser de: familia, compañeros, etc.

- **Persona ↔ DatosPerfil** (One-to-One)
  - Foreign key en `datos_perfiles.persona_id`
  - Cada persona tiene exactamente un perfil

## 🚀 Implementación Manual

### 1. Crear el módulo base:
```bash
php artisan module:make Personas
```

### 2. Generar modelos individuales:
```bash
php artisan module:make-model Personas Persona
php artisan module:make-model Personas Direccion  
php artisan module:make-model Personas DatosPerfil
```

### 3. Crear migraciones:
```bash
php artisan module:make-migration Personas create_personas_table
php artisan module:make-migration Personas create_direcciones_table
php artisan module:make-migration Personas create_datos_perfiles_table
php artisan module:make-migration Personas create_persona_direccion_table
```

### 4. Definir relaciones en modelos:

**Persona.php:**
```php
// Many-to-Many con Direccion
public function direcciones()
{
    return $this->belongsToMany(Direccion::class);
}

// One-to-One con DatosPerfil
public function datosPerfil()
{
    return $this->hasOne(DatosPerfil::class);
}
```

**Direccion.php:**
```php
// Many-to-Many con Persona
public function personas()
{
    return $this->belongsToMany(Persona::class);
}
```

**DatosPerfil.php:**
```php
// Belongs to Persona
public function persona()
{
    return $this->belongsTo(Persona::class);
}
```

### 5. JSON API Schemas:

**PersonaSchema.php:**
```php
public function fields(): array
{
    return [
        ID::make(),
        Str::make('nombre'),
        Str::make('apellido'), 
        Str::make('email')->unique(),
        
        // Relationships
        HasMany::make('direcciones'),
        HasOne::make('datosPerfil'),
        
        DateTime::make('createdAt'),
        DateTime::make('updatedAt'),
    ];
}
```

### 6. Uso de las relaciones:

**Consultar persona con sus direcciones:**
```
GET /api/v1/personas/1?include=direcciones
```

**Consultar persona con todo:**
```
GET /api/v1/personas/1?include=direcciones,datosPerfil
```

**Consultar direcciones con personas:**
```
GET /api/v1/direcciones?include=personas
```

## ⚡ Ventajas del enfoque manual vs automatizado:

### Manual (actual):
✅ Control total sobre las relaciones
✅ Puede manejar cualquier complejidad
✅ Personalización completa
❌ Requiere más tiempo
❌ Más posibilidad de errores

### Automatizado (futuro):
✅ Generación rápida
✅ Consistencia garantizada  
✅ Menos errores
❌ Limitado a casos predefinidos
❌ Menos flexibilidad

## 🎯 Recomendación:

Para tu caso de **Personas con relaciones complejas**, recomiendo:

1. **Usar el módulo Sales como template** - Ya tiene patrones probados
2. **Crear manualmente** siguiendo los estándares establecidos
3. **Documentar el proceso** para futuros módulos similares
4. **Considerar un generador específico** para este tipo de relaciones

## 📝 Próximos pasos:

¿Quieres que:
1. 🏗️ Creemos el módulo Personas manualmente paso a paso?
2. 🤖 Mejoremos el generador para manejar este caso?
3. 📚 Documentemos más patrones de relaciones complejas?
