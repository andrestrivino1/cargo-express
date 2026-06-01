# Feature Specification: Edición de registros operativos por administrador/coordinador

**Feature Branch**: `004-admin-edit-records`  
**Created**: 2026-06-01  
**Status**: Draft  
**Input**: User description: "El administrador debe poder modificar (editar) los registros existentes en los módulos: solicitudes, ingresos (gate-in), vaciado, salidas (gate-out), almacenamiento, transferencias y entregas"

## Resumen del alcance

Hoy los módulos operativos solo permiten **crear** y **consultar** registros; no existe forma de **corregir** un registro ya guardado. Esto obliga a convivir con datos errados (agravado por la importación de inventario histórico). Esta feature habilita la **edición correctiva** de los registros existentes en siete módulos —solicitudes, ingresos (gate-in), vaciado, salidas (gate-out), almacenamiento, transferencias y entregas— para los roles **administrador** y **coordinador**, con **historial de auditoría** de cada cambio.

**Decisiones de alcance confirmadas:**

- **Edición correctiva**: se editan campos descriptivos/correctivos (fechas, placas, conductor, cliente, notas, observaciones, ubicación, etc.). El sistema **no** recalcula ni reajusta automáticamente el inventario ni los movimientos derivados.
- **Cualquier estado**: un registro puede editarse sin importar su estado (incluso cerrado/terminal: contenedor despachado, salida registrada, vaciado finalizado). Es una herramienta de corrección de datos.
- **Auditoría obligatoria**: cada modificación queda registrada (quién, cuándo, valor anterior y valor nuevo).
- **Roles**: administrador y coordinador.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Editar una solicitud (Priority: P1)

Un administrador o coordinador detecta un dato errado en una solicitud existente (cliente, número de contenedor, naviera, puerto de origen, descripción o fecha de solicitud) y necesita corregirlo sin tener que anular y recrear el registro.

**Why this priority**: La solicitud es el inicio del flujo; un error aquí se propaga visualmente a todo el seguimiento. Es el caso de corrección más frecuente y el que establece el patrón (formulario de edición + guardado + auditoría) reutilizable por los demás módulos. MVP.

**Independent Test**: Abrir una solicitud existente, cambiar uno o más campos correctivos, guardar, y verificar que los nuevos valores se reflejan en la consulta y que el cambio quedó auditado.

**Acceptance Scenarios**:

1. **Given** un usuario administrador o coordinador, **When** edita un campo correctivo de una solicitud y guarda, **Then** el sistema persiste el nuevo valor y muestra confirmación de éxito.
2. **Given** un valor inválido (campo obligatorio vacío o formato incorrecto), **When** intenta guardar, **Then** el sistema rechaza el cambio con un mensaje claro y conserva el valor anterior.
3. **Given** un usuario sin rol administrador ni coordinador, **When** intenta acceder a la edición, **Then** el sistema se lo impide.
4. **Given** una edición guardada con éxito, **When** se consulta el historial de auditoría del registro, **Then** aparece quién hizo el cambio, cuándo, y el valor anterior y nuevo.

---

### User Story 2 - Editar un ingreso (gate-in) (Priority: P1)

Un administrador o coordinador corrige los datos de un ingreso ya registrado (p. ej. fecha/hora de ingreso, placa del vehículo, datos del transportista u observaciones) sin alterar el inventario que ese ingreso pudo haber generado.

**Why this priority**: Los ingresos son alto volumen y origen frecuente de errores de digitación; corregirlos sin rehacer el inventario es muy valioso.

**Independent Test**: Editar un ingreso existente, cambiar campos correctivos, guardar, y verificar que el inventario asociado **no** se altera y que el cambio queda auditado.

**Acceptance Scenarios**:

1. **Given** un ingreso existente, **When** un administrador/coordinador corrige un campo descriptivo y guarda, **Then** se persiste el cambio sin modificar las cantidades de inventario derivadas.
2. **Given** un ingreso ya asociado a inventario, **When** se edita, **Then** el sistema no recalcula ni mueve stock automáticamente.

---

### User Story 3 - Editar un vaciado (Priority: P2)

Un administrador o coordinador corrige datos de una orden de vaciado existente (fecha programada, supervisor, notas/observaciones), incluso si ya está finalizada.

**Why this priority**: Importante para mantener la trazabilidad correcta, pero de menor frecuencia que solicitudes e ingresos.

