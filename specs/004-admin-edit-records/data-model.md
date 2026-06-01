# Data Model: Edición de registros operativos por administrador/coordinador

**Feature**: 004-admin-edit-records
**Date**: 2026-06-01

## Resumen

Se agrega **1 entidad nueva** (`CambioAuditoria`) y **no se altera** el esquema de los módulos existentes. La edición opera sobre los modelos actuales modificando solo campos correctivos.

## Entidad nueva: CambioAuditoria (tabla `cambios_auditoria`)

Registra cada modificación correctiva sobre un registro operativo.

| Campo | Tipo | Notas |
|---|---|---|
| `id` | bigint (PK) | — |
| `auditable_type` | string | Clase del modelo editado (morph) |
| `auditable_id` | bigint | Id del registro editado (morph) |
| `usuario_id` | bigint (FK users) | Quién hizo el cambio |
| `cambios` | json | `{ "campo": {"anterior": x, "nuevo": y}, ... }` |
| `created_at` | timestamp | Cuándo se hizo el cambio |

- Índice por (`auditable_type`, `auditable_id`) para listar el historial de un registro.
- Sin `updated_at` (las entradas de auditoría son inmutables).
- Relación: `morphTo` a su auditable; `belongsTo` a `User` (usuario).

### Reglas / invariantes

- Se crea una entrada **solo si hay al menos un campo modificado** (FR-011).
- `cambios` contiene únicamente los campos editados (no el modelo completo).
- Las entradas no se editan ni se borran desde la feature (historial inmutable).

## Modelos existentes afectados (sin cambio de esquema)

Cada uno adopta el trait `Auditable` (expone `cambiosAuditoria(): morphMany`). Campos editables y excluidos:

### Solicitud (`solicitudes`)
- **Editables**: cliente_id, numero_contenedor, naviera, puerto_origen, descripcion, fecha_solicitud
- **Excluidos**: estado, import_batch_id, id, timestamps

### GateEvent — Ingreso (`gate_events`, tipo ingreso)
- **Editables**: hora, estado_fisico, notas
- **Excluidos**: contenedor_id, tipo, usuario_id

### OrdenVaciado (`ordenes_vaciado`)
- **Editables**: fecha_programada, supervisor_id, notas
- **Excluidos**: contenedor_id, fecha_inicio, fecha_fin, estado

### GateEvent — Salida (`gate_events`, tipo salida)
- **Editables**: hora, estado_fisico, notas
- **Excluidos**: contenedor_id, tipo, usuario_id

### Referencia — Almacenamiento (`referencias`)
- **Editables**: ubicacion_patio_id, codigo, descripcion, unidad_medida, fecha_ingreso
- **Excluidos**: cantidad_inicial, cantidad_actual, contenedor_id, cliente_id, producto_id

### Transferencia (`transferencias`)
- **Editables**: motivo, autorizacion_cliente
- **Excluidos**: cantidad, referencia_origen_id, referencia_destino_id, ubicacion_origen_id, ubicacion_destino_id, cliente_origen_id, cliente_destino_id, tipo

### OrdenCargue — Entrega (`ordenes_cargue`)
- **Editables**: cliente_id, fecha_despacho, notas
- **Excluidos**: estado, despachador_id, import_batch_id (tarjas: relación derivada, intacta)

## Validación (reusada de creación, FR-006)

- Obligatoriedad y formato por campo editable, tomados del `Store*Request` del módulo cuando existe.
- Las relaciones (cliente_id, supervisor_id, ubicacion_patio_id) validan `exists` contra su tabla.

## Transiciones de estado

No aplica como flujo nuevo. La edición **no cambia el estado** del registro por el hecho de editar otros campos (FR-010). El estado solo cambia si se editara explícitamente un campo de estado habilitado (fuera del conjunto editable definido aquí, por lo que en esta feature el estado permanece intacto).
