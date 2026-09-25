# Tasks: Corrección de cantidades en ingreso, retiro de productos en almacenamiento y landing del sitio

**Input**: Design documents from `/specs/010-ajustes-ingreso-almacen-ruta/`
**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/rutas.md, contracts/ledger.md, quickstart.md

**Tests**: SÍ se incluyen. El plan define 4 archivos de test nuevos, `contracts/ledger.md` enumera 10 casos obligatorios y el principio VI de la constitución exige ≥80% en servicios y ≥60% en controladores.

**Organization**: agrupadas por historia de usuario. Las tres historias son independientes entre sí; solo comparten la Fase 2.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: puede correr en paralelo (archivos distintos, sin dependencias pendientes)
- **[Story]**: US1 (corrección de cantidades), US2 (retiro en almacenamiento), US3 (entrada al sitio)

## Path Conventions

Aplicación Laravel monolítica en la raíz del repositorio: `app/`, `database/`, `resources/views/`, `routes/`, `tests/`.

---

## Phase 1: Setup

**Purpose**: dejar el punto de partida medido, para no confundir fallos preexistentes con regresiones de esta feature.

- [X] T001 Correr `php artisan test` sobre la rama limpia y anotar el listado de fallos preexistentes en `specs/010-ajustes-ingreso-almacen-ruta/quickstart.md` (se esperan ~15 en los unitarios de importación, ajenos a esta feature)
- [X] T002 [P] Verificar que `php artisan migrate:status` está al día en local antes de agregar migraciones nuevas, desde la raíz del repositorio

**Checkpoint**: baseline conocido. Sin dependencias nuevas que instalar (`composer.json` y `package.json` no cambian).

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: el ledger de inventario aprende a registrar ajustes y bajas. Lo usan US1 y US2, y ambos tocan los mismos dos archivos: hacerlo una vez aquí evita que las dos historias colisionen.

**⚠️ CRITICAL**: US1 y US2 no pueden empezar hasta terminar esta fase. US3 no depende de ella.

- [X] T003 Agregar los casos `AjustePositivo` (`ajuste_positivo`), `AjusteNegativo` (`ajuste_negativo`) y `Baja` (`baja`) al enum en `app/Enums/MovimientoTipo.php`, extendiendo `label()` ("Ajuste (+)", "Ajuste (−)", "Baja") y `color()` con colores distinguibles de entrada y salida
- [X] T004 Escribir el test unitario en `tests/Unit/Services/MovimientoInventarioAjusteTest.php` que cubra: delta positivo → tipo `ajuste_positivo`, delta negativo → tipo `ajuste_negativo`, `cantidad` siempre magnitud (nunca negativa), `saldo_resultante` = `cantidad_actual` de la referencia, y `registrarBaja` con `saldo_resultante` 0
- [X] T005 Implementar `registrarAjuste(Referencia $ref, int $delta, User $usuario, ?Model $documentable, ?string $observaciones)` y `registrarBaja(Referencia $ref, int $cantidad, User $usuario, ?string $motivo)` en `app/Services/MovimientoInventarioService.php`, reutilizando el `registrar()` privado existente; el servicio elige el tipo por el signo del delta y el llamador nunca lo pasa (SRP)

**Checkpoint**: `php artisan test --filter=MovimientoInventarioAjusteTest` en verde. US1 y US2 desbloqueadas.

---

## Phase 3: User Story 1 - Corregir la cantidad recibida de una referencia (Priority: P1) 🎯 MVP

**Goal**: la columna Cantidad de la tabla de referencias en `ingreso/editar` pasa a ser editable; corregir un valor recalcula el disponible, escribe el ajuste en el ledger y queda auditado.

**Independent Test**: editar un ingreso, cambiar una cantidad de 5 a 8, guardar, y verificar que almacenamiento muestra 8, que Reportes → Movimientos tiene una fila `Ajuste (+)` de 3 y que el historial de cambios del ingreso registra el valor anterior y el nuevo.

### Tests for User Story 1 ⚠️

> Escribir primero; deben fallar antes de implementar.

