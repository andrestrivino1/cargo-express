# Tasks: Ajustes a ingreso y vaciado

**Input**: Design documents from `/specs/006-ajustes-ingreso-vaciado/`
**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/, quickstart.md

**Tests**: INCLUIDOS (constitución VI + research D7). Cada historia trae sus pruebas.

**Organization**: Tareas agrupadas por historia de usuario. Construye sobre la feature 005 (módulos Ingreso y Vaciado existentes).

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Puede ir en paralelo (archivos distintos, sin dependencias pendientes)
- **[Story]**: US1..US3 según spec.md
- Rutas relativas a la raíz del repo Laravel

## Path Conventions

Monolito Laravel: `app/`, `database/`, `resources/`, `routes/`, `tests/` en la raíz.

---

## Phase 1: Setup

- [x] T001 Confirmar baseline: el módulo de Ingreso (feature 005) y el de Vaciado existen y la suite corre (`php artisan test --filter=Ingreso`); anotar el estado de partida.

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Tabla padre `ingresos` y su vínculo con `contenedores`. Bloquea US1 y US2.

**⚠️ CRITICAL**: US1 y US2 no pueden completarse sin esta fase.

- [x] T002 [P] Crear migración `database/migrations/2026_06_26_000001_create_ingresos_table.php` (id, bl string(100), cliente_id FK users, fecha_ingreso date, usuario_id FK users, timestamps; índices en cliente_id, fecha_ingreso, bl) según data-model.md.
- [x] T003 [P] Crear migración `database/migrations/2026_06_26_000002_add_ingreso_id_to_contenedores_table.php` (`ingreso_id` FK → ingresos, **nullable**).
- [x] T004 [P] Crear modelo `app/Models/Ingreso.php` con trait `HasPhotos`, `$fillable` (bl, cliente_id, fecha_ingreso, usuario_id), cast `fecha_ingreso => date`, relaciones `cliente()`, `usuario()`, `contenedores()` (HasMany).
- [x] T005 Modificar `app/Models/Contenedor.php`: agregar `ingreso_id` a `$fillable` y relación `ingreso()` (BelongsTo).
- [x] T006 Aplicar migraciones (`php artisan migrate`) y confirmar esquema sin tocar datos existentes.

**Checkpoint**: `ingresos` + `ingreso_id` listos.

---

## Phase 3: User Story 1 - Ingreso de 1 BL con varios contenedores (+ ubicación opcional) (Priority: P1) 🎯 MVP

**Goal**: Un formulario registra un ingreso con un BL (documentos a nivel del BL) y uno o varios contenedores, cada uno con sus referencias (con ubicación opcional); el código de referencia puede repetirse entre contenedores con cantidades independientes.

**Independent Test**: Registrar un ingreso con 1 BL y 2 contenedores (uno con 2 referencias, otro con 1 que repite código), una de ellas sin ubicación; verificar inventario por contenedor, documentos a nivel del BL y la referencia "sin ubicar".

### Tests for User Story 1

- [x] T007 [P] [US1] Crear `tests/Feature/IngresoMultiContenedorTest.php`: ingreso con 1 BL y 2 contenedores crea ambos bajo el mismo `ingreso_id` y suma cada referencia al inventario (FR-001/FR-007); código repetido entre contenedores mantiene cantidades separadas (FR-003); referencia **sin ubicación** queda con `ubicacion_patio_id` null (FR-002a); contenedor sin referencias y números de contenedor duplicados son rechazados (FR-005/FR-006); documentos quedan adjuntos al Ingreso (FR-004).

### Implementation for User Story 1

- [x] T008 [US1] Reescribir `app/Http/Requests/StoreIngresoMercanciaRequest.php` con la estructura anidada de `contracts/ingreso.md`: `bl`, `cliente_id`, `fecha_ingreso`, documentos; `contenedores` array min:1; `contenedores.*.numero` (distinct), `tipo_mercancia`; `contenedores.*.referencias` array min:1 con `codigo`, `descripcion`, `unidad_medida`, `peso`, `cantidad`(min:1) y `ubicacion_patio_id` **nullable**.
- [x] T009 [US1] Reescribir `app/Services/IngresoMercanciaService.php::registrar` para: en transacción, crear `Ingreso` (bl, cliente, fecha, usuario) + guardar los 3 documentos en el Ingreso; por cada contenedor crear `Contenedor(ingreso_id, numero, tipo_mercancia, fecha_ingreso)`; por cada referencia crear `Referencia(..., ubicacion opcional, fecha_ingreso)` y registrar movimiento `entrada` vía `MovimientoInventarioService` (depende de T004, T005).
- [x] T010 [US1] Actualizar `app/Http/Controllers/IngresoMercanciaController.php`: `index` y `show` operan sobre `Ingreso` (con sus contenedores y referencias); `store` arma los documentos y delega en el servicio.
- [x] T011 [US1] Actualizar `routes/web.php`: `GET /ingreso/{ingreso}` (binding a Ingreso) y ajustar `listar`/redirect del store a `ingreso.show` del Ingreso.
- [x] T012 [P] [US1] Reescribir `resources/views/ingreso/create.blade.php` con repetidor de **dos niveles** (contenedores → referencias), campos BL/cliente/documentos arriba y ubicación **opcional** ("— Sin ubicar —") en cada referencia.
- [x] T013 [P] [US1] Actualizar `resources/views/ingreso/index.blade.php` para listar por **Ingreso/BL** (cliente, fecha, nº de contenedores y referencias).
- [x] T014 [P] [US1] Actualizar `resources/views/ingreso/show.blade.php` para mostrar el Ingreso con sus contenedores y, dentro, sus referencias (marcando las "sin ubicar") y los documentos del BL.

