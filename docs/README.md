# 📚 Documentación del Proyecto

Esta carpeta contiene toda la documentación organizada del proyecto API Base.

## 📁 Estructura

```
docs/
├── api/                    # Documentación de la API
│   ├── documentation.md   # Documentación completa de endpoints
│   └── endpoints.json     # Estructura JSON para herramientas
│
├── development/            # Documentación de desarrollo
│   ├── migration-roadmap.md     # Roadmap de migración
│   ├── module-blueprint.md      # Blueprint de módulos v1
│   └── module-blueprint-v2.md   # Blueprint de módulos v2
│
└── architecture/          # Documentación de arquitectura
    └── system-overview.md # Visión general del sistema
```

## 🔄 Regenerar Documentación

Para actualizar la documentación de la API después de cambios:

```bash
php artisan api:generate-docs
```

Este comando:
- ✅ Escanea todos los endpoints automáticamente
- ✅ Extrae campos de los schemas
- ✅ Genera ejemplos de requests
- ✅ Actualiza `docs/api/documentation.md` y `docs/api/endpoints.json`

## 📋 Usar la Documentación

### Para Desarrolladores Frontend
- Lee `docs/api/documentation.md` para entender los endpoints
- Importa `docs/api/endpoints.json` en Postman/Insomnia

### Para IA/Herramientas
- Usa `docs/api/endpoints.json` para procesamiento automático
- Estructura JSON estándar fácil de parsear

### Para Desarrollo de Módulos
- Consulta `docs/development/module-blueprint.md` para crear nuevos módulos
- Sigue el roadmap en `docs/development/migration-roadmap.md`

## 📝 Notas

- La documentación de API se regenera automáticamente
- Los archivos de desarrollo requieren actualización manual
- Mantén esta estructura organizada para facilitar el mantenimiento
