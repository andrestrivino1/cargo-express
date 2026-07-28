# Tasks: Reorganización de roles + módulos Citas y Portero

**Input**: Design documents from `/specs/009-roles-citas-portero/`
**Prerequisites**: [plan.md](./plan.md), [spec.md](./spec.md), [research.md](./research.md), [data-model.md](./data-model.md), [contracts/](./contracts/)

**Tests**: SÍ se incluyen. No por petición explícita del spec, sino porque la **constitución del proyecto (principio VI)** los exige: ≥80 % de cobertura en servicios, ≥60 % en controladores, y pruebas de integración obligatorias en funcionalidad crítica (autenticación y autorización lo son).

**Organization**: agrupadas por historia de usuario. Cada historia posee **su propia migración de permisos**, de modo que se puede implementar, probar y desplegar por separado sin bloquear a las demás.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: paralelizable (archivo distinto, sin dependencias pendientes)
- **[Story]**: historia a la que pertenece (US1…US7)

## Path Conventions

Estructura Laravel del proyecto (ver plan.md → Structure Decision): `app/`, `config/`, `database/migrations/`, `resources/views/`, `routes/`, `tests/`.

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: constantes de negocio y banderas de configuración. Sin lógica; nada depende de nada dentro de esta fase salvo T001.

- [X] T001 Añadir las claves `'citas' => true` y `'porteria' => true` al bloque "Flujo ajustado (visibles)" de `config/modulos.php`, con comentario que explique que son los módulos de la feature 009
- [X] T002 [P] Crear `config/roles.php` con la clave `'retirados' => ['coordinador', 'despachador', 'gerente', 'operador']` y un comentario que documente que el retiro es reversible y que **no** afecta a los usuarios ya asignados (R-004)
- [X] T003 [P] Crear enum `app/Enums/CitaEstado.php` respaldado por string con los casos `Programada`, `Atendida`, `Vencida`, `Cancelada` y método `etiqueta(): string` en español, siguiendo el patrón de `app/Enums/ContenedorEstado.php`
- [X] T004 [P] Crear enum `app/Enums/CitaCondicion.php` con los casos `Full` (`full`) y `Vacio` (`vacio`) y método `etiqueta()`
- [X] T005 [P] Crear enum `app/Enums/TipoContenedor.php` con los casos `Dry`, `Reefer`, `OpenTop`, `FlatRack`, `Tank` (valores `dry`, `reefer`, `open_top`, `flat_rack`, `tank`) y método `etiqueta()`
- [X] T006 [P] Crear enum `app/Enums/TamanoContenedor.php` con los casos `Veinte` (`20`), `Cuarenta` (`40`), `CuarentaHC` (`40hc`), `CuarentaYCinco` (`45`) y método `etiqueta()`
- [X] T007 [P] Crear enum `app/Enums/PorteriaFotoCategoria.php` con los casos `Vehiculo`, `Contenedor`, `Sello`, `Tiquete` y método `etiqueta()`; se usará como valor de `photos.categoria`

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: esquema, modelos y navegación. Sin esto ninguna historia puede empezar.

**⚠️ CRITICAL**: completar antes de cualquier fase de historia.

