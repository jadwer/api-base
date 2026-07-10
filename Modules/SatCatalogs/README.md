# SatCatalogs

Catalogos SAT autohospedados para CFDI 4.0 (WS9). Resuelve la nota 1 del cliente:
los catalogos ClaveProdServ y ClaveUnidad no se consultan por API de terceros
(SW Sapien no ofrece una), se hospedan en nuestra DB y se sincronizan desde
`phpcfdi/resources-sat-catalogs`.

## Tablas

Todas son GLOBALES (dato publico del SAT, compartido entre tenants) y sin timestamps.

| Tabla | PK | Contenido | Filas (catalogo completo) |
|---|---|---|---|
| `sat_clave_prod_serv` | `clave` string(10) | c_ClaveProdServ: descripcion, incluye_iva/ieps (tri-estado), palabras_similares, vigencia_hasta | ~52,500 |
| `sat_clave_unidad` | `clave` string(20) | c_ClaveUnidad: nombre, descripcion, simbolo | ~2,400 |
| `sat_forma_pago` | `clave` string(2) | c_FormaPago: descripcion | 22 |
| `sat_tasa_o_cuota` | `id` | Tasas concretas seleccionables: tipo (Tasa/Cuota/Exento), impuesto (IVA/ISR/IEPS), valor, retencion, traslado | ~20 |

Busquedas con `LIKE` sobre indices normales (sin FULLTEXT: los tests corren en SQLite;
a este volumen la diferencia es sub-ms).

El seeder `SatCatalogsSeeder` carga un subconjunto util sin internet (33 claves
prod/serv orientadas a quimicos/laboratorio, 17 unidades, 11 formas de pago,
6 tasas) con `firstOrCreate`, asi que es idempotente y no pisa el catalogo completo.

## Sincronizacion del catalogo completo

```bash
# Descarga el ultimo release de phpcfdi/resources-sat-catalogs y hace upsert
php artisan sat:sync-catalogs

# Con un archivo local (entornos sin internet, tests)
php artisan sat:sync-catalogs --path=/ruta/catalogs.db
```

El comando descarga `catalogs.db.bz2` (SQLite) del release mas reciente,
descomprime con ext-bz2 (fallback: binario `bunzip2`; si no hay ninguno, error
con instrucciones) y hace upsert por lotes de 500 SOLO de las 4 tablas que
usamos. Al final reporta filas sincronizadas por tabla.

Programado mensual en `routes/console.php` (dia 1, 02:30 America/Mexico_City).
En cPanel sin scheduler activo, agregar el cron estandar de Laravel:

```
* * * * * cd /ruta/al/proyecto && php artisan schedule:run >> /dev/null 2>&1
```

### Mapeo de tablas fuente (catalogs.db de phpcfdi)

| Fuente | Destino | Notas |
|---|---|---|
| `cfdi_40_productos_servicios` | `sat_clave_prod_serv` | `iva_trasladado`/`ieps_trasladado` vacios se guardan como NULL (desconocido) |
| `cfdi_40_claves_unidades` | `sat_clave_unidad` | `texto` es el nombre; descripcion y simbolo vacios se guardan como NULL |
| `cfdi_40_formas_pago` | `sat_forma_pago` | solo id + texto |
| `cfdi_40_reglas_tasa_cuota` | `sat_tasa_o_cuota` | solo filas `tipo = Fijo` (las `Rango` son rangos permitidos, no valores de dropdown); impuestos como "IVA Credito aplicado del 50%" se normalizan a IVA |

Las filas Exento y las retenciones comunes (ISR 10%, IVA 10.6667%) vienen del
seeder; el sync no las toca.

## Endpoints (auth:sanctum, cualquier usuario autenticado)

Dato publico SAT: no requieren permiso especifico, solo sesion valida.

```bash
# Busqueda para dropdown (prefijo de clave primero, luego descripcion)
curl -H "Authorization: Bearer $TOKEN" \
  "https://api.example.com/api/v1/sat/clave-prod-serv?filter[search]=acido&page[size]=20"
# -> { "data": [ { "clave": "12352301", "descripcion": "Ácidos inorgánicos" }, ... ] }

curl -H "Authorization: Bearer $TOKEN" \
  "https://api.example.com/api/v1/sat/clave-unidad?filter[search]=litro"
# -> { "data": [ { "clave": "LTR", "nombre": "Litro", "simbolo": "l" }, ... ] }

# Lista completa (~22 filas)
curl -H "Authorization: Bearer $TOKEN" \
  "https://api.example.com/api/v1/sat/forma-pago"

# Tasas filtradas para el selector de impuestos
curl -H "Authorization: Bearer $TOKEN" \
  "https://api.example.com/api/v1/sat/tasa-o-cuota?filter[impuesto]=IVA&filter[traslado]=1"
```

`page[size]` default 20, maximo 50. Filtros de tasa-o-cuota: `impuesto`,
`traslado`, `retencion`, `tipo`.

## Campos SAT en products

La migracion vive en este modulo (mismo patron que Commissions, que altera
contacts/users desde su propio modulo):

| Campo | Tipo | Semantica |
|---|---|---|
| `sat_clave_prod_serv` | string(10) nullable | FK logica a `sat_clave_prod_serv` (sin constraint: el catalogo puede estar vacio en installs frescos) |
| `sat_clave_unidad` | string(20) nullable | FK logica a `sat_clave_unidad` |
| `product_type` | string nullable | `finished`, `raw_material` o `both` |
| `tax_rate` | decimal(5,2) nullable | Porcentaje (16 = 16%). **NULL = Exento** (distinto de 0 = tasa 0%, distincion fiscal real) |

Backfill al migrar: `tax_rate = 16` si `iva = true`, `0` si `false`. La columna
`iva` boolean se conserva por compatibilidad con el catalogo publico.

### effective_tax_rate

`Product::getEffectiveTaxRateAttribute()` devuelve `tax_rate` cuando no es null
y cae al flag legado (`iva ? 16 : 0`) cuando es null. Decision deliberada: un
producto nuevo creado sin `taxRate` pero con `iva = true` debe seguir cotizando
al 16%, no al 0%. La representacion de Exento a nivel CFDI se maneja con el
catalogo `sat_tasa_o_cuota` (fila tipo Exento) al armar los impuestos del
concepto, no con el accessor. `QuoteController` consume
`$product->effective_tax_rate ?? 0`.

### Como agregar tasas

Insertar en `sat_tasa_o_cuota` (seeder del tenant o tinker):

```php
SatTasaOCuota::firstOrCreate([
    'tipo' => 'Tasa',        // Tasa | Cuota | Exento (valor null para Exento)
    'impuesto' => 'IEPS',    // IVA | ISR | IEPS
    'valor' => 0.080000,
    'retencion' => false,
    'traslado' => true,
]);
```

## CFDIXMLGenerator

Al armar cada `cfdi:Concepto` usa primero las claves SAT del producto ligado al
item (`sat_clave_prod_serv` / `sat_clave_unidad`), luego el valor guardado en el
item y al final los genericos de siempre (`01010101` / `E48`). Los items creados
por `CFDIAutomationService` no traen `product_id`, asi que conservan su
comportamiento actual hasta que ese servicio ligue producto.

## Origen de datos

`phpcfdi/resources-sat-catalogs` (colectivo phpCfdi, estandar del gremio PHP de
facturacion en Mexico). Releases automaticos con `catalogs.db.bz2` (SQLite,
dominio publico); nombres de tabla verificados contra el release
`v10.11.20260703`. La libreria de consumo `phpcfdi/sat-catalogos` no se usa:
leemos el SQLite directo con PDO, sin dependencia extra.
