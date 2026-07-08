# Research: Idempotencia server-side para registro de salidas

**Feature**: 008-prevent-duplicate-salidas
**Fecha**: 2026-07-08

## Pregunta central

¿Cómo garantizar, del lado del servidor, que dos (o más) POST del mismo intento de registro de salida produzcan **una sola** salida (tarja + orden_cargue + consecutivo ODC + descuento de inventario), sin agregar dependencias nuevas y funcionando en hosting compartido sin SSH?

---

## Decisión 1 — Mecanismo de idempotencia: tabla con índice UNIQUE + "insert-first"

**Decisión**: Crear una tabla dedicada `idempotency_keys` con un índice **UNIQUE** sobre el token. En el `store`, dentro de la **misma transacción** que crea la salida, la **primera** sentencia es insertar la fila del token. Si el `INSERT` viola el UNIQUE → es un reenvío; se redirige a la salida ya creada. Si el `INSERT` tiene éxito → se procede a crear la salida y, al final, se asocia el `resource_id` (id de la tarja) a la fila del token.

**Rationale**:
- El índice UNIQUE de la base de datos es el **árbitro atómico**: es imposible que dos filas con el mismo token coexistan, sin importar cuántas peticiones concurrentes lleguen.
- **Serialización correcta bajo concurrencia (InnoDB)**: si la petición A ya insertó el token (transacción abierta) y la petición B intenta insertar el mismo token, B **espera** (lock wait) hasta que A haga commit o rollback. Si A confirma, B recibe el error de duplicado y ve la fila de A ya con `resource_id`; si A revierte, el insert de B procede. Es exactamente el comportamiento buscado.
- **Sin dependencias nuevas**: solo una migración (patrón ya usado en el proyecto — features 004 `cambios_auditoria` y 005 `secuencias` agregaron tablas propias en vez de paquetes).
- **Sin estado compartido entre instancias** más allá de la BD → compatible con escalado horizontal (Constitución, Principio VII).

**Alternativas consideradas**:
- **Token de un solo uso en sesión** (generar en `create`, consumir en `store`): descartada. El driver de sesión del proyecto es `database`, que **no** aplica bloqueo por petición como el driver `file`; dos POST concurrentes podrían leer el token antes de que cualquiera lo consuma → ventana de carrera. No es race-safe sin bloqueo explícito.
- **Deduplicar comparando el contenido** (cliente + referencias + cantidades + ventana de tiempo): descartada. Frágil y produce falsos positivos: bloquearía salidas legítimamente distintas con datos idénticos (viola FR-005).
- **Paquete de idempotencia de terceros**: descartada por NFR-001 (hosting sin SSH, sin `composer install`).
- **UNIQUE natural sobre `tarjas`** (p. ej. hash de payload): descartada, mismo problema de falsos positivos que la deduplicación por contenido.

---

## Decisión 2 — Generación y transporte del token

**Decisión**: El controlador genera un UUID v4 en `create()` y lo pasa a la vista; el formulario lo lleva en un campo oculto `idempotency_key`. En la vista se usa `old('idempotency_key', $tokenGenerado)` para que, ante un error de validación, el reenvío conserve **el mismo** token (el intento no cambió y nada se creó todavía).

**Rationale**:
- Un token por carga de formulario = un "intento". Coincide con la definición de la spec: dos formularios distintos con datos idénticos son intentos distintos y ambos válidos (FR-005).
- Reusar el token tras un fallo de validación evita que una corrección de datos cuente como intento nuevo, y sigue siendo seguro porque el token solo se "consume" cuando la salida se crea con éxito.

**Alternativas consideradas**:
- Generar el token en JavaScript: descartada; debe existir aunque el JS no corra (FR-003/FR-008).
- Regenerar token en cada render aun tras error de validación: innecesario y añade ruido; no aporta seguridad.

---

## Decisión 3 — Ubicación de la lógica (SRP)

**Decisión**: Introducir un servicio `IdempotencyService` inyectable con métodos pequeños:
- `reservar(string $token, string $scope, int $usuarioId): void` — inserta la fila; lanza excepción tipada si el token ya existe.
- `asociarRecurso(string $token, int $resourceId): void` — completa `resource_id` tras crear la salida.
- `recursoReservado(string $token, string $scope): ?int` — devuelve el `resource_id` existente para el reenvío.

`SalidaMercanciaService::registrar` orquesta: llama a `reservar()` como primera sentencia de su transacción y a `asociarRecurso()` al final. Ante duplicado, se lanza `SalidaDuplicadaException` (con el id de la tarja existente) que el **controlador** captura para redirigir. Los controladores solo orquestan (Constitución, Principios III y I).

**Rationale**:
- Mantiene `registrar()` como orquestador y evita engordarlo (Principio I: funciones ≤ 40 líneas).
- El `IdempotencyService` es reutilizable (p. ej. el módulo de Ingreso podría sufrir el mismo problema) sin sobre-diseñar (Principio V; queda genérico por `scope`, no atado al esquema de salida).

**Alternativas consideradas**:
- Meter toda la lógica en el controlador: viola SRP.
- Meterla inline en `registrar()`: engorda una función ya larga y no es reutilizable.

---

## Decisión 4 — Manejo del reenvío y de tokens inválidos

**Decisión**:
- **Reenvío de un intento ya procesado** → el controlador captura `SalidaDuplicadaException`, redirige a `salida.show` de la tarja existente con un mensaje informativo (no un error). (FR-004)
- **Token ausente / con formato inválido** → regla de validación `required|uuid` en el `FormRequest`; el usuario vuelve al formulario con mensaje de reintento, sin crear nada. (FR-010)

**Rationale**: Evita que el usuario, ante un error, genere más duplicados o escale a soporte (User Story 2).

---

## Decisión 5 — Retención de tokens

**Decisión**: No se implementa purga automática en esta iteración. Las filas son diminutas (token + ids + timestamp) y el volumen de operación es bajo. Se deja `created_at` indexado para permitir una limpieza periódica futura (p. ej. borrar tokens > 30 días) mediante un comando, si llegara a ser necesario.

**Rationale**: KISS (Principio V) — no diseñar para un problema de escala que aún no existe; dejar la puerta abierta con el índice.

**Alternativas consideradas**: Comando programado de limpieza desde ya — pospuesto por YAGNI; anotado como mejora futura.

---

## Riesgos y mitigaciones

| Riesgo | Mitigación |
|---|---|
| Espera de bloqueo (lock wait) de la 2ª petición mientras la 1ª sube fotos | La subida de fotos suele tardar < `innodb_lock_wait_timeout` (50s por defecto). Si expira, la 2ª recibe error controlado y el usuario reintenta; no se crea duplicado. |
| Diferencia de comportamiento MySQL 8 (dev) vs MariaDB (prod) | Ambos honran el índice UNIQUE y el bloqueo de inserción de InnoDB; el mecanismo no depende de features exclusivas. |
| Modo no estricto de MariaDB | No aplica: no dependemos de truncados ni de rangos numéricos, solo de la restricción UNIQUE. |

---

## Conclusión

Enfoque elegido: **tabla `idempotency_keys` con UNIQUE + insert-first dentro de la transacción de creación de la salida**, token UUID transportado en campo oculto, lógica encapsulada en `IdempotencyService`, redirección a la ODC existente ante reenvío. Cumple todos los FR/NFR sin dependencias nuevas y es race-safe.
