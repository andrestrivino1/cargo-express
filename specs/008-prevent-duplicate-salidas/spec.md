# Feature Specification: Prevenir salidas de mercancía duplicadas (ODC)

**Feature Branch**: `008-prevent-duplicate-salidas`
**Created**: 2026-07-08
**Status**: Draft
**Input**: User description: "Prevenir salidas de mercancía duplicadas (ODC duplicados) con idempotencia del lado del servidor. Se siguen creando salidas duplicadas a pesar de un fix previo que solo agregó un guard client-side. Caso real: ODC-620 y ODC-621, dos POST separados que el servidor aceptó, cada uno creando su propia tarja + orden_cargue + consecutivo y descontando inventario dos veces."

## Contexto del problema

Al registrar una salida de mercancía, el sistema genera una Orden de Salida (ODC) con un consecutivo, crea la tarja, la orden de cargue y **descuenta el inventario** de las referencias despachadas. Se están registrando salidas **duplicadas**: un mismo despacho físico queda registrado dos veces, con dos consecutivos distintos (caso real: **ODC-620 y ODC-621**), descontando el inventario dos veces y obligando a un borrado y reajuste manual posterior.

Un intento previo de corrección solo agregó una protección en el navegador (deshabilitar el botón al enviar), pero el problema persiste porque cuando llegan dos envíos al servidor, este acepta ambos sin ninguna barrera.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Un despacho = una sola Orden de Salida (Priority: P1)

El despachador llena el formulario de nueva salida (cliente, referencias, cantidades, datos del conductor y las dos fotos obligatorias) y presiona "Registrar salida". Aunque el envío se repita —por doble clic, por reintento tras una espera larga mientras suben las fotos, o por volver atrás y reenviar— el sistema debe registrar **una sola** salida y generar **un solo** consecutivo ODC.

**Why this priority**: Es el corazón del problema. Sin esto, el inventario queda descuadrado y se requiere intervención manual en base de datos (borrar la salida duplicada y devolver stock), lo cual es riesgoso y costoso.

**Independent Test**: Enviar el mismo formulario de salida dos veces seguidas (simulando doble envío) y verificar que solo se creó una salida, un consecutivo ODC y un único descuento de inventario.

**Acceptance Scenarios**:

1. **Given** un despachador con el formulario de salida completo y válido, **When** el formulario se envía dos veces con el mismo intento (mismo token de formulario), **Then** el sistema crea exactamente una salida, asigna un solo consecutivo ODC y descuenta el inventario una sola vez.
2. **Given** que el primer envío ya creó la salida, **When** llega el segundo envío repetido, **Then** el sistema no crea una segunda salida y lleva al usuario a la Orden de Salida ya generada (misma ODC).
3. **Given** un despachador que registra una salida y luego, minutos después, registra **otra salida legítimamente distinta** para el mismo cliente, **When** envía el segundo formulario, **Then** el sistema sí crea la segunda salida (no bloquea salidas verdaderamente diferentes).

---

### User Story 2 - Retroalimentación clara ante un reenvío (Priority: P2)

Cuando un despachador reenvía sin querer una salida ya registrada, el sistema no muestra un error confuso ni una pantalla de fallo, sino que lo lleva a la salida que sí quedó registrada, con un mensaje que aclara que ese despacho ya estaba guardado.

**Why this priority**: Evita que el usuario, ante un mensaje de error, intente "arreglarlo" creando aún más duplicados o llamando a soporte.

**Independent Test**: Reenviar una salida ya creada y verificar que el usuario aterriza en la ODC existente con un mensaje informativo, sin traza de error.

**Acceptance Scenarios**:

1. **Given** una salida ya registrada por un primer envío, **When** el mismo intento se reenvía, **Then** el usuario ve la Orden de Salida existente y un mensaje que indica que el despacho ya había sido registrado.

---

### User Story 3 - Barrera de saldo se mantiene íntegra (Priority: P2)

La protección contra duplicados no debe abrir un hueco en la validación de saldo: si dos envíos compiten, el inventario nunca debe quedar descontado dos veces ni quedar en negativo.

**Why this priority**: El propósito último es la integridad del inventario; la idempotencia y el control de saldo deben ser consistentes entre sí.

**Independent Test**: Simular dos envíos concurrentes del mismo despacho y verificar que el saldo de las referencias se descuenta una sola vez y nunca queda negativo.

**Acceptance Scenarios**:

1. **Given** dos envíos del mismo intento que llegan casi simultáneamente, **When** el sistema los procesa, **Then** solo uno descuenta inventario y el otro se reconoce como repetido.

---

### Edge Cases

