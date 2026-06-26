---
description: "Task list for feature 007 — Editar ingreso con referencias e imágenes del BL"
---

# Tasks: Editar ingreso con referencias e imágenes del BL

**Input**: Design documents from `/specs/007-edit-ingreso-bl-references/`
**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/ingreso-edit-ui.md, quickstart.md

**Tests**: INCLUDED. La constitución del proyecto (Principio VI) exige cobertura mínima (80% servicios, 60% controladores) y pruebas de integración para funcionalidad crítica (inventario). Por eso cada historia incluye tareas de prueba.

**Organization**: Tareas agrupadas por historia de usuario para implementación y prueba independientes.

**Stack**: PHP 8.2 + Laravel 12, Blade + Bootstrap 5.3, Spatie RBAC. **Sin migraciones ni dependencias nuevas.**

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Puede correr en paralelo (archivo distinto, sin dependencias pendientes)
- **[Story]**: US1, US2, US3
- Rutas de archivo exactas incluidas

## Path Conventions

Monolito Laravel: `app/Http/Controllers`, `app/Http/Requests`, `app/Services`, `resources/views/ingreso`, `tests/Feature`, `tests/Unit`.

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Preparación del entorno. Sin migraciones.

- [X] T001 [P] Verificar que el symlink de almacenamiento existe para servir imágenes del ingreso: ejecutar `php artisan storage:link` (idempotente) y confirmar que `public/storage` apunta a `storage/app/public`.
- [X] T002 [P] Crear esqueletos de prueba vacíos: `tests/Feature/IngresoEditarTest.php` (clase `IngresoEditarTest` con `use RefreshDatabase`) y `tests/Unit/IngresoMercanciaServiceTest.php` (clase `IngresoMercanciaServiceTest`).
- [X] T003 [P] Verificar baseline verde: `php artisan test` corre sin fallos antes de empezar.

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Cambios en archivos compartidos (servicio, controlador, vista) que todas las historias extienden. Evita conflictos de edición entre historias.

**⚠️ CRITICAL**: Ninguna historia puede empezar hasta completar esta fase.

- [X] T004 Mover la lógica de actualización del ingreso a un método nuevo `actualizar(Ingreso $ingreso, array $data, array $fotos, ?array $nuevaReferencia, User $usuario): Ingreso` en `app/Services/IngresoMercanciaService.php`, envuelto en `DB::transaction`. Por ahora solo aplica BL/cliente/fecha y `bl_por_confirmar=false` (paridad con el comportamiento actual); los parámetros `$fotos` y `$nuevaReferencia` se cablean en US2/US3.
- [X] T005 Modificar `app/Http/Controllers/IngresoMercanciaController.php::update()` para delegar en `IngresoMercanciaService::actualizar(...)` (pasar `$request->file('fotos', [])`, `$request->validated('nueva_referencia')`, `$request->user()`), eliminando la actualización inline. Mantener el redirect a `ingreso.show` con flash `success`.
- [X] T006 Reestructurar `resources/views/ingreso/editar.blade.php`: agregar `enctype="multipart/form-data"` al `<form>` y dejar puntos de inclusión (`@include`) para los parciales `ingreso.partials._referencias`, `ingreso.partials._imagenes`, `ingreso.partials._agregar-referencia` (los parciales se crean en sus historias). Mantener el archivo < 300 líneas.

**Checkpoint**: Edición sigue funcionando igual que antes (BL/cliente/fecha + confirmar), ahora vía servicio y con form multipart listo.

---

## Phase 3: User Story 1 — Completar el BL viendo todo el contexto del ingreso (Priority: P1) 🎯 MVP

**Goal**: Al editar un ingreso se muestran todas las referencias del BL (agrupadas por contenedor) junto a los campos editables, y al guardar el BL real se confirma el ingreso conservando sus referencias.

**Independent Test**: Abrir un ingreso importado con BL provisional y ≥2 referencias en varios contenedores; verificar que todas las referencias se listan; escribir el BL real y guardar; confirmar que `bl_por_confirmar` queda en `false` y las referencias se conservan.