- [X] T006 [US1] Crear `tests/Feature/IngresoCorregirCantidadTest.php` cubriendo los 7 escenarios de la US1 y los casos L1-L5 y L9 de `contracts/ledger.md`: corrección sin movimientos previos (5→8 ⇒ 8/8, `ajuste_positivo` de 3); corrección con 2 despachadas (5→8 ⇒ 8/6); corrección a la baja (10→8 con 4 despachadas ⇒ 8/4, `ajuste_negativo` de 2); rechazo por cantidad 0 o negativa; rechazo por quedar bajo lo consumido (5→1 con 2 despachadas); corrección de dos referencias de contenedores distintos en un mismo guardado; guardado sin cambios ⇒ 0 movimientos y 0 auditoría; **`referencias[<id de otro ingreso>]` ⇒ error de validación y ningún dato alterado**; usuario con rol `operaciones` ⇒ 403; y que el reporte de Ingresos no varíe tras un ajuste

### Implementation for User Story 1

- [X] T007 [US1] Agregar a `rules()` de `app/Http/Requests/UpdateIngresoRequest.php` las reglas `referencias` (`nullable|array`) y `referencias.*` (`integer|min:1`), más las entradas correspondientes en `attributes()` para que los mensajes hablen de "cantidad" y no de `referencias.12`
- [X] T008 [US1] Extender `withValidator()` en `app/Http/Requests/UpdateIngresoRequest.php` con dos controles por cada clave enviada: (a) que el id pertenezca a un contenedor del `{ingreso}` de la ruta —mismo patrón que el control ya existente para `nueva_referencia.contenedor_id`—, y (b) que la cantidad no sea menor que lo ya consumido (`cantidad_inicial - cantidad_actual`), con un mensaje que nombre las unidades ya despachadas
- [X] T009 [US1] Implementar el método privado `aplicarCorrecciones(Ingreso $ingreso, array $cantidades, User $usuario): void` en `app/Services/IngresoMercanciaService.php` siguiendo la secuencia de `contracts/ledger.md` §3: calcular `consumido`, saltar las referencias con delta 0, fijar `cantidad_inicial` y `cantidad_actual`, llamar a `AuditoriaService::registrarCambios` **antes** de `save()`, y registrar el ajuste con `registrarAjuste()` usando el `Ingreso` como `documentable` y la observación `"Corrección de cantidad declarada: {antes} → {después}"`
- [X] T010 [US1] Invocar `aplicarCorrecciones()` desde `actualizar()` en `app/Services/IngresoMercanciaService.php`, leyendo `$data['referencias'] ?? []` dentro de la transacción que ya existe; `IngresoMercanciaController::update` no cambia porque ya pasa `$request->validated()` completo como `$data`
- [X] T011 [US1] Inyectar `AuditoriaService` por constructor en `app/Services/IngresoMercanciaService.php` si aún no está disponible, manteniendo el patrón de inyección que ya usa para `MovimientoInventarioService`
- [X] T012 [US1] Convertir la celda de cantidad en un input numérico en `resources/views/ingreso/partials/_referencias.blade.php`: `name="referencias[{{ $referencia->id }}]"`, `value="{{ old('referencias.'.$referencia->id, $referencia->cantidad_inicial) }}"`, `min="1"`, mostrando al lado el disponible actual como referencia; actualizar el comentario de cabecera que hoy dice "(solo lectura). Feature 007 / US1"
- [X] T013 [US1] Mostrar los errores de validación por fila en `resources/views/ingreso/partials/_referencias.blade.php` (`@error('referencias.'.$referencia->id)`), de modo que el rechazo por quedar bajo lo consumido se vea junto a la referencia culpable y no solo en el resumen superior

**Checkpoint**: `php artisan test --filter=IngresoCorregirCantidadTest` en verde. US1 entregable por sí sola — es el MVP.

---

## Phase 4: User Story 2 - Retirar un producto del cliente del almacenamiento (Priority: P2)

**Goal**: acción de baja por fila en almacenamiento que saca la referencia del inventario vigente conservando íntegro su historial.

**Independent Test**: retirar una referencia y verificar que desaparece del listado, de los exportables y de la vista del cliente, que no es seleccionable en salida/transferencia/ubicación, que el ledger tiene su fila `Baja`, y que una Orden de Salida previa de esa referencia sigue mostrando el detalle completo.

### Tests for User Story 2 ⚠️