**Checkpoint**: US1 funcional — ingreso multi-contenedor con ubicación opcional.

---

## Phase 4: User Story 2 - Fecha de ingreso retroactiva (Priority: P2)

**Goal**: Capturar una fecha de ingreso anterior a hoy; se usa como fecha de la mercancía en inventario y reportes, conservando la marca de creación.

**Independent Test**: Registrar un ingreso con fecha de días atrás y verificar que `referencia.fecha_ingreso` y el reporte de ingresos muestran esa fecha; una fecha futura se rechaza.

### Tests for User Story 2

- [x] T015 [P] [US2] Crear `tests/Feature/IngresoFechaTest.php`: una `fecha_ingreso` anterior a hoy queda en `Ingreso`, `Contenedor` y `Referencia` (FR-008/FR-009); el reporte de ingresos muestra la fecha capturada, no la de creación (FR-009/FR-010); una `fecha_ingreso` **futura** es rechazada (FR-011).

### Implementation for User Story 2

- [x] T016 [US2] Añadir en `StoreIngresoMercanciaRequest` la regla `fecha_ingreso => before_or_equal:today` (rechazo de fecha futura) y el input de fecha en `resources/views/ingreso/create.blade.php` (default hoy, editable a fechas pasadas).
- [x] T017 [US2] Ajustar el **reporte de ingresos** en `app/Services/ReporteService.php` / `app/Http/Controllers/ReporteController.php` para usar `referencia.fecha_ingreso` (fecha capturada) como columna y filtro de fecha, en lugar de `movimientos_inventario.created_at`.

**Checkpoint**: US2 funcional — fecha retroactiva reflejada en inventario y reportes.

---

## Phase 5: User Story 3 - Vaciado con varias fotos (Priority: P3)

**Goal**: Agregar fotos a un vaciado ya creado/en proceso, sumándolas a las existentes.

**Independent Test**: Crear un vaciado con 2 fotos; luego, desde el detalle, agregar otra; verificar que quedan las 3.

### Tests for User Story 3

- [x] T018 [P] [US3] Crear `tests/Feature/VaciadoFotosTest.php`: la creación con varias fotos las guarda todas (FR-012, ya existente); `POST /vaciado/{id}/fotos` agrega fotos a un vaciado existente **sin reemplazar** las previas (FR-013) y todas quedan asociadas y visibles (FR-014).

### Implementation for User Story 3

- [x] T019 [P] [US3] Crear `app/Http/Requests/AgregarFotosVaciadoRequest.php` (`fotos` required array min:1; `fotos.*` image, mimes:jpg,png,webp, max:5120; authorize por permiso de vaciado).
- [x] T020 [US3] Añadir `agregarFotos(OrdenVaciado $orden, array $fotos)` en `app/Services/VaciadoService.php` que llame `guardarFotos($fotos, "vaciado/{$orden->id}/fotos")`.
- [x] T021 [US3] Añadir `agregarFotos` en `app/Http/Controllers/VaciadoController.php` y la ruta `POST /vaciado/{ordenVaciado}/fotos` (name `vaciado.fotos.store`) en `routes/web.php`.
- [x] T022 [P] [US3] Agregar el formulario "Agregar fotos" (input `fotos[]` multiple) en `resources/views/vaciado/show.blade.php`, apuntando a `vaciado.fotos.store`.

**Checkpoint**: US3 funcional — fotos agregables al vaciado.

---

## Phase 6: Polish & Cross-Cutting Concerns

- [x] T023 [P] Verificar compatibilidad: ingresos de un solo contenedor (feature 005) y contenedores con `ingreso_id` NULL siguen consultables en `/ingreso` y reportes sin error (FR-015).
- [x] T024 (Opcional) Crear migración de backfill que cree un `Ingreso` por cada contenedor con `bl` no nulo y sin `ingreso_id`, vinculándolo, según el volumen real en prod.
- [x] T025 [P] Ejecutar `php artisan test` y confirmar que las pruebas nuevas pasan y no hay regresiones en Ingreso/Vaciado.
- [x] T026 [P] Aplicar Pint a los archivos nuevos/modificados (constitución I).
- [x] T027 Preparar el bloque SQL de estas migraciones (`CREATE TABLE ingresos`, `ALTER contenedores ADD ingreso_id`, registrar en `migrations`) para agregarlo al dump de producción según el procedimiento guardado.

---

## Dependencies & Execution Order

- **Setup (T001)** → **Foundational (T002–T006)**: bloquean US1/US2.
- **US1 (P1)** depende de la Fase 2. Es el MVP.
- **US2 (P2)** depende de la Fase 2 y reutiliza el formulario/servicio de US1 (la fecha se captura en US1; US2 endurece validación y ajusta el reporte). Hacer US2 después de US1.
- **US3 (P3)** es **independiente** (módulo Vaciado); puede hacerse en paralelo a US1/US2 tras el Setup.
- **Polish (T023–T027)** al final.

Orden recomendado: Setup → Foundational → US1 → US2 → US3 → Polish.

## Parallel Execution Examples

- **Fase 2**: T002, T003, T004 en paralelo; luego T005 → T006.
- **US1**: T012, T013, T014 (vistas) en paralelo; T007 (test) en paralelo con T008/T009.
- **Cross-story**: US3 (T018–T022) puede avanzar en paralelo con US1/US2 por ser otro módulo.

## Implementation Strategy

- **MVP** = Setup + Foundational + **US1** (ingreso multi-contenedor con ubicación opcional).
- **Incremento 2** = **US2** (fecha retroactiva).
- **Incremento 3** = **US3** (fotos de vaciado) — independiente, se puede adelantar.
- Para producción: agregar las migraciones al dump SQL (T027) con el procedimiento de import-safe ya definido.
