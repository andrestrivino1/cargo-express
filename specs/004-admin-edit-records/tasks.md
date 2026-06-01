---
description: "Task list for feature 004-admin-edit-records"
---

# Tasks: Edición de registros operativos por administrador/coordinador

**Input**: Design documents from `/specs/004-admin-edit-records/`
**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/

**Tests**: SE INCLUYEN (pedido en research.md Decisión 8 y quickstart.md). Por módulo: edición válida (persiste + audita), inválida (rechaza, sin auditoría), rol no autorizado (403), inventario derivado intacto. Más pruebas del `AuditoriaService`.

**Organization**: Fase transversal de auditoría (bloqueante) + una fase por módulo. Cada módulo es un incremento independiente y testeable.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Puede correr en paralelo (archivos distintos, sin dependencias)
- **[Story]**: Historia de usuario (US1–US7)

## Path Conventions

Monolito Laravel: modelos `app/Models/`, servicios `app/Services/`, traits `app/Traits/`, requests `app/Http/Requests/`, controladores `app/Http/Controllers/`, vistas `resources/views/`, rutas `routes/web.php`, pruebas `tests/Feature/`.

> ⚠️ **Nota sobre rutas**: todas las tareas de rutas editan el mismo archivo `routes/web.php`, por lo que **no son paralelizables entre sí** (sin `[P]`).

---

## Phase 1: Setup

- [X] T001 Crear el directorio de pruebas `tests/Feature/Edicion/`

---

## Phase 2: Foundational — Mecanismo transversal de auditoría (Blocking Prerequisites)

**Purpose**: Infraestructura compartida de auditoría y autorización. **Ningún módulo puede implementarse hasta completar esta fase.**

- [X] T002 Crear migración `database/migrations/2026_06_01_000000_create_cambios_auditoria_table.php` con columnas: `id`, `auditable_type`, `auditable_id`, `usuario_id` (FK users), `cambios` (json), `created_at`; índice en (`auditable_type`, `auditable_id`); sin `updated_at`
- [X] T003 [P] Crear modelo `app/Models/CambioAuditoria.php` con `$fillable` (auditable_type, auditable_id, usuario_id, cambios), cast `cambios => array`, `$timestamps` solo created_at, relaciones `morphTo('auditable')` y `belongsTo(User, 'usuario_id')`
- [X] T004 [P] Crear trait `app/Traits/Auditable.php` con relación `cambiosAuditoria(): MorphMany` (orden descendente por `created_at`)
- [X] T005 Crear servicio `app/Services/AuditoriaService.php` con `registrarCambios(Model $modelo, User $usuario): ?CambioAuditoria` — calcula el diff de los atributos `dirty` (`{campo: {anterior, nuevo}}`), inserta solo si hay cambios, devuelve `null` si no hay (depende de T003)
- [X] T006 [P] Crear parcial `resources/views/components/historial-auditoria.blade.php` que reciba un registro auditable y liste sus entradas (usuario, fecha, campos cambiados anterior → nuevo), más recientes primero
- [X] T007 [P] Crear prueba `tests/Feature/Edicion/AuditoriaServiceTest.php`: (a) editar 2 campos → 1 entrada con diff correcto; (b) guardar sin cambios → 0 entradas; (c) historial ordenado desc
- [X] T008 Ejecutar `php artisan migrate` y `php artisan test --filter=AuditoriaServiceTest`; confirmar verde

**Checkpoint**: Auditoría y autorización listas — pueden comenzar los módulos.

---

## Phase 3: User Story 1 - Editar una solicitud (Priority: P1) 🎯 MVP

**Goal**: Administrador/coordinador corrige campos de una `Solicitud` con auditoría, sin tocar estado ni inventario.

**Independent Test**: Editar una solicitud, cambiar un campo correctivo, guardar → valor reflejado + entrada de auditoría; rol no autorizado bloqueado.

- [X] T009 [P] [US1] Crear prueba `tests/Feature/Edicion/EditarSolicitudTest.php`: edición válida (persiste + audita), inválida (rechaza, sin auditoría), rol portero → 403, estado sin cambios
- [X] T010 [P] [US1] Aplicar el trait `Auditable` al modelo `app/Models/Solicitud.php`
- [X] T011 [P] [US1] Crear `app/Http/Requests/UpdateSolicitudRequest.php` (authorize: administrador|coordinador; reglas de los campos editables reusando `StoreSolicitudRequest`: cliente_id, numero_contenedor, naviera, puerto_origen, descripcion, fecha_solicitud)
- [X] T012 [US1] Añadir `edit()` y `update()` en `app/Http/Controllers/SolicitudController.php` (delgados; `fill(validated())` + `AuditoriaService::registrarCambios()` + `save()`)
- [X] T013 [US1] Registrar rutas `solicitudes.editar` (GET) y `solicitudes.update` (PUT) en `routes/web.php` con middleware `role:administrador|coordinador`
- [X] T014 [US1] Crear vista `resources/views/solicitudes/editar.blade.php` (formulario de campos editables) + incluir el parcial de historial + enlace "Editar" desde `solicitudes/show.blade.php`
- [X] T015 [US1] Ejecutar `php artisan test --filter=EditarSolicitudTest`; confirmar verde

