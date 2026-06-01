# Contrato: Edición por módulo (rutas edit/update)

**Feature**: 004-admin-edit-records
**Date**: 2026-06-01

Todas las rutas `edit`/`update` se protegen con middleware `role:administrador|coordinador`. La acción `update` valida con el `Update*Request` del módulo (mismas reglas que la creación para los campos editables), persiste solo campos correctivos y registra auditoría vía `AuditoriaService`.

## Comportamiento común por módulo

| # | Caso | Resultado esperado |
|---|---|---|
| C1 | GET `edit` como administrador/coordinador | 200, formulario con los valores actuales de los campos editables |
| C2 | PUT `update` con datos válidos | Redirect a `show`/`index` con éxito; campos correctivos actualizados; 1 entrada de auditoría |
| C3 | PUT `update` con campo obligatorio vacío o formato inválido | Error de validación; valor anterior conservado; sin auditoría |
| C4 | GET/PUT como usuario sin rol administrador ni coordinador | 403 (bloqueado) |
| C5 | Editar registro en estado terminal/cerrado | Permitido (FR-005); estado no cambia (FR-010) |
| C6 | Editar un registro con inventario derivado | Cantidades/movimientos derivados sin cambios (FR-004) |

## Rutas por módulo

| Módulo | edit (GET) | update (PUT) | Registro |
|---|---|---|---|
| Solicitudes | `solicitudes/{solicitud}/editar` | `solicitudes/{solicitud}` | `Solicitud` |
| Ingresos (gate-in) | `gate-in/{gateEvent}/editar` | `gate-in/{gateEvent}` | `GateEvent` (ingreso) |
| Vaciado | `vaciado/{ordenVaciado}/editar` | `vaciado/{ordenVaciado}` | `OrdenVaciado` |
| Salidas (gate-out) | `gate-out/{gateEvent}/editar` | `gate-out/{gateEvent}` | `GateEvent` (salida) |
| Almacenamiento | `inventario/{referencia}/editar` | `inventario/{referencia}` | `Referencia` |
| Transferencias | `transferencias/{transferencia}/editar` | `transferencias/{transferencia}` | `Transferencia` |
| Entregas | `entregas/{ordenCargue}/editar` | `entregas/{ordenCargue}` | `OrdenCargue` |

> Nombres de ruta sugeridos: `<modulo>.editar` (GET) y `<modulo>.update` (PUT). Los nombres exactos se ajustan en implementación según el grupo de rutas existente.

## Campos editables aceptados por `update` (resto se ignora)

- **Solicitudes**: cliente_id, numero_contenedor, naviera, puerto_origen, descripcion, fecha_solicitud
- **Ingresos**: hora, estado_fisico, notas
- **Vaciado**: fecha_programada, supervisor_id, notas
- **Salidas**: hora, estado_fisico, notas
- **Almacenamiento**: ubicacion_patio_id, codigo, descripcion, unidad_medida, fecha_ingreso
- **Transferencias**: motivo, autorizacion_cliente
- **Entregas**: cliente_id, fecha_despacho, notas

Cualquier campo fuera de esta lista no se persiste aunque venga en la petición (protección contra edición de campos estructurales/derivados, FR-003/FR-004).
