# Specification Quality Checklist: Corrección de cantidades en ingreso, retiro de productos en almacenamiento y landing del sitio

**Purpose**: Validate specification completeness and quality before proceeding to planning
**Created**: 2026-09-25
**Feature**: [spec.md](../spec.md)

## Content Quality

- [x] No implementation details (languages, frameworks, APIs)
- [x] Focused on user value and business needs
- [x] Written for non-technical stakeholders
- [x] All mandatory sections completed

## Requirement Completeness

- [x] No [NEEDS CLARIFICATION] markers remain
- [x] Requirements are testable and unambiguous
- [x] Success criteria are measurable
- [x] Success criteria are technology-agnostic (no implementation details)
- [x] All acceptance scenarios are defined
- [x] Edge cases are identified
- [x] Scope is clearly bounded
- [x] Dependencies and assumptions identified

## Feature Readiness

- [x] All functional requirements have clear acceptance criteria
- [x] User scenarios cover primary flows
- [x] Feature meets measurable outcomes defined in Success Criteria
- [x] No implementation details leak into specification

## Notes

### Iteración 1 — 2026-09-25

12 de 13 ítems pasaron. Único pendiente: 3 marcadores `[NEEDS CLARIFICATION]`, uno por historia, sobre decisiones sin valor por defecto defendible.

### Iteración 2 — 2026-09-25 (aclaraciones resueltas)

**13 de 13 ítems pasan.** Las tres preguntas fueron respondidas y quedaron incorporadas a la especificación:

1. **US1 — corrección con mercancía ya movida**: se permite siempre. Lo disponible se ajusta en la misma diferencia que lo declarado, conservando lo ya consumido, y se rechaza declarar menos de lo que ya salió (FR-004, FR-005, FR-006; escenarios 5 y 6 de la US1).
2. **US2 — eliminar pasa a ser retirar**: la referencia sale del inventario vigente y su historial completo se conserva. Esto amplió el alcance de la historia: exclusión de listados, exportables, ubicación y vista del cliente; bloqueo en operaciones nuevas; baja registrada en el historial de inventario; filtro para consultar retiradas (FR-011 a FR-020).
3. **US3 — registro público**: se retira, tanto el enlace como el acceso directo. Además, la dirección raíz siempre apunta a la pantalla de acceso (FR-021 a FR-024).

Decisiones tomadas por cuenta propia al redactar, registradas en *Assumptions* para que sean fáciles de revertir:

- **Motivo obligatorio al retirar**: al conservarse el historial, un retiro sin explicación no es interpretable meses después. Es el punto más fácil de soltar si la operación prefiere no pedirlo.
- **"Siempre que ingresen a la URL muestre el login"**: se interpretó como que la raíz siempre apunta a la pantalla de acceso; a quien ya tiene sesión, esa pantalla lo lleva a su tablero en lugar de pedirle credenciales otra vez.

Observaciones sobre los demás ítems:

- **Sin detalles de implementación**: los requisitos hablan de "referencias", "inventario", "auditoría" y "pantalla de acceso" —conceptos del negocio—, sin nombrar rutas, tablas, clases ni framework. La mención a "la página genérica del framework" en el contexto describe lo que el usuario ve hoy, no una instrucción técnica.
- **Criterios medibles**: SC-001 (2 minutos), SC-002/SC-004/SC-005/SC-007 (100 %), SC-006 (cero solicitudes fuera del sistema) y SC-008 (un solo paso) son verificables sin conocer la implementación.
- **Alcance acotado**: *Out of Scope* delimita lo que no entra, incluyendo dos puntos que la decisión de "retiro" hace explícitos: reversar un retiro y el borrado físico de referencias.

Listo para `/speckit.plan`.
