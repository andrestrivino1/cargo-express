# Feature Specification: Sistema de Trazabilidad de Carga

**Feature Branch**: `001-cargo-traceability-system`
**Created**: 2026-03-21
**Status**: Draft
**Input**: Backlog de 15 historias de usuario — Operaciones Logísticas v1.0

---

## User Scenarios & Testing *(mandatory)*

### User Story 1 — Solicitud y asignación de retiro de contenedor (Priority: P1)

Un cliente registra una solicitud de retiro de contenedor adjuntando la documentación requerida. El coordinador de transporte asigna vehículo, conductor y cita en puerto, generando automáticamente una orden de servicio vinculada a la solicitud.

**Why this priority**: Es el punto de entrada del ciclo operativo completo. Sin solicitud registrada no puede existir ninguna operación posterior.

**Independent Test**: Se puede probar creando una solicitud, adjuntando un documento y verificando que el equipo de transporte recibe la notificación y puede asignar vehículo/conductor con su cita.

**Acceptance Scenarios**:

1. **Given** un cliente autenticado, **When** envía una solicitud con datos del contenedor y adjunta documentación, **Then** la solicitud queda registrada con fecha, hora y datos del contenedor, y el equipo de transporte recibe una notificación.
2. **Given** una solicitud registrada, **When** el coordinador selecciona vehículo y conductor disponibles para la fecha, **Then** se genera automáticamente una orden de servicio vinculada a la solicitud.
3. **Given** vehículo o conductor no disponibles para la fecha solicitada, **When** el coordinador intenta asignarlos, **Then** el sistema muestra únicamente los recursos disponibles para esa fecha.

---

### User Story 2 — Ingreso al patio (Gate In) y registro de contenido (Priority: P1)

El portero registra el ingreso físico del contenedor al patio validando que exista una orden de servicio asociada. El operador ingresa las referencias y cantidades de la lista de empaque antes del vaciado.

**Why this priority**: El Gate In activa el ciclo de custodia de la carga. Sin este registro no se puede gestionar vaciado, almacenamiento ni salida.

**Independent Test**: Se puede probar registrando el ingreso de un contenedor con placa y número, adjuntando fotos, verificando el cambio de estado a "En patio" y generando el sticker de marcación con referencias.

**Acceptance Scenarios**:

1. **Given** una orden de servicio activa, **When** el portero registra el ingreso con placa, número de contenedor y hora, **Then** el contenedor cambia a estado "En patio" y queda registrado el usuario que realizó el ingreso.
2. **Given** un intento de ingreso sin orden de servicio asociada, **When** el portero intenta registrarlo, **Then** el sistema bloquea el registro y muestra un aviso de validación.
3. **Given** un contenedor en estado "En patio", **When** el operador ingresa referencias y cantidades, **Then** la información queda asociada al contenedor y al cliente, y se genera el sticker de marcación.

---

### User Story 3 — Vaciado: programación y registro de novedades (Priority: P2)

El supervisor genera y programa una orden de vaciado para coordinar el descargue. Durante la operación, el operador registra cualquier novedad (avería, faltante, daño) con evidencia fotográfica, y el sistema notifica automáticamente al cliente.

**Why this priority**: El vaciado es la operación de mayor riesgo para la integridad de la carga. El registro de novedades protege a la empresa y al cliente con evidencia trazable.

**Independent Test**: Se puede probar creando una orden de vaciado, iniciándola (cambio de estado a "En vaciado"), registrando una novedad con foto y verificando que el cliente recibe la notificación automática.

**Acceptance Scenarios**:

1. **Given** un contenedor en estado "En patio", **When** el supervisor crea y programa la orden de vaciado con fecha y hora, **Then** el estado del contenedor cambia a "En vaciado" al iniciar la operación.
2. **Given** una orden de vaciado activa, **When** el operador registra una novedad con tipo (avería/faltante/daño) y adjunta fotos, **Then** el sistema guarda la evidencia y notifica automáticamente al cliente.
3. **Given** una novedad registrada, **When** el cliente consulta el estado de su operación, **Then** puede ver el tipo de novedad y las fotos adjuntas.

---

### User Story 4 — Almacenamiento: ubicación en patio e inventario en tiempo real (Priority: P2)

El operador asigna una ubicación específica por módulo a la mercancía descargada. El supervisor consulta el inventario en tiempo real filtrado por cliente, referencia o módulo. El sistema calcula automáticamente los días de almacenamiento para facturación.

**Why this priority**: El control de inventario y ubicación es crítico para la operación diaria y para la facturación correcta al cliente.

