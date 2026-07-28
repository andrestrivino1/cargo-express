# Specification Quality Checklist: Reorganización de roles + módulos Citas y Portero

**Purpose**: Validate specification completeness and quality before proceeding to planning
**Created**: 2026-07-27
**Feature**: [spec.md](../spec.md)

## Content Quality

- [x] No implementation details (languages, frameworks, APIs)
- [x] Focused on user value and business needs
- [x] Written for non-technical stakeholders
- [x] All mandatory sections completed

## Requirement Completeness

- [x] No [NEEDS CLARIFICATION] markers remain — las 4 se resolvieron (R-001 a R-004)
- [x] Requirements are testable and unambiguous
- [x] Success criteria are measurable
- [x] Success criteria are technology-agnostic (no implementation details)
- [x] All acceptance scenarios are defined
- [x] Edge cases are identified
- [x] Scope is clearly bounded (sección Out of Scope)
- [x] Dependencies and assumptions identified

## Feature Readiness

- [x] All functional requirements have clear acceptance criteria
- [x] User scenarios cover primary flows
- [x] Feature meets measurable outcomes defined in Success Criteria
- [x] No implementation details leak into specification

## Notes

### Iteración 1

Se detectaron 3 ambigüedades de alto impacto sin valor por defecto razonable → C-001, C-002, C-003.

### Iteración 2 (tras respuesta del usuario)

El usuario definió el orden de la cadena: **Ingreso → Cita → Portero**. El portero valida que el vehículo que llegó tenga cita. Registrado como R-001 (resuelta) y propagado al spec:

- Contexto reescrito con la cadena operativa de tres eslabones.
- US1 ahora parte de un ingreso ya registrado (se selecciona el contenedor, no se redigita).
- US2 incorpora la búsqueda por placa/contenedor y el contraste de datos en la puerta.
- US7 (nueva, P3): seguimiento del estado de cita desde el ingreso.
- FR renumerados: 45 requisitos. Nuevos FR-002 (vínculo con ingreso), FR-015 a FR-017 (validación en puerta), FR-045 (visibilidad desde el ingreso).
- SC-011 a SC-013 añadidos.

**C-001 fue reformulada** en esta iteración: la respuesta del usuario aclaró el orden pero destapó un conflicto con el módulo Ingreso actual, que asume mercancía ya llegada.

### Iteración 3 (decisiones finales)

Las tres pendientes quedaron resueltas. Checklist completo.

| # | Decisión | Efecto en el spec |
|---|----------|-------------------|
| R-002 | El módulo Ingreso **no cambia**: el inventario se cuenta al registrar el ingreso, aunque el vehículo llegue después. Las diferencias se corrigen en el vaciado vía novedades | Alcance acotado: citas y portería se construyen encima, sin tocar el ledger ni el ciclo de vida del contenedor |
| R-003 | Rol nuevo llamado **`operaciones`**; el portero pierde Ingreso y Salida **por completo**, sin lectura residual | FR-026, FR-028 a FR-030, US3 |
| R-004 | Los usuarios con roles retirados **conservan sus permisos** hasta reasignación manual | FR-041, FR-042 nuevos; US6 con dos escenarios más |

### Riesgos aceptados por R-002

Verificados en el código y documentados en el spec. Ninguno bloquea la decisión; los cuatro son condiciones preexistentes del módulo de vaciado y quedan **fuera del alcance** de esta funcionalidad.

| Riesgo | Evidencia |
|--------|-----------|
| El inventario cuenta mercancía que aún no llegó físicamente | `StoreIngresoMercanciaRequest.php:26` (fecha ≤ hoy) + `IngresoMercanciaService.php:45,116` (estado `EnPatio` + entrada al ledger inmediatos) |
| Las novedades de vaciado solo descuentan, nunca suman: un sobrante no se puede corregir | `VaciadoService.php:100` — `max(0, cantidad_actual - cantidad_afectada)` |
| Las novedades ajustan el saldo sin escribir en el ledger `movimientos_inventario` | `VaciadoService.php:97-103` — no inyecta `MovimientoInventarioService`; mismo patrón que el fix de transferencias (`756f996d`) |
| La notificación de novedad no llega al cliente en el flujo nuevo | `VaciadoService.php:110` depende de `contenedor → ordenServicio → solicitud → cliente`, cadena que los contenedores creados desde Ingreso no tienen |

### Otros hallazgos

- Varias rutas de edición administrativa están reservadas a `administrador|coordinador` (`routes/web.php:92-93` y otras); retirar `coordinador` dejaría esas funciones solo en manos del administrador (comportamiento deseado, pero debe verificarse). Capturado en FR-043 y en Dependencies.
- El resto se resolvió con supuestos documentados en la sección Assumptions.

## Estado final

**APROBADO** — 47 requisitos funcionales, 7 historias de usuario priorizadas, 13 criterios de éxito medibles, 0 marcadores de clarificación pendientes. Listo para `/speckit.plan`.