### Tests for User Story 1 ⚠️

- [X] T007 [P] [US1] En `tests/Feature/IngresoEditarTest.php`: test que `GET ingreso.editar` muestra todas las referencias del ingreso agrupadas por contenedor (código, descripción, cantidad, unidad), incluyendo referencias en >1 contenedor (FR-001, FR-002).
- [X] T008 [P] [US1] En `tests/Feature/IngresoEditarTest.php`: test que `PUT ingreso.update` con BL real baja `bl_por_confirmar` a `false` y conserva las referencias (FR-003, FR-004).
- [X] T009 [P] [US1] En `tests/Feature/IngresoEditarTest.php`: test que un ingreso sin contenedores/referencias muestra el mensaje "Sin referencias" y aún permite guardar (FR-012).

### Implementation for User Story 1

- [X] T010 [US1] Modificar `app/Http/Controllers/IngresoMercanciaController.php::edit()` para eager-loadear `contenedores.referencias.producto`, `contenedores.referencias.ubicacionPatio` y `fotos`; pasar `$ingreso`, `$clientes` a la vista.
- [X] T011 [P] [US1] Crear parcial `resources/views/ingreso/partials/_referencias.blade.php`: lista/tabla de solo lectura agrupada por contenedor (`numero`), mostrando código, descripción/producto, `cantidad_actual`/`cantidad_inicial`, unidad y ubicación si existe; con `@forelse` que muestre "Sin referencias" cuando esté vacío (FR-001, FR-002, FR-012).
- [X] T012 [US1] Incluir el parcial `_referencias` en `resources/views/ingreso/editar.blade.php` debajo de los campos editables.

**Checkpoint**: US1 funcional — referencias visibles y BL confirmable de forma independiente.

---

## Phase 4: User Story 2 — Agregar las imágenes del BL durante la edición (Priority: P1)

**Goal**: El operador puede ver las imágenes ya adjuntas y subir nuevas (aditivas) al ingreso desde la pantalla de edición, con validación de tipo/tamaño.

**Independent Test**: Abrir un ingreso, adjuntar 2 imágenes y guardar; verificar que quedan asociadas (tipo `foto`) y visibles; reabrir y agregar otra sin borrar las previas; intentar subir un archivo no-imagen y verificar rechazo sin perder los datos del formulario.

### Tests for User Story 2 ⚠️

- [X] T013 [P] [US2] En `tests/Feature/IngresoEditarTest.php` (usar `Storage::fake('public')` y `UploadedFile::fake()->image(...)`): test que `PUT ingreso.update` con `fotos[]` crea registros `Photo` con `tipo='foto'` asociados al ingreso y conserva las fotos previas (FR-005, FR-006).
- [X] T014 [P] [US2] En `tests/Feature/IngresoEditarTest.php`: test que un archivo no-imagen o que excede el tamaño es rechazado con error de validación y no se altera el ingreso ni se pierden los demás campos (FR-007, FR-010).
- [X] T015 [P] [US2] En `tests/Unit/IngresoMercanciaServiceTest.php`: test unitario de que `actualizar()` con fotos invoca `guardarFotos` en la carpeta `ingresos/{id}` de forma aditiva.

### Implementation for User Story 2

- [X] T016 [US2] Ampliar `app/Http/Requests/UpdateIngresoRequest.php` con reglas `fotos => ['nullable','array']` y `fotos.* => ['image','mimes:jpg,jpeg,png,webp','max:5120']`; agregar atributo legible para `fotos`.
- [X] T017 [US2] Extender `IngresoMercanciaService::actualizar()` en `app/Services/IngresoMercanciaService.php` para, dentro de la transacción, llamar `$ingreso->guardarFotos($fotos, "ingresos/{$ingreso->id}")` cuando `$fotos` no esté vacío (aditivo, sin borrar previas).
- [X] T018 [P] [US2] Crear parcial `resources/views/ingreso/partials/_imagenes.blade.php`: galería de `$ingreso->fotos` (miniaturas vía `Storage::url($foto->ruta)`) e input `<input type="file" name="fotos[]" multiple accept="image/jpeg,image/png,image/webp">` con feedback de error `@error('fotos.*')`.
- [X] T019 [US2] Incluir el parcial `_imagenes` en `resources/views/ingreso/editar.blade.php`.

