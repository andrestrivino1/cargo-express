# Data Model: Ajuste de requerimientos operativos

**Feature**: 005-ajuste-requerimientos-operativos
**Date**: 2026-06-25
**Phase**: 1 — Design

Convención: se **reutiliza** el esquema existente y se agregan columnas/tablas mínimas. Las columnas nuevas son **nullable** o con default para no romper datos históricos. Nada se elimina.

---

## Tablas nuevas

### `movimientos_inventario` (ledger — fuente única de trazabilidad)

| Columna | Tipo | Notas |
|---|---|---|
| `id` | bigint PK | |
| `referencia_id` | FK → referencias | indexado |
| `tipo` | enum (`entrada`,`salida`) | `MovimientoTipo` |
| `cantidad` | unsigned int | > 0 |
| `saldo_resultante` | unsigned int | saldo de la referencia tras el movimiento |
| `usuario_id` | FK → users | responsable de la operación |
| `documentable_type` | string nullable | polimórfico: ingreso (Contenedor) o salida (Tarja) |
| `documentable_id` | bigint nullable | |
| `observaciones` | text nullable | |
| `created_at` | timestamp | fecha/hora del movimiento (FR-012/FR-019) |

- Índices: (`referencia_id`,`created_at`), (`tipo`,`created_at`), (`usuario_id`).
- Sin `updated_at` (registro inmutable, como `cambios_auditoria`).

**Reglas**: el `saldo_resultante` de un movimiento DEBE igualar el `cantidad_actual` de la referencia después de aplicarlo. La suma de entradas − salidas por referencia DEBE igualar `cantidad_actual` (invariante verificable, SC-002).

### `secuencias` (contador de consecutivos)

| Columna | Tipo | Notas |
|---|---|---|
| `id` | bigint PK | |
| `clave` | string unique | p. ej. `odc` |
| `valor` | unsigned int | último consecutivo emitido |
| timestamps | | |

- Semilla: `('odc', 570)` para continuar desde ODC-570.
- Acceso siempre vía `ConsecutivoService::siguiente('odc')` con `lockForUpdate()`.

---

## Tablas modificadas (columnas nuevas)

### `contenedores` (+ ingreso)
| Columna nueva | Tipo | Notas |
|---|---|---|
| `bl` | string(100) nullable | BL del ingreso (FR-001) |
| `tipo_mercancia` | string(100) nullable | Tipo de mercancía (FR-001/FR-004) |

- Se añade trait `HasPhotos` al modelo `Contenedor` para adjuntar BL/DIM/Lista de empaque (`photos` con `categoria`).

### `referencias` (+ peso)
| Columna nueva | Tipo | Notas |
|---|---|---|
| `peso` | decimal(10,2) nullable | Peso de la mercancía ingresada (FR-001/FR-004) |

### `tarjas` (+ salida / ODC)
| Columna nueva | Tipo | Notas |
|---|---|---|
| `conductor_cedula` | string(20) nullable | Cédula del conductor (ODC) |
| `transportador` | string(150) nullable | Empresa transportadora (ODC) |
| `destino` | string(150) nullable | Destino de la carga (ODC) |
| `consecutivo_odc` | unsigned int nullable, unique | Número ODC asignado; único, no reutilizable (FR-013) |
| `observaciones` | (ya existe) | Observaciones/novedades de la salida (FR-009) |

- Se añade trait `HasPhotos` al modelo `Tarja` para `foto_mercancia` y `foto_conductor` (FR-008).

### `users` (+ NIT cliente)
| Columna nueva | Tipo | Notas |
|---|---|---|
| `nit` | string(30) nullable | NIT del cliente, mostrado en el ODC (FR-014) |

### `photos` (+ categoría)
| Columna nueva | Tipo | Notas |
|---|---|---|
| `categoria` | string(30) nullable | `DocumentoCategoria`: `bl`,`dim`,`lista_empaque`,`foto_mercancia`,`foto_conductor` |

---

## Enums nuevos

- **`MovimientoTipo`**: `entrada`, `salida`.
- **`DocumentoCategoria`**: `bl`, `dim`, `lista_empaque`, `foto_mercancia`, `foto_conductor`.

---

## Entidades del dominio (mapa lógico → spec)

| Entidad (spec) | Implementación |
|---|---|
| Ingreso de mercancía | `Contenedor` (+bl, +tipo_mercancia) + sus `Referencia` + movimiento `entrada` + `Photo`(bl/dim/lista_empaque) |
| Documento soporte | `Photo` con `categoria` ∈ {bl, dim, lista_empaque} sobre `Contenedor` |
| Referencia / Inventario | `Referencia` (`cantidad_actual` = saldo, `unidad_medida`, `peso`, `ubicacion_patio_id`, `cliente_id`) |
| Vaciado / Recepción | `OrdenVaciado` (+ `Photo` fotos) — existente, sin cambios |
| Novedad | `Novedad` — existente |
| Salida de mercancía | `OrdenCargue` + `Tarja` (+ cédula/transportador/destino/consecutivo_odc) + `TarjaDetalle` + movimiento `salida` + `Photo`(foto_mercancia/foto_conductor) |
| Orden de Salida (ODC) | Documento PDF `pdf/orden-salida.blade.php` generado desde `Tarja` + cliente (NIT) + detalles + fotos |
| Evidencia fotográfica | `Photo` (foto) en `OrdenVaciado`, `Tarja`, `GateEvent` |
| Movimiento / Trazabilidad | `MovimientoInventario` (ledger) + `CambioAuditoria` (auditoría) + `TrazabilidadService` |

---

## Validaciones (de requisitos)

- **Ingreso (FR-001)**: BL, contenedor, cliente, ubicación, tipo de mercancía, referencia, descripción, unidad de medida, peso, cantidad — todos requeridos. Cantidad > 0.
- **Documentos ingreso (FR-002)**: BL, DIM, Lista de empaque — PDF/imagen; tamaño máx. configurable.
- **Salida (FR-007)**: cliente, referencia, cantidad por despachar, fecha de salida, orden de salida (se materializa al generar ODC) — requeridos. Cantidad > 0.
- **Salida saldo (FR-011)**: `cantidad <= referencia.cantidad_actual`; si no, rechazar e informar saldo. Nunca negativo.
- **Evidencias salida (FR-008)**: `foto_mercancia` y `foto_conductor` — ambas requeridas (imágenes).
- **Consecutivo (FR-013)**: `consecutivo_odc` único; asignado por `ConsecutivoService`; no se reutiliza ante anulación.

---

## Transiciones de estado (sin cambios de esquema)

- `Referencia.cantidad_actual`: aumenta con **entrada** (ingreso), disminuye con **salida** (despacho). Cada cambio genera un registro en `movimientos_inventario`.
- `OrdenCargue.estado`: `pendiente → completada` al confirmar la salida y generar el ODC (patrón existente en `EntregaService`).
- Anulación de salida (edge case): el `consecutivo_odc` queda registrado y **no** se reutiliza; un movimiento de reverso/entrada compensa el inventario si aplica (regla operativa; fuera del MVP salvo que se requiera).
