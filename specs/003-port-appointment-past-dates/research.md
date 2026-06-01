# Research: Permitir fechas anteriores en los campos de fecha de registro operativo

**Feature**: 003-port-appointment-past-dates
**Date**: 2026-06-01

No quedaron marcadores `NEEDS CLARIFICATION`. Se documentan las decisiones a partir del código existente.

## Decisión 1 — Inventario de campos con restricción de "solo futuro"

Se barrió el código en busca de reglas de validación temporal y atributos `min`/`max` en controles de fecha. Campos que **bloquean fechas pasadas** y entran en alcance:

| Campo | Ubicación | Restricción actual | Acción |
|---|---|---|---|
| `cita_puerto` | `app/Http/Requests/StoreOrdenServicioRequest.php:20` | `after:now` | Quitar `after:now` |
| `fecha_despacho` | `app/Http/Requests/StoreOrdenCargueRequest.php:18` | `after:today` | Quitar `after:today` |
| `fecha_programada` | `app/Http/Requests/StoreOrdenVaciadoRequest.php:20` | `after:today` | Quitar `after:today` |
| `fecha_programada` (UI) | `resources/views/vaciado/create.blade.php:49` | `min="now()->addDay()"` | Quitar atributo `min` |

Campos **fuera de alcance** (ya admiten cualquier fecha o no son bloqueos de pasado):

| Campo | Ubicación | Motivo |
|---|---|---|
| `fecha_solicitud` | `StoreSolicitudRequest.php` | Ya es `required|date` sin restricción de futuro |
| `fecha_corte` | `SubirImportacionRequest.php` | Ya es `nullable|date` |
| `fecha_hasta` (reporte) | `ReporteController.php:52` | `after_or_equal:fecha_desde` es un rango lógico (hasta ≥ desde), no un bloqueo de fechas pasadas |

- **Decision**: Relajar únicamente los 3 campos de registro operativo (+ el `min` de la vista de vaciado).
- **Rationale**: Son los únicos puntos que rechazan fechas pasadas en flujos de captura. Mantener `fecha_solicitud`, `fecha_corte` y el rango de reportes sin cambios respeta su intención original.
- **Alternatives considered**: *Eliminar también `after_or_equal:fecha_desde`*: rechazado — no bloquea fechas pasadas, es una validación de coherencia de rango que debe conservarse (FR-009).

## Decisión 2 — Punto de cambio: regla declarativa en cada FormRequest

- **Decision**: Eliminar el token de restricción temporal (`after:now` / `after:today`) de cada regla, dejando `['required', 'date']`.
- **Rationale**: Cambio mínimo, localizado y testeable; la restricción es declarativa. (KISS + SRP).
- **Alternatives considered**: *Regla condicional por rol/origen importado* y *Rule object personalizado*: rechazadas — añaden complejidad sin requerimiento que lo justifique; el spec pide permitir fechas pasadas de forma general y uniforme.

## Decisión 3 — ¿Centralizar la regla compartida?

- **Decision**: No centralizar; ajustar cada FormRequest de forma independiente.
- **Rationale**: Tras el cambio, los campos quedan en `['required', 'date']`, una regla trivial y estándar de Laravel. La regla de tres (KISS) no amerita una abstracción compartida para tres usos de una validación declarativa básica. La consistencia se logra por convención, no por una utilidad nueva.
- **Alternatives considered**: *Constante/trait con la regla*: rechazado por sobre-ingeniería para el alcance actual.

## Decisión 4 — Capa de presentación

- **Decision**: Solo modificar `vaciado/create.blade.php` (quitar `min`). Las demás vistas no requieren cambios.
- **Rationale**: `solicitudes/asignar.blade.php` (cita en puerto) y `entregas/create.blade.php` (fecha de despacho) usan controles de fecha **sin atributo `min`**, por lo que ya permiten seleccionar fechas pasadas. Solo `vaciado/create.blade.php` impone `min="now()->addDay()"`, que debe eliminarse para alinear el front con el back.
- **Alternatives considered**: Ninguna; verificado por inspección de las vistas.

## Decisión 5 — Persistencia / esquema

- **Decision**: Sin cambios de esquema ni migraciones.
- **Rationale**: Las columnas `cita_puerto`, `fecha_despacho` y `fecha_programada` son `datetime`/`date` que ya almacenan cualquier valor temporal; solo cambiaba la validación de entrada.

## Decisión 6 — Validaciones de negocio no temporales

- **Decision**: Conservar intactas todas las validaciones no relacionadas con el límite temporal, en particular el `withValidator` de `StoreOrdenVaciadoRequest` que exige que el contenedor esté "En Patio".
- **Rationale**: La feature solo relaja el límite de fecha; las reglas de negocio siguen siendo válidas (FR-009).

## Decisión 7 — Estrategia de pruebas

- **Decision**: Una prueba Feature por flujo afectado (cita en puerto, despacho, vaciado) cubriendo: fecha pasada se acepta y persiste; campo vacío se rechaza; formato inválido se rechaza; fecha futura sigue aceptándose. En vaciado, conservar/verificar la validación de estado del contenedor.
- **Rationale**: Cumple el principio VI (testeable) y protege contra regresiones de FR-004/FR-005/FR-006/FR-009.
- **Alternatives considered**: *Solo pruebas unitarias de los FormRequests*: complementarias, pero las pruebas Feature sobre los endpoints validan el comportamiento real de extremo a extremo.