- [X] T008 [P] Crear migración `database/migrations/2026_07_27_000001_create_citas_table.php` con todas las columnas, FK e índices de `data-model.md` → tabla `citas`. Índices obligatorios: `(fecha_esperada, estado)`, `(placa)`, `(numero_contenedor)`. FK a `ingresos` y `contenedores` con `onDelete('cascade')`
- [X] T009 [P] Crear migración `database/migrations/2026_07_27_000002_create_porteria_registros_table.php` con `cita_id` **UNIQUE** (garantía anti-reconfirmación, D-008), `portero_id`, `llegada_at` datetime, `observaciones` text nullable
- [X] T010 [P] Crear migración `database/migrations/2026_07_27_000003_create_porteria_novedades_table.php` con `portero_id`, `placa` y `numero_contenedor` nullable, `descripcion` text, `reportado_at` datetime, e índices en `(reportado_at)` y `(placa)`
- [X] T011 Crear migración idempotente `database/migrations/2026_07_27_000004_crear_permisos_citas_porteria.php` que haga `Permission::firstOrCreate` de `citas.ver`, `citas.crear`, `citas.editar`, `porteria.ver`, `porteria.registrar` y los conceda a `administrador` y `gerente`, siguiendo exactamente el patrón de `database/migrations/2026_06_25_000009_grant_ingreso_salida_permissions_to_roles.php` (incluye `forgetCachedPermissions()` al inicio y al final, y `?->` sobre los roles)
- [X] T012 [P] Crear modelo `app/Models/Cita.php` con `$fillable`, `casts()` a los cuatro enums y `fecha_esperada` a `date`; relaciones `ingreso()`, `contenedor()`, `registroPorteria()` (hasOne), `creador()`, `editor()`; método `estadoEfectivo(): CitaEstado` que devuelve `Vencida` cuando `estado === Programada && fecha_esperada < today()` (D-001); método `puedeEditarse(): bool`; scopes `scopeDelDia()`, `scopeVencidas()`, `scopeBuscar(string $termino)` que normaliza el término antes de comparar contra `placa` y `numero_contenedor`
- [X] T013 [P] Crear modelo `app/Models/PorteriaRegistro.php` usando el trait `HasPhotos`, con relaciones `cita()` y `portero()`, cast de `llegada_at` a datetime, y método `fotoPorCategoria(PorteriaFotoCategoria $c): ?Photo`
- [X] T014 [P] Crear modelo `app/Models/PorteriaNovedad.php` con `$fillable`, cast de `reportado_at` y relación `portero()`
- [X] T015 Refactorizar el sidebar de `resources/views/layouts/app.blade.php` para que cada ítem se condicione a **módulo visible Y permiso**, según la tabla del contrato `contracts/rutas.md` → "Contrato de navegación". Incluye: envolver los ítems existentes en `@can(...)`, sustituir `@role('supervisor|gerente|administrador')` de Reportes por `@can('reportes.ver')`, añadir los ítems nuevos de Citas y Portero, y reducir el bloque `@role('cliente')` a solo "Mi Inventario"
- [X] T016 Actualizar `database/seeders/RolesAndPermissionsSeeder.php` para que refleje el **estado final** de la matriz de `contracts/matriz-roles.md`: añadir los 5 permisos nuevos al arreglo, crear los roles `citas` y `operaciones`, y ajustar los permisos de `portero`, `supervisor` y `cliente`. Es el seeder de instalación limpia; los entornos existentes se actualizan por migración
- [X] T017 ~~Crear `database/factories/CitaFactory.php` y `database/factories/PorteriaRegistroFactory.php`~~ → **NO SE HIZO, a propósito.** El proyecto no tiene factories de modelos de dominio: los tests construyen los datos con métodos privados de ayuda (patrón de `tests/Feature/IngresoFechaTest.php:28`). Se siguió esa convención (constitución II) con los helpers `ingresoConContenedor()` y `crearCita()` dentro de cada test

**Checkpoint**: esquema listo y navegación consciente de permisos. Las historias pueden empezar en paralelo.

---

## Phase 3: User Story 1 - Agendar la llegada de un contenedor ya registrado (Priority: P1) 🎯 MVP

**Goal**: el rol `citas` agenda la llegada física de un contenedor perteneciente a un ingreso ya registrado.

**Independent Test**: con un ingreso existente, crear una cita con todos los datos, verla en el listado y editarla — sin que existan el módulo Portero ni los demás cambios de roles.

### Tests for User Story 1 ⚠️

> Escribir primero y comprobar que fallan antes de implementar.

- [X] T018 [P] [US1] Crear el test unitario → se creó como **`tests/Unit/CitaEstadoEfectivoTest.php`** (nombre más preciso: cubre lógica pura de estado y normalización, no el servicio completo). 9 casos: estado efectivo en las 4 transiciones, editabilidad, `Vencida` no persistible, y normalización de placa/cédula en 5 formatos. Extiende `Tests\TestCase` y no `PHPUnit\Framework\TestCase`, porque los casts de Eloquent necesitan el contenedor. Las rutas de `CitaService` (crear, actualizar, cancelar, duplicados) quedan cubiertas por `CitasTest`
- [X] T019 [P] [US1] Crear `tests/Feature/CitasTest.php` cubriendo los 6 escenarios de aceptación de US1: alta completa con estado Programada, validación que nombra los campos faltantes conservando el input, edición con registro de autoría, advertencia sin bloqueo ante contenedor ya agendado (FR-011), denegación de acceso del rol `citas` a `/salida` y `/vaciado`, y filtrado del listado por fecha/BL/contenedor/placa/estado