**Independent Test**: Se puede probar asignando una ubicación a mercancía descargada, verificando que el cliente recibe la notificación por WhatsApp, consultando el inventario filtrado y exportando el reporte.

**Acceptance Scenarios**:

1. **Given** mercancía descargada de un vaciado, **When** el operador selecciona módulo y ubicación exacta, **Then** la ubicación queda registrada en el inventario y el cliente recibe la notificación por WhatsApp.
2. **Given** mercancía en inventario, **When** el supervisor filtra por cliente, referencia, módulo y fechas, **Then** el sistema muestra entradas, salidas y movimientos internos actualizados en tiempo real.
3. **Given** mercancía almacenada, **When** el administrador consulta días de almacenamiento, **Then** el sistema calcula automáticamente desde la fecha de ingreso hasta la salida o fecha actual, visible en inventario y reportes.

---

### User Story 5 — Salida del contenedor vacío (Gate Out) (Priority: P2)

El operador registra la limpieza del contenedor vacío y selecciona el destino (puerto o patio de naviera). El portero registra la salida, genera la tirilla de soporte automáticamente y el cliente la recibe.

**Why this priority**: Cierra el ciclo del contenedor y genera el comprobante de devolución para el cliente y la naviera.

**Independent Test**: Se puede probar registrando la limpieza, seleccionando destino, ejecutando el Gate Out y verificando que se genera la tirilla y el estado cambia a "Fuera de patio".

**Acceptance Scenarios**:

1. **Given** un contenedor vacío en patio, **When** el operador registra si fue limpiado y selecciona el destino, **Then** la información queda registrada antes de proceder con la salida.
2. **Given** información de limpieza y destino registrada, **When** el portero registra la salida con hora, estado final y fotos, **Then** el contenedor cambia a "Fuera de patio", se genera la tirilla de soporte automáticamente y el cliente la recibe.

---

### User Story 6 — Entrega de mercancía al cliente (Priority: P2)

El despachador recibe y gestiona órdenes de cargue del cliente para programar despachos. Al confirmar la entrega, genera la tarja que actualiza automáticamente el inventario con las unidades retiradas.

**Why this priority**: Completa el ciclo de trazabilidad desde el ingreso hasta la entrega final al cliente.

**Independent Test**: Se puede probar registrando una orden de cargue, programando el despacho, generando la tarja y verificando que el inventario se descuenta automáticamente.

**Acceptance Scenarios**:

1. **Given** una orden de cargue del cliente, **When** el despachador la registra en el sistema, **Then** se programa fecha y hora del despacho y se genera una orden de salida.
2. **Given** un despacho programado, **When** el despachador confirma la entrega y genera la tarja, **Then** el inventario descuenta automáticamente las referencias y cantidades desde la ubicación correspondiente, y queda registro del despachador.

---

### User Story 7 — Trazabilidad completa y reportes de operación (Priority: P3)

El gerente consulta el historial completo de cualquier contenedor desde su solicitud hasta la salida, con todos los eventos, fotos y documentos adjuntos. El administrador genera reportes de operación por cliente exportables en Excel o PDF para soporte de facturación y auditoría.

**Why this priority**: Habilita la auditoría, la transparencia con el cliente y el soporte de facturación. Depende de que todos los módulos anteriores estén operando.

**Independent Test**: Se puede probar buscando un contenedor por número y verificando la secuencia completa de eventos con fechas, usuarios y adjuntos, y exportando un reporte filtrado por cliente y fechas.

**Acceptance Scenarios**:

1. **Given** un contenedor con operaciones registradas, **When** el gerente busca por número de contenedor, **Then** se muestra la secuencia cronológica completa de eventos con fecha, hora, usuario, fotos y documentos adjuntos en cada etapa.
2. **Given** múltiples operaciones de un cliente, **When** el administrador genera un reporte filtrado por cliente y rango de fechas, **Then** el reporte incluye movimientos, novedades y resumen de días almacenados, y puede exportarse en Excel o PDF.

---

### Edge Cases

- ¿Qué ocurre si se intenta registrar un Gate In para un contenedor que ya está en estado "En patio"?
- ¿Cómo se maneja una orden de vaciado si el contenedor aún no tiene referencias/cantidades registradas?
- ¿Qué pasa si el cliente no tiene número de WhatsApp registrado al momento de asignar ubicación?
- ¿Puede un contenedor tener múltiples novedades registradas en el mismo vaciado?
- ¿Qué ocurre si la entrega parcial de referencias actualiza el inventario incorrectamente?
- ¿Cómo se maneja el cálculo de días de almacenamiento cuando hay salidas parciales de mercancía?