- [X] T014 [US2] Crear `tests/Feature/InventarioRetiroTest.php` cubriendo los 8 escenarios de la US2 y los casos L6-L8 y L10 de `contracts/ledger.md`: retiro ⇒ desaparece del listado; ausencia en export Excel, export PDF y vista del cliente; retiro de una referencia con salidas previas ⇒ procede y el historial sigue resolviendo; no seleccionable en salida, transferencia y `inventario.ubicar`; movimiento `baja` por el disponible con `saldo_resultante` 0 y `cantidad_actual` 0; retiro con 0 disponibles ⇒ sin movimiento `baja`; filtro `incluir_retiradas` visible solo con permiso; **rol `cliente` con `?incluir_retiradas=1` ⇒ no ve retiradas**; y rol sin `inventario.retirar` ⇒ 403
- [X] T015 [US2] Agregar a `tests/Feature/InventarioRetiroTest.php` el test de regresión de borrado físico (caso L10): eliminar un ingreso completo por `ingreso.destroy` y verificar que `Referencia::withTrashed()` **no** devuelve nada para ese ingreso; ídem para la consolidación de duplicados de Pendientes por completar

### Implementation for User Story 2

- [X] T016 [US2] Crear la migración `database/migrations/2026_09_25_000001_add_retiro_to_referencias_table.php` con `softDeletes()` y `retirado_por` (`foreignId` nullable con constraint a `users`), y un `down()` que retire la foránea y ambas columnas
- [X] T017 [US2] Agregar el trait `SoftDeletes` a `app/Models/Referencia.php`, sumar `retirado_por` al `$fillable` y declarar la relación `retiradoPor(): BelongsTo` hacia `User`
- [X] T018 [P] [US2] Agregar `->withTrashed()` a `referencia()` en `app/Models/MovimientoInventario.php`
- [X] T019 [P] [US2] Agregar `->withTrashed()` a `referencia()` en `app/Models/TarjaDetalle.php`
- [X] T020 [P] [US2] Agregar `->withTrashed()` a `referencia()` en `app/Models/Novedad.php`
- [X] T021 [P] [US2] Agregar `->withTrashed()` a `referenciaOrigen()` y `referenciaDestino()` en `app/Models/Transferencia.php`
- [X] T022 [P] [US2] Agregar `->withTrashed()` a `referencia()` en `app/Models/ImportRowResult.php`
- [X] T023 [US2] Cambiar `Referencia::whereIn('id', $referenciaIds)->delete()` por `->forceDelete()` en `eliminar()` de `app/Services/IngresoMercanciaService.php`, dejando comentado el porqué: eliminar un ingreso es borrado físico, no retiro
- [X] T024 [US2] Cambiar `Referencia::where('contenedor_id', $dup->id)->delete()` por `->forceDelete()` en `app/Http/Controllers/PendientesCompletarController.php`, por el mismo motivo
- [X] T025 [US2] Crear la migración idempotente `database/migrations/2026_09_25_000002_crear_permiso_retirar_inventario.php` siguiendo el patrón de `2026_07_27_000010_crear_permiso_eliminar_ingreso.php`: `firstOrCreate` del permiso `inventario.retirar`, concesión a los roles `administrador` y `coordinador`, y limpieza de la caché de permisos antes y después
- [X] T026 [P] [US2] Agregar `inventario.retirar` a la lista de permisos y a los roles `administrador` y `coordinador` en `database/seeders/RolesAndPermissionsSeeder.php`, para que las instalaciones nuevas queden iguales a las migradas
- [X] T027 [P] [US2] Crear `app/Http/Requests/RetirarReferenciaRequest.php` con `authorize()` verificando el permiso `inventario.retirar`; sin reglas: el retiro no recibe datos
- [X] T028 [US2] Implementar `retirar(Referencia $ref, User $usuario): void` en `app/Services/InventarioService.php` siguiendo la secuencia de `contracts/ledger.md` §3: guardar el disponible, fijar `retirado_por`/`cantidad_actual = 0`, auditar antes de `save()`, registrar la baja solo si había disponible, y aplicar el soft delete al final
- [X] T029 [US2] Inyectar `MovimientoInventarioService` y `AuditoriaService` por constructor en `app/Services/InventarioService.php`, que hoy no recibe dependencias, manteniendo el patrón de `IngresoMercanciaService`
- [X] T030 [US2] Aceptar el filtro `incluir_retiradas` en `consultarInventario()` de `app/Services/InventarioService.php` aplicando `withTrashed()` solo cuando venga activo; **no** tocar `exportarInventario()` ni `exportarInventarioPdf()`, que siempre excluyen retiradas (FR-014)
- [X] T031 [US2] Implementar `retirar(RetirarReferenciaRequest $request, Referencia $referencia)` en `app/Http/Controllers/AlmacenamientoController.php` delegando en `InventarioService::retirar` y redirigiendo a `inventario.index` con el flash de éxito que nombre el código de la referencia
- [X] T032 [US2] En `index()` de `app/Http/Controllers/AlmacenamientoController.php`, pasar `incluir_retiradas` al servicio **solo** si el usuario tiene el permiso `inventario.retirar`, ignorando el parámetro en cualquier otro caso (incluido el rol `cliente`)
- [X] T033 [US2] Registrar la ruta `DELETE /inventario/{referencia}` con nombre `inventario.retirar` y middleware `permission:inventario.retirar` dentro del grupo de inventario en `routes/web.php`
- [X] T034 [US2] Agregar en `resources/views/almacenamiento/index.blade.php` el botón Retirar por fila y el modal de confirmación que informe código y cliente, visible solo bajo `@can('inventario.retirar')`
- [X] T035 [US2] Agregar en `resources/views/almacenamiento/index.blade.php` el checkbox de filtro "Incluir retiradas" en el formulario de filtros y, cuando esté activo, una marca visual por fila con responsable y fecha de retiro; ambos bajo `@can('inventario.retirar')`

