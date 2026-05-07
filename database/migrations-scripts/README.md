# Scripts de Migracion de Datos

## Contexto
Estos scripts migran datos desde el sistema de produccion actual hacia la nueva estructura.

## Estructura del Sistema Actual (Produccion)
El sistema de produccion tiene las siguientes tablas principales que deben migrarse:

### Datos Maestros
- `users` - Usuarios del sistema
- `contacts` - Clientes y proveedores
- `products` - Catalogo de productos
- `categories` - Categorias de productos
- `brands` - Marcas
- `units` - Unidades de medida
- `warehouses` - Almacenes
- `warehouse_locations` - Ubicaciones

### Transaccionales
- `sales_orders` + `sales_order_items` - Ordenes de venta
- `purchase_orders` + `purchase_order_items` - Ordenes de compra
- `stock` - Inventario actual
- `product_batches` - Lotes de productos
- `inventory_movements` - Movimientos de inventario

### Facturacion
- `cfdi_invoices` - Facturas CFDI
- `company_settings` - Configuracion fiscal

## Proceso de Migracion

### Paso 1: Backup de Produccion
```bash
# En servidor de produccion
mysqldump -u usuario -p base_produccion > backup_produccion_$(date +%Y%m%d).sql
```

### Paso 2: Crear BD Nueva
```bash
# Crear BD y correr migraciones
php artisan migrate:fresh
php artisan db:seed --class=PermissionSeeder
```

### Paso 3: Importar Datos
```bash
# Ejecutar script de importacion
php artisan system:migrate-production-data --source=backup_produccion.sql
```

### Paso 4: Verificar Integridad
```bash
php artisan system:verify-migration
```

## Mapeo de Tablas

| Tabla Produccion | Tabla Nueva | Notas |
|------------------|-------------|-------|
| usuarios | users | Mantener passwords hash |
| clientes | contacts (is_customer=1) | Agregar campos nuevos |
| proveedores | contacts (is_supplier=1) | Agregar campos nuevos |
| productos | products | Verificar SKUs unicos |
| categorias | categories | Reconstruir jerarquia |
| marcas | brands | Agregar default_lead_time |
| ordenes_venta | sales_orders | Mantener folios |
| ordenes_compra | purchase_orders | Agregar warehouse_id |

## Campos Nuevos (Requieren valor default)

### brands
- `default_lead_time` - NULL por default

### purchase_orders
- `warehouse_id` - Asignar almacen principal
- `order_number` - Generar si no existe

### company_settings
- `commercial_conditions` - NULL
- `bank_accounts_json` - NULL
- `payment_terms_days` - 30

## Comandos Artisan Disponibles

```bash
# Migrar datos de produccion
php artisan system:migrate-production-data --source=file.sql

# Verificar integracion
php artisan system:verify-migration

# Regenerar folios
php artisan system:regenerate-folios --type=quotes

# Limpiar datos de prueba
php artisan system:clean-test-data
```

## Rollback
En caso de fallo, restaurar backup:
```bash
mysql -u usuario -p base_nueva < backup_antes_migracion.sql
```
