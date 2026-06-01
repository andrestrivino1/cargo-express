# Contrato: Mecanismo transversal de auditoría

**Feature**: 004-admin-edit-records
**Date**: 2026-06-01

## AuditoriaService

```
AuditoriaService::registrarCambios(Model $modelo, User $usuario): ?CambioAuditoria
```

- **Entrada**: un modelo Eloquent con cambios pendientes (atributos `dirty`) y el usuario autenticado.
- **Comportamiento**:
  - Calcula el diff `{campo: {anterior, nuevo}}` a partir de los atributos modificados (solo campos editables del módulo).
  - Si **no hay cambios**, devuelve `null` y **no** inserta (FR-011).
  - Si hay cambios, crea una fila en `cambios_auditoria` con `auditable_*`, `usuario_id`, `cambios`, `created_at`.
- **Salida**: la entrada creada, o `null` si no hubo cambios.

## Trait Auditable

- Provee `cambiosAuditoria(): MorphMany` para listar el historial de un registro (orden descendente por fecha).
- Lo usan: `Solicitud`, `GateEvent`, `OrdenVaciado`, `Referencia`, `Transferencia`, `OrdenCargue`.

## Comportamiento esperado (casos)

| # | Caso | Resultado |
|---|---|---|
| A1 | Editar 2 campos | 1 entrada de auditoría con 2 campos en `cambios` (anterior/nuevo correctos) |
| A2 | Guardar sin cambios | 0 entradas creadas |
| A3 | Consultar historial de un registro | Lista de entradas con usuario, fecha y diff, más recientes primero |
| A4 | Entrada de auditoría | Inmutable (no se edita ni borra desde la feature) |

## Historial (vista)

- Parcial `components/historial-auditoria.blade.php` recibe un registro auditable y muestra sus entradas (usuario, fecha, campos cambiados con valor anterior → nuevo).
- Visible solo para administrador/coordinador.