### Implementation for User Story 1

- [X] T020 [US1] Crear migración idempotente `database/migrations/2026_07_27_000005_crear_rol_citas.php` que haga `Role::firstOrCreate(['name' => 'citas'])` y le conceda `citas.ver`, `citas.crear`, `citas.editar` e `ingreso.ver` (consulta necesaria para elegir el contenedor, FR-012)
- [X] T021 [US1] Implementar `app/Services/CitaService.php` con `crear(array $data, User $usuario): Cita`, `actualizar(Cita $cita, array $data, User $usuario): Cita`, `cancelar(Cita $cita, User $usuario): void` y `listar(array $filtros)` paginado. Normaliza `placa` y `conductor_cedula` (mayúsculas, sin espacios/guiones/puntos) guardando además los valores originales; copia `numero_contenedor` normalizado desde el contenedor; detecta y devuelve la advertencia de cita duplicada. Dependencias inyectadas por constructor (principio VI)
- [X] T022 [P] [US1] Crear `app/Http/Requests/StoreCitaRequest.php` con las reglas de `data-model.md` → "Reglas de validación", `authorize()` contra `citas.crear`, `attributes()` en español, y validación cruzada en `withValidator()` de que `contenedor_id` pertenece a `ingreso_id` (patrón de `StoreIngresoMercanciaRequest`). **`fecha_esperada` NO lleva `after_or_equal:today`** — el agendamiento retroactivo es válido
- [X] T023 [P] [US1] Crear `app/Http/Requests/UpdateCitaRequest.php` con las mismas reglas, `authorize()` contra `citas.editar` y rechazo si la cita está en estado `Atendida` (FR-008)
- [X] T024 [US1] Implementar `app/Http/Controllers/CitaController.php` con `index`, `create`, `store`, `show`, `edit`, `update`, `cancelar` y `contenedoresDeIngreso` (JSON para el selector dependiente). Solo orquesta: la lógica vive en `CitaService` (principio III)
- [X] T025 [US1] Registrar el grupo de rutas de Citas en `routes/web.php` según `contracts/rutas.md` → "Módulo Citas", con middleware `['modulo:citas', 'permission:citas.ver']` y los permisos por acción
- [X] T026 [P] [US1] Crear `resources/views/citas/index.blade.php`: listado paginado con filtros por fecha esperada, BL, contenedor, placa y estado; cada fila muestra el **estado efectivo** (`estadoEfectivo()`), no el persistido
- [X] T027 [P] [US1] Crear `resources/views/citas/create.blade.php` y `resources/views/citas/editar.blade.php` (o un partial `_form` compartido): selector de ingreso que puebla el de contenedores vía `citas.contenedores`, selects de tipo/tamaño/condición alimentados por los enums, y campos de conductor, cédula, placa y empresa
- [X] T028 [P] [US1] Crear `resources/views/citas/show.blade.php` con el detalle de la cita, su estado efectivo, la autoría (creador/editor con fecha) y los botones de editar y cancelar visibles solo si `puedeEditarse()`

**Checkpoint**: US1 funcional y probable de forma independiente.

---

## Phase 4: User Story 2 - Control de portería contra la cita del día (Priority: P1)

**Goal**: el portero valida que el vehículo que llegó tenga cita para hoy y captura las cuatro evidencias obligatorias.

**Independent Test**: con una cita agendada para hoy, localizarla por placa, adjuntar las cuatro fotos y cerrarla como atendida; verificar el cambio de estado y que las imágenes quedan asociadas y consultables.

**Depende de**: US1 solo a nivel de datos (necesita citas existentes para probar). El código no se solapa.

### Tests for User Story 2 ⚠️

- [X] T029 [P] [US2] Crear `tests/Unit/PorteriaServiceTest.php` cubriendo: que `citasDelDia()` excluye ayer y mañana, que la búsqueda normalizada encuentra `ABC-123` buscando `abc 123`, que confirmar una cita ya atendida lanza excepción, y que un fallo a mitad de la transacción no deja registro ni fotos huérfanas (D-005)
- [X] T030 [P] [US2] Crear `tests/Feature/PorteriaTest.php` cubriendo los 8 escenarios de US2: listado limitado a hoy, búsqueda por placa y contenedor, confirmación exitosa con las 4 fotos y sello de hora, rechazo nombrando la evidencia faltante, mensaje explícito de vehículo sin cita, mensaje de agenda vacía, modo consulta en cita atendida, y denegación de acceso del portero a `/ingreso` y `/salida`

