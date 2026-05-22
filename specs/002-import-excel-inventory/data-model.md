# Data Model — Importación de Inventario Histórico desde Excel

**Date**: 2026-05-21
**Plan**: [plan.md](./plan.md)
**Storage**: MySQL 8 (mismo que feature 001)
**Base de partida**: [specs/001-cargo-traceability-system/data-model.md](../001-cargo-traceability-system/data-model.md)

Este documento describe **solo las tablas nuevas y las columnas añadidas** por esta feature. Las entidades existentes (Solicitud, OrdenServicio, Contenedor, Referencia, OrdenCargue, Tarja, TarjaDetalle, UbicacionPatio, User) se reutilizan tal cual; sus campos preexistentes no se redocumentan aquí.

---

## ER Overview (delta)

```text
ImportBatch (1)──(N) ImportRowResult
ImportBatch (1)──(N) ImportPendingRecord
ImportBatch (1)──(N) Solicitud           (FK nullable: import_batch_id)
ImportBatch (1)──(N) OrdenServicio       (FK nullable: import_batch_id)
ImportBatch (1)──(N) Contenedor          (FK nullable: import_batch_id)
ImportBatch (1)──(N) OrdenCargue         (FK nullable: import_batch_id)
ImportBatch (1)──(N) Tarja               (FK nullable: import_batch_id)
ImportBatch (1)──(N) User                (FK nullable: import_batch_id_origen — solo para clientes auto-creados)

ImportPendingRecord ──(morph)──> Solicitud | OrdenServicio | Contenedor | OrdenCargue | Tarja | User
```

---

## Tablas nuevas

### 1. `import_batches`

Cabecera de cada operación de importación (validar o importar).

| Campo | Tipo | Restricciones | Notas |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| usuario_id | BIGINT UNSIGNED | FK → users.id, NOT NULL | Quien disparó la importación (FR-014) |
| archivo_nombre | VARCHAR(255) | NOT NULL | Nombre original subido |
| archivo_hash | CHAR(64) | NOT NULL | SHA-256 del archivo (FR-014, R7) |
| archivo_path | VARCHAR(500) | NOT NULL | `storage/app/imports/{uuid}.xlsx` (R10) |
| modo | ENUM('validar','importar') | NOT NULL | (R2) |
| dry_run | BOOLEAN | NOT NULL | Espejo de `modo='validar'` — facilita queries |
| politica_duplicados | ENUM('omitir','actualizar_saldo','abortar') | NOT NULL DEFAULT 'omitir' | Solo aplica si `modo='importar'` (R7) |
| fecha_corte | DATE | NULLABLE | Default 2026-02-27 — del nombre del archivo |
| origen | VARCHAR(50) | NOT NULL DEFAULT 'carga_historica_27_02_2026' | Trazabilidad de procedencia (FR-019, FR-021) |
| estado | ENUM | NOT NULL DEFAULT 'pendiente' | `pendiente`, `procesando`, `completado`, `fallido`, `cancelado` |
| total_filas | INT UNSIGNED | NULLABLE | Llenado al finalizar |
| importables | INT UNSIGNED | NULLABLE | |
| errores | INT UNSIGNED | NULLABLE | |
| advertencias | INT UNSIGNED | NULLABLE | |
| ignoradas | INT UNSIGNED | NULLABLE | |
| clientes_autocreados | INT UNSIGNED | NULLABLE | |
| contenedores_creados | INT UNSIGNED | NULLABLE | |
| referencias_creadas | INT UNSIGNED | NULLABLE | |
| despachos_historicos_creados | INT UNSIGNED | NULLABLE | |
| resumen | JSON | NULLABLE | Estructura libre para detalles agregados (conteos por hoja, etc.) |
| started_at | DATETIME | NULLABLE | |
| finished_at | DATETIME | NULLABLE | |
| error_mensaje | TEXT | NULLABLE | Si `estado=fallido` |
| created_at | TIMESTAMP | | |
| updated_at | TIMESTAMP | | |

**Índices**: `usuario_id`, `archivo_hash`, `estado`, `dry_run`.

**Transiciones**: `pendiente → procesando → completado` (o `fallido` desde cualquiera de los dos primeros, `cancelado` solo desde `pendiente`).

---

### 2. `import_row_results`

Resultado por fila del Excel (incluido el dry-run).