**Checkpoint**: US1 + US2 funcionan de forma independiente — referencias visibles y carga de imágenes operativa.

---

## Phase 5: User Story 3 — Agregar referencias faltantes al BL durante la edición (Priority: P2)

**Goal**: El operador puede agregar una referencia nueva a un contenedor existente del ingreso desde la edición, generando su movimiento de inventario, con validación de datos mínimos.

**Independent Test**: Abrir un ingreso con ≥1 contenedor, agregar una referencia con datos válidos a un contenedor del ingreso y guardar; verificar que la referencia y su MovimientoInventario de entrada se crean y la referencia aparece en la lista; verificar que datos incompletos y un contenedor ajeno al ingreso se rechazan.

### Tests for User Story 3 ⚠️

- [X] T020 [P] [US3] En `tests/Feature/IngresoEditarTest.php`: test que `PUT ingreso.update` con `nueva_referencia[...]` válida crea la `Referencia` en un contenedor del ingreso y registra el `MovimientoInventario` de entrada (FR-008).
- [X] T021 [P] [US3] En `tests/Feature/IngresoEditarTest.php`: test que una `nueva_referencia` incompleta (p. ej. con `codigo` pero sin `cantidad`) falla por `required_with` y no crea nada (FR-009, FR-010).
- [X] T022 [P] [US3] En `tests/Feature/IngresoEditarTest.php`: test que un `nueva_referencia[contenedor_id]` que no pertenece al ingreso es rechazado (integridad D5).
- [X] T023 [P] [US3] En `tests/Unit/IngresoMercanciaServiceTest.php`: test unitario de `crearReferencia()` (campos heredados `cliente_id`/`fecha_ingreso` del ingreso, `cantidad_inicial==cantidad_actual`, movimiento de entrada registrado).

### Implementation for User Story 3

- [X] T024 [US3] Extraer método privado `crearReferencia(Contenedor $contenedor, array $fila, User $usuario, Ingreso $ingreso): Referencia` en `app/Services/IngresoMercanciaService.php` (mover el bloque de `registrar()` que crea la referencia + `movimientos->registrarEntrada`) y hacer que `registrar()` lo invoque (DRY, sin cambiar comportamiento).
- [X] T025 [US3] Ampliar `app/Http/Requests/UpdateIngresoRequest.php` con reglas `nueva_referencia.*` (`contenedor_id`, `codigo`, `descripcion`, `unidad_medida`, `cantidad`, `peso`, `ubicacion_patio_id`) usando `required_with:nueva_referencia.codigo` según data-model; en `withValidator`, validar que `nueva_referencia.contenedor_id` pertenezca al `$ingreso` de la ruta.
- [X] T026 [US3] Extender `IngresoMercanciaService::actualizar()` para, cuando `$nuevaReferencia` esté presente y completa, resolver el `Contenedor` del ingreso y llamar `crearReferencia(...)` dentro de la transacción.
- [X] T027 [P] [US3] Crear parcial `resources/views/ingreso/partials/_agregar-referencia.blade.php`: selector de contenedor destino (`$ingreso->contenedores`) + campos de la referencia nueva (código, descripción, unidad, cantidad, peso, ubicación opcional). Deshabilitar el bloque con un aviso cuando `$ingreso->contenedores` esté vacío (FR-012 / edge case D5).
- [X] T028 [US3] Incluir el parcial `_agregar-referencia` en `resources/views/ingreso/editar.blade.php`.

