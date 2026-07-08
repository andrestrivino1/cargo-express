---
description: "Task list for feature 008 - Prevenir salidas duplicadas (idempotencia)"
---

# Tasks: Prevenir salidas de mercancía duplicadas (ODC)

**Input**: Design documents from `/specs/008-prevent-duplicate-salidas/`
**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/salida-store.md

**Tests**: INCLUIDOS. La spec define pruebas independientes por historia y la constitución (Principio VI) exige pruebas de integración para flujos críticos (descuento de inventario / consecutivo).

**Organization**: Tareas agrupadas por user story para implementación y prueba independientes.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Puede correr en paralelo (archivo distinto, sin dependencias pendientes)
- **[Story]**: US1 / US2 / US3 (mapea a las historias de spec.md)

## Path Conventions

Monolito Laravel; rutas relativas a la raíz del repo (`app/`, `database/`, `resources/`, `tests/`).

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Esquema y modelo de la barrera de idempotencia.

- [X] T001 Crear migración `database/migrations/2026_07_08_000001_create_idempotency_keys_table.php` con columnas `id`, `token` char(36) **UNIQUE**, `scope` varchar(40), `usuario_id` FK→`users.id`, `resource_id` unsignedBigInteger nullable, `created_at` useCurrent; índices (`scope`,`resource_id`) y (`created_at`). (Ver data-model.md)
- [X] T002 [P] Crear modelo `app/Models/IdempotencyKey.php` con `$fillable = ['token','scope','usuario_id','resource_id']`, `public $timestamps = false` y `const CREATED_AT` gestionado como en `MovimientoInventario`.

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Mecanismo reutilizable de idempotencia. **Bloquea todas las historias.**

**⚠️ CRITICAL**: Ninguna historia puede completarse hasta terminar esta fase.

- [X] T003 Crear excepción `app/Exceptions/SalidaDuplicadaException.php` que transporte el `tarjaId` existente (getter `tarjaId(): int`).
- [X] T004 Crear servicio `app/Services/IdempotencyService.php` con: `reservar(string $token, string $scope, int $usuarioId): void` (INSERT; ante duplicado UNIQUE lanza excepción/senal reconocible), `asociarRecurso(string $token, int $resourceId): void` (UPDATE `resource_id`), `recursoReservado(string $token, string $scope): ?int` (SELECT del `resource_id`). Métodos cortos, sin acceso directo a otros modelos de negocio.
- [X] T005 Ejecutar `php artisan migrate` y verificar la tabla `idempotency_keys` con su índice UNIQUE.

**Checkpoint**: Mecanismo de idempotencia disponible para inyectar.

---

## Phase 3: User Story 1 - Un despacho = una sola ODC (Priority: P1) 🎯 MVP

**Goal**: Que un mismo intento (mismo token) cree **una sola** salida aunque el formulario se envíe varias veces, y que intentos distintos sí creen salidas distintas.

**Independent Test**: Enviar el mismo formulario dos veces con el mismo `idempotency_key` → una sola tarja, un solo consecutivo, inventario descontado una vez; con dos tokens distintos → dos salidas.

### Tests for User Story 1 ⚠️ (escribir primero, deben fallar)

- [X] T006 [US1] Crear `tests/Feature/SalidaIdempotenciaTest.php` con prueba: doble POST a `salida.store` con el **mismo** `idempotency_key` → existe exactamente 1 `Tarja` con consecutivo, 1 `OrdenCargue`, N `MovimientoInventario`, y `referencias.cantidad_actual` descontado **una** sola vez.
- [X] T007 [US1] En el mismo archivo, prueba: dos POST con `idempotency_key` **distintos** y mismos datos/cliente → se crean **2** tarjas (FR-005).
- [X] T008 [US1] En el mismo archivo, prueba: POST sin `idempotency_key` o con formato no-UUID → 302 back con error de validación, **0** tarjas creadas (FR-010).

### Implementation for User Story 1

- [X] T009 [US1] Añadir regla `'idempotency_key' => ['required','uuid']` en `app/Http/Requests/StoreSalidaMercanciaRequest.php`.
- [X] T010 [US1] En `app/Services/SalidaMercanciaService.php`: inyectar `IdempotencyService`; dentro de `registrar()` llamar `reservar($data['idempotency_key'], 'salida', $despachador->id)` como **primera** sentencia de la transacción y `asociarRecurso($token, $tarja->id)` tras crear la tarja; propagar `SalidaDuplicadaException` (con `recursoReservado()` para adjuntar el `tarjaId`) cuando el token ya existía.
- [X] T011 [US1] En `app/Http/Controllers/SalidaMercanciaController.php`: en `create()` generar `$idempotencyKey = (string) Str::uuid()` y pasarlo a la vista; en `store()` envolver la llamada al servicio en try/catch de `SalidaDuplicadaException`.
- [X] T012 [US1] En `resources/views/salida/create.blade.php`: agregar `<input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', $idempotencyKey) }}">`; conservar el guard JS anti doble-submit existente.

**Checkpoint**: MVP funcional — el doble envío ya no duplica la salida.

---

## Phase 4: User Story 2 - Retroalimentación clara ante un reenvío (Priority: P2)

**Goal**: Ante un reenvío de un intento ya procesado, el usuario aterriza en la ODC existente con un mensaje informativo, sin ver un error.

**Independent Test**: Reenviar una salida ya creada → 302 a `salida.show` de la tarja original con flash informativo; sin traza de error.

### Tests for User Story 2 ⚠️

