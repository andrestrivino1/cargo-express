# Implementation Plan: Sistema de Trazabilidad de Carga

**Branch**: `001-cargo-traceability-system` | **Date**: 2026-03-21 | **Spec**: [spec.md](spec.md)
**Input**: Feature specification from `/specs/001-cargo-traceability-system/spec.md`

## Summary

Sistema web de trazabilidad de carga para operaciones logísticas de patio de contenedores. Cubre el ciclo completo: solicitud de retiro → Gate In → vaciado → almacenamiento → Gate Out → entrega de mercancía, con inventario en tiempo real, notificaciones automáticas (WhatsApp/email), generación de documentos (tirillas, tarjas, stickers) y reportes exportables (Excel/PDF). Implementado como aplicación monolítica Laravel con arquitectura modular, MySQL como almacenamiento y Bootstrap para la interfaz.

## Technical Context

**Language/Version**: PHP 8.2+ con Laravel 11
**Primary Dependencies**: Laravel 11, Bootstrap 5.3, Laravel Breeze (auth), Spatie Laravel-Permission (RBAC), Maatwebsite Excel (exportación), DomPDF (exportación PDF), Laravel Notifications (email)
**Storage**: MySQL 8.0+
**Testing**: PHPUnit + Laravel Testing (Feature & Unit tests)
**Target Platform**: Servidor Linux (web), navegadores modernos (Chrome, Edge, Firefox)
**Project Type**: Web application (monolito Laravel con Blade + Bootstrap)
**Performance Goals**: Inventario actualizado en <30s, reportes generados en <10s, notificaciones enviadas en <60s
**Constraints**: Zona horaria Colombia (UTC-5), interfaz en español, operación con conexión estable
**Scale/Scope**: ~50 usuarios concurrentes, ~7 módulos, ~20 vistas principales, ~44 requerimientos funcionales

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principio | Estado | Evaluación |
|-----------|--------|------------|
| I. Código Limpio | PASS | Laravel enforces clean patterns (controllers, models, services). Blade templates separados. Convenciones de naming del framework. |
| II. Convención sobre Configuración | PASS | Se adoptan convenciones Laravel sin desviación: Eloquent ORM, migrations, seeders, config/. |
| III. Responsabilidad Única (SRP) | PASS | Controllers orquestan, Services con lógica de negocio, Models con persistencia. Estructura modular por dominio. |
| IV. No Duplicidad (DRY) | PASS | Constantes de estados en Enums PHP. Validaciones en Form Requests reutilizables. Traits para comportamiento compartido. |
| V. Simplicidad (KISS) | PASS | Monolito Laravel — sin microservicios, sin SPA, sin complejidad innecesaria. Blade + Bootstrap directo. |
| VI. Código Testeable | PASS | PHPUnit + Laravel factories/fakes. Inyección de dependencias nativa de Laravel. Meta: 80% servicios, 60% controllers. |
| VII. Escalabilidad | PASS | Jobs/Queues para notificaciones y reportes pesados. Paginación en consultas. Índices en migraciones. |
| Seguridad | PASS | Laravel Breeze (JWT/session), Spatie Permission (RBAC), CSRF nativo, validación en Form Requests, .env para secretos. |
| Nomenclatura | PASS | camelCase (variables/funciones), PascalCase (clases), snake_case (tablas BD), kebab-case (archivos Blade). Alineado con convención Laravel. |
| Commits | PASS | Conventional Commits: feat(gate-in): descripción. |
| PRs | PASS | Máximo 400 líneas, 1 aprobación, develop como rama base. |

**Gate Result: ALL PASS — Proceed to Phase 0**

## Project Structure

### Documentation (this feature)

```text
specs/[###-feature]/
├── plan.md              # This file (/speckit.plan command output)
├── research.md          # Phase 0 output (/speckit.plan command)
├── data-model.md        # Phase 1 output (/speckit.plan command)
├── quickstart.md        # Phase 1 output (/speckit.plan command)
├── contracts/           # Phase 1 output (/speckit.plan command)
└── tasks.md             # Phase 2 output (/speckit.tasks command - NOT created by /speckit.plan)
```

### Source Code (repository root)

