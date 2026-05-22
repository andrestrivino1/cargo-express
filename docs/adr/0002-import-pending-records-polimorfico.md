# ADR 0002: Tabla polimórfica `import_pending_records` para el patrón "importar ahora, completar al consultar"

**Status**: Accepted
**Date**: 2026-05-21
**Feature**: [002-import-excel-inventory](../../specs/002-import-excel-inventory/spec.md)

## Contexto

Al importar el archivo histórico `INVENTARIO TOTAL CONTROLCARGA 27022026.xlsx` necesitamos crear **registros incompletos** en seis tablas operativas: `solicitudes`, `ordenes_servicio`, `contenedores`, `ordenes_cargue`, `tarjas` y `users` (clientes auto-creados). El Excel no contiene los campos `placa_vehiculo`, `vehiculo`, `conductor`, `cita_puerto`, `despachador_id`, `naviera`, etc. Estos deben quedar marcados como "pendientes" y completarse cuando un operador abra el registro.

## Decisión

Modelar el patrón con una **única tabla polimórfica** `import_pending_records (id, pendienteable_type, pendienteable_id, import_batch_id, campos_pendientes JSON, prioridad, completado_at, completado_por_id)` + el trait `App\Traits\HasImportPendingFields`.

## Opciones consideradas

### A. Sentinel string en cada columna afectada (`'PENDIENTE_HISTORICO'` literal)

**Rechazada.** Contamina datos operativos, rompe queries existentes (`WHERE placa_vehiculo LIKE 'ABC%'` empieza a fallar de forma silenciosa), no comunica el campo faltante de forma estructurada, dificulta filtros y reportes.

### B. Columna `pending_fields JSON NULL` en cada tabla afectada

**Rechazada.** La query "todos los pendientes" requiere `UNION` de 6 tablas; paginar/ordenar/filtrar se vuelve costoso. El concepto se dispersa en lugar de centralizarse.

### C. Columna `estado_completitud ENUM` por tabla

**Rechazada.** No comunica *qué* campo falta, solo que algo falta. Violaría Principio I (claridad > brevedad).

### D. Tabla polimórfica `import_pending_records` (elegida)

- Una sola tabla concentra la "cola de trabajo" de FR-023 — la pantalla `/pendientes` la lee con un solo `SELECT … ORDER BY prioridad DESC` paginado.
- La fuente de verdad es la presencia de un registro **vivo** (`completado_at IS NULL`): si no existe registro vivo, la entidad está completa.
- El catálogo de campos por tipo vive en `App\Enums\PendingFieldCatalog` y se valida tanto al **registrar** (`PendingFieldsRegistrar::registrar`) como al **completar** (`ImportPendingRecord::completar`), evitando claves arbitrarias en JSON.
- Las policies (`ContenedorPolicy`, `OrdenServicioPolicy`, `TarjaPolicy`, `OrdenCarguePolicy`) consultan `tienePendientesImportacion()` para bloquear acciones operativas hasta completar.

## Consecuencias

**Positivas**

- DRY: la lógica de "pendiente" vive en un solo trait + una sola tabla.
- Extensible: agregar un nuevo tipo polimórfico requiere solo extender el catálogo (sin migración).
- Auditoría: `completado_at`, `completado_por_id`, `prioridad` + el batch de origen están vinculados desde la cola.

**Negativas**

- Una tabla más en el esquema. Asumido como costo aceptable contra los beneficios de centralización.
- Los catálogos de campos viven en código (enum) en lugar de BD. Si se requiere edición en runtime habrá que migrar a una tabla; por ahora YAGNI.

## Referencias

- [research.md §R3](../../specs/002-import-excel-inventory/research.md#r3-modelado-del-estado-pendiente_historico)
- [contracts/pending-fields.md](../../specs/002-import-excel-inventory/contracts/pending-fields.md)
- [data-model.md §3](../../specs/002-import-excel-inventory/data-model.md#3-import_pending_records)
