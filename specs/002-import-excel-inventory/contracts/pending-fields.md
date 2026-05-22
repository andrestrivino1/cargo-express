# Pending Fields Catalog Contract

**Date**: 2026-05-21
**Plan**: [../plan.md](../plan.md)

Catálogo único que vive en código (`App\Enums\PendingFieldCatalog`) y se valida en BD vía la columna `import_pending_records.campos_pendientes`. Esta es la **única fuente de verdad** sobre qué campos puede pedir el sistema a un usuario cuando abre un registro importado.

---

## Por entidad

### `App\Models\Solicitud`

| Campo | Tipo de input | Validación al completar |
|---|---|---|
| `naviera` | text | nullable, max 100 |
| `puerto_origen` | text | nullable, max 100 |
| `descripcion` | textarea | nullable |

### `App\Models\OrdenServicio`

| Campo | Tipo de input | Validación al completar |
|---|---|---|
| `vehiculo` | text | required, max 20 — placa del vehículo que trajo el contenedor |
| `conductor` | text | required, max 255 |
| `conductor_documento` | text | nullable, max 20 |
| `cita_puerto` | datetime-local | required, fecha del retiro en puerto |

### `App\Models\Contenedor`

| Campo | Tipo de input | Validación al completar |
|---|---|---|
| `placa_vehiculo` | text | required, max 20 |
| `tipo` | select | nullable; opciones: `20`, `40`, `40HC`, `45HC`, `OTRO` |
| `destino_salida` | text | nullable, max 100 |

### `App\Models\OrdenCargue`

| Campo | Tipo de input | Validación al completar |
|---|---|---|
| `despachador_id` | select (users con rol `despachador`) | required, exists:users,id |
| `notas` | textarea | nullable |

### `App\Models\Tarja`

| Campo | Tipo de input | Validación al completar |
|---|---|---|
| `despachador_id` | select (users con rol `despachador`) | required, exists:users,id |
| `observaciones` | textarea | nullable |
| `vehiculo` | text | nullable (puede no aplicar para tarja retroactiva) |
| `conductor` | text | nullable |

### `App\Models\User` (clientes auto-creados)

**No** aparece en la cola de pendientes. Se maneja con middleware dedicado (`primer_login`) y dos pasos en `/primer-login/password` y `/primer-login/email`.

---

## Reglas transversales

1. **Validación de claves**: al persistir o actualizar un `ImportPendingRecord`, el servicio valida que cada string en `campos_pendientes` exista en el catálogo del `pendienteable_type` correspondiente. Una clave fuera de catálogo es rechazada con excepción tipada `PendingFieldNotInCatalogException` (no se acepta "trust the JSON").
2. **No se permite parcial**: el botón "Guardar" del formulario solo se habilita cuando **todos** los campos pendientes están completos (los campos con regla `nullable` se completan dejándolos vacíos explícitamente — el usuario marca un checkbox "sin información" para esos).
3. **Cambio de catálogo en producción**: agregar un campo al catálogo es trivial (editar el enum + la vista del formulario). Quitar un campo requiere migración de datos: para cualquier `ImportPendingRecord` vivo que mencione la clave eliminada, el servicio decide si la completa automáticamente con un valor por defecto o si requiere intervención.
4. **Bloqueo de acciones operativas**: las policies de `Contenedor`, `OrdenServicio`, `OrdenCargue`, `Tarja` consultan si existe un `ImportPendingRecord` vivo antes de permitir acciones de transición de estado (gate in, gate out, programar vaciado, marcar despachado, etc.). Si existe, devuelven `false` y el controlador redirige al formulario de completado en lugar de a la pantalla pedida.
5. **Auditoría**: al completar, se emite un evento `RegistroImportadoCompletado($modelo, $batch, $usuario, $camposCompletados)` que cualquier listener puede escuchar (en el alcance inicial: ninguno; queda como gancho).

---

## Tabla resumen de bloqueos por entidad

| Entidad | Acción operativa | Bloqueada si hay `ImportPendingRecord` vivo? |
|---|---|---|
| Contenedor | Gate In | Sí — requiere placa, tipo |
| Contenedor | Gate Out | Sí |
| Contenedor | Iniciar vaciado | Sí — requiere OrdenServicio completa |
| OrdenServicio | Crear gate event | Sí |
| OrdenCargue | Marcar en proceso / completar | Sí |
| Tarja | Imprimir / cerrar | Sí |
| Solicitud | Ver detalle | **No** — bloquea solo acciones que requieran los campos faltantes |
| Referencia | Consulta / inventario | **No** — la Referencia se importa siempre completa |
