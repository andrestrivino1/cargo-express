# Feature Specification: Permitir fechas anteriores en los campos de fecha de registro operativo

**Feature Branch**: `003-port-appointment-past-dates`  
**Created**: 2026-06-01  
**Status**: Draft  
**Input**: User description: "Cita en puerto debe poder elegir fechas anteriores ya que con el ajuste de importar inventario hay data vieja entonces se debe permitir hacer eso" + ampliación: "Apliquemos este ajuste a todos los sitios donde pida fechas para que sea más fácil y no después venir uno por uno"

## Resumen del alcance

Con la importación de inventario histórico, varios registros operativos corresponden a eventos que **ya ocurrieron**. Hoy varios campos de fecha que se ingresan al registrar operaciones exigen una fecha futura, lo que impide capturar datos históricos. Esta feature relaja esa restricción temporal en **todos los campos de fecha de registro operativo** que hoy bloquean fechas pasadas, de forma uniforme, para no tener que ajustarlos uno por uno más adelante.

**Campos afectados** (hoy exigen fecha futura → pasarán a admitir fechas pasadas, presentes y futuras):

- Cita en puerto (al asignar la orden de servicio)
- Fecha de despacho (al crear la orden de cargue)
- Fecha programada (al programar el vaciado)

**Fuera de alcance** (ya admiten cualquier fecha o no son campos de registro): fecha de solicitud, fecha de corte de importación, y los filtros de fecha de reportes (incluida la regla "fecha hasta ≥ fecha desde", que es un rango lógico y se conserva).

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Registrar una cita en puerto con fecha pasada para inventario histórico (Priority: P1)

Tras importar inventario antiguo, un coordinador o administrador asigna la orden de servicio a un contenedor cuyo evento de puerto ya ocurrió. El usuario debe poder elegir una fecha y hora anterior a la actual y guardar la orden sin que el sistema la rechace.

**Why this priority**: Es el motivo original del requerimiento; sin esto el inventario importado no puede completarse y la trazabilidad queda incompleta.

**Independent Test**: Asignar una orden de servicio eligiendo una cita en puerto con fecha anterior a hoy; verificar que se guarda sin error y que la fecha queda registrada tal cual.

**Acceptance Scenarios**:

1. **Given** un usuario con permiso para asignar la orden, **When** ingresa una cita en puerto con fecha/hora pasada, **Then** el sistema la guarda sin error de validación.
2. **Given** un usuario asignando la orden, **When** ingresa una fecha futura, **Then** el sistema también la acepta.
3. **Given** un usuario asignando la orden, **When** deja vacío el campo, **Then** el sistema sigue exigiéndolo como obligatorio.

---

### User Story 2 - Registrar una orden de cargue con fecha de despacho pasada (Priority: P1)

Al cargar datos históricos, un usuario crea una orden de cargue cuyo despacho ocurrió en el pasado. Debe poder ingresar una fecha de despacho anterior a hoy y guardar la orden.

**Why this priority**: Mismo motivo de datos históricos; la orden de cargue es parte central del flujo operativo importado.

**Independent Test**: Crear una orden de cargue con fecha de despacho anterior a hoy y confirmar que se guarda sin error de validación.

**Acceptance Scenarios**:

1. **Given** un usuario creando una orden de cargue, **When** ingresa una fecha de despacho pasada, **Then** el sistema la guarda sin error.
2. **Given** el mismo formulario, **When** deja vacía la fecha de despacho, **Then** el sistema sigue exigiéndola.

---

### User Story 3 - Programar un vaciado con fecha pasada para registro histórico (Priority: P2)

Al registrar operaciones históricas de vaciado, un usuario necesita capturar la fecha programada real, que puede ser anterior a hoy. Debe poder elegirla tanto en el formulario (sin que el control de fecha la bloquee) como al guardar.

**Why this priority**: Completa la cobertura de los campos de registro operativo; es importante pero el vaciado histórico es menos frecuente que cita en puerto y despacho.

**Independent Test**: Crear una orden de vaciado con fecha programada anterior a hoy y confirmar que el formulario permite seleccionarla y que se guarda sin error.

**Acceptance Scenarios**:

1. **Given** un usuario programando un vaciado, **When** selecciona una fecha programada pasada, **Then** el control de fecha del formulario se lo permite y el sistema la guarda sin error.
2. **Given** el mismo formulario, **When** deja vacía la fecha programada, **Then** el sistema sigue exigiéndola.
3. **Given** un contenedor que no está "En Patio", **When** se intenta programar el vaciado, **Then** el sistema mantiene la validación existente que lo rechaza (esta feature no la altera).

---

### User Story 4 - Comportamiento uniforme entre todos los flujos de registro de fechas (Priority: P2)