**Independent Test**: Editar una orden de vaciado (incluida una finalizada), cambiar campos correctivos, guardar, y verificar persistencia y auditoría sin alterar el inventario resultante del vaciado.

**Acceptance Scenarios**:

1. **Given** una orden de vaciado finalizada, **When** un administrador/coordinador corrige un campo correctivo y guarda, **Then** el sistema persiste el cambio sin alterar el resultado de inventario del vaciado.

---

### User Story 4 - Editar una salida (gate-out) (Priority: P2)

Un administrador o coordinador corrige los datos de una salida registrada (fecha/hora de salida, datos del vehículo/transportista, observaciones), incluso si el contenedor ya fue despachado.

**Why this priority**: Necesario para la exactitud de los reportes de salida; frecuencia media.

**Independent Test**: Editar una salida ya registrada, cambiar campos correctivos, guardar, y verificar persistencia y auditoría.

**Acceptance Scenarios**:

1. **Given** una salida ya registrada (contenedor despachado), **When** un administrador/coordinador corrige un campo correctivo y guarda, **Then** el sistema persiste el cambio y lo audita.

---

### User Story 5 - Editar un registro de almacenamiento (Priority: P2)

Un administrador o coordinador corrige datos descriptivos de un registro de almacenamiento/inventario ubicado (p. ej. ubicación en patio, datos de la referencia, observaciones), sin que ello dispare un movimiento de stock.

**Why this priority**: Mantiene la exactitud de la ubicación y descripción del inventario; los cambios de cantidad siguen su flujo propio (transferencias/movimientos), por eso es corrección descriptiva.

**Independent Test**: Editar un registro de almacenamiento, corregir su ubicación o datos descriptivos, guardar, y verificar que no se generó un movimiento de inventario y que el cambio quedó auditado.

**Acceptance Scenarios**:

1. **Given** un registro de almacenamiento existente, **When** un administrador/coordinador corrige su ubicación o un dato descriptivo y guarda, **Then** el sistema persiste el cambio sin registrar un movimiento de inventario.

---

### User Story 6 - Editar una transferencia (Priority: P3)

Un administrador o coordinador corrige datos descriptivos de una transferencia existente (entre módulos o entre clientes): notas, fecha, observaciones, sin reejecutar ni revertir el movimiento de cantidades.

**Why this priority**: Frecuencia baja; las transferencias son sensibles porque mueven cantidades, por eso la edición se limita a datos descriptivos.

**Independent Test**: Editar una transferencia existente, corregir un dato descriptivo, guardar, y verificar que las cantidades ya movidas no cambian y que el cambio queda auditado.

**Acceptance Scenarios**:

1. **Given** una transferencia existente, **When** un administrador/coordinador corrige un dato descriptivo y guarda, **Then** el sistema persiste el cambio sin revertir ni recalcular las cantidades transferidas.

---

### User Story 7 - Editar una entrega (Priority: P3)

Un administrador o coordinador corrige datos de una orden de cargue/entrega existente (cliente, fecha de despacho, notas), sin alterar las tarjas ni las cantidades ya entregadas.

**Why this priority**: Frecuencia baja; cierre del flujo. Sensible por las cantidades entregadas, por eso es edición descriptiva.

**Independent Test**: Editar una entrega existente, corregir un dato descriptivo, guardar, y verificar que las tarjas y cantidades entregadas no cambian y que el cambio queda auditado.

**Acceptance Scenarios**:

1. **Given** una entrega existente con tarjas, **When** un administrador/coordinador corrige un dato descriptivo y guarda, **Then** el sistema persiste el cambio sin alterar las tarjas ni las cantidades entregadas.

---

### Edge Cases

