# Documentacion de api-base

| Tema | Documento |
|---|---|
| Reglas de trabajo, convenciones y comandos | `../CLAUDE.md` |
| Generador de modulos (`php artisan module:generate`) | `module-generator.md`, `module-generator-wizard.html` |
| Reglas de negocio (corregidas con la auditoria de 2026-07) | `architecture/BUSINESS_RULES_COMPLETE.md` |
| Contratos JSON:API | `../tests/Feature/Contracts/` |
| Release, rollback y servidor | `docs/RUNBOOK_RELEASE.md` del workspace (`base/`) |

Las carpetas `api/`, `architecture/`, `development/`, `frontend/`, `modules/` y
`performance/` son de enero de 2026 y no se han revisado contra el codigo actual: sirven
como contexto historico, no como referencia. `api/` se regenera con
`php artisan scribe:generate`. El esquema de la base se lee de las migraciones.
