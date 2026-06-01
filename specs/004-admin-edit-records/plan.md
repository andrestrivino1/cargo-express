# Implementation Plan: Edición de registros operativos por administrador/coordinador

**Branch**: `004-admin-edit-records` | **Date**: 2026-06-01 | **Spec**: [spec.md](./spec.md)
**Input**: Feature specification from `/specs/004-admin-edit-records/spec.md`

## Summary

Los siete módulos operativos (solicitudes, ingresos, vaciado, salidas, almacenamiento, transferencias, entregas) hoy solo permiten crear y consultar. Esta feature agrega **edición correctiva** para los roles administrador y coordinador, con **auditoría transversal** de cambios. El enfoque: un **mecanismo transversal compartido** (tabla de auditoría polimórfica + servicio de auditoría + trait `Auditable` + autorización por rol) y, por módulo, un par de acciones `edit`/`update` con su Form Request de actualización y su vista de edición. La edición se limita a campos correctivos/descriptivos; **no** se recalcula ni mueve inventario (FR-004). Los registros se pueden editar en cualquier estado (FR-005).

## Technical Context

**Language/Version**: PHP 8.2 + Laravel 12  
**Primary Dependencies**: Spatie Laravel-Permission 6.25 (RBAC, ya en uso), Laravel Breeze (auth), Blade + Bootstrap 5.3. **Sin nuevas dependencias** (la auditoría se implementa con una tabla propia; en hosting compartido sin SSH no conviene añadir paquetes que requieran `composer install`).  
**Storage**: MySQL 8. Se agrega **1 tabla nueva** `cambios_auditoria` (auditoría polimórfica). No se altera el esquema de los módulos existentes.  
**Testing**: PHPUnit (Laravel Feature tests) — `tests/Feature/`  
**Target Platform**: Aplicación web (servidor PHP, hosting compartido GoDaddy; deploy con `vendor/` versionado)  
**Project Type**: Web application (monolito Laravel; backend + vistas Blade)  
**Performance Goals**: N/A — operaciones CRUD puntuales; la auditoría es una inserción por edición.  
**Constraints**: Solo campos correctivos (no recalcular inventario); editable en cualquier estado; auditoría obligatoria; roles administrador y coordinador; reusar validaciones de creación cuando aplique (DRY).  
**Scale/Scope**: 7 módulos × (ruta edit + ruta update + UpdateRequest + vista edit) + 1 mecanismo transversal de auditoría + autorización. Sin eliminación de registros.

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principio | Evaluación |
|---|---|
| I. Código Limpio | ✅ Patrón uniforme y legible por módulo; controladores delgados. |
| II. Convención sobre Configuración | ✅ Se usan recursos REST de Laravel (`edit`/`update`), Form Requests y middleware/políticas estándar. |
| III. Responsabilidad Única | ✅ Controladores orquestan; la auditoría vive en un `AuditoriaService`/trait; la validación en Form Requests. |
| IV. No Duplicidad (DRY) | ✅ Auditoría, autorización y diffing centralizados; los `Update*Request` reutilizan reglas de los `Store*Request` cuando coinciden. |
| V. Simplicidad (KISS) | ✅ Tabla de auditoría propia y mínima en vez de un paquete externo (evita dependencia pesada en hosting sin SSH). Regla de tres cumplida: 7 usos justifican el mecanismo transversal. |
| VI. Código Testeable | ✅ Servicio de auditoría inyectable; pruebas Feature por módulo (edición, validación, auditoría, autorización) + pruebas del servicio. |
| VII. Escalabilidad | ✅ Auditoría = 1 insert por edición; tabla indexada por `auditable_type/id`. Sin operaciones masivas. |

**Resultado del gate**: PASA. Sin violaciones ni Complexity Tracking.

## Decisiones de diseño clave

### Mecanismo transversal de auditoría
- Tabla `cambios_auditoria` polimórfica: `auditable_type`, `auditable_id`, `usuario_id`, `cambios` (JSON con `{campo: {anterior, nuevo}}`), `created_at`.
- `AuditoriaService::registrarCambios(Model $modelo, User $usuario)`: calcula los atributos `dirty` antes de guardar y crea la entrada solo si hay cambios reales (FR-007, FR-011).
- Trait `Auditable` sobre los modelos para exponer `cambiosAuditoria()` (relación `morphMany`) y soportar la consulta de historial (FR-009).

### Autorización
- Middleware de rol `role:administrador|coordinador` sobre las rutas `edit`/`update` (consistente con cómo `admin/importaciones` ya usa `role:administrador|coordinador`). Cubre FR-001/FR-002 sin crear permisos nuevos.

