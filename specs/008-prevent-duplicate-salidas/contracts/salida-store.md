# Contrato: Registro de salida idempotente

**Feature**: 008-prevent-duplicate-salidas

Este proyecto es una aplicación web Laravel + Blade. El "contrato" es el comportamiento observable del endpoint de creación de salida y del formulario.

## Formulario — `GET /salida/crear` (`salida.create`)

- La respuesta DEBE incluir un campo oculto `idempotency_key` con un UUID v4 generado en el servidor al renderizar.
- Ante un re-render por error de validación, el campo DEBE conservar el mismo `idempotency_key` del envío anterior (`old()`), no uno nuevo.

```html
<input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', $idempotencyKey) }}">
```

## Endpoint — `POST /salida` (`salida.store`)

### Entrada (campos relevantes al contrato)

| Campo | Regla | Notas |
|---|---|---|
| `idempotency_key` | `required`, `uuid` | Identifica el intento. Añadido a las reglas ya existentes. |
| `cliente_id`, `fecha_salida`, `detalles[]`, `conductor`, `placa_vehiculo`, `transportador`, `destino`, `foto_mercancia`, `foto_conductor`, ... | (reglas existentes sin cambios) | |

### Comportamiento

| # | Precondición | Acción | Resultado esperado |
|---|---|---|---|
| C1 | `idempotency_key` nuevo, payload válido, saldo suficiente | Primer POST | 302 → `salida.show` de la **nueva** tarja. Se crean 1 tarja, 1 orden_cargue, 1 consecutivo ODC, N movimientos de inventario; se inserta 1 fila en `idempotency_keys` con `resource_id` = tarja.id. |
| C2 | Mismo `idempotency_key` que C1 ya procesado | POST repetido | 302 → `salida.show` de la **misma** tarja de C1. **No** se crea otra salida, **no** avanza el consecutivo, **no** se descuenta inventario de nuevo. Mensaje informativo "el despacho ya estaba registrado". |
| C3 | Dos `idempotency_key` **distintos**, mismo cliente y datos | Dos POST | Se crean **dos** salidas independientes (no se bloquean). |
| C4 | `idempotency_key` ausente o con formato inválido | POST | 302 back con error de validación; **no** se crea nada. |
| C5 | Saldo insuficiente en alguna referencia | POST | 422/back con `ValidationException` existente; **no** se crea salida **ni** persiste la fila del token (transacción revertida) → el token queda libre para reintentar. |
| C6 | Dos POST concurrentes con el **mismo** `idempotency_key` | Casi simultáneos | Exactamente **una** salida creada; la otra petición se resuelve como reenvío (redirige a la misma ODC) o con reintento controlado. El inventario se descuenta una sola vez; nunca queda negativo. |

### Errores

- `SalidaDuplicadaException` (interna): lanzada por el servicio cuando el token ya fue reservado; capturada por el controlador → redirección informativa (C2). Nunca se muestra como error 500 al usuario.
- Falla a mitad del proceso (p. ej. guardar foto) → la transacción revierte todo, incluida la fila del token (C5-like); no queda salida parcial ni consecutivo consumido.

## Trazabilidad de requisitos

| Contrato | FR / SC cubierto |
|---|---|
| C1 | FR-001, FR-007 |
| C2 | FR-001, FR-004, FR-009 · SC-001, SC-003 |
| C3 | FR-005 · SC-004 |
| C4 | FR-010 |
| C5 | FR-006, FR-007 · SC-005 |
| C6 | FR-001, FR-003, FR-006 · SC-005 |
