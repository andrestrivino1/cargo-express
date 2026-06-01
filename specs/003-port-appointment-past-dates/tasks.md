---
description: "Task list for feature 003-port-appointment-past-dates"
---

# Tasks: Permitir fechas anteriores en los campos de fecha de registro operativo

**Input**: Design documents from `/specs/003-port-appointment-past-dates/`
**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/

**Tests**: SE INCLUYEN. La estrategia de pruebas está pedida explícitamente en research.md (Decisión 7) y quickstart.md. Cada flujo afectado tiene una prueba Feature que cubre: fecha pasada (acepta), vacío (rechaza), formato inválido (rechaza), fecha futura (acepta), y validaciones de negocio no temporales.

**Organization**: Tareas agrupadas por historia de usuario. Cada historia (US1–US3) es un cambio independiente sobre un FormRequest (US3 también una vista) + su prueba Feature. US4 verifica el comportamiento uniforme.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Puede correr en paralelo (archivos distintos, sin dependencias)
- **[Story]**: Historia de usuario a la que pertenece (US1, US2, US3, US4)

## Path Conventions

Monolito Laravel: FormRequests en `app/Http/Requests/`, vistas en `resources/views/`, pruebas en `tests/Feature/`.

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Estructura mínima para las pruebas de la feature

- [X] T001 Crear el directorio de pruebas `tests/Feature/FechasRegistro/` (contenedor de las pruebas Feature de los tres flujos)

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: No hay infraestructura bloqueante. Esta feature solo relaja reglas de validación declarativas existentes; no hay esquema, modelos ni servicios nuevos.

- [X] T002 Verificar el inventario de campos a modificar contra el código actual (confirmar que `after:now`/`after:today` siguen presentes en `app/Http/Requests/StoreOrdenServicioRequest.php`, `app/Http/Requests/StoreOrdenCargueRequest.php`, `app/Http/Requests/StoreOrdenVaciadoRequest.php` y que `resources/views/vaciado/create.blade.php` conserva el atributo `min`)

**Checkpoint**: Confirmado el punto de cambio — pueden iniciar las historias en paralelo.

---

## Phase 3: User Story 1 - Cita en puerto con fecha pasada (Priority: P1) 🎯 MVP

**Goal**: Permitir asignar una orden de servicio con `cita_puerto` en fecha/hora pasada, manteniendo obligatoriedad y formato.

**Independent Test**: Asignar una orden de servicio con cita en puerto anterior a hoy → se guarda sin error de validación y persiste la fecha ingresada.

### Tests for User Story 1 ⚠️

> Escribir la prueba PRIMERO y confirmar que FALLA antes de implementar (hoy la fecha pasada es rechazada).

- [X] T003 [P] [US1] Crear prueba Feature `tests/Feature/FechasRegistro/CitaPuertoFechaPasadaTest.php` que cubra el endpoint `POST /solicitudes/{solicitud}/ordenes-servicio`: (a) fecha pasada válida → orden creada y `cita_puerto` persistida con el valor enviado; (b) campo vacío → error de validación; (c) formato inválido → error de validación; (d) fecha futura → orden creada (sin regresión)

### Implementation for User Story 1

- [X] T004 [US1] En `app/Http/Requests/StoreOrdenServicioRequest.php`, cambiar la regla de `cita_puerto` de `['required', 'date', 'after:now']` a `['required', 'date']`
- [X] T005 [US1] Ejecutar `php artisan test --filter=CitaPuertoFechaPasadaTest` y confirmar que pasa

**Checkpoint**: US1 funcional y verificable de forma independiente (MVP).

---

## Phase 4: User Story 2 - Fecha de despacho con fecha pasada (Priority: P1)

**Goal**: Permitir crear una orden de cargue con `fecha_despacho` anterior a hoy, manteniendo obligatoriedad, formato y la validación `cliente_id exists`.

**Independent Test**: Crear una orden de cargue con fecha de despacho anterior a hoy → se guarda sin error de validación.

### Tests for User Story 2 ⚠️

> Escribir la prueba PRIMERO y confirmar que FALLA antes de implementar.

