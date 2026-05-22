---

description: "Tasks: Importación de Inventario Histórico desde Excel"
---

# Tasks: Importación de Inventario Histórico desde Excel

**Input**: Design documents from [/specs/002-import-excel-inventory/](.)
**Prerequisites**: [plan.md](./plan.md), [spec.md](./spec.md), [research.md](./research.md), [data-model.md](./data-model.md), [contracts/](./contracts/), [quickstart.md](./quickstart.md)

**Tests**: Incluidos. Justificación: la Constitución (Principio VI) exige cobertura ≥ 80 % en servicios y ≥ 60 % en controladores, y prueba de integración obligatoria para funcionalidad crítica — la importación de inventario califica como crítica (afecta saldo de mercancía real).

**Organization**: Tareas agrupadas por user story. La US3 comparte la misma operación de importación que US2 (decisión Q3=B) pero entrega valor adicional separable.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Puede ejecutarse en paralelo (archivos distintos, sin dependencias en tareas no completadas)
- **[Story]**: A qué user story pertenece la tarea (US1, US2, US3) — solo en fases 3+

## Path Conventions

Aplicación Laravel 12 monolítica. Todos los paths son **relativos a la raíz del repo** `cargo_express/`.

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Crear directorios nuevos, archivo de configuración y disco de almacenamiento para los archivos subidos.

