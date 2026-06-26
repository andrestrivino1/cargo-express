# Phase 0 — Research: Editar ingreso con referencias e imágenes del BL

Las dos aclaraciones de la spec (FR-013, FR-014) ya fueron resueltas con el usuario, por lo que no quedan `NEEDS CLARIFICATION`. Esta investigación documenta las decisiones técnicas que se derivan de la spec y del código existente.

## D1 — Nivel de asociación de las imágenes

- **Decision**: Las imágenes se adjuntan al **Ingreso** (BL) completo, usando la relación polimórfica existente `photos` vía el trait `HasPhotos` que `Ingreso` ya incluye.
- **Rationale**: FR-014 = opción A (galería única por ingreso). `Ingreso` ya usa `HasPhotos` (`app/Models/Ingreso.php:12`), y el flujo de creación ya guarda archivos del ingreso bajo `ingresos/{id}` (`IngresoMercanciaService::registrar`). Reutilizar evita esquema nuevo y es la opción más simple (KISS).
- **Alternatives considered**: Imágenes por referencia (rechazada: `Referencia` no usa `HasPhotos`, requeriría más UI/almacenamiento y fue descartada en FR-014). Imágenes por contenedor (rechazada: el usuario razona a nivel de BL).

## D2 — Tipo y validación de las imágenes

- **Decision**: Campo `fotos[]` (múltiple), validado como `['nullable','array']` + `'fotos.*' => ['image','mimes:jpg,jpeg,png,webp','max:5120']`. Se guardan como `tipo = 'foto'` con `HasPhotos::guardarFotos($archivos, "ingresos/{$ingreso->id}")`.
- **Rationale**: Replica exactamente el patrón ya probado en vaciado (`UpdateOrdenVaciadoRequest.php:21-22`, `resources/views/vaciado/editar.blade.php:59`). DRY y consistencia operativa. `guardarFotos` ya marca `tipo='foto'`, distinto de los `documentos` (BL/DIM/lista) que el ingreso guarda en creación.
- **Alternatives considered**: Documentos PDF además de imágenes (fuera de alcance: el usuario pide "imágenes"; los documentos del BL ya se cargan en creación). Límite 10 MB como los documentos del ingreso (se opta por 5 MB como en fotos de vaciado para consistencia del tipo "foto").

## D3 — Agregar imágenes sin reemplazar las existentes

- **Decision**: La carga **añade** fotos (no reemplaza). Mostrar la galería de `$ingreso->fotos` existentes; el `update` solo crea nuevas `Photo` para los archivos subidos. No se borran las previas (FR-006, FR-013).
- **Rationale**: `guardarFotos` hace `photos()->create(...)` por archivo, es aditivo por naturaleza. Coincide con `VaciadoService::agregarFotos`.
- **Alternatives considered**: Permitir eliminar fotos existentes (fuera de alcance por FR-013).

## D4 — Mostrar las referencias del BL en la edición

- **Decision**: Eager-load `contenedores.referencias` (y `referencias.producto`/`ubicacionPatio` para mostrar descripción/ubicación) en `IngresoMercanciaController::edit`, y renderizar una lista de solo lectura agrupada por contenedor.
- **Rationale**: La relación ya existe (`Ingreso → Contenedor → Referencia`). Eager-loading evita N+1 (Principio VII). Solo lectura cumple FR-001/FR-002 y respeta FR-013 (no editar existentes).
- **Alternatives considered**: Editar referencias inline (fuera de alcance, FR-013). Consultar referencias por una relación `hasManyThrough` directa Ingreso→Referencia (innecesaria; el árbol contenedor→referencias ya se usa en `show`).

## D5 — Agregar una referencia nueva durante la edición

- **Decision**: Permitir agregar referencias nuevas asociándolas a un **contenedor existente del ingreso** (selector de contenedor). Reutilizar la lógica de creación de referencia + movimiento de inventario extrayendo un método privado `crearReferencia()` desde `registrar()`. Validación de la referencia nueva con reglas `nullable` (solo se procesa si el operador la completa).
- **Rationale**: Las referencias cuelgan de un `contenedor_id` (no directamente del ingreso). El bloque de creación en `registrar()` (`IngresoMercanciaService.php:48-67`) ya hace `Referencia::create(...)` + `movimientos->registrarEntrada(...)`; extraerlo evita duplicación (DRY) y mantiene la consistencia de inventario.
- **Edge case**: Si el ingreso no tiene contenedores (placeholder vacío de importación), no se puede asociar la referencia a un contenedor. Para esta iteración: si no hay contenedores, el bloque "agregar referencia" se deshabilita con un aviso, y el ingreso igualmente puede confirmar BL y subir imágenes (FR-012). Crear contenedores desde la edición queda fuera de alcance.
- **Alternatives considered**: Crear un contenedor implícito al agregar la primera referencia (rechazado por KISS/alcance; añade reglas de negocio no pedidas).

## D6 — Confirmación del BL y atomicidad

- **Decision**: `update()` aplica BL/Cliente/Fecha + baja `bl_por_confirmar=false` (comportamiento actual) y, en la **misma** operación, guarda fotos y/o crea la referencia nueva, todo dentro de una transacción cuando haya escrituras de referencia/inventario.
- **Rationale**: FR-004 + FR-010 (no perder datos ante error). `registrar()` ya usa `DB::transaction`; replicar para las escrituras compuestas. La confirmación del BL ocurre aunque se agreguen imágenes/referencias (edge case "Confirmación del BL").
- **Alternatives considered**: Endpoints separados para fotos y referencias (más HTTP/JS; se prefiere un único submit del formulario de edición por simplicidad de UX, con la opción de un endpoint dedicado de "agregar referencia" solo si el repeater inline complica la vista > 300 líneas).

## D7 — Autorización

- **Decision**: Mantener RBAC actual: rutas `ingreso.editar`/`ingreso.update` bajo `role:administrador|coordinador` y `UpdateIngresoRequest::authorize()` (`hasAnyRole(['administrador','coordinador'])`). Las nuevas capacidades heredan esa protección (FR-011).
- **Rationale**: Seguridad por endpoint (constitución). Sin permisos nuevos para no tocar seeders de permisos en prod.
- **Alternatives considered**: Permiso granular `ingreso.editar.referencias` (innecesario; añade fricción de despliegue en hosting sin SSH).

## Resumen de decisiones

| ID | Decisión | Impacto en esquema |
|----|----------|--------------------|
| D1 | Imágenes a nivel Ingreso vía `HasPhotos` | Ninguno |
| D2 | `fotos[]` image/mimes/max:5120, tipo 'foto' | Ninguno |
| D3 | Carga aditiva (no reemplaza) | Ninguno |
| D4 | Lista de referencias (solo lectura, eager-load) | Ninguno |
| D5 | Agregar referencia a contenedor existente (método `crearReferencia` reutilizado) | Ninguno |
| D6 | Update atómico (transacción) + confirmación BL | Ninguno |
| D7 | RBAC existente (admin/coordinador) | Ninguno |

**Conclusión**: Feature implementable **sin migraciones** ni dependencias nuevas, reutilizando trait `HasPhotos`, el patrón de fotos de vaciado y la lógica de referencias de `registrar()`.
