# 🗑️ FASE 0: Backup y Limpieza
## Eliminación segura de módulos Finance/Accounting Phase 1

**Objetivo:** Backup completo y eliminación segura de implementación Phase 1

---

## 🎯 **OBJETIVO**

Realizar backup completo de la implementación Phase 1 de Finance y Accounting, y eliminar los módulos actuales para preparar la regeneración empresarial.

## 📦 **SCOPE DE ELIMINACIÓN**

### **Módulos a Eliminar**
```
Modules/Finance/     (11 entidades)
Modules/Accounting/  (6 entidades)
```

### **Archivos de Configuración a Limpiar**
- `temp/finance-*.json` (configs usados en Phase 1)
- `temp/accounting-*.json` (configs usados en Phase 1)  
- Referencias en `app/JsonApi/V1/Server.php`
- Seeders en `database/seeders/DatabaseSeeder.php`

---

## 🛠️ **IMPLEMENTACIÓN**

### **Paso 1: Backup Completo**

```bash
# Crear directorio de backup con timestamp
mkdir -p temp/backup/phase1_$(date +%Y%m%d_%H%M%S)

# Backup de módulos completos
cp -r Modules/Finance temp/backup/phase1_$(date +%Y%m%d_%H%M%S)/
cp -r Modules/Accounting temp/backup/phase1_$(date +%Y%m%d_%H%M%S)/

# Backup de configuraciones
cp app/JsonApi/V1/Server.php temp/backup/phase1_$(date +%Y%m%d_%H%M%S)/
cp database/seeders/DatabaseSeeder.php temp/backup/phase1_$(date +%Y%m%d_%H%M%S)/

# Backup de migraciones ejecutadas
cp database/migrations/*_create_*_table.php temp/backup/phase1_$(date +%Y%m%d_%H%M%S)/migrations/
```

### **Paso 2: Export de Datos de Producción**

```bash
# Export datos críticos para preservar
php artisan db:export --tables=accounts,fiscal_periods --output=temp/backup/phase1_$(date +%Y%m%d_%H%M%S)/production_data.sql
```

### **Paso 3: Eliminación Segura con Comando**

```bash
# Usar comando force-delete para limpieza completa
php artisan module:force-delete Finance
php artisan module:force-delete Accounting
```

### **Paso 4: Limpieza Manual de Referencias**

Verificar eliminación completa de:

```php
// app/JsonApi/V1/Server.php - eliminar registros
"accounts" => AccountSchema::class,
"fiscal-periods" => FiscalPeriodSchema::class,
// ... todos los schemas Finance/Accounting

// database/seeders/DatabaseSeeder.php - eliminar llamadas
$this->call([
    // NO: \Modules\Finance\Database\Seeders\FinanceDatabaseSeeder::class,
    // NO: \Modules\Accounting\Database\Seeders\AccountingDatabaseSeeder::class,
]);
```

### **Paso 5: Rollback de Migraciones**

```bash
# Rollback migraciones específicas de Finance/Accounting
php artisan migrate:rollback --path=database/migrations --step=XX

# Alternativamente, fresh con preserve de otros módulos
php artisan migrate:fresh --force
php artisan db:seed --class=BaseSystemSeeder # Solo lo esencial
```

---

## ✅ **CRITERIOS DE ACEPTACIÓN**

### **Backup Completo**
- [ ] Módulos Finance y Accounting respaldados completamente
- [ ] Configuraciones de Server.php y DatabaseSeeder.php respaldadas
- [ ] Migraciones y datos de producción exportados
- [ ] Backup verificado y accesible

### **Eliminación Limpia**
- [ ] Directorio `Modules/Finance/` eliminado completamente
- [ ] Directorio `Modules/Accounting/` eliminado completamente  
- [ ] Referencias en Server.php eliminadas
- [ ] Seeders eliminados de DatabaseSeeder.php
- [ ] Sistema base funciona sin errores

### **Estado Limpio**
- [ ] `php artisan route:list` no muestra rutas Finance/Accounting
- [ ] `php artisan test` ejecuta sin errores de módulos faltantes
- [ ] Composer autoload actualizado
- [ ] Cache Laravel limpio

---

## 🚨 **PROCEDIMIENTOS DE EMERGENCIA**

### **Rollback Completo**
Si algo sale mal durante la eliminación:

```bash
# Restaurar desde backup
cp -r temp/backup/phase1_TIMESTAMP/Finance Modules/
cp -r temp/backup/phase1_TIMESTAMP/Accounting Modules/

# Restaurar configuraciones
cp temp/backup/phase1_TIMESTAMP/Server.php app/JsonApi/V1/
cp temp/backup/phase1_TIMESTAMP/DatabaseSeeder.php database/seeders/

# Regenerar autoload y cache
composer dump-autoload
php artisan config:clear
php artisan route:clear
```

### **Validación Post-Rollback**
```bash
# Verificar funcionalidad
php artisan migrate:status
php artisan test --filter=Finance
php artisan test --filter=Accounting
```

---

## 📋 **CHECKLIST DE VALIDACIÓN**

### **Pre-Eliminación**
- [ ] Backup creado y verificado
- [ ] Datos de producción exportados
- [ ] Team notificado de la operación
- [ ] Ambiente de testing disponible

### **Durante Eliminación**  
- [ ] Comando force-delete ejecutado exitosamente
- [ ] Referencias manuales eliminadas
- [ ] Migraciones rollback aplicado
- [ ] Cache limpio

### **Post-Eliminación**
- [ ] Sistema base funcional
- [ ] Tests de otros módulos pasan
- [ ] No hay referencias rotas
- [ ] Preparado para regeneración

---

## 🔄 **COORDINACIÓN CON TEAM**

### **Comunicación Requerida**
- [ ] Notificar a frontend team sobre downtime temporal
- [ ] Coordinar con QA para testing post-eliminación
- [ ] Backup location compartido con team lead

### **Dependencias**
- [ ] PRE-FASE completada (campos de integración aplicados)
- [ ] Ambiente de testing actualizado
- [ ] JSONs empresariales finalizados y validados

---

## 📅 **PLAN DE EJECUCIÓN**

### **Etapa 1: Backup y Preparación**
- Crear backup completo de módulos
- Export de datos de producción  
- Verificar integridad del backup

### **Etapa 2: Eliminación Segura**
- Ejecutar force-delete commands
- Limpieza manual de referencias
- Rollback de migraciones

### **Etapa 3: Validación**
- Tests de sistema base
- Verificación de estado limpio
- Documentar estado final

---

## 🚀 **SIGUIENTE FASE**

Una vez completada la FASE 0, proceder con **FASE 1: Regeneración de Accounting** con estructura empresarial completa.