### Implementation for User Story 2

- [X] T031 [US2] Crear migración idempotente `database/migrations/2026_07_27_000006_ajustar_rol_portero.php` que conceda `porteria.ver` y `porteria.registrar` al rol `portero` y le **revoque** `ingreso.ver`, `ingreso.crear`, `salida.ver`, `salida.crear` (FR-026). ⚠️ Debe desplegarse junto con el módulo Portero: si se aplica sola, el portero queda sin acceso a nada
- [X] T032 [US2] Implementar `app/Services/PorteriaService.php` con `citasDelDia(?string $busqueda = null)`, `confirmarLlegada(Cita $cita, array $fotos, ?string $observaciones, User $portero): PorteriaRegistro` y `registrarNovedad(array $data, User $portero): PorteriaNovedad`. `confirmarLlegada` corre entera en `DB::transaction()`: crea el registro, guarda las 4 fotos con `guardarArchivo($archivo, "porteria/{$registro->id}", 'foto', $categoria->value)` y cambia el estado de la cita a `Atendida`
- [X] T033 [P] [US2] Crear `app/Http/Requests/StorePorteriaLlegadaRequest.php` con las 4 fotos como `required|image|mimes:jpg,jpeg,png|max:10240` y `observaciones` `nullable|string|max:500`, con `attributes()` que nombre cada evidencia en español para que el error identifique la que falta (FR-019, SC-010)
- [X] T034 [P] [US2] Crear `app/Http/Requests/StorePorteriaNovedadRequest.php` con `placa` y `numero_contenedor` `nullable` pero `required_without` mutuo, y `descripcion` `required|string|max:500`
- [X] T035 [US2] Implementar `app/Http/Controllers/PorteriaController.php` con `index`, `show`, `registrarLlegada`, `crearNovedad` y `guardarNovedad`. `show` debe devolver 404 si la cita no es de hoy y renderizar en modo consulta si ya está atendida
- [X] T036 [US2] Registrar el grupo de rutas de Portería en `routes/web.php` según `contracts/rutas.md` → "Módulo Portero", con middleware `['modulo:porteria', 'permission:porteria.ver']`
- [X] T037 [P] [US2] Crear `resources/views/porteria/index.blade.php` **optimizada para móvil**: buscador por placa o contenedor bien visible, tarjetas grandes en vez de tabla, mensaje explícito de "sin citas para hoy" y enlace a reportar novedad cuando la búsqueda no encuentra nada
- [X] T038 [P] [US2] Crear `resources/views/porteria/show.blade.php` con los datos esperados de la cita para contrastar (FR-016) y el formulario de las cuatro fotos usando `accept="image/*" capture="environment"` para abrir la cámara en móvil; en cita atendida, mostrar las evidencias en modo consulta sin botón de confirmar
- [X] T039 [P] [US2] Crear `resources/views/porteria/novedad.blade.php` con el formulario de constancia de vehículo sin cita

**Checkpoint**: US1 y US2 funcionan de forma independiente. Cadena Ingreso → Cita → Portero completa.

---

## Phase 5: User Story 3 - Rol `operaciones` dedicado a ingresos y salidas (Priority: P2)

**Goal**: existe un rol cuyo único alcance son Ingreso y Salida.

**Independent Test**: crear un usuario con el rol nuevo y verificar que opera Ingreso y Salida y ningún otro módulo.

- [X] T040 [P] [US3] Crear `tests/Feature/RolOperacionesTest.php` con los 4 escenarios de US3: acceso a Ingreso y Salida, denegación a Citas/Portero/Vaciado/administración, denegación al `portero` de crear ingreso o salida, y disponibilidad del rol en el selector de usuarios
- [X] T041 [US3] Crear migración idempotente `database/migrations/2026_07_27_000007_crear_rol_operaciones.php` que haga `Role::firstOrCreate(['name' => 'operaciones'])` y le conceda `ingreso.ver`, `ingreso.crear`, `salida.ver`, `salida.crear`

**Checkpoint**: el registro documental de ingreso y salida queda separado de la portería.

---