**Checkpoint**: MVP funcional — edición de solicitudes con auditoría y autorización.

---

## Phase 4: User Story 2 - Editar un ingreso (gate-in) (Priority: P1)

**Goal**: Corregir un `GateEvent` de ingreso (hora, estado_fisico, notas) sin alterar inventario.

**Independent Test**: Editar un ingreso, cambiar un campo correctivo, guardar → persiste + audita; inventario asociado intacto.

- [X] T016 [P] [US2] Crear prueba `tests/Feature/Edicion/EditarGateInTest.php`: edición válida + audita, inválida, rol no autorizado → 403, inventario derivado intacto
- [X] T017 [P] [US2] Aplicar el trait `Auditable` al modelo `app/Models/GateEvent.php`
- [X] T018 [P] [US2] Crear `app/Http/Requests/UpdateGateInRequest.php` (authorize: administrador|coordinador; reglas: hora date, estado_fisico, notas)
- [X] T019 [US2] Añadir `edit()` y `update()` en `app/Http/Controllers/GateInController.php` (sobre el GateEvent de ingreso; usa `AuditoriaService`)
- [X] T020 [US2] Registrar rutas `gate-in.editar` (GET `gate-in/{gateEvent}/editar`) y `gate-in.update` (PUT) en `routes/web.php` con `role:administrador|coordinador`
- [X] T021 [US2] Crear vista `resources/views/gate-in/editar.blade.php` + parcial de historial + enlace "Editar" desde el listado/detalle de gate-in
- [X] T022 [US2] Ejecutar `php artisan test --filter=EditarGateInTest`; confirmar verde

**Checkpoint**: Solicitudes e ingresos editables.

---

## Phase 5: User Story 3 - Editar un vaciado (Priority: P2)

**Goal**: Corregir una `OrdenVaciado` (fecha_programada, supervisor_id, notas), incluso finalizada, sin alterar el resultado de inventario.

**Independent Test**: Editar un vaciado finalizado, cambiar un campo correctivo, guardar → persiste + audita; inventario intacto.

- [X] T023 [P] [US3] Crear prueba `tests/Feature/Edicion/EditarVaciadoTest.php`: edición válida + audita (incl. orden finalizada), inválida, rol no autorizado → 403
- [X] T024 [P] [US3] Aplicar el trait `Auditable` al modelo `app/Models/OrdenVaciado.php`
- [X] T025 [P] [US3] Crear `app/Http/Requests/UpdateOrdenVaciadoRequest.php` (authorize: administrador|coordinador; reglas: fecha_programada date, supervisor_id exists, notas)
- [X] T026 [US3] Añadir `edit()` y `update()` en `app/Http/Controllers/VaciadoController.php` (usa `AuditoriaService`)
- [X] T027 [US3] Registrar rutas `vaciado.editar` (GET) y `vaciado.update` (PUT) en `routes/web.php` con `role:administrador|coordinador`
- [X] T028 [US3] Crear vista `resources/views/vaciado/editar.blade.php` + parcial de historial + enlace "Editar" desde `vaciado/show.blade.php`
- [X] T029 [US3] Ejecutar `php artisan test --filter=EditarVaciadoTest`; confirmar verde

**Checkpoint**: Vaciado editable.

---

## Phase 6: User Story 4 - Editar una salida (gate-out) (Priority: P2)

**Goal**: Corregir un `GateEvent` de salida (hora, estado_fisico, notas), incluso con contenedor despachado.

**Independent Test**: Editar una salida registrada, cambiar un campo correctivo, guardar → persiste + audita.

- [X] T030 [P] [US4] Crear prueba `tests/Feature/Edicion/EditarGateOutTest.php`: edición válida + audita (contenedor despachado), inválida, rol no autorizado → 403
- [X] T031 [P] [US4] Crear `app/Http/Requests/UpdateGateOutRequest.php` (authorize: administrador|coordinador; reglas: hora date, estado_fisico, notas)

> Nota: el trait `Auditable` en `GateEvent` ya se aplicó en T017 (compartido ingreso/salida).