| Campo | Tipo | Restricciones | Notas |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| import_batch_id | BIGINT UNSIGNED | FK → import_batches.id, NOT NULL | |
| hoja | VARCHAR(120) | NOT NULL | Nombre de la pestaña del Excel |
| fila_excel | INT UNSIGNED | NOT NULL | Número de fila tal como aparece en Excel (1-indexed, incluye header) |
| estado | ENUM('importado','error','advertencia','ignorado') | NOT NULL | (R9) |
| tipo | VARCHAR(60) | NULLABLE | Catálogo: `CLIENTE_NO_RESUELTO`, `CONTENEDOR_FALTANTE`, `FECHA_INVALIDA`, `CANTIDAD_INVALIDA`, `SALDO_INCONSISTENTE`, `CONTENEDOR_CONFLICTO_CLIENTE`, `DUPLICADO_EXISTENTE`, `HOJA_DUPLICADA`, `HOJA_VACIA`, `HOJA_COPIA`, `DESPACHO_INCOMPLETO`, `OK` |
| mensaje | TEXT | NOT NULL | Descripción humana |
| referencia_id | BIGINT UNSIGNED | FK → referencias.id, NULLABLE | Llenado si se creó/asoció |
| contenedor_id | BIGINT UNSIGNED | FK → contenedores.id, NULLABLE | |
| user_cliente_id | BIGINT UNSIGNED | FK → users.id, NULLABLE | Cliente involucrado |
| payload_original | JSON | NULLABLE | Snapshot de las celdas relevantes para reabrir el error |
| created_at | TIMESTAMP | | |

**Índices**: `import_batch_id`, (`import_batch_id`, `estado`), (`import_batch_id`, `hoja`), `tipo`.

---

### 3. `import_pending_records`

Cola polimórfica de registros importados con campos por completar (R3, FR-022, FR-023, FR-031).

| Campo | Tipo | Restricciones | Notas |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| pendienteable_type | VARCHAR(255) | NOT NULL | Morph type (e.g. `App\Models\Contenedor`) |
| pendienteable_id | BIGINT UNSIGNED | NOT NULL | Morph ID |
| import_batch_id | BIGINT UNSIGNED | FK → import_batches.id, NOT NULL | |
| campos_pendientes | JSON | NOT NULL | Lista de claves de campo: `["placa_vehiculo","conductor","conductor_documento","cita_puerto"]` |
| prioridad | TINYINT UNSIGNED | NOT NULL DEFAULT 50 | Para ordenar la cola (mayor = más arriba) |
| completado_at | DATETIME | NULLABLE | NULL = pendiente (fuente de verdad) |
| completado_por_id | BIGINT UNSIGNED | FK → users.id, NULLABLE | |
| created_at | TIMESTAMP | | |
| updated_at | TIMESTAMP | | |

**Índices**: (`pendienteable_type`, `pendienteable_id`), `import_batch_id`, `completado_at`.

**Catálogo de campos por entidad** (definido también en código vía `App\Enums\PendingFieldCatalog` para validar los valores aceptados):

| Entidad | Campos posibles |
|---|---|
| `Solicitud` | `naviera`, `puerto_origen`, `descripcion` |
| `OrdenServicio` | `vehiculo`, `conductor`, `conductor_documento`, `cita_puerto` |
| `Contenedor` | `placa_vehiculo`, `tipo`, `fecha_ingreso`, `destino_salida` |
| `OrdenCargue` | `despachador_id`, `notas` |
| `Tarja` | `despachador_id`, `observaciones`, `vehiculo`, `conductor` |
| `User` (clientes auto-creados) | `email_real`, `phone` (manejado vía middleware, no por la pantalla genérica) |

---

## Modificaciones a tablas existentes

### `users` — flags para primer login forzado (R8, FR-024 a FR-026)

Migración: `2026_05_21_100000_add_pending_fields_to_users_table.php`

| Campo | Tipo | Default | Notas |
|---|---|---|---|
| requiere_cambio_password | BOOLEAN | FALSE | TRUE para clientes auto-creados |
| email_placeholder | BOOLEAN | FALSE | TRUE si el email es derivado del nombre, no real |
| password_actualizada_at | TIMESTAMP | NULL | Auditoría del primer cambio |
| import_batch_id_origen | BIGINT UNSIGNED NULL | NULL | FK → import_batches.id; identifica usuarios creados por una importación |