```text
app/
├── Enums/                    # Estados, tipos de novedad, roles
├── Http/
│   ├── Controllers/
│   │   ├── SolicitudController.php
│   │   ├── GateInController.php
│   │   ├── VaciadoController.php
│   │   ├── AlmacenamientoController.php
│   │   ├── GateOutController.php
│   │   ├── EntregaController.php
│   │   ├── TrazabilidadController.php
│   │   └── ReporteController.php
│   ├── Requests/             # Form Requests (validación)
│   └── Middleware/
├── Models/
│   ├── Solicitud.php
│   ├── OrdenServicio.php
│   ├── Contenedor.php
│   ├── OrdenVaciado.php
│   ├── Novedad.php
│   ├── Referencia.php
│   ├── UbicacionPatio.php
│   ├── OrdenCargue.php
│   ├── Tarja.php
│   └── User.php
├── Services/
│   ├── SolicitudService.php
│   ├── GateInService.php
│   ├── VaciadoService.php
│   ├── InventarioService.php
│   ├── GateOutService.php
│   ├── EntregaService.php
│   ├── TrazabilidadService.php
│   ├── ReporteService.php
│   └── NotificacionService.php
├── Notifications/            # Canal: email (mail)
│   ├── NuevaSolicitudNotification.php
│   ├── NovedadRegistradaNotification.php
│   ├── UbicacionAsignadaNotification.php
│   └── TirillaGateOutNotification.php
├── Jobs/
│   └── GenerarReporteAsync.php
└── Exports/                  # Maatwebsite Excel exports
    └── ReporteOperacionExport.php

database/
├── migrations/
├── seeders/
└── factories/

resources/
├── views/
│   ├── layouts/
│   │   └── app.blade.php     # Layout principal con Bootstrap 5
│   ├── solicitudes/
│   ├── gate-in/
│   ├── vaciado/
│   ├── almacenamiento/
│   ├── gate-out/
│   ├── entregas/
│   ├── trazabilidad/
│   ├── reportes/
│   └── components/           # Componentes Blade reutilizables
├── css/
└── js/

routes/
└── web.php                   # Rutas agrupadas por módulo con middleware

tests/
├── Unit/
│   └── Services/
└── Feature/
    ├── SolicitudTest.php
    ├── GateInTest.php
    ├── VaciadoTest.php
    ├── AlmacenamientoTest.php
    ├── GateOutTest.php
    ├── EntregaTest.php
    └── TrazabilidadTest.php

config/
storage/
    └── app/public/           # Fotos y documentos adjuntos
```

**Structure Decision**: Monolito Laravel estándar con estructura modular por dominio. Los controladores, servicios y modelos siguen la convención Laravel. La lógica de negocio vive en `Services/`, los controladores solo orquestan. Las vistas usan Blade con layout compartido Bootstrap 5. Alineado con la constitución (SRP, convención sobre configuración).

## Complexity Tracking

> No hay violaciones a la constitución. Todas las decisiones técnicas están alineadas con los principios definidos.

## Post-Design Constitution Re-Check

| Principio | Estado | Verificación post-diseño |
|-----------|--------|--------------------------|
| I. Código Limpio | PASS | Estructura clara: 1 controller/service por módulo. Models Eloquent con relaciones explícitas. Enums PHP para estados. |
| II. Convención sobre Configuración | PASS | 100% convención Laravel: migrations, seeders, Form Requests, Policies, Notifications. Sin patrones custom. |
| III. SRP | PASS | Controllers orquestan, Services con lógica, Models con persistencia. NotificacionService separado. |
| IV. DRY | PASS | Modelo Photo polimórfico (no duplicado por entidad). Enums centralizados. Componentes Blade reutilizables. |
| V. KISS | PASS | Monolito Laravel, Blade + Bootstrap, sin SPA, sin microservicios. Database queue (sin Redis inicial). |
| VI. Testeable | PASS | Services inyectables. Feature tests por módulo. Factories para cada model. |
| VII. Escalabilidad | PASS | Jobs/Queues para notificaciones y reportes. Paginación en inventario. Broadcasting para tiempo real. |
| Seguridad | PASS | Breeze auth, Spatie RBAC en cada ruta, Form Requests para validación, Policies para scope por cliente. |

**Post-Design Gate Result: ALL PASS**

---

## Generated Artifacts

| Artefacto | Path | Descripción |
|-----------|------|-------------|
| Plan | `specs/001-cargo-traceability-system/plan.md` | Este archivo |
| Research | `specs/001-cargo-traceability-system/research.md` | Decisiones técnicas y alternativas |
| Data Model | `specs/001-cargo-traceability-system/data-model.md` | 14 entidades, relaciones, índices, migraciones |
| Web Routes | `specs/001-cargo-traceability-system/contracts/web-routes.md` | Rutas, controllers, permisos, broadcasting |
| Quickstart | `specs/001-cargo-traceability-system/quickstart.md` | Setup del proyecto paso a paso |

## Next Step

Ejecutar `/speckit.tasks` para generar las tareas de implementación ordenadas por dependencia.
