# ADR 0003: Idempotencia server-side del registro de salidas con tabla `idempotency_keys`

**Status**: Accepted
**Date**: 2026-07-08
**Feature**: [008-prevent-duplicate-salidas](../../specs/008-prevent-duplicate-salidas/spec.md)

## Contexto

El registro de salida de mercancía crea una tarja con consecutivo ODC, una orden de cargue y **descuenta inventario**. Se estaban generando salidas duplicadas (caso real ODC-620/621): dos POST separados del mismo despacho que el servidor aceptaba, descontando inventario dos veces y obligando a corrección manual en base de datos. El único guard previo era del navegador (deshabilitar el botón), insuficiente ante doble-clic, envío lento por las dos fotos obligatorias, reintento por red inestable o "atrás" y reenviar. Restricción: hosting compartido sin SSH → sin dependencias nuevas.

## Decisión

Barrera de idempotencia en el **servidor** con una tabla `idempotency_keys (id, token UNIQUE, scope, usuario_id, resource_id nullable, created_at)`. Un token UUID de un solo uso viaja en el formulario (`create()` lo genera, campo oculto con `old()`). En `store()`, **dentro de la misma transacción** que crea la salida, la primera sentencia inserta el token: si viola el UNIQUE es un reenvío → se redirige a la ODC ya creada; si no, se crea la salida y se asocia el `resource_id`. La lógica vive en `IdempotencyService`; el servicio de salida lanza `SalidaDuplicadaException` y el controlador la captura para redirigir.

## Opciones consideradas

### A. Token de un solo uso en sesión (consumir en `store`)

**Rechazada.** El driver de sesión es `database`, que no aplica bloqueo por petición como el driver `file`; dos POST concurrentes pueden leer el token antes de que cualquiera lo consuma → ventana de carrera.

### B. Deduplicar por contenido (cliente + referencias + cantidades + ventana de tiempo)

**Rechazada.** Frágil y con falsos positivos: bloquearía salidas legítimamente distintas con datos idénticos (viola FR-005).

### C. Paquete de idempotencia de terceros

**Rechazada.** Requiere `composer install`, imposible en hosting compartido sin SSH (NFR-001).

### D. Tabla `idempotency_keys` con UNIQUE + insert-first (elegida)

- El índice UNIQUE es el árbitro atómico: imposible dos filas con el mismo token.
- **Serialización correcta bajo concurrencia (InnoDB)**: si A ya insertó el token (transacción abierta) y B intenta el mismo, B espera al commit/rollback de A. Si A confirma, B ve la fila con `resource_id` y redirige; si A revierte, B procede. Exactamente el comportamiento buscado.
- **Atomicidad**: al insertar el token dentro de la misma transacción que la salida, un rollback (p. ej. saldo insuficiente) elimina también la fila del token, dejándolo libre para reintentar (FR-007).
- Sin dependencias nuevas (solo 1 migración), patrón ya usado en el proyecto (ADR 0001/0002 agregaron tablas propias).
- Genérico por `scope`: reutilizable por otros módulos (p. ej. Ingreso) sin duplicar lógica.

## Consecuencias

**Positivas**

- Un solo intento = una sola ODC, aun con reenvíos o concurrencia; el inventario se descuenta una sola vez.
- El reenvío es amable (redirige a la ODC existente con mensaje informativo), no un error.
- Sin estado compartido salvo la BD → compatible con escalado horizontal.

**Negativas**

- Una tabla más y un INSERT+UPDATE por registro (costo despreciable).
- La 2ª petición concurrente puede esperar hasta `innodb_lock_wait_timeout` mientras la 1ª sube fotos; si expira, recibe un error controlado y reintenta (no se crea duplicado).
- Los tokens se acumulan; la purga periódica se pospone (YAGNI) dejando `created_at` indexado.

## Referencias

- [research.md](../../specs/008-prevent-duplicate-salidas/research.md)
- [data-model.md](../../specs/008-prevent-duplicate-salidas/data-model.md)
- [contracts/salida-store.md](../../specs/008-prevent-duplicate-salidas/contracts/salida-store.md)
