# Implementation Plan: Permitir fechas anteriores en los campos de fecha de registro operativo

**Branch**: `003-port-appointment-past-dates` | **Date**: 2026-06-01 | **Spec**: [spec.md](./spec.md)
**Input**: Feature specification from `/specs/003-port-appointment-past-dates/spec.md`

## Summary

Varios campos de fecha de registro operativo validan hoy con reglas de "solo futuro" (`after:now` / `after:today`), lo que rechaza fechas pasadas. Con la importación de inventario histórico, esos registros corresponden a eventos ya ocurridos y deben capturarse con su fecha real. El enfoque consiste en **relajar la validación temporal de forma uniforme** en los tres FormRequests afectados y eliminar el bloqueo de fechas pasadas en el control de fecha del formulario de vaciado, manteniendo obligatoriedad, formato y todas las validaciones de negocio no temporales. Así se alinean todos los flujos con el de completar pendientes, que ya acepta fechas pasadas.

## Technical Context

**Language/Version**: PHP 8.2 + Laravel 12  
**Primary Dependencies**: Laravel Validation (FormRequest), Spatie Laravel-Permission (RBAC), Blade + Bootstrap 5.3 (vistas)  
**Storage**: MySQL 8 — tablas `ordenes_servicio` (`cita_puerto`), `ordenes_cargue` (`fecha_despacho`), `ordenes_vaciado` (`fecha_programada`). Sin cambios de esquema.  
**Testing**: PHPUnit (Laravel Feature tests) — `tests/Feature/`  
**Target Platform**: Aplicación web (servidor PHP, hosting compartido GoDaddy)  
**Project Type**: Web application (monolito Laravel; backend + vistas Blade)  
**Performance Goals**: N/A — cambios en reglas de validación, sin impacto de rendimiento.  
**Constraints**: Mantener `required` y `date` en cada campo; no introducir límite inferior ni superior; conservar validaciones de negocio no temporales (estado del contenedor en vaciado, existencia de cliente/contenedor, rango "hasta ≥ desde" en reportes).  
**Scale/Scope**: 3 FormRequests + 1 vista (atributo `min`) + cobertura de pruebas. Sin migraciones ni nuevas entidades.

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principio | Evaluación |
|---|---|
| I. Código Limpio | ✅ Cambios mínimos y legibles; se eliminan restricciones de reglas de validación declarativas. |
| II. Convención sobre Configuración | ✅ Reglas nativas de Laravel en sus FormRequests; sin desviaciones. |
| III. Responsabilidad Única | ✅ La validación vive en cada FormRequest; controladores y servicios no cambian. |
| IV. No Duplicidad (DRY) | ✅ El criterio "se permiten fechas pasadas" queda uniforme en los tres flujos y consistente con `CompletarOrdenServicioRequest`, eliminando divergencias. |
| V. Simplicidad (KISS) | ✅ Solución más simple: ajustar reglas existentes y un atributo de vista. No se introduce abstracción nueva (regla de tres no amerita centralizar tres reglas declarativas triviales). |
| VI. Código Testeable | ✅ Se añaden pruebas Feature por flujo que cubren fecha pasada (acepta), vacío (rechaza) y formato inválido (rechaza). |
| VII. Escalabilidad | ✅ Sin impacto; no hay consultas ni procesamiento nuevo. |

**Resultado del gate**: PASA. Sin violaciones ni necesidad de Complexity Tracking.

## Project Structure

### Documentation (this feature)

```text
specs/003-port-appointment-past-dates/
├── plan.md              # Este archivo (/speckit.plan)
├── research.md          # Phase 0 (/speckit.plan)
├── data-model.md        # Phase 1 (/speckit.plan)
├── quickstart.md        # Phase 1 (/speckit.plan)
├── contracts/           # Phase 1 (/speckit.plan)
│   └── validacion-fechas-registro.md
└── tasks.md             # Phase 2 (/speckit.tasks — NO lo crea /speckit.plan)
```

### Source Code (repository root)

```text
app/Http/Requests/
├── StoreOrdenServicioRequest.php     # MODIFICAR: quitar 'after:now' de cita_puerto
├── StoreOrdenCargueRequest.php       # MODIFICAR: quitar 'after:today' de fecha_despacho
├── StoreOrdenVaciadoRequest.php      # MODIFICAR: quitar 'after:today' de fecha_programada (conservar withValidator de estado)
└── Pendientes/
    └── CompletarOrdenServicioRequest.php   # SIN CAMBIOS (ya acepta fechas pasadas; referencia de consistencia)

resources/views/
├── solicitudes/asignar.blade.php     # SIN CAMBIOS (datetime-local sin atributo min)
├── ordenes-cargue/...create.blade.php (entregas/create.blade.php) # SIN CAMBIOS (date sin min)
└── vaciado/create.blade.php          # MODIFICAR: quitar min="now()->addDay()" del input fecha_programada

tests/Feature/
└── FechasRegistro/
    ├── CitaPuertoFechaPasadaTest.php       # NUEVO
    ├── FechaDespachoFechaPasadaTest.php    # NUEVO
    └── FechaVaciadoFechaPasadaTest.php     # NUEVO
```

**Structure Decision**: Monolito Laravel existente. El cambio toca tres FormRequests y un atributo de la vista de vaciado, y agrega pruebas Feature por flujo. Los controladores (`OrdenServicioController`, `OrdenCargueController`, `VaciadoController`/`OrdenVaciadoController`) y los modelos no cambian, ya que solo consumen los valores ya validados. La regla `after_or_equal:fecha_desde` de reportes se conserva por ser un rango lógico.

## Complexity Tracking

> Sin violaciones de la constitución. No aplica.
