# Research: Ajustes a ingreso y vaciado

**Feature**: 006-ajustes-ingreso-vaciado
**Date**: 2026-06-25
**Phase**: 0 — Outline & Research

No quedan marcadores `NEEDS CLARIFICATION`. Decisiones técnicas:

## D1. Modelar el BL como tabla padre `ingresos`

- **Decisión**: Crear `ingresos` (id, bl, cliente_id, fecha_ingreso date, usuario_id, timestamps) con `HasPhotos` para los documentos (bl/dim/lista_empaque). `contenedores` recibe `ingreso_id` (nullable FK). Un ingreso `hasMany` contenedores; cada contenedor `hasMany` referencias.
- **Rationale**: El BL agrupa varios contenedores y comparte documentos, fecha y cliente (FR-001, FR-004). Una tabla padre normaliza esos atributos y permite adjuntar los documentos una sola vez al BL. Cumple "1 BL → N contenedores → M referencias".
- **Alternativas**: (a) Denormalizar `bl`/`fecha`/docs por contenedor — rechazada: duplica datos, complica la carga única de documentos. (b) Reutilizar `ordenes_servicio` como padre — rechazada: ese flujo está oculto (feature 005) y tiene otra semántica.

## D2. Compatibilidad con ingresos de la feature 005

- **Decisión**: `ingreso_id` nullable. Los contenedores creados por la feature 005 (con `bl` en el propio contenedor y documentos adjuntos al contenedor) quedan con `ingreso_id = null` y siguen consultables. **Backfill opcional**: una migración de datos puede crear un `Ingreso` por cada contenedor con `bl` no nulo y sin `ingreso_id`, moviendo la relación; se evalúa según volumen real (probablemente pocos o ninguno en prod).
- **Rationale**: FR-015 (no romper lo previo). El listado de ingresos mostrará los `ingresos` nuevos; los contenedores-con-bl legados se incluyen vía backfill o como vista de compatibilidad.
- **Alternativas**: Forzar migración de todos — innecesario si hay poco dato; se deja como tarea opcional.

## D3. Fecha de ingreso retroactiva

- **Decisión**: `ingresos.fecha_ingreso` (date) capturada en el formulario, validada `≤ hoy` en el FormRequest. Al guardar, se propaga a `contenedores.fecha_ingreso` y `referencias.fecha_ingreso` (que ya existen). La marca de creación es `ingresos.created_at` (auditoría), independiente de la fecha capturada.
- **Rationale**: FR-008/FR-009/FR-010/FR-011. El inventario y los días de almacenamiento ya leen `referencias.fecha_ingreso`, así que reflejan la fecha capturada automáticamente.
- **Reporte de ingresos**: el `movimientos_inventario.created_at` (feature 005) es la hora real de la operación. Para que el **reporte de ingresos** muestre la fecha capturada (FR-009), el reporte usará `referencia.fecha_ingreso` como columna/filtro de fecha en lugar de `movimiento.created_at`. El ledger conserva su `created_at` de auditoría sin cambios.
- **Alternativas**: Backdatear `movimientos_inventario.created_at` — rechazada: contamina la auditoría (FR-010).

## D4. Ubicación opcional

- **Decisión**: `referencias.ubicacion_patio_id` **ya es nullable** en la BD (feature 005). Solo se cambia la validación: `StoreIngresoMercanciaRequest` pasa `referencias.*.ubicacion_patio_id` de `required` a `nullable`. Las referencias sin ubicación quedan "sin ubicar" y se ubican luego con el flujo existente `InventarioService::asignarUbicacion`.
- **Rationale**: FR-002a/SC-007. Cambio mínimo; el módulo de inventario ya soporta ubicar después.
- **Alternativas**: Crear un estado "pendiente de ubicar" — innecesario: `ubicacion_patio_id IS NULL` ya lo representa; se puede filtrar en el inventario.

## D5. Vaciado: agregar fotos después de creado

- **Decisión**: Nueva ruta `POST /vaciado/{ordenVaciado}/fotos` → `VaciadoController::agregarFotos` → `VaciadoService::agregarFotos` que llama `guardarFotos($fotos, "vaciado/{id}/fotos")` sobre la `OrdenVaciado` (trait `HasPhotos`, ya soporta array). Formulario "agregar fotos" en `vaciado/show.blade.php`. Validación en `AgregarFotosVaciadoRequest` (`fotos` array, `fotos.*` image, ≤5MB) reutilizando las reglas existentes.
- **Rationale**: FR-012/FR-013/FR-014. La carga múltiple **al crear** ya funciona (`StoreOrdenVaciadoRequest.fotos[]`); falta solo sumarlas después.
- **Alternativas**: Reusar el form de novedad — rechazada: mezcla evidencia de novedad con fotos generales del vaciado.

## D6. Formulario de ingreso anidado (contenedores → referencias)

- **Decisión**: Reestructurar `ingreso/create.blade.php` a un repetidor de dos niveles: BL + fecha + cliente + documentos arriba; lista de contenedores (número, tipo de mercancía) y, dentro de cada uno, lista de referencias (código, descripción, unidad, peso, cantidad, ubicación opcional). El `StoreIngresoMercanciaRequest` valida `contenedores` array y `contenedores.*.referencias` array.
- **Rationale**: FR-001/FR-002/FR-005/FR-006. Una sola operación crea todo bajo transacción.
- **Validaciones**: ≥1 contenedor, cada uno con ≥1 referencia; números de contenedor únicos dentro del ingreso (FR-006); cantidad ≥ 1.

## D7. Testing

- **Decisión**: Pruebas de Feature: (a) ingreso con 1 BL y 2 contenedores —incluyendo código de referencia repetido entre contenedores— verificando inventario por contenedor; (b) fecha retroactiva reflejada en `referencias.fecha_ingreso` y rechazo de fecha futura; (c) ingreso con referencias sin ubicación (quedan `ubicacion_patio_id` null); (d) números de contenedor duplicados rechazados; (e) agregar fotos a un vaciado existente suma sin reemplazar.
- **Rationale**: Constitución VI; cubre todos los FR.

---

**Salida de Fase 0**: incógnitas resueltas. Listo para Fase 1.