- **Acceso no autorizado**: un usuario sin rol administrador ni coordinador no puede ver ni ejecutar la edición en ningún módulo.
- **Validación**: la edición aplica las mismas reglas de validación que la creación (obligatoriedad, formato); un valor inválido se rechaza conservando el valor anterior.
- **Campo no editable**: los campos estructurales o derivados (identificadores, cantidades de inventario calculadas, vínculos que romperían la consistencia) no se exponen para edición; solo se editan campos correctivos.
- **Registro en estado terminal**: se permite editar; el sistema no reabre ni cambia el estado salvo que el usuario edite explícitamente un campo de estado permitido.
- **Sin cambios**: guardar sin modificar nada no genera una entrada de auditoría vacía.
- **Edición concurrente**: si dos usuarios editan el mismo registro, el último guardado prevalece; ambos cambios quedan en la auditoría.
- **Registros importados**: los registros provenientes de importación histórica son editables igual que los demás.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: El sistema MUST permitir a los roles administrador y coordinador editar registros existentes en los siete módulos: solicitudes, ingresos (gate-in), vaciado, salidas (gate-out), almacenamiento, transferencias y entregas.
- **FR-002**: El sistema MUST impedir el acceso a la edición a usuarios que no tengan rol administrador o coordinador.
- **FR-003**: El sistema MUST limitar la edición a campos correctivos/descriptivos y NO exponer para edición los campos estructurales o derivados cuya modificación rompería la consistencia.
- **FR-004**: El sistema MUST NOT recalcular, mover ni revertir automáticamente inventario, cantidades o movimientos derivados a partir de una edición.
- **FR-005**: El sistema MUST permitir editar un registro independientemente de su estado, incluidos estados terminales/cerrados.
- **FR-006**: El sistema MUST aplicar en la edición las mismas reglas de validación que en la creación del registro (obligatoriedad y formato), rechazando valores inválidos con un mensaje claro y conservando el valor previo.
- **FR-007**: El sistema MUST registrar en un historial de auditoría cada modificación, incluyendo: el usuario que la realizó, la fecha y hora, el registro afectado, y el valor anterior y el valor nuevo de cada campo modificado.
- **FR-008**: El sistema MUST mostrar confirmación de éxito al guardar una edición válida y reflejar de inmediato los nuevos valores en la consulta del registro.
- **FR-009**: El sistema MUST permitir consultar el historial de auditoría de un registro a los roles autorizados.
- **FR-010**: El sistema MUST preservar el cambio de estado existente solo cuando el usuario edite explícitamente un campo de estado habilitado; en caso contrario, el estado del registro no cambia por el hecho de editar otros campos.
- **FR-011**: El sistema MUST no crear una entrada de auditoría cuando se guarda sin haber modificado ningún valor.

### Key Entities *(include if feature involves data)*

- **Registro operativo editable**: cualquier registro existente de los módulos solicitudes, ingresos, vaciado, salidas, almacenamiento, transferencias y entregas. Tiene un conjunto de campos correctivos editables y campos estructurales/derivados no editables.
- **Entrada de auditoría de cambio**: representa una modificación realizada sobre un registro operativo. Atributos: usuario que modificó, fecha y hora, tipo de registro y su identificador, y el detalle de campos cambiados con su valor anterior y nuevo.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Un administrador o coordinador puede corregir un dato errado en cualquiera de los siete módulos sin anular ni recrear el registro, completando la corrección en menos de 1 minuto.
- **SC-002**: El 100% de las ediciones guardadas quedan registradas en el historial de auditoría con usuario, fecha, valor anterior y valor nuevo.
- **SC-003**: El 100% de los intentos de edición por usuarios sin rol autorizado son bloqueados.
- **SC-004**: Tras cualquier edición correctiva, las cantidades de inventario y los movimientos derivados permanecen sin cambios (0 alteraciones automáticas de stock).
- **SC-005**: La edición rechaza el 100% de las entradas inválidas (campos obligatorios vacíos o con formato incorrecto), conservando el valor anterior.
- **SC-006**: Se reduce a cero la necesidad de soluciones alternativas (recrear registros o editar directamente la base de datos) para corregir datos en estos módulos.

## Assumptions

- "Editar" significa **corrección de datos**: ajustar valores de campos existentes, no eliminar registros ni alterar las cantidades/movimientos de inventario derivados.
- Cada módulo expone un subconjunto de campos correctivos; la definición exacta de qué campos son editables por módulo se detalla en la fase de planeación, respetando la regla de "solo correctivos, sin recalcular inventario".
- El historial de auditoría aplica de forma transversal a los siete módulos con un formato uniforme.
- Los permisos se basan en los roles existentes (administrador, coordinador); no se crean nuevos tipos de usuario.
- La eliminación de registros queda fuera de alcance de esta feature.
- La edición de cantidades de inventario, cuando sea necesaria, sigue su flujo propio (movimientos/transferencias) y no forma parte de esta corrección descriptiva.
