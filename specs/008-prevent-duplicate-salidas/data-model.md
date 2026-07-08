# Data Model: Idempotencia de salidas

**Feature**: 008-prevent-duplicate-salidas
**Fecha**: 2026-07-08

## Entidad nueva: `idempotency_keys`

Representa un **intento** de operación de un solo uso. Su índice UNIQUE sobre `token` es la barrera atómica que impide procesar dos veces el mismo intento.

| Columna | Tipo | Nulo | Notas |
|---|---|---|---|
| `id` | bigint unsigned, PK, auto-inc | No | |
| `token` | char(36) | No | UUID v4. **UNIQUE**. Identifica el intento. |
| `scope` | varchar(40) | No | Ámbito de la operación. Valor inicial: `salida`. Permite reutilizar la tabla en otros módulos (p. ej. `ingreso`) sin colisiones. |
| `usuario_id` | bigint unsigned, FK → `users.id` | No | Quién originó el intento (auditoría). |
| `resource_id` | bigint unsigned | Sí | Id del recurso creado (para `salida` = `tarjas.id`). Se completa al finalizar la creación; queda nulo si la creación se revirtió. |
| `created_at` | timestamp | No | `useCurrent()`. Indexado para purga futura. |

### Índices y restricciones

- **UNIQUE** (`token`) — barrera de idempotencia (obligatorio).
- Índice (`scope`, `resource_id`) — para localizar el recurso en el reenvío.
- Índice (`created_at`) — para purga periódica futura (opcional pero barato).
- FK `usuario_id` → `users(id)`.

### Reglas de negocio

- **RN-1**: Un `token` solo puede insertarse una vez (UNIQUE). El segundo intento de insertar el mismo `token` es un reenvío.
- **RN-2**: `resource_id` se asocia dentro de la misma transacción que crea la salida. Si la transacción se revierte, la fila del token **no** persiste (nunca se creó el registro), dejando el token libre para un reintento legítimo.
- **RN-3**: La combinación (`token`) es global; el `scope` es informativo/organizativo y para el lookup del recurso, no relaja la unicidad del token.

### Ciclo de vida

```
[Formulario cargado]
      │  genera token UUID (aún NO existe fila)
      ▼
[POST store]
      │  INSERT token  ──► ¿UNIQUE viola?
      │                        │ sí (reenvío)
      │                        ▼
      │                 buscar resource_id existente → redirigir a la ODC
      │ no (primer intento)
      ▼
[crear tarja + orden + consecutivo + descuento inventario + fotos]
      │
      ▼
[UPDATE token.resource_id = tarja.id]  ── commit ──► redirigir a la nueva ODC
```

## Entidades existentes (sin cambios de esquema)

Estas entidades participan pero **no** se modifican estructuralmente; solo se garantiza que se creen una sola vez por intento:

- **Tarja** (`tarjas`): la salida. Ya tiene `consecutivo_odc` UNIQUE.
- **OrdenCargue** (`ordenes_cargue`): una por salida.
- **TarjaDetalle** (`tarja_detalles`): renglones despachados.
- **MovimientoInventario** (`movimientos_inventario`): descuento de saldo (uno por referencia por salida).
- **Referencia** (`referencias`): su `cantidad_actual` se descuenta una sola vez por intento.
- **Secuencia** (`secuencias`, clave `odc`): el consecutivo avanza una sola vez por intento (solo en el camino de éxito).

## Modelo Eloquent nuevo

- `App\Models\IdempotencyKey` — `$fillable = ['token', 'scope', 'usuario_id', 'resource_id']`; `$timestamps` deshabilitado salvo `created_at` (o `CREATED_AT` sin `updated_at`, coherente con `movimientos_inventario`).
