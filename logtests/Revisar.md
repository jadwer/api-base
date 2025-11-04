📋 GUÍA: Tests en Memoria SQLite (Estrategia Conservadora)
¿Por qué SQLite en Memoria es Más Seguro?
Diferencia con intentos anteriores:
❌ Antes: Intentamos optimizar con bootstrap personalizado, DatabaseTransactions, y caché complejo
✅ Ahora: Solo cambiamos la base de datos de MySQL a SQLite en memoria
Resultado: Laravel maneja todo automáticamente, sin cambios complejos
Ventajas:
✅ RefreshDatabase sigue funcionando igual (pero 10-20x más rápido)
✅ Cada test sigue siendo independiente y aislado
✅ Los seeders siguen corriendo igual
✅ No hay riesgo de estado compartido entre tests
✅ Rollback simple si algo falla
Desventajas (limitaciones conocidas):
⚠️ SQLite tiene algunas diferencias con MySQL (pocas, pero existen)
⚠️ Si usas features muy específicos de MySQL, pueden no funcionar igual
⚠️ Puede haber pequeñas diferencias en validación de constraints
🔧 FASE 1: Preparación y Backup
Paso 1.1 - Crear backup del estado actual
git add -A
git commit -m "checkpoint: baseline state before SQLite migration"
Paso 1.2 - Verificar que tests actuales pasan
# Ejecutar un test simple para verificar que todo funciona
php artisan test Modules/Auth/tests/Feature/LoginTest.php
Si este test pasa, estamos listos. Si falla, NO continuar con la guía.
🔧 FASE 2: Implementación SQLite (Cambio Mínimo)
Paso 2.1 - Modificar .env.testing Archivo: .env.testing Cambiar SOLO estas líneas:
# Antes:
DB_CONNECTION=mysql
DB_DATABASE=api-base-test

# Después:
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
Eso es todo. No cambiar nada más en este archivo. Paso 2.2 - Verificar configuración de database (probablemente ya está bien) Archivo: config/database.php Verificar que existe esta configuración para SQLite:
'sqlite' => [
    'driver' => 'sqlite',
    'url' => env('DB_URL'),
    'database' => env('DB_DATABASE', database_path('database.sqlite')),
    'prefix' => '',
    'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
],
Si ya existe (que debería), no tocar nada.
🔧 FASE 3: Verificación Gradual
Paso 3.1 - Test individual simple
php artisan test Modules/Auth/tests/Feature/LoginTest.php
Resultado esperado:
✅ Debe pasar en ~2-5 segundos (vs 20-40 segundos antes)
✅ Debe mostrar los mismos resultados que antes
Si falla aquí:
Leer el error cuidadosamente
Si es sobre migraciones o seeders, puede ser incompatibilidad SQLite
Rollback inmediato (ver Fase 5)
Paso 3.2 - Test de módulo completo
php artisan test Modules/Auth/tests/Feature/
Resultado esperado:
✅ Todos los tests de Auth deben pasar
✅ Tiempo total: ~30-60 segundos (vs varios minutos antes)
Paso 3.3 - Tests de módulo con más complejidad
# Probar módulo con relaciones más complejas
php artisan test Modules/Sales/tests/Feature/OrderTest.php
Si falla aquí:
Probablemente es una incompatibilidad específica de MySQL/SQLite
Anotar el error exacto
Decidir si vale la pena continuar o hacer rollback
Paso 3.4 - Test de varios módulos
# Probar 3-4 módulos juntos
timeout 180 php artisan test Modules/Auth/ Modules/User/ Modules/Product/
Resultado esperado:
✅ Todos pasan
✅ Tiempo total: 2-5 minutos (vs 15-30 minutos antes)
✅ Sin errores de memoria
🔧 FASE 4: Ajustes Específicos de SQLite (Si es Necesario)
Problema común 1: Foreign keys Si ves errores de foreign key constraints, puede ser orden de migraciones. Solución:
# Verificar que migraciones están en orden correcto
ls -la database/migrations/
ls -la Modules/*/Database/migrations/
Las migraciones deben estar numeradas en orden de dependencias:
Primero: Users, Permissions
Después: Entities básicas (Products, Contacts)
Finalmente: Entities con relaciones complejas (Orders, Invoices)
Problema común 2: JSON fields SQLite maneja JSON diferente a MySQL. Si ves errores en campos JSON: Solución: En los modelos que usan JSON, verificar que tengan el cast correcto:
protected $casts = [
    'metadata' => 'array', // o AsArrayObject::class
];
Problema común 3: Decimales y floats SQLite no distingue entre DECIMAL y FLOAT como MySQL. Solución: Verificar que los casts en modelos usan 'float':
protected $casts = [
    'price' => 'float',
    'quantity' => 'float',
];
🔧 FASE 5: Rollback (Si Algo Sale Mal)
Si decides que no vale la pena:
# Deshacer cambios en .env.testing
git checkout .env.testing

# Verificar que volvimos al estado anterior
cat .env.testing | grep DB_CONNECTION
# Debe mostrar: DB_CONNECTION=mysql

# Ejecutar test para confirmar que todo vuelve a funcionar
php artisan test Modules/Auth/tests/Feature/LoginTest.php
Eso es todo. Como solo cambiamos .env.testing, el rollback es trivial.
📊 FASE 6: Medición de Resultados
Si todo funciona bien, medir el impacto:
# Tiempo antes (MySQL):
time php artisan test Modules/Auth/tests/Feature/LoginTest.php
# Esperado: 20-40 segundos

# Tiempo después (SQLite):
time php artisan test Modules/Auth/tests/Feature/LoginTest.php
# Esperado: 2-5 segundos

# Test de módulo completo:
time php artisan test Modules/Sales/tests/Feature/
# Antes: ~10-15 minutos
# Después: ~2-3 minutos
⚠️ DECISIONES IMPORTANTES
¿Deberías implementar esto? SÍ, si:
✅ No usas features muy específicos de MySQL (triggers, procedures, etc)
✅ Tus tests actuales ya pasan consistentemente
✅ Quieres acelerar el ciclo de desarrollo
✅ Estás dispuesto a aceptar pequeñas diferencias en comportamiento
NO, si:
❌ Usas stored procedures o triggers de MySQL
❌ Dependes de comportamiento específico de MySQL
❌ Tus tests tienen bugs que aún no has arreglado
❌ Prefieres mantener paridad exacta con producción
🎯 ESTRATEGIA RECOMENDADA
Mi recomendación basada en lo que pasó:
PRIMERO: Arreglar los tests que están fallando actualmente
No optimizar hasta que todos pasen con MySQL
Ir módulo por módulo
Confirmar que la lógica de negocio es correcta
DESPUÉS: Implementar SQLite como mejora opcional
Hacer la migración gradual (Fases 1-3 de esta guía)
Si encuentras problemas, rollback es simple
No hay prisa - el sistema actual funciona
NUNCA MÁS: Optimizaciones complejas antes de estabilidad
No más bootstrap personalizado
No más DatabaseTransactions sin entender las implicaciones
No más cambios grandes sin verificación gradual
📝 RESUMEN DE LA GUÍA
Cambio mínimo:
Modificar 2 líneas en .env.testing
Nada más
Verificación gradual:
Test individual → Módulo → Varios módulos
Si falla en cualquier punto, rollback simple
Resultado esperado:
10-20x más rápido
Mismo comportamiento
Sin cambios en código de tests
Diferencia con intentos anteriores:
❌ Antes: Cambios complejos en múltiples archivos
✅ Ahora: Un solo archivo, dos líneas