**Índices**: `requiere_cambio_password` (parcial — útil para reportes "cuántos clientes aún no entraron"), `import_batch_id_origen`.

### `solicitudes`, `ordenes_servicio`, `contenedores`, `ordenes_cargue`, `tarjas` — trazabilidad de lote

Migración única: `2026_05_21_100400_add_import_batch_id_to_operational_tables.php`

Cada una recibe:

| Campo | Tipo | Default | Notas |
|---|---|---|---|
| import_batch_id | BIGINT UNSIGNED NULL | NULL | FK → import_batches.id |

**Índice**: `import_batch_id` en cada tabla.

**Comportamiento**: Si `import_batch_id IS NOT NULL` y existe al menos un `import_pending_records` vivo para esa fila, los controladores/policies que muestran el detalle deben redirigir al formulario de completado antes de permitir acciones operativas (FR-022, FR-031).

---

## Validation rules

| Regla | Donde se aplica | Origen |
|---|---|---|
| Cliente referenciado debe existir o ser auto-creable | `ClienteResolver` | FR-024 |
| Contenedor por fila debe tener número no vacío | `RowValidator` (regla `CONTENEDOR_FALTANTE`) | FR-005 |
| Fecha de depósito requerida | `RowValidator` (regla `FECHA_INVALIDA`) | FR-005 |
| Cantidad numérica > 0 para línea importable | `RowValidator` (regla `CANTIDAD_INVALIDA`) | Edge Cases (`#`) |
| Mismo contenedor en hojas de clientes distintos = `CONTENEDOR_CONFLICTO_CLIENTE` | `InventarioImportService` (pre-pass que agrupa) | FR-009 |
| Saldo Excel no se recalcula | `ReferenciaMapper` | FR-030 |
| Cada `import_pending_records` debe referenciar al menos un campo en `campos_pendientes` | DB check + service | R3 |
| `politica_duplicados` solo se acepta cuando `modo='importar'` | FormRequest | R7 |

---

## State transitions

### `ImportBatch.estado`

```
pendiente ── start ──► procesando ── ok ──► completado
    │                      │
    │                      └── error ───► fallido
    │
    └── cancel ──► cancelado     (solo antes de start)
```

### `ImportPendingRecord.completado_at`

```
NULL  ── completarPendientes() ──► (timestamp + completado_por_id)
```

No se reabre: si se descubre un nuevo campo faltante, se crea un **nuevo** `ImportPendingRecord` y se enlaza al mismo registro polimórfico.

---

## Migration order (delta sobre feature 001)

1. `2026_05_21_100000_add_pending_fields_to_users_table` — añade flags y `import_batch_id_origen` (este último se hace `nullable` y SIN FK porque la tabla `import_batches` aún no existe; la FK la añadimos en el paso 5).
2. `2026_05_21_100100_create_import_batches_table`
3. `2026_05_21_100200_create_import_row_results_table` (FK → import_batches)
4. `2026_05_21_100300_create_import_pending_records_table` (FK → import_batches)
5. `2026_05_21_100400_add_import_batch_id_to_operational_tables` — añade FK a solicitudes, ordenes_servicio, contenedores, ordenes_cargue, tarjas, **y** la FK retroactiva `users.import_batch_id_origen → import_batches.id`.

Todas las migraciones son **aditivas y no destructivas**: pueden aplicarse sobre la BD de producción de feature 001 sin migrar datos existentes.

---

## Cálculos derivados

### "Tengo pendientes de completar"

```sql
SELECT COUNT(*) FROM import_pending_records WHERE completado_at IS NULL;
```

Sin joins, escalable a millones.

### "Pendientes asignados a un rol"

Las policies determinan qué roles pueden completar qué tipo (ej. `coordinador` para `OrdenServicio`, `despachador` para `Tarja`). La query base se filtra por `pendienteable_type`.

### "Importaciones recientes"

```sql
SELECT * FROM import_batches ORDER BY created_at DESC LIMIT 20;
```

### Saldo de referencia (sin cambios respecto a feature 001)

`cantidad_actual` se persiste tal cual el Excel (FR-030); el cálculo `dias_almacenamiento` definido en feature 001 sigue siendo válido y se aplica también a referencias históricas (su `fecha_ingreso` = `fecha_deposito` del Excel).
