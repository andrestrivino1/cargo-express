# Contrato: Validación de fechas en flujos de registro operativo

**Feature**: 003-port-appointment-past-dates
**Date**: 2026-06-01

Contrato de comportamiento (UI/endpoint) para los tres flujos de registro afectados. En todos, la entrada de fecha pasa de "solo futuro" a "cualquier fecha válida", conservando obligatoriedad, formato y validaciones de negocio.

## Comportamiento esperado común por campo de fecha

| # | Caso | Entrada de fecha | Resultado esperado |
|---|---|---|---|
| C1 | Fecha pasada válida | anterior a hoy/ahora | **OK** — registro creado; fecha persistida con el valor ingresado |
| C2 | Fecha futura válida | posterior a hoy/ahora | **OK** — registro creado (comportamiento preexistente preservado) |
| C3 | Campo vacío | `""` / ausente | **Error de validación** — mensaje de campo obligatorio |
| C4 | Formato inválido | `"no-es-fecha"` | **Error de validación** — mensaje de fecha inválida |

---

## Flujo 1 — Cita en puerto (Orden de Servicio)

- **Endpoint**: `POST /solicitudes/{solicitud}/ordenes-servicio` (`OrdenServicioController@store`, `StoreOrdenServicioRequest`)
- **Campo**: `cita_puerto` (datetime-local)
- **Postcondiciones (éxito)**: crea `OrdenServicio`, crea `Contenedor` en estado `Solicitado`, `Solicitud` pasa a `Asignada`, redirige a `solicitudes.show`.
- **Cambio**: antes C1 fallaba ("debe ser posterior a ahora"); ahora C1 tiene éxito. C2/C3/C4 sin cambios.

## Flujo 2 — Fecha de despacho (Orden de Cargue)

- **Endpoint**: creación de orden de cargue (`EntregaController`, `StoreOrdenCargueRequest`)
- **Campo**: `fecha_despacho` (date)
- **Validaciones de negocio conservadas**: `cliente_id` debe existir.
- **Cambio**: antes C1 fallaba ("debe ser posterior a hoy"); ahora C1 tiene éxito. C2/C3/C4 sin cambios.

## Flujo 3 — Fecha programada (Orden de Vaciado)

- **Endpoint**: creación de orden de vaciado (`VaciadoController`, `StoreOrdenVaciadoRequest`)
- **Campo**: `fecha_programada` (date) — **además** se elimina `min` del control en `vaciado/create.blade.php` para que el formulario permita seleccionar fechas pasadas.
- **Validaciones de negocio conservadas**: `contenedor_id` debe existir; el contenedor **debe estar "En Patio"** (si no, error en `contenedor_id`).
- **Cambio**: antes C1 fallaba ("debe ser posterior a hoy") tanto en el navegador (por `min`) como en servidor; ahora C1 tiene éxito en ambos. C2/C3/C4 sin cambios.

## Fuera de contrato (sin cambios)

- `fecha_solicitud` (ya admite cualquier fecha).
- `fecha_corte` de importación (ya admite cualquier fecha).
- Filtros de reporte `fecha_desde`/`fecha_hasta`: se conserva la regla `fecha_hasta ≥ fecha_desde`.