El usuario espera que la regla "se permiten fechas pasadas" aplique de manera consistente en todos los campos de registro operativo, igual que ya ocurre en el flujo de completar pendientes. No debe haber pantallas que acepten fechas pasadas y otras que las rechacen.

**Why this priority**: Evita confusión y retrabajo de ir corrigiendo pantalla por pantalla. Es el objetivo explícito de la ampliación del requerimiento.

**Independent Test**: Ingresar una fecha pasada en cada flujo afectado (cita en puerto, despacho, vaciado) y confirmar que todos la aceptan con el mismo criterio.

**Acceptance Scenarios**:

1. **Given** una fecha pasada válida, **When** se ingresa en cualquiera de los flujos afectados, **Then** todos la aceptan y la persisten con el valor ingresado.

---

### Edge Cases

- **Fecha inválida o con formato incorrecto**: el sistema debe seguir rechazándola con un mensaje claro en todos los campos (la relajación aplica solo al límite temporal, no al formato).
- **Campo vacío**: los campos que hoy son obligatorios siguen siendo obligatorios.
- **Fecha extremadamente antigua**: se acepta; no se impone límite inferior porque el inventario histórico puede tener cualquier antigüedad.
- **Fecha futura**: se mantiene aceptada en todos los campos afectados (no se introduce límite superior nuevo).
- **Validaciones de negocio no temporales** (p. ej. "el contenedor debe estar En Patio" para vaciado): permanecen intactas.
- **Filtros de reporte**: la regla "fecha hasta ≥ fecha desde" se conserva por ser un rango lógico, no un bloqueo de fechas pasadas.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: El sistema MUST permitir guardar una orden de servicio cuya **cita en puerto** sea una fecha/hora anterior al momento actual.
- **FR-002**: El sistema MUST permitir guardar una orden de cargue cuya **fecha de despacho** sea anterior a la fecha actual.
- **FR-003**: El sistema MUST permitir guardar una orden de vaciado cuya **fecha programada** sea anterior a la fecha actual, incluyendo que el control de fecha del formulario permita seleccionar fechas pasadas.
- **FR-004**: El sistema MUST mantener obligatorios los campos que hoy lo son (cita en puerto, fecha de despacho, fecha programada).
- **FR-005**: El sistema MUST seguir validando que cada campo de fecha tenga un formato válido y rechazar valores con formato inválido.
- **FR-006**: El sistema MUST seguir aceptando fechas futuras en todos los campos afectados (sin introducir un límite superior nuevo).
- **FR-007**: El sistema MUST conservar y mostrar cada fecha exactamente como fue ingresada, sin desplazarla ni normalizarla.
- **FR-008**: El sistema MUST aplicar el mismo criterio de aceptación de fechas pasadas de forma uniforme en todos los flujos de registro operativo afectados, consistente con el flujo de completar pendientes.
- **FR-009**: El sistema MUST preservar todas las validaciones de negocio no relacionadas con el límite temporal (p. ej. estado del contenedor en vaciado, existencia del cliente/contenedor, rango "fecha hasta ≥ fecha desde" en reportes).

### Key Entities *(include if feature involves data)*

- **Orden de Servicio**: Asignación de vehículo, conductor y **cita en puerto** a una solicitud. La cita en puerto admite valores pasados, presentes o futuros.
- **Orden de Cargue**: Registro de despacho con **fecha de despacho**, que admite valores pasados, presentes o futuros.
- **Orden de Vaciado**: Programación de vaciado de un contenedor con **fecha programada**, que admite valores pasados, presentes o futuros.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: El 100% de los intentos de registrar una cita en puerto, fecha de despacho o fecha programada con una fecha pasada válida se guardan correctamente, sin error de validación.
- **SC-002**: Los usuarios pueden completar el registro de inventario histórico importado sin ajustar manualmente ninguna fecha a una futura, eliminando ese paso en los tres flujos.
- **SC-003**: No se reportan rechazos por "la fecha debe ser futura" en ninguno de los tres flujos de registro para datos históricos.
- **SC-004**: Los tres campos siguen rechazando el 100% de las entradas vacías o con formato inválido (sin regresiones).
- **SC-005**: Las validaciones de negocio no temporales (estado del contenedor, existencia de cliente/contenedor, rango de fechas de reporte) siguen funcionando sin cambios.

## Assumptions

- El cambio aplica únicamente a los tres campos de fecha de **registro operativo** que hoy exigen fecha futura. No se modifican otras fechas (fecha de solicitud y fecha de corte de importación ya admiten cualquier valor; los filtros de reporte conservan su rango lógico).
- No se requiere límite inferior de fecha: cualquier fecha histórica válida es aceptable.
- Los permisos actuales de cada flujo se mantienen sin cambios; esta feature solo afecta la restricción temporal de las fechas.
- Se conserva la obligatoriedad y la validación de formato de cada campo.
