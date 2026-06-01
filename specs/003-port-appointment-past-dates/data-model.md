# Data Model: Permitir fechas anteriores en los campos de fecha de registro operativo

**Feature**: 003-port-appointment-past-dates
**Date**: 2026-06-01

## Resumen

Esta feature **no introduce ni modifica el esquema de base de datos**. Solo relaja reglas de validación de entrada (y un atributo de control de fecha en una vista). Se documentan las entidades afectadas para contexto.

## Entidad: Orden de Servicio (`ordenes_servicio`)

| Campo afectado | Tipo | Reglas (después) | Antes | Notas |
|---|---|---|---|---|
| `cita_puerto` | datetime | `required, date` | `required, date, after:now` | Admite fechas pasadas/presentes/futuras |

## Entidad: Orden de Cargue (`ordenes_cargue`)

| Campo afectado | Tipo | Reglas (después) | Antes | Notas |
|---|---|---|---|---|
| `fecha_despacho` | date | `required, date` | `required, date, after:today` | Admite fechas pasadas/presentes/futuras |

## Entidad: Orden de Vaciado (`ordenes_vaciado`)

| Campo afectado | Tipo | Reglas (después) | Antes | Notas |
|---|---|---|---|---|
| `fecha_programada` | date | `required, date` | `required, date, after:today` | Admite fechas pasadas/presentes/futuras. UI: se elimina `min` del control |

## Regla de validación afectada (común a los tres campos)

- **Antes**: la fecha debía ser **posterior al momento/día actual**.
- **Después**: la fecha debe ser **válida** y **estar presente** (obligatoria); admite valores pasados, presentes o futuros.

## Invariantes que se mantienen

- Los tres campos siguen siendo **obligatorios** (FR-004).
- Los tres campos siguen exigiendo **formato de fecha válido** (FR-005).
- El valor se persiste **tal cual se ingresa**, sin normalización (FR-007).
- **Validaciones de negocio no temporales** intactas (FR-009):
  - Orden de Vaciado: el contenedor debe estar en estado **"En Patio"** (`withValidator`).
  - Orden de Cargue: `cliente_id` debe existir.
  - Orden de Vaciado: `contenedor_id` debe existir.
  - Reportes: `fecha_hasta` ≥ `fecha_desde` (rango lógico conservado).

## Transiciones de estado

No aplica. Esta feature no altera estados de `Solicitud`, `Contenedor`, `OrdenServicio`, `OrdenCargue` ni `OrdenVaciado`; los flujos de creación y sus transiciones permanecen idénticos.