**Checkpoint**: Las tres historias funcionan de forma independiente.

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Calidad, consistencia y validación final.

- [X] T029 [P] Verificar límites de la constitución: `resources/views/ingreso/editar.blade.php` < 300 líneas (apoyado en parciales) y métodos del servicio < 40 líneas; refactorizar si excede.
- [X] T030 [P] Revisar mensajes de error y `old()` en la vista para que ningún campo (BL, cliente, fecha, fotos, referencia) se pierda ante errores de validación (FR-010).
- [X] T031 Ejecutar la suite de la feature: `php artisan test --filter=IngresoEditarTest` y `php artisan test --filter=IngresoMercanciaServiceTest` en verde.
- [X] T032 Ejecutar la validación manual de `quickstart.md` (flujo importación → editar → confirmar BL + imágenes + referencia) y marcar el checklist "Definition of Done".

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: sin dependencias.
- **Foundational (Phase 2)**: depende de Setup. **BLOQUEA todas las historias** (toca servicio/controlador/vista compartidos).
- **User Stories (Phase 3–5)**: dependen de Foundational. US1, US2, US3 son independientes entre sí en cuanto a comportamiento, pero comparten 3 archivos (`IngresoMercanciaService.php`, `UpdateIngresoRequest.php`, `editar.blade.php`); las ediciones a esos archivos son secuenciales (no `[P]` entre historias).
- **Polish (Phase 6)**: depende de las historias deseadas.

### User Story Dependencies

- **US1 (P1)**: arranca tras Foundational. Independiente.
- **US2 (P1)**: arranca tras Foundational. Independiente de US1.
- **US3 (P2)**: arranca tras Foundational. Independiente; reutiliza `crearReferencia` (extraído en T024).

### Within Each User Story

- Tests primero (deben fallar), luego implementación.
- Servicio antes que vista cuando hay dependencia de datos.
- Los parciales `[P]` son archivos nuevos distintos → paralelizables dentro de su historia.

### Parallel Opportunities

- Setup: T001, T002, T003 en paralelo.
- Tests de cada historia (T007–T009, T013–T015, T020–T023) en paralelo (archivos de prueba; coordinar si tocan el mismo archivo de test — usar métodos separados).
- Parciales nuevos (`_referencias`, `_imagenes`, `_agregar-referencia`) son archivos distintos → `[P]`.
- **No** paralelizar ediciones a `IngresoMercanciaService.php`, `UpdateIngresoRequest.php` ni `editar.blade.php` entre historias (mismo archivo).

---

## Parallel Example: User Story 1

```bash
# Tests de US1 (métodos distintos del mismo archivo de prueba; escribir antes de implementar):
Task: "T007 GET editar lista referencias agrupadas por contenedor"
Task: "T008 PUT update baja bl_por_confirmar y conserva referencias"
Task: "T009 ingreso sin referencias muestra 'Sin referencias' y permite guardar"

# Implementación paralelizable:
Task: "T011 Crear parcial _referencias.blade.php"   # archivo nuevo, [P]
# (T010 edit() y T012 include en editar.blade.php no son [P]: tocan archivos compartidos)
```

---

## Implementation Strategy

### MVP First (US1)

1. Phase 1 Setup → 2. Phase 2 Foundational → 3. Phase 3 US1 → **STOP y validar**: referencias visibles + BL confirmable. Desplegable como MVP.

### Incremental Delivery

1. Setup + Foundational → base lista.
2. US1 → validar → demo (MVP: ver referencias + confirmar BL).
3. US2 → validar → demo (imágenes del BL).
4. US3 → validar → demo (agregar referencia).

### Notas

- [P] = archivos distintos, sin dependencias pendientes.
- Sin migraciones: confirmar con `php artisan migrate:status` que no hay pendientes inesperados.
- Commit por tarea o grupo lógico (Conventional Commits: `feat(ingreso): ...`).
- Reutilizar SIEMPRE `HasPhotos`, el patrón de fotos de vaciado y `crearReferencia` (DRY).