- [X] T006 [P] [US2] Crear prueba Feature `tests/Feature/FechasRegistro/FechaDespachoFechaPasadaTest.php` que cubra la creación de orden de cargue (`EntregaController` / `StoreOrdenCargueRequest`): (a) fecha de despacho pasada válida → orden creada; (b) campo vacío → error de validación; (c) formato inválido → error de validación; (d) fecha futura → orden creada (sin regresión)

### Implementation for User Story 2

- [X] T007 [US2] En `app/Http/Requests/StoreOrdenCargueRequest.php`, cambiar la regla de `fecha_despacho` de `['required', 'date', 'after:today']` a `['required', 'date']` (no tocar `cliente_id` ni `notas`)
- [X] T008 [US2] Ejecutar `php artisan test --filter=FechaDespachoFechaPasadaTest` y confirmar que pasa

**Checkpoint**: US1 y US2 funcionales de forma independiente.

---

## Phase 5: User Story 3 - Fecha programada de vaciado con fecha pasada (Priority: P2)

**Goal**: Permitir programar un vaciado con `fecha_programada` anterior a hoy, tanto en el control de fecha del formulario (quitar `min`) como en servidor, conservando la validación de estado "En Patio".

**Independent Test**: En el formulario de vaciado, seleccionar una fecha programada anterior a hoy y guardar (con contenedor "En Patio") → se guarda sin error; un contenedor que no está "En Patio" sigue siendo rechazado.

### Tests for User Story 3 ⚠️

> Escribir la prueba PRIMERO y confirmar que FALLA antes de implementar.

- [X] T009 [P] [US3] Crear prueba Feature `tests/Feature/FechasRegistro/FechaVaciadoFechaPasadaTest.php` que cubra la creación de orden de vaciado (`VaciadoController` / `StoreOrdenVaciadoRequest`): (a) fecha programada pasada válida con contenedor "En Patio" → orden creada; (b) campo vacío → error de validación; (c) formato inválido → error de validación; (d) fecha futura → orden creada; (e) contenedor que NO está "En Patio" → sigue rechazado (validación de negocio intacta)

### Implementation for User Story 3

- [X] T010 [P] [US3] En `app/Http/Requests/StoreOrdenVaciadoRequest.php`, cambiar la regla de `fecha_programada` de `['required', 'date', 'after:today']` a `['required', 'date']` (CONSERVAR el método `withValidator` que valida el estado "En Patio")
- [X] T011 [P] [US3] En `resources/views/vaciado/create.blade.php`, eliminar el atributo `min="{{ now()->addDay()->format('Y-m-d') }}"` del input `fecha_programada` (línea ~49)
- [X] T012 [US3] Ejecutar `php artisan test --filter=FechaVaciadoFechaPasadaTest` y confirmar que pasa (depende de T010, T011)

**Checkpoint**: Los tres flujos de registro aceptan fechas pasadas de forma independiente.

---

## Phase 6: User Story 4 - Comportamiento uniforme entre flujos (Priority: P2)

**Goal**: Confirmar que el criterio "se permiten fechas pasadas" aplica de forma consistente en los tres flujos y que no se alteraron campos fuera de alcance.

**Independent Test**: Ingresar una fecha pasada en cada flujo afectado y verificar que todos la aceptan con el mismo criterio; verificar que `fecha_solicitud`, `fecha_corte` y el rango de reportes no cambiaron.

- [X] T013 [US4] Verificar por inspección que NO se modificaron campos fuera de alcance: `app/Http/Requests/StoreSolicitudRequest.php` (`fecha_solicitud`), `app/Http/Requests/Importacion/SubirImportacionRequest.php` (`fecha_corte`) y la regla `after_or_equal:fecha_desde` en `app/Http/Controllers/ReporteController.php`
- [X] T014 [US4] Ejecutar `php artisan test --filter=FechasRegistro` y confirmar que los tres flujos pasan con idéntico criterio de aceptación de fechas pasadas

**Checkpoint**: Comportamiento uniforme verificado en todos los flujos.

---