**Checkpoint**: `php artisan test --filter=InventarioRetiroTest` en verde. US1 y US2 funcionan de forma independiente.

---

## Phase 5: User Story 3 - Llegar al acceso de la aplicación escribiendo la dirección del sitio (Priority: P3)

**Goal**: la raíz del sitio lleva al login de Cargo Express y desaparece el registro público de usuarios.

**Independent Test**: entrar a la raíz sin sesión ⇒ login; con sesión ⇒ tablero; con primer login pendiente ⇒ cambio de contraseña; `/register` ⇒ 404.

**Nota**: esta historia no depende de la Fase 2 y puede hacerse en cualquier momento, incluso antes que US1.

### Tests for User Story 3 ⚠️

- [X] T036 [US3] Crear `tests/Feature/EntradaSitioTest.php` cubriendo los 6 escenarios de la US3: invitado en `/` ⇒ redirige a `/login`; usuario autenticado en `/` ⇒ termina en `/dashboard` sin ver el formulario; usuario con `requiere_cambio_password` ⇒ termina en `primer-login.password`; `GET /register` ⇒ 404; `POST /register` con datos válidos ⇒ 404 y ningún usuario creado; y que la respuesta del login no contiene la cadena "Laravel"

### Implementation for User Story 3

- [X] T037 [US3] Reemplazar el closure de `Route::get('/')` por `Route::redirect('/', '/login')` en `routes/web.php`, apoyándose en que el middleware `guest` de la ruta `login` ya desvía a `/dashboard` a quien tenga sesión y en que `primer_login` intercepta allí (decisión D-005)
- [X] T038 [US3] Eliminar las rutas `GET register` y `POST register` del grupo `guest` en `routes/auth.php`; el enlace del layout desaparece solo porque `resources/views/layouts/app.blade.php` ya lo envuelve en `@if (Route::has('register'))`
- [X] T039 [P] [US3] Eliminar `app/Http/Controllers/Auth/RegisteredUserController.php`
- [X] T040 [P] [US3] Eliminar `resources/views/auth/register.blade.php`
- [X] T041 [P] [US3] Eliminar `resources/views/welcome.blade.php`
- [X] T042 [P] [US3] Eliminar `tests/Feature/Auth/RegistrationTest.php`, que prueba una funcionalidad retirada a propósito y dejaría la suite en rojo

**Checkpoint**: las tres historias funcionan de forma independiente.

---

## Phase 6: Polish & Cross-Cutting Concerns