- **Doble clic muy rápido**: dos envíos con el mismo token → una sola salida.
- **Envío lento por fotos pesadas**: el usuario cree que no pasó nada y reenvía → una sola salida.
- **Botón "atrás" del navegador y reenviar** el formulario → una sola salida (o aterrizaje en la ODC ya creada).
- **Red inestable en el patio (celular)** con reintento automático → una sola salida.
- **Token ausente o manipulado** en el envío → el sistema rechaza el envío de forma segura y pide reintentar desde el formulario, sin crear una salida a medias.
- **Sesión expirada** entre abrir el formulario y enviarlo → el sistema maneja el caso sin crear duplicados ni salidas parciales.
- **Dos salidas legítimamente distintas** del mismo cliente en poco tiempo → ambas se registran (no se bloquean por parecerse).
- **Fallo a mitad del registro** (p. ej., al guardar una foto) → no debe quedar una salida a medias que consuma consecutivo ni descuente inventario sin generar la ODC completa.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: El sistema DEBE garantizar que un mismo intento de registro de salida produzca **como máximo una** salida (tarja), una orden de cargue, un consecutivo ODC y un único descuento de inventario, sin importar cuántas veces se reenvíe ese intento.
- **FR-002**: El sistema DEBE incorporar en cada formulario de nueva salida un identificador de intento de un solo uso, generado al mostrar el formulario.
- **FR-003**: El sistema DEBE reconocer y neutralizar en el **servidor** los envíos repetidos de un mismo intento; la protección NO puede depender únicamente del navegador.
- **FR-004**: Ante un envío repetido de un intento ya procesado, el sistema DEBE dirigir al usuario a la Orden de Salida ya creada, con un mensaje que aclare que el despacho ya estaba registrado, sin mostrar un error.
- **FR-005**: El sistema DEBE permitir registrar salidas verdaderamente distintas (diferente intento) para el mismo cliente sin bloquearlas, aunque coincidan en cliente, referencias o cantidades.
- **FR-006**: El sistema DEBE mantener la validación de saldo disponible existente, de modo que ni siquiera bajo envíos repetidos o concurrentes el inventario se descuente dos veces ni quede negativo.
- **FR-007**: El registro de una salida DEBE ser atómico: o se completa por entero (tarja + orden + consecutivo + descuento de inventario + evidencias) o no deja rastro parcial (no consume consecutivo ni descuenta inventario).
- **FR-008**: El sistema DEBE conservar la protección del lado del navegador (deshabilitar el botón al enviar) como mejora de experiencia, pero como capa adicional y no como única defensa.
- **FR-009**: El identificador de intento DEBE quedar invalidado tras el primer registro exitoso, de forma que no pueda reutilizarse para crear otra salida.
- **FR-010**: El sistema DEBE manejar de forma segura los envíos sin identificador de intento válido (ausente, ya usado o no reconocido), sin crear salidas parciales y orientando al usuario a reintentar.

### Non-Functional / Constraints

- **NFR-001**: La solución DEBE implementarse **sin agregar dependencias nuevas** que requieran instalación por línea de comandos (hosting compartido sin SSH). Debe apoyarse en las capacidades ya disponibles en la plataforma actual.
- **NFR-002**: La solución DEBE funcionar en el entorno de producción actual (base de datos compartida) y no asumir servicios externos adicionales.
- **NFR-003**: El mecanismo de idempotencia NO debe degradar de forma perceptible el tiempo de registro de una salida en condiciones normales.

### Key Entities *(include if feature involves data)*

- **Intento de salida (token de un solo uso)**: identificador único generado al abrir el formulario de nueva salida; representa un intento concreto de registrar un despacho. Se consume al registrarse la salida y queda asociado a la salida resultante para poder redirigir ante reenvíos.
- **Salida (Orden de Salida / ODC)**: registro del despacho con su consecutivo, cliente, referencias, cantidades, datos de conductor/vehículo y evidencias; es la entidad que no debe duplicarse.
- **Movimiento de inventario**: registro del descuento de saldo asociado a la salida; debe ocurrir una sola vez por intento.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Reenviar el mismo intento de salida (doble clic, reintento o "atrás" y reenviar) produce **exactamente una** Orden de Salida en el 100% de los casos probados.
- **SC-002**: Tras la puesta en producción, los incidentes de salidas duplicadas que requieren corrección manual en base de datos se reducen a **cero**.
- **SC-003**: Ante un reenvío, el usuario llega a la Orden de Salida ya existente en lugar de ver un error, en el 100% de los casos.
- **SC-004**: Dos salidas legítimamente distintas del mismo cliente se registran ambas correctamente (0% de bloqueos falsos).
- **SC-005**: Bajo envíos repetidos o concurrentes del mismo intento, el inventario de cada referencia se descuenta una sola vez y nunca queda en negativo (0 descuentos dobles).

## Assumptions

- La solución se centra en el flujo de **creación** de salida (nueva ODC). La **edición** de una salida existente ya no crea consecutivos ni descuenta inventario, por lo que queda fuera del riesgo de duplicación.
- La **limpieza de duplicados históricos** ya generados (p. ej., borrar ODC-621 y devolver stock) se realiza aparte mediante corrección manual y no forma parte de esta funcionalidad.
- El identificador de intento de un solo uso puede sustentarse en las capacidades ya presentes en la plataforma (almacenamiento de sesión/estado en base de datos), sin dependencias nuevas.
- Un "mismo intento" se define por el identificador embebido en el formulario, no por comparar el contenido de dos salidas; dos despachos con datos idénticos pero abiertos en formularios distintos son intentos distintos y ambos son válidos.