### Campos editables por módulo (solo correctivos; excluye estructurales/derivados)

| Módulo | Registro editado | Campos correctivos editables | Excluidos (estructurales/derivados) |
|---|---|---|---|
| Solicitudes | `Solicitud` | cliente_id, numero_contenedor, naviera, puerto_origen, descripcion, fecha_solicitud | estado, import_batch_id |
| Ingresos (gate-in) | `GateEvent` (tipo ingreso) | hora, estado_fisico, notas | contenedor_id, tipo, usuario_id |
| Vaciado | `OrdenVaciado` | fecha_programada, supervisor_id, notas | contenedor_id, fecha_inicio, fecha_fin, estado |
| Salidas (gate-out) | `GateEvent` (tipo salida) | hora, estado_fisico, notas | contenedor_id, tipo, usuario_id |
| Almacenamiento | `Referencia` | ubicacion_patio_id, codigo, descripcion, unidad_medida, fecha_ingreso | cantidad_inicial, cantidad_actual, contenedor_id, cliente_id |
| Transferencias | `Transferencia` | motivo, autorizacion_cliente | cantidad, referencia/ubicacion/cliente origen y destino, tipo |
| Entregas | `OrdenCargue` | cliente_id, fecha_despacho, notas | estado, tarjas (derivado) |

> Las cantidades de inventario quedan fuera de la edición correctiva (FR-004); sus ajustes siguen el flujo de movimientos/transferencias.

## Project Structure

### Documentation (this feature)

```text
specs/004-admin-edit-records/
├── plan.md              # Este archivo (/speckit.plan)
├── research.md          # Phase 0 (/speckit.plan)
├── data-model.md        # Phase 1 (/speckit.plan)
├── quickstart.md        # Phase 1 (/speckit.plan)
├── contracts/           # Phase 1 (/speckit.plan)
│   ├── auditoria.md
│   └── edicion-modulos.md
└── tasks.md             # Phase 2 (/speckit.tasks — NO lo crea /speckit.plan)
```

### Source Code (repository root)

```text
app/
├── Models/
│   └── CambioAuditoria.php                 # NUEVO (modelo de auditoría)
├── Services/
│   └── AuditoriaService.php                # NUEVO (diffing + registro)
├── Traits/
│   └── Auditable.php                       # NUEVO (morphMany cambiosAuditoria)
├── Http/Requests/
│   ├── UpdateSolicitudRequest.php          # NUEVO (reusa reglas de Store)
│   ├── UpdateGateInRequest.php             # NUEVO
│   ├── UpdateOrdenVaciadoRequest.php       # NUEVO
│   ├── UpdateGateOutRequest.php            # NUEVO
│   ├── UpdateReferenciaRequest.php         # NUEVO (almacenamiento)
│   ├── UpdateTransferenciaRequest.php      # NUEVO
│   └── UpdateOrdenCargueRequest.php        # NUEVO (entregas)
└── Http/Controllers/                       # MODIFICAR: añadir edit()/update()
    ├── SolicitudController.php
    ├── GateInController.php
    ├── VaciadoController.php
    ├── GateOutController.php
    ├── AlmacenamientoController.php
    ├── TransferenciaController.php
    └── EntregaController.php

database/migrations/
└── 2026_06_01_000000_create_cambios_auditoria_table.php   # NUEVO

resources/views/
├── solicitudes/editar.blade.php            # NUEVO (y equivalentes por módulo)
├── gate-in/editar.blade.php
├── vaciado/editar.blade.php
├── gate-out/editar.blade.php
├── almacenamiento/editar.blade.php
├── transferencias/editar.blade.php
├── entregas/editar.blade.php
└── components/historial-auditoria.blade.php  # NUEVO (parcial reutilizable de historial)

routes/web.php                              # MODIFICAR: rutas edit/update por módulo (role:administrador|coordinador)

tests/Feature/Edicion/                      # NUEVO (una prueba Feature por módulo + auditoría/autorización)
```

**Structure Decision**: Monolito Laravel existente. Se introduce un **mecanismo transversal** (modelo `CambioAuditoria`, `AuditoriaService`, trait `Auditable`, tabla `cambios_auditoria`, middleware de rol) y, por módulo, acciones `edit`/`update` + `Update*Request` + vista `editar`. Los controladores quedan delgados delegando validación a Form Requests y auditoría al servicio. No se elimina ni se recalcula inventario.

## Complexity Tracking

> Sin violaciones de la constitución. No aplica.