## Phase 7: Polish & Cross-Cutting Concerns

**Purpose**: Validación final y cierre

- [X] T015 Ejecutar la suite completa `php artisan test` para confirmar que no hay regresiones en otros flujos (especialmente `tests/Feature/Pendientes/CompletarRegistroTest.php`)
- [ ] T016 Ejecutar la verificación manual de `specs/003-port-appointment-past-dates/quickstart.md` para los tres flujos (fecha pasada, vacío, fecha futura)

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: Sin dependencias — inicia de inmediato.
- **Foundational (Phase 2)**: Depende de Setup. Es una verificación rápida; no produce código.
- **User Stories (Phase 3–6)**: Dependen de Phase 2. US1, US2 y US3 son independientes entre sí (archivos distintos) y pueden hacerse en paralelo. US4 depende de que US1, US2 y US3 estén implementadas (verifica el conjunto).
- **Polish (Phase 7)**: Depende de todas las historias completas.

### User Story Dependencies

- **US1 (P1)**: Independiente — solo `StoreOrdenServicioRequest.php` + su prueba.
- **US2 (P1)**: Independiente — solo `StoreOrdenCargueRequest.php` + su prueba.
- **US3 (P2)**: Independiente — `StoreOrdenVaciadoRequest.php` + vista de vaciado + su prueba.
- **US4 (P2)**: Verificación transversal — requiere US1+US2+US3 implementadas.

### Within Each User Story

- La prueba Feature se escribe primero y debe FALLAR antes de la implementación.
- Implementación (FormRequest / vista) → ejecutar la prueba → confirmar verde.

### Parallel Opportunities

- Las pruebas T003, T006, T009 son archivos distintos → pueden escribirse en paralelo ([P]).
- Las implementaciones T004 (US1), T007 (US2) y T010/T011 (US3) tocan archivos distintos → pueden hacerse en paralelo si hay varios desarrolladores.
- T010 y T011 (US3) son archivos distintos → paralelizables entre sí; T012 depende de ambos.

---

## Parallel Example: arranque de las tres historias

```bash
# Escribir las tres pruebas Feature en paralelo (archivos distintos):
Task: "Crear tests/Feature/FechasRegistro/CitaPuertoFechaPasadaTest.php"      # US1
Task: "Crear tests/Feature/FechasRegistro/FechaDespachoFechaPasadaTest.php"   # US2
Task: "Crear tests/Feature/FechasRegistro/FechaVaciadoFechaPasadaTest.php"    # US3

# Aplicar las tres relajaciones de validación en paralelo (archivos distintos):
Task: "Editar StoreOrdenServicioRequest.php (cita_puerto)"     # US1
Task: "Editar StoreOrdenCargueRequest.php (fecha_despacho)"    # US2
Task: "Editar StoreOrdenVaciadoRequest.php (fecha_programada)" # US3
```

---

## Implementation Strategy

### MVP First (User Story 1)

1. Phase 1 (Setup) → Phase 2 (verificación) → Phase 3 (US1).
2. **STOP y VALIDAR**: cita en puerto acepta fecha pasada (motivo original del requerimiento).
3. Demo/deploy del MVP.

### Incremental Delivery

1. Setup + Foundational listos.
2. US1 (cita en puerto) → probar → demo (MVP).
3. US2 (fecha de despacho) → probar → demo.
4. US3 (fecha programada vaciado) → probar → demo.
5. US4 (verificación uniforme) → cierre.

### Parallel Team Strategy

Tras Phase 2, repartir US1/US2/US3 entre desarrolladores (archivos disjuntos); US4 y Polish al final por una sola persona.

---

## Notes

- [P] = archivos distintos, sin dependencias.
- Sin migraciones, sin cambios de modelo, sin cambios de servicio: la feature solo relaja reglas de validación y un atributo de vista.
- Conservar todas las validaciones de negocio no temporales (estado "En Patio" en vaciado, `exists` de cliente/contenedor, rango de reportes).
- Hacer commit por tarea o grupo lógico siguiendo Conventional Commits (`fix(...)` o `feat(...)`).
