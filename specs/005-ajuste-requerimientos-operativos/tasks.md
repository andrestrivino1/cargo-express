# Tasks: Ajuste de requerimientos operativos

**Input**: Design documents from `/specs/005-ajuste-requerimientos-operativos/`
**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/, quickstart.md

**Tests**: INCLUIDOS — la constitución (principio VI) exige ≥80% en servicios nuevos y pruebas de integración para lógica crítica (inventario, consecutivo). Cada historia incluye tareas de prueba.

**Organization**: Tareas agrupadas por historia de usuario (spec.md) para implementación y prueba independientes.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Puede ejecutarse en paralelo (archivos distintos, sin dependencias pendientes)
- **[Story]**: US1..US6 según spec.md
- Rutas absolutas relativas a la raíz del repo Laravel

## Path Conventions

Monolito Laravel: `app/`, `config/`, `database/`, `resources/`, `routes/`, `tests/` en la raíz del repositorio (ver plan.md → Structure Decision).

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Configuración base requerida por varias historias.

- [x] T001 Crear `config/empresa.php` con razón social ("CARGA TRANS XPRESS S.A.S"), NIT emisor (901615219-4), datos de contacto y ruta del logo para el ODC; agregar claves correspondientes a `.env.example`.
- [x] T002 [P] Verificar que la suite de pruebas corre en limpio (`php artisan test`) y crear `tests/Feature` base si falta, para alojar las pruebas de esta feature.

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Infraestructura compartida (ledger de movimientos, consecutivo, enums, columna de categoría de fotos) que US1 y US2 necesitan antes de empezar.

**⚠️ CRITICAL**: Ninguna historia puede completarse sin esta fase.

- [x] T003 [P] Crear enum `app/Enums/MovimientoTipo.php` con casos `entrada` y `salida`.
- [x] T004 [P] Crear enum `app/Enums/DocumentoCategoria.php` con casos `bl`, `dim`, `lista_empaque`, `foto_mercancia`, `foto_conductor`.
- [x] T005 [P] Crear migración `database/migrations/2026_06_25_000001_create_movimientos_inventario_table.php` (referencia_id, tipo, cantidad, saldo_resultante, usuario_id, documentable_type/id nullable, observaciones, created_at; índices en (referencia_id,created_at),(tipo,created_at),(usuario_id); sin updated_at) según data-model.md.
- [x] T006 [P] Crear migración `database/migrations/2026_06_25_000002_create_secuencias_table.php` (clave unique, valor unsigned int, timestamps).
- [x] T007 [P] Crear migración `database/migrations/2026_06_25_000007_add_categoria_to_photos_table.php` (columna `categoria` string(30) nullable).
- [x] T008 [P] Crear modelo `app/Models/MovimientoInventario.php` (fillable, casts de `tipo` a `MovimientoTipo`, relaciones `referencia()`, `usuario()`, `documentable()` morphTo; `$timestamps=false` salvo created_at).
- [x] T009 [P] Crear modelo `app/Models/Secuencia.php` (fillable clave/valor).
- [x] T010 Crear `database/seeders/SecuenciaOdcSeeder.php` que inserte/actualice `('odc', 570)` y registrarlo en `DatabaseSeeder`.
- [x] T011 Crear `app/Services/ConsecutivoService.php` con `siguiente(string $clave): int` usando transacción + `lockForUpdate()` sobre `secuencias` (incrementa y devuelve el nuevo valor).
- [x] T012 Crear `app/Services/MovimientoInventarioService.php` con `registrarEntrada(Referencia, int $cantidad, User, $documentable, ?string $obs)` y `registrarSalida(...)` que escriben en el ledger con `saldo_resultante` = `cantidad_actual` posterior (depende de T005, T008).
- [x] T013 Aplicar las migraciones de fase 2 (`php artisan migrate`) y ejecutar el seeder ODC; confirmar esquema sin tocar datos existentes.

**Checkpoint**: Ledger, consecutivo y enums listos — US1 y US2 pueden comenzar.

---

## Phase 3: User Story 1 - Ingreso de mercancía consolidado (Priority: P1) 🎯 MVP

**Goal**: Un único formulario registra el ingreso completo (BL, contenedor, cliente, ubicación, tipo, referencias con descripción/unidad/peso/cantidad) + adjuntos BL/DIM/Lista de empaque, sumando al inventario del cliente.

