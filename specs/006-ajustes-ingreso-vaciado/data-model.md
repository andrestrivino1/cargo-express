# Data Model: Ajustes a ingreso y vaciado

**Feature**: 006-ajustes-ingreso-vaciado
**Date**: 2026-06-25
**Phase**: 1 — Design

Se reutiliza el esquema de la feature 005. Cambios mínimos y aditivos (nada se elimina).

---

## Tabla nueva

### `ingresos` (padre — agrupa un BL)

| Columna | Tipo | Notas |
|---|---|---|
| `id` | bigint PK | |
| `bl` | string(100) | BL del ingreso (obligatorio) |
| `cliente_id` | FK → users | cliente del ingreso |
| `fecha_ingreso` | date | fecha real de llegada (puede ser anterior a hoy) |
| `usuario_id` | FK → users | responsable que registró |
| timestamps | | `created_at` = marca de creación (auditoría), independiente de `fecha_ingreso` |

- Índices: `cliente_id`, `fecha_ingreso`, `bl`.
- Trait `HasPhotos`: documentos BL/DIM/Lista de empaque se adjuntan **al ingreso** (`photos` con `categoria` ∈ {bl, dim, lista_empaque}).
- Relación: `hasMany(Contenedor)`.

---

## Tabla modificada

### `contenedores` (+ vínculo al ingreso)
| Columna nueva | Tipo | Notas |
|---|---|---|
| `ingreso_id` | FK → ingresos, **nullable** | Vincula el contenedor a su ingreso/BL. Null = contenedor histórico o ingreso de un solo contenedor (feature 005). |

- Columnas existentes que se siguen usando: `numero`, `tipo_mercancia`, `bl` (queda para compatibilidad; en el flujo nuevo el BL vive en `ingresos`), `fecha_ingreso` (se setea con la fecha del ingreso).

### `referencias` (sin cambios de esquema)
- `ubicacion_patio_id` **ya es nullable** → ubicación opcional (FR-002a).
- `fecha_ingreso` se setea con la fecha del ingreso (retroactiva).

---

## Entidades del dominio (mapa lógico → spec)

| Entidad (spec) | Implementación |
|---|---|
| Ingreso (por BL) | `Ingreso` (bl, cliente, fecha_ingreso, usuario) + `Photo`(bl/dim/lista_empaque) |
| Contenedor | `Contenedor` (+ingreso_id, numero, tipo_mercancia, fecha_ingreso) |
| Referencia / Inventario | `Referencia` (codigo, descripcion, unidad_medida, peso, cantidad, ubicacion_patio_id **opcional**, fecha_ingreso) + movimiento `entrada` |
| Documento soporte | `Photo` con `categoria` sobre `Ingreso` |
| Vaciado / Recepción | `OrdenVaciado` + `Photo` (varias, agregables luego) |

---

## Validaciones (de requisitos)

- **Ingreso (FR-001/FR-005)**: `bl`, `cliente_id`, `fecha_ingreso` requeridos; `contenedores` ≥ 1; cada contenedor con `referencias` ≥ 1.
- **Contenedor (FR-002/FR-006)**: `numero` y `tipo_mercancia` requeridos; números de contenedor **únicos dentro del mismo ingreso**.
- **Referencia (FR-002)**: `codigo`, `descripcion`, `unidad_medida`, `peso`, `cantidad` (≥1) requeridos; `ubicacion_patio_id` **opcional** (FR-002a).
- **Referencia repetida (FR-003)**: el mismo `codigo` puede existir en distintos contenedores del mismo ingreso, con cantidades independientes.
- **Fecha (FR-008/FR-011)**: `fecha_ingreso ≤ hoy`; se rechaza fecha futura.
- **Documentos (FR-004)**: BL/DIM/Lista de empaque a nivel del ingreso (PDF/imagen, ≤10MB), como en feature 005.
- **Fotos de vaciado (FR-012/FR-013)**: `fotos` array, `fotos.*` imagen ≤5MB; agregables a un vaciado existente sin reemplazar las previas.

---

## Reglas / transiciones

- Al guardar un ingreso: crea `Ingreso` → por cada contenedor crea `Contenedor(ingreso_id)` con `fecha_ingreso` del ingreso → por cada referencia crea `Referencia(fecha_ingreso, ubicacion opcional)` y registra movimiento `entrada` en el ledger.
- `referencia.fecha_ingreso` (y no `movimiento.created_at`) es la fecha usada por el **reporte de ingresos** y por los días de almacenamiento (FR-009).
- Referencia sin ubicación: `ubicacion_patio_id = NULL` → aparece "sin ubicar" en inventario y puede ubicarse con `InventarioService::asignarUbicacion`.
- Compatibilidad: contenedores con `ingreso_id = NULL` siguen válidos; backfill opcional crea un `Ingreso` por cada contenedor-con-bl legado.