---

## Requirements *(mandatory)*

### Functional Requirements

**Módulo 1 — Solicitud y planeación**

- **FR-001**: El sistema DEBE permitir al cliente adjuntar documentos a la solicitud de retiro de contenedor.
- **FR-002**: El sistema DEBE registrar cada solicitud con fecha, hora y datos del contenedor de forma automática.
- **FR-003**: El sistema DEBE enviar una notificación al equipo de transporte al crear una nueva solicitud.
- **FR-004**: El sistema DEBE permitir seleccionar únicamente vehículos y conductores disponibles para la fecha de la solicitud.
- **FR-005**: El sistema DEBE generar automáticamente una orden de servicio vinculada a la solicitud original al confirmar la asignación.

**Módulo 2 — Ingreso al patio (Gate In)**

- **FR-006**: El sistema DEBE validar que exista una orden de servicio activa antes de permitir el registro de ingreso de un contenedor.
- **FR-007**: El registro de ingreso DEBE incluir placa, número de contenedor, hora exacta y usuario que registra.
- **FR-008**: El sistema DEBE permitir adjuntar fotos del estado físico del contenedor en el momento del ingreso.
- **FR-009**: El sistema DEBE cambiar automáticamente el estado del contenedor a "En patio" al completar el registro de ingreso.
- **FR-010**: El sistema DEBE permitir registrar múltiples referencias con sus cantidades por contenedor.
- **FR-011**: Las referencias ingresadas DEBEN quedar asociadas al número de contenedor y al cliente.
- **FR-012**: El sistema DEBE generar un sticker de marcación con número de contenedor, referencia y nombre del cliente.

**Módulo 3 — Vaciado**

- **FR-013**: La orden de vaciado DEBE crearse únicamente desde un contenedor en estado "En patio".
- **FR-014**: El sistema DEBE permitir programar fecha y hora del vaciado al crear la orden.
- **FR-015**: El sistema DEBE cambiar el estado del contenedor a "En vaciado" al iniciar la operación.
- **FR-016**: El sistema DEBE permitir registrar novedades con tipo (avería, faltante o daño visible) durante el vaciado.
- **FR-017**: El sistema DEBE permitir adjuntar fotos como evidencia de cada novedad registrada.
- **FR-018**: El sistema DEBE notificar automáticamente al cliente cuando se registre una novedad en su contenedor.

**Módulo 4 — Almacenamiento e inventarios**

- **FR-019**: El sistema DEBE permitir seleccionar el módulo y la ubicación exacta dentro del patio para cada mercancía descargada.
- **FR-020**: Las ubicaciones asignadas DEBEN quedar registradas en el sistema de inventarios.
- **FR-021**: El sistema DEBE notificar al cliente la ubicación asignada vía WhatsApp.
- **FR-022**: El inventario DEBE mostrar entradas, salidas y movimientos internos actualizados en tiempo real.
- **FR-023**: El sistema DEBE permitir filtrar el inventario por cliente, referencia, módulo y fechas.
- **FR-024**: El sistema DEBE permitir exportar el inventario como reporte por cliente.
- **FR-025**: El sistema DEBE calcular automáticamente los días de almacenamiento desde la fecha de ingreso hasta la salida o la fecha actual.
- **FR-026**: El cálculo de días de almacenamiento DEBE ser visible en el inventario y en los reportes por cliente.

**Módulo 5 — Salida del contenedor (Gate Out)**

- **FR-027**: El sistema DEBE registrar si el contenedor fue limpiado antes de la salida.
- **FR-028**: El sistema DEBE permitir seleccionar el destino del contenedor vacío: puerto o patio designado por la naviera.
- **FR-029**: El registro de salida DEBE incluir hora de salida, estado final y fotos del contenedor.
- **FR-030**: El sistema DEBE generar automáticamente la tirilla de soporte de entrega al registrar la salida.
- **FR-031**: El sistema DEBE cambiar el estado del contenedor a "Fuera de patio" al registrar la salida.
- **FR-032**: El sistema DEBE enviar la tirilla de soporte al cliente de forma automática.

**Módulo 6 — Entrega de mercancía al cliente**