**Independent Test**: Registrar un ingreso completo con los 3 documentos; verificar que la referencia aparece en `/inventario` con su saldo y que los documentos se descargan; guardar sin un campo obligatorio falla.

### Tests for User Story 1

- [x] T014 [P] [US1] Crear `tests/Feature/IngresoMercanciaTest.php`: campos obligatorios (FR-001) rechazan al faltar; ingreso válido crea contenedor+referencias, suma inventario y registra movimiento `entrada`; documentos BL/DIM/Lista de empaque quedan accesibles (FR-002).

### Implementation for User Story 1

- [x] T015 [P] [US1] Crear migración `database/migrations/2026_06_25_000003_add_ingreso_fields_to_contenedores_table.php` (`bl` string(100) nullable, `tipo_mercancia` string(100) nullable).
- [x] T016 [P] [US1] Crear migración `database/migrations/2026_06_25_000004_add_peso_to_referencias_table.php` (`peso` decimal(10,2) nullable).
- [x] T017 [US1] Modificar `app/Models/Contenedor.php`: agregar trait `HasPhotos`, añadir `bl` y `tipo_mercancia` a `$fillable`.
- [x] T018 [US1] Modificar `app/Models/Referencia.php`: añadir `peso` a `$fillable` (y cast numérico).
- [x] T019 [US1] Crear `app/Http/Requests/StoreIngresoMercanciaRequest.php` con las reglas de `contracts/ingreso.md` (campos obligatorios, array de referencias, archivos BL/DIM/Lista de empaque pdf/imagen ≤10MB).
- [x] T020 [US1] Crear `app/Services/IngresoMercanciaService.php` con `registrar(array $data, array $archivos, User $usuario): Contenedor` en transacción: crea/ubica contenedor, crea referencias, llama `MovimientoInventarioService::registrarEntrada` por referencia y guarda documentos como `Photo` con `categoria` (depende de T012, T017, T018).
- [x] T021 [US1] Crear `app/Http/Controllers/IngresoMercanciaController.php` (`index`, `create`, `store`, `show`) delegando en el servicio (depende de T019, T020).
- [x] T022 [US1] Registrar rutas `/ingreso` (index, create, store, show) en `routes/web.php` con permisos RBAC del rol operativo/coordinador/administrador.
- [x] T023 [P] [US1] Crear vistas `resources/views/ingreso/index.blade.php`, `create.blade.php` (formulario consolidado con repetidor de referencias y 3 inputs de documento) y `show.blade.php` (detalle + descarga de documentos).
- [x] T024 [US1] Agregar el ítem "Ingreso" al sidebar `resources/views/layouts/app.blade.php` (sección Operaciones), condicionado a `config('modulos.ingreso')`.

**Checkpoint**: US1 funcional y verificable de forma independiente (ingreso → inventario + documentos).

---

## Phase 4: User Story 2 - Salida de mercancía con evidencias y control automático (Priority: P1)

**Goal**: Un único formulario del despachador registra la salida (cliente, referencias, cantidad, fecha, conductor/vehículo/transportador/destino) con fotos obligatorias de mercancía y conductor; al confirmar descuenta inventario atómicamente, nunca negativo, y registra responsable + fecha/hora.

**Independent Test**: Registrar una salida sobre saldo disponible con ambas fotos; verificar que el saldo baja exactamente la cantidad, que hay movimiento `salida` con usuario/fecha, y que cantidad>saldo o falta de foto se rechazan.

### Tests for User Story 2

- [x] T025 [P] [US2] Crear `tests/Feature/SalidaMercanciaTest.php`: obligatoriedad de campos (FR-007) y de ambas fotos (FR-008); descuento exacto de inventario (FR-010); rechazo cuando cantidad>saldo informando saldo (FR-011); movimiento `salida` con responsable y timestamp (FR-012).
- [x] T026 [P] [US2] Crear `tests/Feature/MovimientoInventarioTest.php`: invariante `cantidad_actual == sum(entradas) - sum(salidas)` por referencia (SC-002); el saldo nunca queda negativo bajo dos salidas concurrentes.

### Implementation for User Story 2