- [X] T032 [US4] Añadir `edit()` y `update()` en `app/Http/Controllers/GateOutController.php` (sobre el GateEvent de salida; usa `AuditoriaService`)
- [X] T033 [US4] Registrar rutas `gate-out.editar` (GET `gate-out/{gateEvent}/editar`) y `gate-out.update` (PUT) en `routes/web.php` con `role:administrador|coordinador`
- [X] T034 [US4] Crear vista `resources/views/gate-out/editar.blade.php` + parcial de historial + enlace "Editar" desde `gate-out/show.blade.php`
- [X] T035 [US4] Ejecutar `php artisan test --filter=EditarGateOutTest`; confirmar verde

**Checkpoint**: Salidas editables.

---

## Phase 7: User Story 5 - Editar un registro de almacenamiento (Priority: P2)

**Goal**: Corregir una `Referencia` (ubicacion_patio_id, codigo, descripcion, unidad_medida, fecha_ingreso) sin disparar movimiento de inventario.

**Independent Test**: Editar una referencia, corregir ubicación/descripción, guardar → persiste + audita; sin movimiento de inventario; cantidades sin cambio.

- [X] T036 [P] [US5] Crear prueba `tests/Feature/Edicion/EditarAlmacenamientoTest.php`: edición válida + audita, inválida, rol no autorizado → 403, cantidades de inventario intactas
- [X] T037 [P] [US5] Aplicar el trait `Auditable` al modelo `app/Models/Referencia.php`
- [X] T038 [P] [US5] Crear `app/Http/Requests/UpdateReferenciaRequest.php` (authorize: administrador|coordinador; reglas: ubicacion_patio_id exists, codigo, descripcion, unidad_medida, fecha_ingreso date; NO incluir cantidades)
- [X] T039 [US5] Añadir `edit()` y `update()` en `app/Http/Controllers/AlmacenamientoController.php` (usa `AuditoriaService`)
- [X] T040 [US5] Registrar rutas `inventario.editar` (GET `inventario/{referencia}/editar`) y `inventario.update` (PUT) en `routes/web.php` con `role:administrador|coordinador`
- [X] T041 [US5] Crear vista `resources/views/almacenamiento/editar.blade.php` + parcial de historial + enlace "Editar" desde el listado de inventario
- [X] T042 [US5] Ejecutar `php artisan test --filter=EditarAlmacenamientoTest`; confirmar verde

**Checkpoint**: Almacenamiento editable.

---

## Phase 8: User Story 6 - Editar una transferencia (Priority: P3)

**Goal**: Corregir datos descriptivos de una `Transferencia` (motivo, autorizacion_cliente) sin revertir cantidades.

**Independent Test**: Editar una transferencia, corregir un dato descriptivo, guardar → persiste + audita; cantidades movidas sin cambio.

- [X] T043 [P] [US6] Crear prueba `tests/Feature/Edicion/EditarTransferenciaTest.php`: edición válida + audita, inválida, rol no autorizado → 403, cantidades transferidas intactas
- [X] T044 [P] [US6] Aplicar el trait `Auditable` al modelo `app/Models/Transferencia.php`
- [X] T045 [P] [US6] Crear `app/Http/Requests/UpdateTransferenciaRequest.php` (authorize: administrador|coordinador; reglas: motivo, autorizacion_cliente; NO incluir cantidad ni vínculos origen/destino)
- [X] T046 [US6] Añadir `edit()` y `update()` en `app/Http/Controllers/TransferenciaController.php` (usa `AuditoriaService`)
- [X] T047 [US6] Registrar rutas `transferencias.editar` (GET) y `transferencias.update` (PUT) en `routes/web.php` con `role:administrador|coordinador`
- [X] T048 [US6] Crear vista `resources/views/transferencias/editar.blade.php` + parcial de historial + enlace "Editar" desde `transferencias/show.blade.php`
- [X] T049 [US6] Ejecutar `php artisan test --filter=EditarTransferenciaTest`; confirmar verde

**Checkpoint**: Transferencias editables.

---

## Phase 9: User Story 7 - Editar una entrega (Priority: P3)

**Goal**: Corregir una `OrdenCargue` (cliente_id, fecha_despacho, notas) sin alterar tarjas ni cantidades entregadas.

**Independent Test**: Editar una entrega con tarjas, corregir un dato descriptivo, guardar → persiste + audita; tarjas y cantidades intactas.