- **FR-033**: El sistema DEBE permitir registrar órdenes de cargue del cliente.
- **FR-034**: El sistema DEBE permitir programar fecha y hora del despacho.
- **FR-035**: El sistema DEBE generar una orden de salida para el despachador al confirmar el cargue.
- **FR-036**: La tarja de entrega DEBE indicar referencias, cantidades entregadas y ubicación de origen.
- **FR-037**: El sistema DEBE descontar automáticamente del inventario las unidades confirmadas en la tarja.
- **FR-038**: El sistema DEBE registrar el despachador que ejecutó la entrega.

**Módulo 7 — Trazabilidad y reportes**

- **FR-039**: El sistema DEBE permitir buscar cualquier contenedor por su número.
- **FR-040**: El historial del contenedor DEBE mostrar la secuencia completa de eventos con fecha, hora y usuario responsable.
- **FR-041**: La trazabilidad DEBE incluir fotos y documentos adjuntos en cada etapa de la operación.
- **FR-042**: El sistema DEBE permitir generar reportes de operación filtrables por cliente, rango de fechas y tipo de evento.
- **FR-043**: Los reportes DEBEN poder exportarse en formato Excel y PDF.
- **FR-044**: Los reportes DEBEN incluir un resumen de días almacenados por cliente para facilitar la facturación.

### Key Entities

- **Solicitud de retiro**: Petición del cliente para retirar un contenedor. Tiene fecha, hora, datos del contenedor y documentos adjuntos. Origina una orden de servicio.
- **Contenedor**: Unidad central del ciclo operativo. Tiene número, placa del vehículo asociado y estado (Solicitado → En patio → En vaciado → Fuera de patio).
- **Orden de servicio**: Generada automáticamente desde una solicitud. Vincula el contenedor con el vehículo, conductor y cita en puerto.
- **Orden de vaciado**: Programación del descargue del contenedor. Registra fecha, hora y novedades ocurridas.
- **Novedad**: Incidencia durante el vaciado (avería, faltante, daño). Tiene tipo, descripción y fotos de evidencia.
- **Referencia / ítem de inventario**: Unidad de mercancía identificada por código, cantidad y ubicación en el patio. Asociada a un contenedor y a un cliente.
- **Ubicación en patio**: Posición física de la mercancía, identificada por módulo y posición exacta.
- **Tarja de entrega**: Documento generado al despachar mercancía. Detalla referencias, cantidades y ubicación de origen. Actualiza el inventario automáticamente.
- **Tirilla de soporte Gate Out**: Comprobante generado al registrar la salida del contenedor vacío. Se envía automáticamente al cliente.
- **Reporte de operación**: Consolidado de movimientos, novedades y días de almacenamiento por cliente, exportable en Excel o PDF.

---

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: El ciclo completo de un contenedor (solicitud → Gate In → vaciado → almacenamiento → Gate Out) puede ser registrado y consultado sin pérdida de ningún evento.
- **SC-002**: El 100% de las novedades registradas durante el vaciado generan una notificación al cliente en menos de 60 segundos.
- **SC-003**: El inventario refleja el estado actual del patio con un retraso máximo de 30 segundos respecto a la operación física.
- **SC-004**: Los días de almacenamiento calculados por el sistema coinciden con los registros manuales en el 100% de los casos auditados.
- **SC-005**: El portero puede completar el registro de Gate In (incluyendo fotos y validación de orden) en menos de 3 minutos.
- **SC-006**: La generación de la tarja de entrega actualiza el inventario automáticamente sin intervención manual adicional.
- **SC-007**: Los reportes de operación pueden generarse y exportarse (Excel o PDF) en menos de 10 segundos para cualquier rango de fechas dentro del año en curso.
- **SC-008**: El 100% de las tirillas de soporte Gate Out son enviadas al cliente automáticamente al completar el registro de salida.

---

## Assumptions

- Los usuarios (cliente, portero, operador, coordinador, supervisor, despachador, gerente, administrador) tienen roles diferenciados con acceso controlado a las funciones de cada módulo.
- La notificación al cliente vía WhatsApp asume integración con la API de WhatsApp Business; si no está disponible, se usa correo electrónico como canal alternativo.
- "Tiempo real" en el inventario se define como actualización dentro de los 30 segundos siguientes a cualquier movimiento registrado.
- El sticker de marcación se imprime físicamente desde el sistema; se asume disponibilidad de impresora conectada en el patio.
- El cálculo de días de almacenamiento usa días calendario, no días hábiles, salvo acuerdo especial con el cliente.
- Las fotos adjuntas (Gate In, novedades, Gate Out) se almacenan vinculadas al contenedor y son accesibles desde el historial de trazabilidad.
- El sistema opera en español y en zona horaria Colombia (UTC-5).