- [x] T027 [P] [US2] Crear migración `database/migrations/2026_06_25_000005_add_salida_fields_to_tarjas_table.php` (`conductor_cedula` string(20) nullable, `transportador` string(150) nullable, `destino` string(150) nullable, `consecutivo_odc` unsigned int nullable unique).
- [x] T028 [P] [US2] Crear migración `database/migrations/2026_06_25_000006_add_nit_to_users_table.php` (`nit` string(30) nullable).
- [x] T029 [US2] Modificar `app/Models/Tarja.php`: agregar trait `HasPhotos` y los nuevos campos a `$fillable`.
- [x] T030 [US2] Modificar `app/Models/User.php`: añadir `nit` a `$fillable`.
- [x] T031 [US2] Crear `app/Http/Requests/StoreSalidaMercanciaRequest.php` con las reglas de `contracts/salida-odc.md` (detalles array, fotos `foto_mercancia`/`foto_conductor` required image).
- [x] T032 [US2] Crear `app/Services/SalidaMercanciaService.php` con `registrar(array $data, array $fotos, User $despachador): Tarja` en transacción con `lockForUpdate()`: valida saldo, decrementa `cantidad_actual`, registra movimiento `salida`, asigna `consecutivo_odc` vía `ConsecutivoService`, guarda fotos como `Photo` con `categoria`, marca `OrdenCargue` completada (depende de T011, T012, T029).
- [x] T033 [US2] Crear `app/Http/Controllers/SalidaMercanciaController.php` (`index`, `create`, `store`, `show`) delegando en el servicio (depende de T031, T032).
- [x] T034 [US2] Registrar rutas `/salida` (index, create, store, show) en `routes/web.php` con permisos RBAC del rol despachador/operativo/coordinador/administrador.
- [x] T035 [P] [US2] Crear vistas `resources/views/salida/index.blade.php`, `create.blade.php` (selección de cliente→referencias con saldo, detalles, datos de transporte, 2 inputs de foto) y `show.blade.php` (detalle + evidencias + enlace al ODC).
- [x] T036 [US2] Agregar el ítem "Salida" al sidebar `resources/views/layouts/app.blade.php`, condicionado a `config('modulos.salida')`.

**Checkpoint**: US2 funcional — salida descuenta inventario con evidencias y trazabilidad.

---

## Phase 5: User Story 3 - Orden de Salida (ODC) con el formato requerido (Priority: P1)