- [X] T013 [US2] En `tests/Feature/SalidaIdempotenciaTest.php`, prueba: segundo POST con token repetido → redirige a `salida.show` de la **misma** tarja y la sesión contiene un flash informativo ("el despacho ya estaba registrado"); status no es 5xx.

### Implementation for User Story 2

- [X] T014 [US2] En `SalidaMercanciaController::store()`, en el catch de `SalidaDuplicadaException`: `redirect()->route('salida.show', $e->tarjaId())->with('info', 'Este despacho ya estaba registrado (ODC existente).')`.

**Checkpoint**: US1 + US2 funcionan; el reenvío es amable y no genera duplicados.

---

## Phase 5: User Story 3 - Barrera de saldo íntegra (Priority: P2)

**Goal**: Garantizar que ni con reenvíos ni con fallos parciales el inventario se descuente dos veces o quede negativo, y que un fallo revierta también la reserva del token.

**Independent Test**: Provocar saldo insuficiente / fallo dentro de la transacción → no se crea salida, no avanza el consecutivo, el token queda libre para reintentar; y el descuento nunca deja `cantidad_actual` negativo.

### Tests for User Story 3 ⚠️

- [X] T015 [US3] En `tests/Feature/SalidaIdempotenciaTest.php`, prueba: POST con cantidad > saldo disponible → `ValidationException` (back con errores), **0** tarjas, **0** filas en `idempotency_keys` (transacción revertida, token libre) y `cantidad_actual` intacto (FR-006, FR-007, C5).
- [X] T016 [US3] En el mismo archivo, prueba: tras un reenvío del mismo token, la suma de `MovimientoInventario` tipo salida y el descuento sobre la referencia corresponden a **un** despacho (no doble) (SC-005).

### Implementation for User Story 3

- [X] T017 [US3] Verificar en `SalidaMercanciaService::registrar()` que `reservar()` ocurre **dentro** de la misma `DB::transaction` que el descuento de inventario y la creación de tarja (para que un rollback elimine también la fila del token); ajustar el orden si fuese necesario. Sin lógica nueva de negocio, solo garantizar atomicidad (FR-007).

**Checkpoint**: Las tres historias funcionan de forma independiente y el inventario queda íntegro.

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Documentación, despliegue y validación final.

- [X] T018 [P] Crear ADR `docs/adr/0003-idempotencia-registro-salidas.md` documentando la decisión (tabla UNIQUE + insert-first) según Governance de la constitución.
- [X] T019 [P] Artefacto de despliegue `specs/008-prevent-duplicate-salidas/deploy-prod.sql` con prelude import-safe (`DROP TABLE IF EXISTS idempotency_keys`) para el dump de producción.
- [X] T020 Ejecutar `php artisan test --filter=SalidaIdempotenciaTest` — 6/6 pruebas pasan.
- [~] T021 Validación manual siguiendo `quickstart.md` (doble clic real + "atrás" y reenviar). **Pendiente**: requiere app + MySQL corriendo en el entorno del usuario. El comportamiento ya está cubierto por `SalidaIdempotenciaTest` (POST end-to-end contra el endpoint real).

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: sin dependencias.
- **Foundational (Phase 2)**: depende de Setup; **bloquea** todas las historias.
- **User Stories (Phase 3–5)**: dependen de Foundational.
  - US1 (P1) es el MVP y debe ir primero.
  - US2 (P2) depende de que el catch de `store()` exista (T011) → construye sobre US1.
  - US3 (P2) valida atomicidad del mecanismo de US1 → construye sobre US1.
- **Polish (Phase 6)**: tras completar las historias deseadas.

### User Story Dependencies

- **US1**: solo Foundational.
- **US2**: Foundational + T011 (catch en el controller). No modifica archivos de US1 salvo el `store()` (misma persona/PR recomendado).
- **US3**: Foundational + T010 (transacción en el servicio).

### Within Each User Story

- Tests primero (deben fallar), luego implementación.
- Modelo → servicio → controller → vista.

### Parallel Opportunities

- T002 [P] en paralelo con T001 no (T002 no depende del archivo de migración, pero conviene tras definir columnas; puede ir en paralelo con seguridad).
- T018 y T019 [P] entre sí (archivos distintos: ADR vs procedimiento de deploy).
- Las pruebas de una misma historia comparten `SalidaIdempotenciaTest.php` → **no** marcar [P] entre ellas.

---

## Parallel Example: Phase 6

```bash
# Documentación y deploy en paralelo (archivos distintos):
Task: "Crear ADR docs/adr/0001-idempotencia-registro-salidas.md"
Task: "Actualizar procedimiento de dump para incluir idempotency_keys"
```

---

## Implementation Strategy

### MVP First (User Story 1)

1. Phase 1 (Setup) → Phase 2 (Foundational) → Phase 3 (US1).
2. **STOP y VALIDAR**: doble envío del mismo token crea una sola ODC.
3. Desplegar si está listo (ya resuelve el problema reportado).

### Incremental Delivery

1. Setup + Foundational → mecanismo listo.
2. US1 → barrera funcional (MVP). Test + demo.
3. US2 → UX de reenvío. Test + demo.
4. US3 → integridad de inventario verificada. Test + demo.
5. Polish → ADR, deploy, validación manual.

---

## Notes

- **Sin dependencias nuevas**: solo 1 migración (hosting compartido sin SSH).
- La limpieza de la ODC-621 histórica es un SQL manual aparte, fuera de estas tareas.
- Commit por tarea o grupo lógico; `feat(salida): idempotencia server-side anti-duplicados`.
- Total: **21 tareas** — Setup 2, Foundational 3, US1 7, US2 2, US3 3, Polish 4.