- [X] T050 [P] [US7] Crear prueba `tests/Feature/Edicion/EditarEntregaTest.php`: edición válida + audita, inválida, rol no autorizado → 403, tarjas/cantidades intactas
- [X] T051 [P] [US7] Aplicar el trait `Auditable` al modelo `app/Models/OrdenCargue.php`
- [X] T052 [P] [US7] Crear `app/Http/Requests/UpdateOrdenCargueRequest.php` (authorize: administrador|coordinador; reglas reusando `StoreOrdenCargueRequest`: cliente_id exists, fecha_despacho date, notas)
- [X] T053 [US7] Añadir `edit()` y `update()` en `app/Http/Controllers/EntregaController.php` (usa `AuditoriaService`)
- [X] T054 [US7] Registrar rutas `entregas.editar` (GET) y `entregas.update` (PUT) en `routes/web.php` con `role:administrador|coordinador`
- [X] T055 [US7] Crear vista `resources/views/entregas/editar.blade.php` + parcial de historial + enlace "Editar" desde `entregas/show.blade.php`
- [X] T056 [US7] Ejecutar `php artisan test --filter=EditarEntregaTest`; confirmar verde

**Checkpoint**: Los siete módulos son editables.

---

## Phase 10: Polish & Cross-Cutting Concerns

- [X] T057 Ejecutar la suite completa `php artisan test --filter=Edicion` y confirmar que los 7 módulos + auditoría pasan
- [X] T058 Ejecutar `php artisan test` completo para confirmar que no hay regresiones en otros flujos
- [ ] T059 Ejecutar la verificación manual de `specs/004-admin-edit-records/quickstart.md` (edición, auditoría, autorización, inventario intacto) en al menos solicitudes y una entrega

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: sin dependencias.
- **Foundational (Phase 2)**: depende de Setup. **BLOQUEA todos los módulos** (auditoría + autorización).
- **User Stories (Phase 3–9)**: dependen de Phase 2. Entre sí son independientes salvo por dos recursos compartidos:
  - `routes/web.php` (todas las tareas de ruta lo editan → secuenciales entre módulos).
  - `GateEvent` comparte el trait `Auditable` (aplicado una vez en T017; US4 lo reutiliza).
- **Polish (Phase 10)**: depende de todas las historias deseadas.

### User Story Dependencies

- US1 (Solicitudes, P1) y US2 (Ingresos, P1): independientes — MVP.
- US3 (Vaciado), US4 (Salidas), US5 (Almacenamiento) — P2.
- US6 (Transferencias), US7 (Entregas) — P3.
- US4 reutiliza el trait aplicado en US2 (mismo modelo `GateEvent`).

### Within Each User Story

- La prueba Feature se escribe primero (debe FALLAR antes de implementar).
- Trait + Request (paralelos) → controlador → ruta → vista → ejecutar prueba en verde.

### Parallel Opportunities

- Foundational: T003, T004, T006, T007 en paralelo (archivos distintos); T005 tras T003; T008 al final.
- Por módulo: la prueba, el trait y el Request son `[P]` (archivos distintos). El controlador, la ruta y la vista son secuenciales dentro del módulo.
- Entre módulos (tras Phase 2): pueden trabajarse en paralelo por distintos desarrolladores, **coordinando los cambios a `routes/web.php`** (recurso compartido).

---

## Parallel Example: arranque de un módulo (US1)

```bash
# En paralelo (archivos distintos):
Task: "Crear tests/Feature/Edicion/EditarSolicitudTest.php"     # T009
Task: "Aplicar trait Auditable a app/Models/Solicitud.php"      # T010
Task: "Crear app/Http/Requests/UpdateSolicitudRequest.php"      # T011
# Luego secuencial: controlador (T012) → ruta (T013) → vista (T014) → test (T015)
```

---

## Implementation Strategy

### MVP First (US1 Solicitudes)

1. Phase 1 (Setup) → Phase 2 (auditoría transversal) → Phase 3 (US1).
2. **STOP y VALIDAR**: editar una solicitud, ver auditoría, confirmar bloqueo por rol.
3. Demo del MVP.

### Incremental Delivery

1. Transversal de auditoría listo.
2. US1 Solicitudes → probar → demo (MVP).
3. US2 Ingresos → probar → demo.
4. US3/US4/US5 (P2) → probar → demo.
5. US6/US7 (P3) → probar → demo.

### Parallel Team Strategy

Tras Phase 2, repartir módulos entre desarrolladores. Coordinar los cambios a `routes/web.php` (único archivo compartido). El trait, Request, vista y prueba de cada módulo son independientes.

---

## Notes

- [P] = archivos distintos, sin dependencias.
- Auditoría con tabla propia (sin paquete externo) por el hosting compartido sin SSH.
- Solo campos correctivos: NUNCA exponer cantidades de inventario ni vínculos estructurales (FR-003/FR-004).
- Editable en cualquier estado; el estado NO cambia por editar otros campos (FR-005/FR-010).
- Commits por tarea o grupo lógico siguiendo Conventional Commits (`feat(edicion): ...`).