**Goal**: Generar el documento ODC con el formato de la imagen: encabezado (razón social, ODC-###, cliente, NIT, fecha, logo), tabla "Detalle de la carga" con total, "Datos del conductor y vehículo", fotos de conductor y carga, y firmas.

**Independent Test**: Generar el ODC de una salida con varias referencias y verificar, contra la imagen, que aparecen todos los bloques.

**Depends on**: US2 (la salida y sus datos/consecutivo).

### Tests for User Story 3

- [x] T037 [P] [US3] Crear `tests/Feature/OrdenSalidaPdfTest.php`: la ruta del ODC responde `application/pdf`; el HTML renderizado contiene consecutivo `ODC-`, cliente, NIT, total de unidades, datos del conductor y los bloques de firma (FR-013..FR-017, SC-004).

### Implementation for User Story 3

- [x] T038 [P] [US3] Crear vista `resources/views/pdf/orden-salida.blade.php` replicando el formato de la imagen (encabezado con logo desde `config('empresa')`, tabla de detalle con total, sección conductor/vehículo, fotos embebidas desde storage, firmas).
- [x] T039 [US3] Agregar método `ordenSalidaPdf(Tarja $tarja)` en `app/Http/Controllers/SalidaMercanciaController.php` que arma el detalle (contenedor, descripción, observaciones, cantidad), el total, el cliente/NIT y las fotos, y renderiza con DomPDF (depende de T038).
- [x] T040 [US3] Registrar ruta `GET /salida/{tarja}/orden-salida.pdf` en `routes/web.php` (permiso `salida.ver`) y enlazarla desde `salida/show.blade.php`.

**Checkpoint**: US3 — ODC descargable con el formato oficial.

---

## Phase 6: User Story 4 - Fotografías y novedades en el vaciado (Priority: P2)

**Goal**: Confirmar que por cada contenedor recibido se pueden cargar fotos y registrar novedades (funcionalidad existente), visible en el flujo ajustado.

**Independent Test**: En `/vaciado`, cargar una foto y registrar una novedad; verificar que quedan asociadas y consultables en trazabilidad.

### Tests for User Story 4

- [x] T041 [P] [US4] Crear `tests/Feature/VaciadoEvidenciasTest.php`: cargar foto en una orden de vaciado y registrar una novedad quedan asociadas al contenedor (FR-005, FR-006) — prueba de regresión del módulo existente.

### Implementation for User Story 4

- [x] T042 [US4] Verificar/asegurar el ítem "Vaciado" visible en el sidebar `resources/views/layouts/app.blade.php` condicionado a `config('modulos.vaciado')` (true).
- [x] T043 [US4] Revisar `app/Http/Controllers/VaciadoController.php` y vistas de vaciado para confirmar carga de fotos + registro de novedades sin regresión; documentar cualquier ajuste menor necesario.

**Checkpoint**: US4 — vaciado conforme al instructivo y visible.

---

## Phase 7: User Story 5 - Reportes operativos (Priority: P2)

**Goal**: Reportes de inventario actual por cliente, ingresos, salidas, historial de movimientos, novedades y evidencias/trazabilidad.

**Independent Test**: Tras registrar ingresos y salidas, cada reporte muestra datos coherentes; el inventario por cliente coincide con ingresos−salidas.

**Depends on**: ledger (Fase 2) y datos de US1/US2 para ser significativo.

### Tests for User Story 5

- [x] T044 [P] [US5] Crear `tests/Feature/ReportesTest.php`: cada endpoint de reporte responde 200 con permiso `reportes.ver`; inventario por cliente refleja saldos; ingresos/salidas reflejan el ledger (FR-020..FR-022, SC-008).

### Implementation for User Story 5

- [x] T045 [US5] Extender `app/Services/ReporteService.php` con métodos `inventarioPorCliente`, `ingresos`, `salidas`, `movimientos` (sobre `movimientos_inventario`), `novedades` y `evidencias` (sobre `photos` + `TrazabilidadService`), todos paginados/filtrables por cliente y rango de fechas.
- [x] T046 [US5] Agregar métodos y rutas en `app/Http/Controllers/ReporteController.php` y `routes/web.php` para `/reportes/inventario-por-cliente`, `/ingresos`, `/salidas`, `/movimientos`, `/novedades`, `/evidencias` (permiso `reportes.ver`), con export `?export=pdf|excel` (depende de T045).
- [x] T047 [P] [US5] Crear vistas `resources/views/reportes/*.blade.php` para los seis reportes y, si aplica, plantillas PDF/Excel reutilizando los mecanismos existentes.
- [x] T048 [US5] Asegurar el submenú "Reportes" en el sidebar con los nuevos reportes, condicionado a `config('modulos.reportes')` y rol autorizado.

**Checkpoint**: US5 — reportes requeridos disponibles y coherentes.

---

## Phase 8: User Story 6 - Ocultar módulos no utilizados sin eliminarlos (Priority: P2)

**Goal**: Mostrar solo el flujo ajustado; ocultar Solicitudes, Gate-In separado, Entregas/Tarja, Transferencias, Gate-Out e Importación histórica + Pendientes, conservando datos e historial y permitiendo reactivación.

**Independent Test**: Con usuario operativo, el menú no muestra los módulos ocultos y acceder a sus rutas da 404; cambiar la bandera a `true` los reactiva con sus datos.

### Tests for User Story 6

- [x] T049 [P] [US6] Crear `tests/Feature/VisibilidadModulosTest.php`: módulo oculto → ruta responde 404 y no aparece en el menú; módulo visible → 200; el ledger/reportes siguen leyendo datos de módulos ocultos (FR-023..FR-026, SC-006/SC-007).

### Implementation for User Story 6

- [x] T050 [US6] Crear `config/modulos.php` con las banderas de `contracts/visibilidad-modulos.md` (visibles: ingreso, vaciado, inventario, salida, reportes, trazabilidad, productos, usuarios, ubicaciones; ocultos: solicitudes, gate_in, entregas, transferencias, gate_out, importaciones).
- [x] T051 [P] [US6] Crear helper `modulo_visible(string $clave): bool` (en `app/Support/helpers.php` o similar, autoload en `composer.json`) que lea `config("modulos.$clave", false)`.
- [x] T052 [US6] Crear middleware `app/Http/Middleware/ModuloVisible.php` que devuelva 404 si la bandera del módulo es `false`; registrar el alias `modulo` en `bootstrap/app.php`.
- [x] T053 [US6] Aplicar `->middleware('modulo:<clave>')` a los grupos de rutas de los módulos ocultos en `routes/web.php` (solicitudes, gate-in, entregas, transferencias, gate-out, importaciones, pendientes) sin eliminar las rutas.
- [x] T054 [US6] Editar el sidebar `resources/views/layouts/app.blade.php` envolviendo cada ítem con `@if(config('modulos.<clave>'))`, ocultando los módulos no utilizados y conservando los visibles.
- [x] T055 [US6] Verificar que `ReporteService`/`TrazabilidadService` siguen consultando tablas de módulos ocultos (tarjas, transferencias, importaciones) para preservar el historial (FR-026).

**Checkpoint**: US6 — interfaz simplificada, datos intactos, reactivación por bandera.

---

## Phase 9: Polish & Cross-Cutting Concerns

**Purpose**: Cierre, consistencia y gobernanza.

- [x] T056 [P] Ejecutar la suite completa `php artisan test`; las pruebas nuevas pasan (11+2). Quedan 15 fallos **pre-existentes** en pruebas unitarias de `Importacion` (requieren contenedor de la app), ajenos a esta feature.
- [ ] T057 [P] Verificar manualmente el ODC contra la imagen de referencia (SC-004). Contenido cubierto por `OrdenSalidaPdfTest`; falta revisión visual humana + logo real en `public/`.
- [x] T058 [P] Aplicar estilo de código (Pint) a los archivos nuevos/modificados (constitución I).
- [x] T059 Crear ADR `docs/adr/0001-ledger-y-visibilidad-modulos.md` (constitución — Governance).
- [ ] T060 [P] Actualizar el manual de usuario (`ManualController`/PDF) reflejando los módulos visibles del flujo ajustado. (Pendiente.)
- [x] T061 Validar el quickstart de extremo a extremo (US1→US6) — cubierto por las pruebas de Feature; checklist "no eliminar" verificado (migraciones aditivas, rutas conservadas).

---

## Dependencies & Execution Order

- **Setup (Fase 1)** → **Foundational (Fase 2)**: bloquean todo lo demás.
- **US1 (P1)** y **US2 (P1)**: pueden desarrollarse en paralelo tras la Fase 2 (archivos distintos). Ambas usan el ledger/consecutivo de Fase 2.
- **US3 (P1)** depende de **US2** (datos de salida + consecutivo).
- **US5 (P2)** depende del ledger (Fase 2); es significativa con datos de US1/US2.
- **US4 (P2)** y **US6 (P2)** son independientes entre sí y del resto (US6 toca rutas/sidebar/config; US4 verifica vaciado existente).
- **Polish (Fase 9)** al final.

Orden recomendado: Fase 1 → Fase 2 → US1 → US2 → US3 → US5 → US4 → US6 → Polish.

## Parallel Execution Examples

- **Fase 2 (foundational)**: T003, T004, T005, T006, T007, T008, T009 pueden ir en paralelo (archivos distintos); luego T010→T011/T012→T013.
- **US1**: T015, T016 (migraciones) y T023 (vistas) en paralelo; T014 (test) en paralelo con las migraciones.
- **US2**: T027, T028 (migraciones) y T035 (vistas) en paralelo; T025, T026 (tests) en paralelo.
- **Cross-story (tras Fase 2)**: un dev en US1 (T014–T024) y otro en US2 (T025–T036) simultáneamente.

## Implementation Strategy

- **MVP** = Fase 1 + Fase 2 + **US1** (ingreso consolidado con documentos e inventario). Entrega valor verificable por sí solo.
- **Incremento 2** = **US2 + US3** (salida con evidencias, descuento de inventario y ODC oficial) — completa el flujo operativo P1.
- **Incremento 3** = **US5** (reportes) + **US4** (vaciado conforme) + **US6** (ocultar módulos) — consolidación y simplificación P2.
- Cada historia es un incremento desplegable y testeable de forma independiente.