## Phase 6: User Story 4 - Supervisor a cargo del vaciado y la ubicación (Priority: P2)

**Goal**: el supervisor concentra vaciado y definición de ubicación física.

**Independent Test**: con un usuario supervisor, programar y ejecutar un vaciado y luego asignar ubicación a las referencias resultantes.

- [X] T042 [P] [US4] Crear `tests/Feature/RolSupervisorTest.php` con los 3 escenarios de US4: programar/iniciar/finalizar vaciado y registrar novedad, asignar ubicación con el inventario reflejando el cambio, y denegación de `POST /ingreso` y `POST /salida`
- [X] T043 [US4] Crear migración idempotente `database/migrations/2026_07_27_000008_ajustar_rol_supervisor.php` que conceda `vaciado.registrar-novedad` e `inventario.ubicar` al rol `supervisor` (hoy solo tiene `vaciado.ver`, `vaciado.programar`, `inventario.ver`, `reportes.ver`)

**Checkpoint**: la capacidad de ubicar mercancía ya no depende del rol `operador`, que se retira en US6.

---

## Phase 7: User Story 5 - El cliente ve solo el almacenamiento de sus productos (Priority: P3) 🔒

**Goal**: cerrar el hueco por el que hoy un cliente puede consultar el inventario de otro.

**Independent Test**: con dos clientes con mercancía distinta, iniciar sesión con cada uno y verificar que cada quien ve solo lo suyo, incluso manipulando `cliente_id` en la URL.

**⚠️ Prioridad de seguridad**: aunque es P3 por valor operativo, cierra una exposición de datos real entre clientes. Considerar adelantarla.

- [X] T044 [P] [US5] Crear `tests/Feature/ClienteAlcanceTest.php` con los 4 escenarios de US5, incluyendo explícitamente el **acceso cruzado**: cliente A pidiendo `/inventario?cliente_id={id_de_B}` debe responder 200 pero con cero registros de B (SC-005); repetir la comprobación en las rutas de exportación a Excel y PDF
- [X] T045 [US5] Crear migración idempotente `database/migrations/2026_07_27_000009_restringir_rol_cliente.php` que **revoque** al rol `cliente` los permisos `referencias.ver`, `entregas.ver`, `entregas.crear` y `reportes.ver`, dejándole solo `inventario.ver` (FR-034, FR-036)
- [X] T046 [US5] Modificar `app/Services/InventarioService.php` para que `consultarInventario()`, `exportarInventario()` y `exportarInventarioPdf()` reciban el usuario autenticado y **fuercen** `$filtros['cliente_id'] = $usuario->id` cuando `$usuario->hasRole('cliente')`, ignorando el valor recibido del request. Un solo punto de aplicación para los tres puntos de entrada (D-006, principio IV)
- [X] T047 [US5] Modificar `app/Http/Controllers/AlmacenamientoController.php` para pasar `$request->user()` a los tres métodos del servicio en `index`, `exportExcel` y `exportPdf`
- [X] T048 [US5] Añadir en `resources/views/almacenamiento/index.blade.php` un estado vacío explícito ("sin mercancía almacenada") cuando el resultado no tiene registros, en lugar de una tabla vacía (FR-037)

**Checkpoint**: aislamiento entre clientes verificado, incluidas las exportaciones.

---

## Phase 8: User Story 6 - Retirar de circulación los roles no usados (Priority: P3)

**Goal**: `coordinador`, `despachador`, `gerente` y `operador` dejan de ofrecerse, **sin** afectar a quienes ya los tienen.

**Independent Test**: el formulario de crear usuario ya no ofrece los cuatro roles, mientras los registros históricos y los usuarios ya asignados siguen intactos.

- [X] T049 [P] [US6] Crear `tests/Feature/RolesRetiradosTest.php` con los 6 escenarios de US6, incluyendo especialmente: que un usuario **preexistente** con rol `coordinador` sigue operando con sus permisos (R-004, FR-041), que el envío forzado de `role=coordinador` se rechaza, y que el administrador conserva la edición administrativa de registros tras el retiro (FR-043)
- [X] T050 [US6] Crear `app/Services/RolesDisponibles.php` con `asignables(): Collection` (roles menos los de `config('roles.retirados')`) y `esRetirado(string $rol): bool`. Punto único de verdad para no duplicar la lista (principio IV)
- [X] T051 [US6] Modificar `app/Http/Controllers/UserController.php`: `create()` y `edit()` usan `RolesDisponibles::asignables()` en vez de `Role::orderBy('name')->get()`; `store()` y `update()` añaden una regla que rechaza los roles retirados junto al `exists:roles,name` actual (FR-038, FR-039)
- [X] T052 [US6] Modificar `resources/views/admin/usuarios/index.blade.php` para marcar visualmente los usuarios que conservan un rol retirado, de modo que el administrador pueda identificarlos y reasignarlos (FR-042)