- [X] T043 Verificar que el reporte general de movimientos renderiza los tres tipos nuevos con etiqueta y color, sin celdas en blanco, en `resources/views/reportes/movimientos.blade.php`
- [X] T044 [P] Verificar que `ProductoController::destroy` en `app/Http/Controllers/ProductoController.php` ahora permite borrar un producto cuyas únicas referencias están retiradas, y documentar el cambio de comportamiento en el comentario del método
- [X] T045 Correr la suite completa con `php artisan test` y contrastar contra el baseline de T001: los únicos fallos admisibles son los preexistentes de los unitarios de importación
- [ ] T046 Ejecutar la validación manual de las tres historias siguiendo `specs/010-ajustes-ingreso-almacen-ruta/quickstart.md` §2, incluida la tabla de regresiones de §3
- [X] T047 [P] Actualizar la sección "Recent Changes" de `CLAUDE.md` con la entrada de la feature 010 si el script de contexto no la dejó al día

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Fase 1)**: sin dependencias
- **Foundational (Fase 2)**: depende de Fase 1 — **bloquea US1 y US2**, no bloquea US3
- **US1 (Fase 3)**: depende de Fase 2
- **US2 (Fase 4)**: depende de Fase 2
- **US3 (Fase 5)**: **sin dependencias** más allá de Fase 1; puede adelantarse en cualquier momento
- **Polish (Fase 6)**: depende de las historias que se quieran entregar

### User Story Dependencies

- **US1 (P1)**: independiente. Solo necesita los tipos de ajuste de la Fase 2
- **US2 (P2)**: independiente de US1. Solo necesita el tipo `baja` de la Fase 2
- **US3 (P3)**: independiente de todo. Es la más pequeña y la de menor riesgo

### Within Each User Story

- Los tests se escriben primero y deben fallar antes de implementar
- Migraciones y modelos antes que servicios; servicios antes que controladores y rutas; vistas al final
- En US2, T016 y T017 (migración + `SoftDeletes`) bloquean todo lo demás de la fase: sin `deleted_at` nada funciona

### Parallel Opportunities

- **Fase 4, bloque de relaciones**: T018-T022 tocan cinco modelos distintos y pueden hacerse a la vez, una vez listo T017
- **Fase 5, bloque de borrados**: T039-T042 son cuatro archivos distintos, todos eliminables en paralelo tras T038
- **Entre historias**: con Fase 2 terminada, US1 y US2 pueden avanzar en paralelo por personas distintas; US3 puede avanzar desde el primer momento
- **Archivos compartidos a vigilar**: `app/Services/InventarioService.php` lo tocan T028, T029 y T030 (secuencial); `resources/views/almacenamiento/index.blade.php` lo tocan T034 y T035 (secuencial); `app/Http/Requests/UpdateIngresoRequest.php` lo tocan T007 y T008 (secuencial)

---

## Parallel Example: User Story 2, bloque de relaciones

```bash
# Tras completar T017 (SoftDeletes en Referencia), estas cinco van a la vez:
T018  app/Models/MovimientoInventario.php   → referencia()->withTrashed()
T019  app/Models/TarjaDetalle.php           → referencia()->withTrashed()
T020  app/Models/Novedad.php                → referencia()->withTrashed()
T021  app/Models/Transferencia.php          → referenciaOrigen() y referenciaDestino()->withTrashed()
T022  app/Models/ImportRowResult.php        → referencia()->withTrashed()
```

---

## Implementation Strategy

### MVP (entrega mínima con valor)

**Fases 1 + 2 + 3 (US1)** = 13 tareas. Resuelve el problema que hoy no tiene ninguna salida dentro del sistema: corregir una cantidad mal digitada sin tocar la base de datos a mano. Desplegable por sí solo: una migración menos (US1 no necesita esquema nuevo), riesgo bajo.

### Entrega incremental sugerida

1. **US3 primero si se quiere un resultado visible ya** (7 tareas, sin migraciones, sin dependencias): es el arreglo más barato y el de mayor impacto de seguridad, porque cierra el alta pública de usuarios.
2. **US1** como MVP funcional.
3. **US2** al final: es la que más superficie toca y la única con riesgo de regresión real (los dos `forceDelete` de T023-T024). Conviene desplegarla sola, no mezclada con otra historia, para que cualquier problema sea fácil de atribuir.

### Riesgo concentrado

T023 y T024 son las dos tareas donde un olvido rompe algo que hoy funciona: si `eliminar` un ingreso deja de borrar físicamente, quedan referencias huérfanas apuntando a contenedores borrados. T015 existe precisamente para atrapar eso, y debe escribirse antes que T016.