- [X] T001 Crear directorios nuevos: `app/Imports/`, `app/Imports/Sheets/`, `app/Jobs/`, `app/Services/Importacion/`, `app/Traits/`, `resources/views/importacion/`, `resources/views/pendientes/completar/`, `resources/views/auth/` (este último ya puede existir — no sobreescribir), `tests/Feature/Importacion/`, `tests/Feature/Pendientes/`, `tests/Feature/Auth/`, `tests/Unit/Services/Importacion/`
- [X] T002 Crear archivo `config/importacion.php` con las claves `password_generica`, `dominio_placeholder`, `fecha_corte_default`, `origen_default`, `chunk_size`, `max_pares_despacho` según [quickstart.md §Preparación](./quickstart.md#preparación-del-entorno)
- [X] T003 Agregar disco `imports` en `config/filesystems.php` apuntando a `storage_path('app/imports')` con driver `local`, y crear `.gitignore` dentro de `storage/app/imports/` que excluya todo excepto sí mismo
- [X] T004 Agregar entradas en `.env.example` para `IMPORT_PASSWORD_GENERICA` y `IMPORT_DOMINIO_PLACEHOLDER` con valores placeholder explícitos de "cambiar antes de producción"

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Migraciones, modelos base, trait polimórfico, enums y utilidades compartidas. **Sin esto ninguna user story puede empezar.**

**CRITICAL**: Las 5 migraciones deben generarse en el orden definido en [data-model.md §Migration order](./data-model.md#migration-order-delta-sobre-feature-001) — el orden importa porque el paso 5 añade FKs retroactivas.

### Migraciones

- [X] T005 Migración `database/migrations/2026_05_21_100000_add_pending_fields_to_users_table.php` añade columnas `requiere_cambio_password BOOLEAN DEFAULT FALSE`, `email_placeholder BOOLEAN DEFAULT FALSE`, `password_actualizada_at TIMESTAMP NULL`, `import_batch_id_origen BIGINT UNSIGNED NULL` (sin FK aún), e índice parcial en `requiere_cambio_password`
- [X] T006 Migración `database/migrations/2026_05_21_100100_create_import_batches_table.php` con la estructura completa de [data-model.md §1 import_batches](./data-model.md#1-import_batches) incluyendo enums, índices y `JSON resumen`
- [X] T007 Migración `database/migrations/2026_05_21_100200_create_import_row_results_table.php` con FK a `import_batches`, enums `estado` y `tipo`, y los 4 índices listados en [data-model.md §2](./data-model.md#2-import_row_results)
- [X] T008 Migración `database/migrations/2026_05_21_100300_create_import_pending_records_table.php` con columnas polimórficas, `campos_pendientes JSON`, `completado_at`, `completado_por_id` (FK a users) e índices de [data-model.md §3](./data-model.md#3-import_pending_records)
- [X] T009 Migración `database/migrations/2026_05_21_100400_add_import_batch_id_to_operational_tables.php` añade columna `import_batch_id` (FK nullable → import_batches) a las 5 tablas `solicitudes`, `ordenes_servicio`, `contenedores`, `ordenes_cargue`, `tarjas`, **y** añade la FK retroactiva `users.import_batch_id_origen → import_batches.id`
- [ ] T010 Ejecutar `php artisan migrate` localmente y verificar `php artisan migrate:status` muestra las 5 migraciones aplicadas; documentar el comando de rollback en un comentario en cada migración

### Enums (parallelizables — archivos distintos)

- [X] T011 [P] Crear `app/Enums/ImportEstado.php` con casos `Pendiente`, `Procesando`, `Completado`, `Fallido`, `Cancelado` (backed string)
- [X] T012 [P] Crear `app/Enums/ImportRowEstado.php` con casos `Importado`, `Error`, `Advertencia`, `Ignorado`
- [X] T013 [P] Crear `app/Enums/OrigenImportacion.php` con caso inicial `CargaHistorica27_02_2026` (backed string `'carga_historica_27_02_2026'`)
- [X] T014 [P] Crear `app/Enums/PendingFieldCatalog.php` exponiendo método estático `forType(string $morphClass): array` que devuelve el catálogo de campos válidos por entidad, según [contracts/pending-fields.md](./contracts/pending-fields.md#por-entidad). Lanzar `PendingFieldNotInCatalogException` si se consulta una clave fuera de catálogo

### Modelos y trait base

- [X] T015 [P] Crear `app/Models/ImportBatch.php` con casts (`modo`, `estado` ⇒ enums; `resumen` ⇒ array; `dry_run` ⇒ bool; `started_at`/`finished_at` ⇒ datetime), relaciones `hasMany(ImportRowResult::class)`, `hasMany(ImportPendingRecord::class)`, `belongsTo(User::class, 'usuario_id')`
- [X] T016 [P] Crear `app/Models/ImportRowResult.php` con cast `payload_original ⇒ array`, relaciones `belongsTo(ImportBatch::class)`, `belongsTo(Referencia::class)`, `belongsTo(Contenedor::class)`, `belongsTo(User::class, 'user_cliente_id')`
- [X] T017 [P] Crear `app/Models/ImportPendingRecord.php` con `morphTo('pendienteable')`, cast `campos_pendientes ⇒ array`, scope `vivos()` (where `completado_at IS NULL`), scope `paraTipo(string)`, método `completar(array $datos, User $por): void` que valida claves contra `PendingFieldCatalog` y actualiza `completado_at`+`completado_por_id`
- [X] T018 Crear `app/Traits/HasImportPendingFields.php` con: relación `morphOne(ImportPendingRecord::class, 'pendienteable')` filtrada por `vivos()`, método `tienePendientesImportacion(): bool`, método `camposPendientesImportacion(): array`, método estático `bootHasImportPendingFields()` que registra un observer mínimo (sin lógica aún — gancho para US2/US3)
- [X] T019 [P] Modificar `app/Models/User.php`: añadir a `$casts` los campos `requiere_cambio_password ⇒ bool`, `email_placeholder ⇒ bool`, `password_actualizada_at ⇒ datetime`; añadir a `$fillable` los 4 campos nuevos (incluyendo `import_batch_id_origen`); usar trait `HasImportPendingFields`; relación `belongsTo(ImportBatch::class, 'import_batch_id_origen')`
- [X] T020 [P] Modificar `app/Models/Solicitud.php` añadiendo `import_batch_id` a fillable, relación `belongsTo(ImportBatch::class)`, y `use HasImportPendingFields`
- [X] T021 [P] Modificar `app/Models/OrdenServicio.php` idéntico patrón a T020
- [X] T022 [P] Modificar `app/Models/Contenedor.php` idéntico patrón a T020
- [X] T023 [P] Modificar `app/Models/OrdenCargue.php` idéntico patrón a T020
- [X] T024 [P] Modificar `app/Models/Tarja.php` idéntico patrón a T020

### Utilidades compartidas (parser fechas, header resolver)

- [X] T025 [P] Crear `app/Services/Importacion/DateParser.php` con método estático `parse(string|int|null $value): ?\Carbon\CarbonImmutable` que intenta en orden: entero serial Excel vía `\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject`, luego los formatos `d/m/Y`, `d/m/y`, `d-m-Y`, `d-m-y`, `Y-m-d`. Devuelve `null` si todos fallan
- [X] T026 [P] Crear `app/Services/Importacion/ExcelHeaderResolver.php` con clase `HeaderMap` (DTO interno) y método `resolve(array $primeraFila): HeaderMap`. Reconoce los aliases listados en [contracts/excel-schema.md §Columnas canónicas](./contracts/excel-schema.md#columnas-canónicas-y-aliases) y los pares despacho según las reglas allí descritas. Devuelve qué columnas obligatorias faltan
- [X] T027 [P] Crear `app/Services/Importacion/UbicacionResolver.php` con regex de normalización descrito en [contracts/excel-schema.md §Normalizaciones](./contracts/excel-schema.md#normalizaciones-aplicadas-al-mapear-a-entidades). Método `resolverONormalizar(string $raw): array{modulo:string, posicion:string, normalizada:bool}` — no toca BD aún (lo hace US1 con caché en memoria, US2 con persistencia)
- [X] T028 [P] Crear excepciones tipadas en `app/Exceptions/Importacion/`: `PendingFieldNotInCatalogException.php`, `HojaSinColumnasRequeridasException.php`, `ImportacionFallidaException.php`

### Notification base

- [X] T029 [P] Crear `app/Notifications/ImportacionFinalizada.php` (canales `database` + `mail`) con constructor `__construct(public ImportBatch $batch)` y método `toArray()` que produce el payload descrito en [contracts/http-routes.md §Notification](./contracts/http-routes.md#notification-contract-importacionfinalizada)

**Checkpoint Foundational**: BD migrada, modelos base + trait + enums creados, utilidades de parseo listas. Las user stories pueden empezar en paralelo.

---

## Phase 3: User Story 1 - Diagnóstico de compatibilidad del Excel (Priority: P1) 🎯 MVP

**Goal**: Subir el archivo real en modo `validar` y producir un reporte completo sin escribir nada operativo en BD (solo `import_batches` y `import_row_results`).

**Independent Test**: Como administrador, subir `INVENTARIO TOTAL CONTROLCARGA 27022026.xlsx` desde `/admin/importaciones`, escoger modo "Validar", esperar < 3 min, ver reporte con conteos por hoja, clientes a auto-crear, hojas ignoradas, filas en error clasificadas, descargar reporte en Excel y filas-erróneas en Excel. **Cero filas creadas** en tablas operativas (`SELECT COUNT(*) FROM contenedores` antes y después: idéntico).

### Tests for User Story 1

> Escribir primero, deben FALLAR antes de implementar.

- [X] T030 [P] [US1] Crear fixture sintético `tests/Fixtures/inventario_minimo.xlsx` con 3 hojas (1 vacía, 1 estándar, 1 con columna en blanco al inicio), 50 filas que cubren los casos de [research.md §R11](./research.md#r11-tests-sobre-el-archivo-real--20k-filas). Si no es posible generar el binario, documentar en `tests/Fixtures/README.md` el script de generación con PhpSpreadsheet
- [X] T031 [P] [US1] Test unitario `tests/Unit/Services/Importacion/DateParserTest.php` cubriendo: serial Excel entero, `9/4/2026`, `13/02/2026`, `15-03-2026`, `2/05/2026`, valor vacío, valor basura (`'XX'`) — espera null en los dos últimos
- [X] T032 [P] [US1] Test unitario `tests/Unit/Services/Importacion/ExcelHeaderResolverTest.php` cubriendo: header estándar, header sin columna `Mercancia`, header con columna inicial en blanco, header con 6 pares de despacho, header faltando `cliente` (debe reportar columna requerida ausente)
- [X] T033 [P] [US1] Test unitario `tests/Unit/Services/Importacion/UbicacionResolverTest.php` para entradas `Modulo6 Bloque B`, `Modulo 2 Bloque A`, `Modulo 3-Bloque C`, `Modulo 2-Bloque A`, texto no reconocido (espera `normalizada=false`)
- [X] T034 [P] [US1] Test unitario `tests/Unit/Services/Importacion/RowValidatorTest.php` para cada regla bloqueante y advertencia de [contracts/excel-schema.md §Reglas por fila](./contracts/excel-schema.md#reglas-por-fila-importable)
- [X] T035 [P] [US1] Test unitario `tests/Unit/Services/Importacion/ClienteResolverTest.php` modo `validar` (caché en memoria) — verifica que dos llamadas con el mismo nombre devuelven el "mismo" cliente sintético sin tocar BD
- [X] T036 [US1] Test de integración `tests/Feature/Importacion/ValidarExcelTest.php` que: autentica un administrador, POSTea el fixture a `/admin/importaciones` con `modo=validar`, espera 202, despacha el job sincrónicamente (`Queue::fake` + `Bus::dispatchSync`), verifica que `import_batches` tiene 1 fila con `estado=completado` y `dry_run=true`, que `import_row_results` tiene N filas con los estados esperados, y que **no se creó ningún `Contenedor`, `Referencia`, `User`**

### Implementation for User Story 1

- [X] T037 [P] [US1] `app/Services/Importacion/RowValidator.php` con método `validar(array $fila, HeaderMap $headers): RowValidationResult` aplicando las reglas de [contracts/excel-schema.md §Reglas por fila](./contracts/excel-schema.md#reglas-por-fila-importable). Devuelve estado + tipo + mensaje + lista de advertencias
- [X] T038 [P] [US1] `app/Services/Importacion/ClienteResolver.php` con constructor `__construct(bool $modoCacheMemoria)`. En modo memoria mantiene `Collection` propia keyed por nombre normalizado; en modo persistente busca/crea en `users`. Método `resolver(string $nombreCliente): User` (devuelve un User incluso en modo memoria, pero con `exists=false`)
- [X] T039 [P] [US1] `app/Services/Importacion/ImportReportBuilder.php` con métodos `registrarFila(ImportBatch, hoja, fila_excel, estado, tipo, mensaje, payload)`, `registrarHojaIgnorada(...)`, `consolidar(ImportBatch): void` (calcula contadores y los persiste en el batch + `resumen JSON`)
- [X] T040 [US1] `app/Imports/Sheets/ClienteSheetImport.php` (Maatwebsite — implementa `ToCollection`, `WithHeadingRow=null` (porque resolvemos header manualmente), `WithChunkReading` chunk = 500). Constructor recibe `InventarioImportService` + `ImportBatch` + nombre de hoja. Itera y delega cada fila al servicio
- [X] T041 [US1] `app/Imports/InventarioHistoricoImport.php` implementa `WithMultipleSheets` y devuelve un `ClienteSheetImport` por hoja procesable, ignorando `Hoja1` y hojas que empiezan con `Copia de` (registra `HOJA_VACIA`/`HOJA_COPIA` vía el ReportBuilder antes de delegar)
- [X] T042 [US1] `app/Services/Importacion/InventarioImportService.php` — orquestador. Método `procesar(ImportBatch $batch): void`. En esta tarea implementar **solo la rama `dry_run`**: carga archivo, instancia resolvers en modo memoria, recorre hojas via `InventarioHistoricoImport`, llama RowValidator y registra resultados; al final consolida y dispara la notification. Sin escritura en tablas operativas
- [X] T043 [US1] `app/Jobs/ProcesarImportacionInventario.php` queued job (`implements ShouldQueue`, `use Queueable`). Constructor `__construct(public int $importBatchId)`. `handle(InventarioImportService $service)` carga el batch, marca `estado=procesando`+`started_at`, delega al servicio, marca `estado=completado`+`finished_at`. Captura excepciones y marca `estado=fallido`+`error_mensaje`
- [X] T044 [US1] `app/Http/Requests/Importacion/SubirImportacionRequest.php` con reglas de [contracts/http-routes.md §POST /admin/importaciones](./contracts/http-routes.md#post-adminimportaciones). En `prepareForValidation` calcula y guarda en el request el `archivo_hash` (sha256 del UploadedFile)
- [X] T045 [US1] `app/Http/Controllers/ImportacionInventarioController.php` métodos `index` (listado paginado), `store` (recibe SubirImportacionRequest, mueve archivo a `storage/app/imports/{uuid}.xlsx`, crea ImportBatch, despacha el job, redirige al show), `show` (devuelve vista o JSON según `Accept`), `cancelar` (solo permitido si `estado=pendiente`)
- [X] T046 [P] [US1] `app/Exports/ReporteValidacionExport.php` (Maatwebsite — implementa `WithMultipleSheets` con hojas "Resumen", "Por hoja", "Errores", "Advertencias", "Clientes a auto-crear", "Hojas ignoradas")
- [X] T047 [P] [US1] `app/Exports/FilasErroneasExport.php` que extrae cada `ImportRowResult.payload_original` con `estado=error` y produce un Excel que replica las columnas del original más una columna `_motivo` al final (SC-005)
- [X] T048 [US1] `resources/views/importacion/index.blade.php` con tabla de batches recientes y botón "Nueva importación"
- [X] T049 [US1] `resources/views/importacion/_form_subida.blade.php` partial con inputs `archivo`, `modo` (radio), `politica_duplicados` (radio, oculto si `modo=validar`), `fecha_corte`, `confirmar_clientes_autocreados` (checkbox, condicional). Usar Bootstrap 5
- [X] T050 [US1] `resources/views/importacion/reporte.blade.php` con todas las secciones de [contracts/http-routes.md §GET /admin/importaciones/{batch}](./contracts/http-routes.md#get-adminimportacionesbatch). Incluir polling JS (fetch cada 3 s) mientras `estado in (pendiente, procesando)`; al cambiar a `completado`, recargar
- [X] T051 [US1] Registrar rutas en `routes/web.php` bajo prefix `/admin/importaciones`, name prefix `importaciones.`, middleware `auth` + `role:administrador|coordinador`: `index`, `store`, `show`, `cancelar`, `reporte.xlsx`, `reporte.pdf`, `errores.xlsx`
- [X] T052 [US1] Generar PDF del reporte (`reportePdf` en el controller) usando DomPDF + vista `resources/views/importacion/_reporte_pdf.blade.php` (estilizada para impresión)

**Checkpoint US1**: la prueba de humo manual de [quickstart.md §Validar el archivo real](./quickstart.md#flujo-paso-a-paso-validar-el-archivo-real) pasa con el archivo real. **MVP demoable.**

---

## Phase 4: User Story 2 - Importación efectiva + clientes auto-creados + primer login forzado + completado de Contenedor/OrdenServicio (Priority: P2)

**Goal**: Modo `importar` que persiste contenedores, referencias, ubicaciones, clientes auto-creados con password genérica; bloquear acciones operativas hasta completar campos `PENDIENTE_HISTORICO`; forzar cambio de password y email en primer login del cliente.

**Independent Test**: Tras un dry-run sin errores, confirmar importación. Verificar en BD: contenedores creados con `import_batch_id` no nulo, cada uno con `ImportPendingRecord` vivo, referencias con `cantidad_actual` = `Inventario físico` del Excel. Login con un cliente auto-creado: debe forzar cambio de password y luego de email. Completar un Contenedor pendiente: el formulario aparece, al guardar el `ImportPendingRecord` se marca `completado_at` y se permite continuar al detalle.

### Tests for User Story 2

- [X] T053 [P] [US2] Test integración `tests/Feature/Importacion/ImportarExcelTest.php`: dispara import en modo persist, verifica cantidad de `Contenedor`/`Referencia` creados, valida que `cantidad_actual = inventario_fisico_excel` (sin recálculo, FR-030), verifica que cada cliente nuevo tiene `requiere_cambio_password=true`+`email_placeholder=true`
- [X] T054 [P] [US2] Test integración idempotencia: re-disparar el mismo import con `politica=omitir` no crea nada nuevo; con `politica=actualizar_saldo` actualiza solo `cantidad_actual`; con `politica=abortar` retorna error y no crea nada
- [X] T055 [P] [US2] Test integración conflicto: fixture con el mismo número de contenedor en 2 hojas distintas — verifica `tipo=CONTENEDOR_CONFLICTO_CLIENTE` en `import_row_results` y que **ninguna** de las dos filas se importa
- [X] T056 [P] [US2] Test `tests/Feature/Auth/PrimerLoginForzadoTest.php`: login con cliente auto-creado, cualquier ruta redirige a `/primer-login/password`; tras cambiar password redirige a `/primer-login/email`; tras actualizar email accede normal
- [X] T057 [P] [US2] Test `tests/Feature/Pendientes/CompletarRegistroTest.php` para `Contenedor` y `OrdenServicio`: GET formulario muestra solo los campos `PENDIENTE_HISTORICO`, POST inválido devuelve 422 con errores, POST válido marca `completado_at` y redirige
- [X] T058 [P] [US2] Test policy: con `Contenedor` con pendiente vivo, intentar gate-in vía `GateInController` debe redirigir al formulario de completado en vez de procesar

### Implementation for User Story 2

- [X] T059 [P] [US2] `app/Services/Importacion/PendingFieldsRegistrar.php` con método `registrar(Model $modelo, array $campos, ImportBatch $batch, int $prioridad = 50): ImportPendingRecord`. Valida los campos contra `PendingFieldCatalog::forType(get_class($modelo))`
- [X] T060 [US2] `app/Services/Importacion/ContenedorResolver.php` con método `crearContenedorHistorico(User $cliente, string $numeroNormalizado, array $datosFila, ImportBatch $batch): Contenedor`. Crea `Solicitud` sintética, `OrdenServicio` sintética, `Contenedor`, y para cada uno llama `PendingFieldsRegistrar` con los campos listados en [contracts/excel-schema.md §Campos pendientes generados](./contracts/excel-schema.md#campos-pendientes-generados-por-la-importación)
- [X] T061 [US2] `app/Services/Importacion/ReferenciaMapper.php` con método `crear(Contenedor $c, User $cliente, UbicacionPatio $ubicacion, array $fila, ImportBatch $batch): Referencia`. Setea `cantidad_actual = (int) $fila['inventario_fisico']` **sin recálculo** (FR-030). Setea `fecha_ingreso = DateParser::parse($fila['fecha_deposito'])`
- [X] T062 [US2] Extender `ClienteResolver` (de T038) con rama `modoCacheMemoria=false`: al no encontrar User, lo crea con `email = Str::slug($nombre).'@'.config('importacion.dominio_placeholder')`, `password = bcrypt(config('importacion.password_generica'))`, `requiere_cambio_password=true`, `email_placeholder=true`, `import_batch_id_origen=$batch->id`, asigna rol `cliente` vía Spatie
- [X] T063 [US2] Extender `InventarioImportService` (de T042) con rama `modo=importar`: pre-pass que detecta `CONTENEDOR_CONFLICTO_CLIENTE` agrupando todas las filas por número antes de procesar; usa resolvers en modo persistente; envuelve cada hoja en `DB::transaction()`; respeta `politica_duplicados` del batch
- [X] T064 [US2] `app/Http/Middleware/ForzarCambioPasswordYEmail.php` con la lógica del [contracts/http-routes.md §Middleware](./contracts/http-routes.md#middleware-contract-primer_login). Registrar alias `primer_login` en `bootstrap/app.php`
- [X] T065 [US2] `app/Http/Controllers/Auth/PrimerLoginController.php` con métodos `password` (GET form), `actualizarPassword` (POST), `email` (GET form), `actualizarEmail` (POST). Validación de password con `Password::defaults()->mixedCase()->numbers()->symbols()`
- [X] T066 [US2] `resources/views/auth/primer-login-password.blade.php` y `resources/views/auth/primer-login-email.blade.php` con forms simples Bootstrap, mensajes de bienvenida y aviso del por qué del cambio forzado
- [X] T067 [US2] Registrar rutas `/primer-login/password` (GET+POST) y `/primer-login/email` (GET+POST) en `routes/web.php` con middleware `auth` (sin `primer_login` para no loopear)
- [X] T068 [US2] Aplicar middleware `primer_login` al grupo de rutas autenticadas existentes en `routes/web.php` (las heredadas de feature 001) — verificar que la ruta de logout queda excluida explícitamente
- [X] T069 [US2] `app/Http/Controllers/PendientesCompletarController.php` con métodos `index(Request)` (paginado con filtros `tipo`, `import_batch_id`), `editar(string $type, int $id)`, `actualizar(string $type, int $id, Request)`. El método `editar` resuelve el `morphClass` desde `$type` (`contenedor` ⇒ `Contenedor::class`, etc.) usando un mapa autoritativo en el propio controlador
- [X] T070 [P] [US2] `app/Http/Requests/Pendientes/CompletarContenedorRequest.php` con reglas de [contracts/pending-fields.md §Contenedor](./contracts/pending-fields.md#appmodelscontenedor)
- [X] T071 [P] [US2] `app/Http/Requests/Pendientes/CompletarOrdenServicioRequest.php` con reglas de [contracts/pending-fields.md §OrdenServicio](./contracts/pending-fields.md#appmodelsordenservicio)
- [X] T072 [P] [US2] `app/Http/Requests/Pendientes/CompletarSolicitudRequest.php` con reglas de [contracts/pending-fields.md §Solicitud](./contracts/pending-fields.md#appmodelssolicitud)
- [X] T073 [US2] `resources/views/pendientes/index.blade.php` con tabla paginada, chips de campos pendientes, filtros y botón "Completar" por fila
- [X] T074 [P] [US2] `resources/views/pendientes/completar/contenedor.blade.php` con form para los campos `placa_vehiculo`, `tipo`, `destino_salida`
- [X] T075 [P] [US2] `resources/views/pendientes/completar/orden-servicio.blade.php` con form para `vehiculo`, `conductor`, `conductor_documento`, `cita_puerto`
- [X] T076 [P] [US2] `resources/views/pendientes/completar/solicitud.blade.php` con form para `naviera`, `puerto_origen`, `descripcion`
- [X] T077 [P] [US2] `resources/views/pendientes/completar/_campos_comunes.blade.php` partial con la cabecera de contexto (archivo origen, fila Excel, batch) reutilizable por todos los formularios
- [X] T078 [US2] `app/Policies/ContenedorPolicy.php` (crearla o extenderla si ya existe en feature 001) — método `programarVaciado`, `gateIn`, `gateOut` devuelven `false` cuando `$contenedor->tienePendientesImportacion()` es true. Los controllers afectados (`GateInController`, `GateOutController`, `VaciadoController`) usan `authorize` y al fallar redirigen a `/pendientes/contenedor/{id}/completar`
- [X] T079 [US2] `app/Policies/OrdenServicioPolicy.php` análogo — bloquear creación de gate events si hay pendientes vivos
- [X] T080 [US2] Registrar las rutas de Pendientes en `routes/web.php`: `GET /pendientes`, `GET /pendientes/{type}/{id}/completar`, `POST /pendientes/{type}/{id}/completar` con `auth` + `primer_login`

**Checkpoint US2**: BD limpia ⇒ subir Excel real ⇒ validar ⇒ importar ⇒ verificar conteos ⇒ login con cliente auto-creado y completar el flujo de primer-login ⇒ abrir un contenedor pendiente y completar sus campos ⇒ ahora sí permitir gate-in. Todo el flujo de [quickstart.md](./quickstart.md) hasta esa sección pasa.

---

## Phase 5: User Story 3 - Historial de despachos retroactivos + completado de Tarja/OrdenCargue (Priority: P2)

**Goal**: En la misma operación de import, persistir los pares (FECHA DE DESPACHO, DESPACHO) como `OrdenCargue` + `Tarja` + `TarjaDetalle` retroactivas; bloquear cerrar/imprimir tarja hasta diligenciar `PENDIENTE_HISTORICO`.

**Independent Test**: Tras importar, verificar que `tarjas` tiene N filas con `import_batch_id` no nulo y cada una con `ImportPendingRecord` vivo. Verificar que `cantidad_actual` de la Referencia sigue siendo el `Inventario físico` del Excel y NO se descuenta por el historial (FR-030). Abrir una tarja retroactiva: el sistema pide despachador, vehículo, conductor, observaciones; tras completar, permite imprimir/cerrar.

### Tests for User Story 3

- [X] T081 [P] [US3] Test `tests/Feature/Importacion/HistorialDespachosTest.php`: import con fixture que tiene una fila con 3 pares de despacho rellenos — verifica que se crearon 3 `Tarja`+`TarjaDetalle` retroactivas con `fecha_despacho` del Excel y `import_batch_id` del batch; verifica que `cantidad_actual` de la `Referencia` NO se modificó por crear los TarjaDetalle (FR-030)
- [X] T082 [P] [US3] Test pares incompletos: fila con par `(fecha, '')` o `('', cantidad)` — verifica que se registra `tipo=DESPACHO_INCOMPLETO` como `advertencia` y la fila igual se importa con los pares válidos
- [X] T083 [P] [US3] Test advertencia saldo inconsistente: fila con `unidad − Σ despachos ≠ inventario_fisico` — verifica advertencia `SALDO_INCONSISTENTE` y que `cantidad_actual` se persiste como el `inventario_fisico` literal
- [X] T084 [P] [US3] Test `tests/Feature/Pendientes/CompletarRegistroTest.php` extendido para `Tarja` y `OrdenCargue` (puede vivir en el mismo archivo de T057 — añadir métodos `test_completar_tarja` y `test_completar_orden_cargue`)
- [X] T085 [P] [US3] Test policy: con `Tarja` con pendiente vivo, intentar imprimirla vía `TarjaController` debe redirigir al formulario de completado

### Implementation for User Story 3

- [X] T086 [P] [US3] `app/Services/Importacion/HistorialDespachoMapper.php` con método `crearHistorial(Referencia $ref, User $cliente, Contenedor $cont, UbicacionPatio $ubic, array $pares, ImportBatch $batch): array` (retorna lista de Tarjas creadas). Para cada par válido: crea `OrdenCargue` (con `despachador_id=null`, marca pendientes), `Tarja` (idem), `TarjaDetalle` apuntando a la Referencia. **No** descuenta `cantidad_actual`. Pares inválidos los reporta vía `ImportReportBuilder` con tipo `DESPACHO_INCOMPLETO`
- [X] T087 [US3] Wire en `InventarioImportService` (modo importar): después de crear Referencia, invocar `HistorialDespachoMapper::crearHistorial` con los pares extraídos por `ExcelHeaderResolver`. Sumar a contador `despachos_historicos_creados` del batch
- [X] T088 [US3] Wire de advertencia `SALDO_INCONSISTENTE` en `RowValidator` (de T037 — extender para incluir esta regla no bloqueante)
- [X] T089 [P] [US3] `app/Http/Requests/Pendientes/CompletarTarjaRequest.php` con reglas de [contracts/pending-fields.md §Tarja](./contracts/pending-fields.md#appmodelstarja)
- [X] T090 [P] [US3] `app/Http/Requests/Pendientes/CompletarOrdenCargueRequest.php` con reglas de [contracts/pending-fields.md §OrdenCargue](./contracts/pending-fields.md#appmodelsordencargue)
- [X] T091 [P] [US3] `resources/views/pendientes/completar/tarja.blade.php` con form para `despachador_id` (select), `observaciones`, `vehiculo`, `conductor`
- [X] T092 [P] [US3] `resources/views/pendientes/completar/orden-cargue.blade.php` con form para `despachador_id` y `notas`
- [X] T093 [US3] Extender el mapa de tipos en `PendientesCompletarController` (de T069) para incluir `tarja ⇒ Tarja::class` y `orden-cargue ⇒ OrdenCargue::class`, y enrutar a los FormRequests respectivos
- [X] T094 [US3] `app/Policies/TarjaPolicy.php` y `app/Policies/OrdenCarguePolicy.php` — bloquear acciones `imprimir`, `cerrar`, `procesar` si hay pendiente vivo. Los controllers `TarjaController`, `EntregaController` autorizan y redirigen al completar

**Checkpoint US3**: Toda la importación queda completa con saldo + historial; pantallas operativas de tarjas y órdenes de cargue piden completar antes de operar. La prueba de humo manual completa de [quickstart.md](./quickstart.md) pasa de inicio a fin.

---

## Phase 6: Polish & Cross-Cutting Concerns

- [X] T095 Crear ADR `docs/adr/0002-import-pending-records-polimorfico.md` documentando la decisión de R3 (tabla polimórfica vs sentinel en columna vs ENUM por tabla) según Constitución §Governance
- [X] T096 [P] Crear factory `database/factories/ImportBatchFactory.php` para uso en tests (ya referenciado por T053 y siguientes)
- [X] T097 [P] Ejecutar Pint sobre todo lo nuevo: `./vendor/bin/pint app/Services/Importacion app/Imports app/Jobs app/Models/Import*.php app/Traits app/Http/Controllers/ImportacionInventarioController.php app/Http/Controllers/PendientesCompletarController.php app/Http/Middleware/ForzarCambioPasswordYEmail.php`
- [ ] T098 Ejecutar `php artisan test --filter=Importacion` y `--filter=Pendientes` y `--filter=PrimerLogin` y confirmar cobertura ≥ 80 % en `app/Services/Importacion/` y ≥ 60 % en los controllers nuevos (Constitución §VI)
- [ ] T099 Ejecutar la prueba de humo manual de [quickstart.md §Prueba de humo manual con el archivo real](./quickstart.md#prueba-de-humo-manual-con-el-archivo-real) y registrar los conteos finales obtenidos
- [ ] T100 Confirmar SC-001 (≤ 3 min validación del archivo real) midiendo `started_at`-`finished_at` del batch real; si excede, ajustar `chunk_size` en `config/importacion.php`
- [X] T101 [P] Agregar enlace en el menú lateral / dashboard del admin a `/admin/importaciones` y `/pendientes` (modificar la vista de layout principal — `resources/views/layouts/app.blade.php` o equivalente — sin tocar las features 001 existentes)
- [ ] T102 [P] Auditar permisos Spatie: confirmar que `role:administrador|coordinador` está aplicado a importaciones; que `/pendientes` se filtra por policy según rol; que la password genérica no es la del `.env.example` en el ambiente real
- [ ] T103 Limpieza: eliminar el fixture binario si el repositorio decide no versionar `.xlsx`; en ese caso dejar **solo** el script de generación en `tests/Fixtures/README.md`

---

## Dependencies & Execution Order

### Phase Dependencies

- **Phase 1 (Setup)**: sin dependencias — puede empezar de inmediato
- **Phase 2 (Foundational)**: requiere Phase 1; **bloquea** todas las user stories
- **Phase 3 (US1)**: requiere Phase 2
- **Phase 4 (US2)**: requiere Phase 2; reutiliza servicios de US1 (resolvers, job, controller, vistas) pero **extiende** sin reemplazar — US1 sigue funcional durante US2
- **Phase 5 (US3)**: requiere Phase 2 y la parte de "persistencia operativa" de US2 (Contenedor + Referencia ya creados); puede empezar **en paralelo** con la parte de US2 que cubre primer-login + pendientes de Contenedor/OrdenServicio
- **Phase 6 (Polish)**: requiere todas las stories deseadas completas

### User Story Dependencies

- **US1 (P1)**: independiente — entrega valor sola (puede ir a producción como herramienta de diagnóstico aunque US2/US3 no estén)
- **US2 (P2)**: depende de US1 conceptualmente (el operador validó antes de importar) pero **técnicamente** puede ir sola si el usuario acepta el riesgo
- **US3 (P2)**: técnicamente parte del mismo job que US2 — si US2 está, agregar US3 es solo añadir el `HistorialDespachoMapper` al flujo

### Within Each User Story

- Tests primero (T030–T036 para US1, T053–T058 para US2, T081–T085 para US3) — escribirlos y verificar que FALLAN antes de implementar
- Modelos/Enums/Trait (Phase 2) antes de Servicios
- Servicios antes de Jobs/Controllers
- Controllers antes de Vistas (las vistas se ajustan al payload del controller)
- Policies/Middleware después del flujo principal (porque bloquean caminos ya operativos)

### Parallel Opportunities

- **Setup**: T001–T004 son independientes (todos [P]-eligibles)
- **Foundational**: T011–T014 (enums) en paralelo entre sí, T015–T017 (modelos Import*) en paralelo, T019–T024 (modificaciones a modelos existentes) en paralelo entre sí pero **después** de T018 (trait)
- **US1**: T030–T036 (tests) en paralelo; T037 + T038 + T039 + T046 + T047 en paralelo entre sí
- **US2**: T053–T058 (tests) en paralelo; T070–T072 (FormRequests) y T074–T077 (vistas pendientes) en paralelo
- **US3**: T081–T085 en paralelo; T089–T092 en paralelo

---

## Parallel Example: User Story 1

```bash
# Lanzar todos los tests de US1 en paralelo:
Task: "Crear fixture sintético en tests/Fixtures/inventario_minimo.xlsx"
Task: "DateParserTest.php"
Task: "ExcelHeaderResolverTest.php"
Task: "UbicacionResolverTest.php"
Task: "RowValidatorTest.php"
Task: "ClienteResolverTest.php"

# Lanzar los servicios "stateless" de US1 en paralelo:
Task: "RowValidator.php"
Task: "ClienteResolver.php (modo memoria)"
Task: "ImportReportBuilder.php"
Task: "ReporteValidacionExport.php"
Task: "FilasErroneasExport.php"
```

---

## Implementation Strategy

### MVP First (User Story 1 only)

1. Phase 1 (Setup) — un día
2. Phase 2 (Foundational completa) — el catálogo polimórfico y la modificación de 6 modelos es la parte más cuidadosa
3. Phase 3 (US1) — la entrega permite al usuario subir el archivo real y diagnosticar **sin riesgo** (no se escribe nada operativo). Demoable y deployable
4. **PARAR y VALIDAR**: ejecutar quickstart §Validar — confirmar el reporte sobre el archivo real
5. Tomar decisión: ¿el Excel está limpio para US2, o requiere corrección en origen?

### Incremental Delivery

- Día 1–2: Setup + Foundational ⇒ migraciones aplicadas en ambientes
- Día 3–5: US1 ⇒ deploy a staging ⇒ admin valida el archivo real ⇒ demo MVP
- Día 6–9: US2 + US3 (idealmente en paralelo si hay 2 personas) ⇒ deploy a staging ⇒ admin ejecuta importación real sobre BD de staging ⇒ valida saldos contra el Excel
- Día 10: Phase 6 (Polish) + corte a producción

### Parallel Team Strategy

Con 2 desarrolladores tras Phase 2:

- Dev A: US1 → US2 (importación + primer login + pendientes Contenedor/OrdenServicio)
- Dev B: en cuanto US2 termine el mapper de Contenedor/Referencia, comienza US3 (HistorialDespachoMapper + pendientes Tarja/OrdenCargue)

Los puntos de fricción son las vistas compartidas (`pendientes/index`, `_campos_comunes`); coordinar por owner único.

---

## Notes

- Las features marcadas [P] son ejecutables en paralelo porque tocan archivos distintos. Tareas que tocan el mismo controller/servicio (T042 ↔ T063, T037 ↔ T088, T069 ↔ T093) **no** son paralelas: extienden lo mismo en pasos secuenciales.
- Las migraciones T005–T009 son secuenciales por dependencia de FK.
- El trait `HasImportPendingFields` (T018) debe existir antes que las modificaciones a los 6 modelos (T019–T024).
- Constitution Check al final: confirmar funciones ≤ 40 LOC, archivos ≤ 300 LOC en lo creado.
- El archivo real `INVENTARIO TOTAL CONTROLCARGA 27022026.xlsx` **no se commitea** al repo; vive en `storage/app/imports-prueba/` localmente y en la carpeta de Descargas del usuario.