**Checkpoint**: selector limpio, nadie pierde acceso, histórico intacto.

---

## Phase 9: User Story 7 - Seguimiento de citas desde el ingreso (Priority: P3)

**Goal**: desde un ingreso se ve el estado de cita de cada contenedor y la evidencia de portería.

**Independent Test**: con un ingreso que tenga un contenedor agendado y otro sin agendar, distinguir el estado de cada uno y consultar las evidencias del que ya llegó.

**Depende de**: US1 y US2 (necesita citas y llegadas que mostrar).

- [X] T053 [P] [US7] Crear `tests/Feature/IngresoCitasTest.php` con los 2 escenarios de US7: distinción entre contenedor agendado y sin agendar, y visibilidad de la hora real de llegada más las cuatro evidencias
- [X] T054 [US7] Añadir a `app/Models/Ingreso.php` la relación `citas(): HasMany` y a `app/Models/Contenedor.php` la relación `cita(): HasOne` (última cita del contenedor), sin tocar nada más del módulo Ingreso (R-002)
- [X] T055 [US7] Modificar `resources/views/ingreso/show.blade.php` para mostrar, por contenedor, el estado efectivo de su cita —o "sin cita agendada"— y, si está atendida, la fecha y hora real de llegada con enlaces a las cuatro evidencias (FR-047)

**Checkpoint**: las siete historias funcionan de forma independiente.

---

## Phase 10: Polish & Cross-Cutting Concerns

- [X] T056 [P] Crear `tests/Feature/MatrizRolesTest.php` que recorra **completa** la matriz de `contracts/matriz-roles.md`, incluidos los 11 casos negativos explícitos, para cubrir SC-006 de extremo a extremo
- [X] T057 [P] Añadir a `tests/Feature/VisibilidadModulosTest.php` los casos de ocultar y reactivar los módulos `citas` y `porteria` (404 y 200), siguiendo el patrón de los tests existentes
- [X] T058 Verificar la cobertura exigida por la constitución: ≥80 % en `app/Services/CitaService.php` y `app/Services/PorteriaService.php`, ≥60 % en `app/Http/Controllers/CitaController.php` y `app/Http/Controllers/PorteriaController.php`
- [X] T059 Ejecutar `php artisan test` sobre `tests/` completo y **comparar contra el baseline de `main`**: hay ~15 fallos preexistentes en los unitarios de importación (`Class "config" does not exist`) que no son regresión de esta feature
- [X] T060 Recorrer el guion de validación manual de [quickstart.md](./quickstart.md) completo, incluido el paso 3 **desde un móvil real con datos móviles** (no solo WiFi), para validar la subida de las cuatro fotos
- [X] T061 Revisar que ningún archivo nuevo supere los límites de la constitución (funciones ≤40 líneas, archivos ≤300 líneas); si `app/Services/CitaService.php` o `app/Services/PorteriaService.php` los rozan, extraer la normalización de placa y cédula a un helper compartido en `app/Support/`
- [X] T062 Actualizar la sección "Active Technologies" y "Recent Changes" de `CLAUDE.md` si el script de contexto dejó entradas duplicadas o mal formateadas

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: sin dependencias — puede empezar de inmediato
- **Foundational (Phase 2)**: depende de Setup — **BLOQUEA todas las historias**
- **US1 (Phase 3)**: depende de Foundational
- **US2 (Phase 4)**: depende de Foundational; para **probar** necesita citas de US1
- **US3 (Phase 5)**, **US4 (Phase 6)**, **US5 (Phase 7)**, **US6 (Phase 8)**: dependen solo de Foundational — independientes entre sí
- **US7 (Phase 9)**: depende de US1 y US2 (muestra sus datos)
- **Polish (Phase 10)**: depende de todas las historias deseadas

### Dependencia operativa que no se ve en el código

