# Phase 1 — Data Model: Editar ingreso con referencias e imágenes del BL

**Cambios de esquema: NINGUNO.** Esta feature reutiliza tablas y relaciones existentes. Este documento describe las entidades involucradas, sus campos relevantes y las reglas de validación que aplica la feature.

## Entidades y relaciones (existentes)

```
Ingreso (ingresos)
 ├─ photos (polimórfica, photoable)         → galería de imágenes del BL  [HasPhotos]
 └─ contenedores (hasMany)
      └─ referencias (hasMany)
           ├─ producto (belongsTo)
           └─ ubicacionPatio (belongsTo)
```

### Ingreso (`ingresos`)
Registro de entrada por BL.

| Campo | Tipo | Notas para la feature |
|---|---|---|
| `id` | bigint PK | — |
| `bl` | string(100) | Editable. Provisional cuando viene de importación. |
| `bl_por_confirmar` | boolean | Se baja a `false` al guardar el BL real (comportamiento actual). |
| `cliente_id` | FK users | Editable (select). |
| `fecha_ingreso` | date | Editable (`before_or_equal:today`). |
| `usuario_id` | FK users | No editable aquí. |

Relaciones usadas: `contenedores()` (hasMany), `fotos()`/`documentos()`/`photos()` (morphMany vía `HasPhotos`).

### Contenedor (`contenedores`)
Agrupa referencias dentro del ingreso. Un BL/ingreso puede tener uno o varios.

| Campo | Tipo | Notas |
|---|---|---|
| `id` | bigint PK | — |
| `ingreso_id` | FK ingresos (nullable) | Vincula al BL. |
| `numero` | string(20) | Mostrado para identificar el contenedor de cada referencia. |
| `bl` | string | Copia del BL. |

Relación usada: `referencias()` (hasMany). Selector destino al **agregar** una referencia nueva.

### Referencia (`referencias`)
Unidad de mercancía del BL. Solo lectura en la lista; creable (nueva) durante la edición.

| Campo | Tipo | Rol en la feature |
|---|---|---|
| `id` | bigint PK | — |
| `contenedor_id` | FK contenedores | Obligatorio al crear (destino). |
| `cliente_id` | FK users | Se hereda del ingreso al crear. |
| `codigo` | string(100) | Mostrado / requerido al crear. |
| `descripcion` | string(255) | Mostrado / requerido al crear. |
| `cantidad_inicial` | int | Se setea = cantidad al crear. |
| `cantidad_actual` | int | Se setea = cantidad al crear. |
| `unidad_medida` | string(50) | Requerido al crear. |
| `peso` | decimal(.,2) | Opcional. |
| `ubicacion_patio_id` | FK ubicaciones_patio (nullable) | Opcional. |
| `fecha_ingreso` | datetime | Se hereda de `ingreso.fecha_ingreso` al crear. |

Efecto colateral al crear: se registra un **MovimientoInventario** de entrada (vía `MovimientoInventarioService::registrarEntrada`), igual que en el alta de ingreso.

### Photo (`photos`)
Archivo polimórfico. La feature crea registros con `tipo = 'foto'`.

| Campo | Tipo | Valor al subir imagen del BL |
|---|---|---|
| `photoable_type` / `photoable_id` | morph | → `Ingreso` correspondiente |
| `ruta` | string | `ingresos/{ingreso_id}/...` (disk `public`) |
| `nombre` | string | nombre original del archivo |
| `tipo` | string | `'foto'` |
| `categoria` | string nullable | `null` |
| `mime_type` | string | del archivo |
| `tamaño` | int | bytes |

## Reglas de validación (aplicadas por `UpdateIngresoRequest`)

Campos actuales (se conservan):
- `bl`: `required|string|max:100`
- `cliente_id`: `required|exists:users,id`
- `fecha_ingreso`: `required|date|before_or_equal:today`

Campos nuevos:
- `fotos`: `nullable|array`
- `fotos.*`: `image|mimes:jpg,jpeg,png,webp|max:5120`
- Referencia nueva (opcional — solo se procesa si el operador la diligencia; presencia condicionada a `nueva_referencia.codigo` no vacío):
  - `nueva_referencia.contenedor_id`: `nullable|required_with:nueva_referencia.codigo|exists:contenedores,id` (y debe pertenecer al ingreso — validado en `withValidator`/servicio)
  - `nueva_referencia.codigo`: `nullable|string|max:100`
  - `nueva_referencia.descripcion`: `nullable|required_with:nueva_referencia.codigo|string|max:255`
  - `nueva_referencia.unidad_medida`: `nullable|required_with:nueva_referencia.codigo|string|max:50`
  - `nueva_referencia.cantidad`: `nullable|required_with:nueva_referencia.codigo|integer|min:1`
  - `nueva_referencia.peso`: `nullable|numeric|min:0`
  - `nueva_referencia.ubicacion_patio_id`: `nullable|exists:ubicaciones_patio,id`

> Nota: el contenedor destino de la referencia nueva debe pertenecer al ingreso en edición (validación de integridad en `withValidator` o en el servicio antes de crear).

## Transiciones de estado

- **BL provisional → confirmado**: al guardar con un `bl` válido, `bl_por_confirmar` pasa de `true` a `false`. La confirmación ocurre aunque en el mismo submit se agreguen imágenes y/o una referencia.
- **Sin transición** para imágenes/referencias: son adiciones; no cambian el estado del ingreso ni eliminan datos previos.

## Invariantes / reglas de negocio

1. Una referencia nueva siempre se asocia a un contenedor que pertenece al ingreso en edición.
2. Crear una referencia genera su movimiento de inventario de entrada (consistencia con el alta normal).
3. Las imágenes se acumulan; ninguna operación de esta feature borra fotos o referencias existentes (FR-013).
4. `cliente_id` y `fecha_ingreso` de una referencia nueva se heredan del ingreso (no se piden por separado).