⚠️ **T031 (revocar ingreso/salida al portero) debe desplegarse junto con el módulo Portero (T032-T039)**, nunca antes. Aplicada sola, deja al rol `portero` sin acceso a ningún módulo. Es la única dependencia de despliegue estricta de la feature.

⚠️ **T043 (permisos de supervisor) debe aplicarse antes o junto con T051 (retiro de roles)**. El rol `operador` es hoy el único con `inventario.ubicar` además de administrador y gerente; retirarlo del selector sin haber habilitado al supervisor dejaría la capacidad de ubicar mercancía sin un rol vigente que la ejerza.

### Within Each User Story

- Los tests se escriben primero y deben fallar antes de implementar
- Migración de permisos → servicio → form requests → controlador → rutas → vistas
- Los modelos ya están en Foundational

### Parallel Opportunities

- **Phase 1**: T002 a T007 en paralelo (6 archivos distintos)
- **Phase 2**: T008, T009, T010 en paralelo; luego T012, T013, T014, T017 en paralelo
- **Phase 3**: T018 y T019 en paralelo; luego T022 y T023 en paralelo; luego T026, T027, T028 en paralelo
- **Phase 4**: T029 y T030 en paralelo; luego T033 y T034 en paralelo; luego T037, T038, T039 en paralelo
- **Entre historias**: con Foundational cerrado, US3, US4, US5 y US6 pueden ir en paralelo por personas distintas — cada una toca archivos y migraciones propios

---

## Parallel Example: User Story 1

```bash
# Tests primero, en paralelo:
Task: "Crear tests/Unit/CitaServiceTest.php"
Task: "Crear tests/Feature/CitasTest.php"

# Tras el servicio, los form requests en paralelo:
Task: "Crear app/Http/Requests/StoreCitaRequest.php"
Task: "Crear app/Http/Requests/UpdateCitaRequest.php"

# Tras el controlador y las rutas, las vistas en paralelo:
Task: "Crear resources/views/citas/index.blade.php"
Task: "Crear resources/views/citas/create.blade.php y editar.blade.php"
Task: "Crear resources/views/citas/show.blade.php"
```

---

## Implementation Strategy

### MVP (US1 solamente)

1. Phase 1: Setup
2. Phase 2: Foundational
3. Phase 3: US1
4. **PARAR Y VALIDAR**: agendar, listar y editar citas funcionan de forma aislada
5. Entregable: visibilidad de qué contenedores se esperan y qué día — valor real sin tocar ningún flujo existente

### Entrega incremental sugerida

| Entrega | Fases | Valor | Riesgo |
|---------|-------|-------|--------|
| 1 | Setup + Foundational + US1 | Agenda de llegadas | Bajo — solo agrega |
| 2 | US2 | Control físico en puerta con evidencia | Medio — fotos en móvil; **incluye la revocación al portero** |
| 3 | US5 | Cierra la exposición de datos entre clientes | Medio — seguridad |
| 4 | US4 + US3 | Reparto de responsabilidades operativas | Medio — cambia permisos |
| 5 | US6 | Limpieza del selector de roles | Bajo — reversible por config |
| 6 | US7 + Polish | Cierre del ciclo y verificación completa | Bajo |

US5 se adelanta respecto a su prioridad P3 porque cierra una exposición de datos real; el resto sigue el orden del spec.

### Estrategia con varias personas

Con Foundational cerrado: una persona en US1+US2 (la cadena principal), otra en US3+US4+US6 (matriz de roles), otra en US5 (seguridad). US7 al final, cuando US1 y US2 estén listas.

---

## Notes

- **62 tareas** en total: 7 de setup, 10 fundacionales, 38 repartidas en 7 historias, 7 de cierre
- Cada historia posee su propia migración de permisos → se despliegan y se revierten por separado
- El módulo Ingreso **no se modifica** (R-002); US7 solo añade relaciones de lectura y una sección de vista
- Sin tareas de cron ni de cola: el hosting no tiene scheduler ni worker (research.md D-001)
- Las limitaciones conocidas del vaciado (novedades que solo descuentan, que no escriben en el ledger y que no notifican al cliente en el flujo nuevo) son **riesgos aceptados fuera de alcance** — no generan tareas aquí
- Hacer commit por tarea o por grupo lógico; la constitución limita los PR a 400 líneas
