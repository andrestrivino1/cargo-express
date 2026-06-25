# Feature Specification: Ajuste de requerimientos operativos

**Feature Branch**: `005-ajuste-requerimientos-operativos`
**Created**: 2026-06-25
**Status**: Draft
**Input**: User description: "vamos implementar estos cambios al sistema lo que no se vaya a utilizar deberiamos ocultarlo mas no eliminarlo del sistema los nuevos requerimientos se encuentran en mi carpeta de descargas con el nombre REQUERIMIENTOS PROGRAMA (AJUSTE) este es un pdf y la imagen que te adjunto es la orden de salida asi deberia ser"

## Resumen

La Directora Administrativa de Carga Trans Xpress entregó un instructivo de requerimientos que ajusta el flujo operativo del sistema a un modelo más simple y directo basado en cuatro capacidades: **ingreso de mercancía**, **vaciado/desembalaje**, **salida de mercancía** y **reportes**. El sistema actual contiene módulos y pasos intermedios (solicitudes, transferencias, puertas de entrada/salida separadas, etc.) que no forman parte de este flujo simplificado.

El objetivo de esta funcionalidad es alinear el sistema con el instructivo: completar los campos y evidencias obligatorios de cada módulo, generar la **Orden de Salida** con el formato indicado en la imagen de referencia (documento "ODC"), reforzar el control automático de inventario en cada salida, y **ocultar (no eliminar)** del sistema todo lo que no se utilice en el nuevo flujo, de modo que la información histórica y la capacidad técnica permanezcan disponibles si más adelante se requieren.

## Clarifications

### Session 2026-06-25

- Q: ¿Cómo se implementa el nuevo "Módulo de Ingreso de Mercancía" frente al flujo actual (Solicitud → Orden de Servicio → Gate-In → Referencias)? → A: Un solo formulario de ingreso consolidado que captura todo (BL, contenedor, cliente, ubicación, tipo, referencias, peso, cantidad + adjuntos) y crea por debajo los registros necesarios; se ocultan Solicitudes y el Gate-In separado.
- Q: ¿Cómo se implementa la salida frente al flujo actual (Entregas/Orden de Cargue → Tarja, creado por el cliente)? → A: Un solo formulario de salida operado por el despachador que descuenta inventario y genera la Orden de Salida (ODC) como documento oficial; el flujo Entregas/Tarja y su PDF tarja se ocultan (datos históricos conservados).
- Q: ¿La "orden de salida autorizada" es implícita o requiere aprobación explícita de otro usuario antes del despacho? → A: Implícita — la Orden de Salida (ODC) generada por el sistema y firmada (conductor/empresa) es la orden autorizada; no hay paso de aprobación separado.
- Q: ¿Qué módulos restantes (fuera del instructivo) se ocultan? → A: Ocultar Transferencias, Salida de contenedor (Gate-Out) e Importación histórica + Pendientes por completar; mantener Productos visible como soporte de administración. (Ya estaban decididos a ocultar: Solicitudes, Gate-In separado y Entregas/Tarja.)
- Q: ¿Las fotos de evidencia en la salida (mercancía y conductor) son obligatorias para confirmar? → A: Sí, ambas obligatorias — no se confirma la salida sin foto de la mercancía despachada Y foto del conductor.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Registrar el ingreso de mercancía con campos obligatorios y documentos (Priority: P1)

El operador de bodega registra el ingreso de mercancía de un contenedor capturando todos los datos obligatorios y adjuntando los documentos soporte (BL, DIM y Lista de empaque). Cada referencia de producto ingresada queda disponible en el inventario del cliente con su ubicación, unidad de medida, peso y cantidad.

**Why this priority**: El ingreso es el punto de partida de toda la trazabilidad. Sin datos completos y documentos soporte en el ingreso, ni el vaciado, ni la salida, ni los reportes tienen información confiable. Es la base mínima viable del sistema ajustado.

**Independent Test**: Se puede probar de forma independiente registrando un ingreso completo (con BL, contenedor, cliente, ubicación, tipo, referencia, descripción, unidad/peso y cantidad) y adjuntando los tres documentos; luego verificando que la mercancía aparece en el inventario del cliente y que los documentos quedan accesibles.

**Acceptance Scenarios**:

1. **Given** un operador en el módulo de ingreso, **When** intenta guardar un ingreso sin BL, contenedor, cliente, ubicación de almacenamiento, tipo de mercancía, referencia, descripción, unidad de medida y peso, o cantidad ingresada, **Then** el sistema impide guardar e indica qué campos obligatorios faltan.
2. **Given** un ingreso con todos los campos obligatorios diligenciados, **When** el operador adjunta los documentos BL, DIM y Lista de empaque y guarda, **Then** el sistema registra el ingreso, suma la cantidad al inventario del cliente y deja los documentos disponibles para consulta.
3. **Given** un ingreso ya registrado, **When** otro usuario autorizado lo consulta, **Then** puede visualizar/descargar los documentos adjuntos (BL, DIM, Lista de empaque) asociados a ese ingreso.

---

### User Story 2 - Registrar la salida de mercancía con evidencias y control automático de inventario (Priority: P1)

El despachador registra la salida de inventario indicando cliente, referencia del producto, cantidad por despachar, fecha de salida y la orden de salida autorizada. Adjunta como evidencia la fotografía de la mercancía despachada y la foto del conductor, y registra observaciones o novedades. Al confirmar, el sistema descuenta automáticamente las unidades del inventario, actualiza los saldos, registra quién hizo la operación y la fecha/hora del movimiento.

**Why this priority**: La salida con control automático de inventario y evidencia fotográfica es el corazón del control operativo y la rendición de cuentas ante el cliente. Es tan crítica como el ingreso porque garantiza que los saldos reflejen la realidad y que cada despacho tenga respaldo probatorio.

**Independent Test**: Se puede probar registrando una salida sobre una referencia con saldo disponible, adjuntando foto de la mercancía y del conductor; luego verificando que el saldo disminuyó exactamente en la cantidad despachada, que el movimiento quedó atribuido al usuario con fecha y hora, y que las evidencias quedaron asociadas.

**Acceptance Scenarios**:

1. **Given** un despachador en el módulo de salida, **When** intenta registrar una salida sin cliente, referencia, cantidad por despachar, fecha de salida u orden de salida autorizada, **Then** el sistema impide continuar e indica los campos obligatorios faltantes.
2. **Given** una referencia con saldo disponible suficiente, **When** el despachador confirma la salida con sus evidencias, **Then** el sistema descuenta la cantidad despachada del saldo, actualiza el inventario disponible y registra usuario, fecha y hora del movimiento.
3. **Given** una salida en proceso, **When** la cantidad por despachar supera el saldo disponible de la referencia, **Then** el sistema impide la salida e informa el saldo disponible.
4. **Given** una salida registrada, **When** se consulta su evidencia, **Then** se pueden ver la fotografía de la mercancía despachada, la foto del conductor y las observaciones/novedades registradas.

---

### User Story 3 - Generar la Orden de Salida (ODC) con el formato requerido (Priority: P1)

A partir de una salida de mercancía, el sistema genera el documento **Orden de Salida** con el formato de la imagen de referencia: encabezado con consecutivo (ODC-###), cliente, NIT y fecha de salida; tabla "Detalle de la carga" con contenedor, descripción, observaciones y cantidad por línea, más el total; y sección "Datos del conductor y vehículo" con nombre del conductor, cédula, placa del vehículo, transportador, destino, las dos fotografías (conductor y carga) y espacios de firma del conductor y de la empresa.

**Why this priority**: La Orden de Salida es el documento físico/oficial que acompaña al despacho y que el cliente y el transportador firman. El usuario explícitamente indicó que "así debería ser" mostrando el formato esperado, por lo que es un entregable de alta prioridad y claramente verificable.

**Independent Test**: Se puede probar generando la Orden de Salida de un despacho con varias referencias y verificando, contra la imagen de referencia, que aparecen todos los bloques (encabezado con consecutivo/cliente/NIT/fecha, detalle de carga con total, datos de conductor/vehículo, fotografías y firmas).

**Acceptance Scenarios**:

1. **Given** una salida de mercancía con líneas de detalle, **When** el usuario genera la Orden de Salida, **Then** el documento muestra el consecutivo (formato ODC-###), el cliente, el NIT, la fecha de salida y el logo de la empresa.
2. **Given** una salida con varias referencias/contenedores, **When** se genera la Orden de Salida, **Then** la tabla "Detalle de la carga" lista por línea el contenedor, la descripción, las observaciones y la cantidad, y muestra el total de unidades.
3. **Given** una salida con datos de transporte y evidencias, **When** se genera la Orden de Salida, **Then** la sección de conductor y vehículo muestra nombre del conductor, cédula, placa, transportador y destino, incluye la foto del conductor y la foto de la carga, y presenta los espacios para firma del conductor y firma de la empresa.

---

### User Story 4 - Registrar fotografías y novedades durante el vaciado (Priority: P2)

Por cada contenedor recibido, el operador del vaciado/desembalaje puede cargar fotografías y registrar las novedades encontradas durante la recepción (averías, faltantes, daños visibles), dejando evidencia para la trazabilidad.

**Why this priority**: El vaciado ya cuenta con soporte de fotografías y novedades en el sistema actual; el ajuste consiste en confirmar que cumple el instructivo y que queda visible en el flujo simplificado. Es importante pero de menor riesgo que ingreso y salida.

**Independent Test**: Se puede probar tomando un contenedor recibido, cargando una o más fotografías y registrando una novedad; luego verificando que ambas quedan asociadas al contenedor y disponibles en la trazabilidad.

**Acceptance Scenarios**:

1. **Given** un contenedor en proceso de vaciado, **When** el operador carga fotografías, **Then** quedan asociadas al contenedor y consultables posteriormente.
2. **Given** un contenedor en proceso de vaciado, **When** el operador registra una novedad con su tipo y descripción, **Then** la novedad queda registrada y vinculada a la mercancía/contenedor correspondiente.

---

### User Story 5 - Consultar reportes operativos (Priority: P2)

Los usuarios autorizados consultan los reportes requeridos: inventario actual por cliente, ingresos, salidas, historial de movimientos, novedades, y evidencias fotográficas con trazabilidad.

**Why this priority**: Los reportes consolidan el valor de la información capturada y permiten a la administración y a los clientes verificar el estado del inventario y la operación. Dependen de que ingreso, salida y vaciado capturen datos completos, por lo que se priorizan después de esos módulos.

**Independent Test**: Se puede probar, tras registrar ingresos y salidas, consultando el reporte de inventario actual por cliente y verificando que los saldos coinciden con los movimientos; y consultando los reportes de ingresos, salidas, movimientos, novedades y evidencias.

**Acceptance Scenarios**:

1. **Given** movimientos de ingreso y salida registrados, **When** un usuario autorizado consulta el reporte de inventario actual por cliente, **Then** ve los saldos disponibles por cliente y referencia.
2. **Given** la operación en curso, **When** el usuario consulta los reportes de ingresos, salidas, historial de movimientos y novedades, **Then** cada reporte muestra los registros correspondientes con su fecha, responsable y datos clave.
3. **Given** evidencias fotográficas asociadas a ingresos, vaciados y salidas, **When** el usuario consulta el reporte de evidencias/trazabilidad, **Then** puede acceder a las fotografías y al recorrido del movimiento de la mercancía.

---

### User Story 6 - Ocultar módulos y opciones no utilizados sin eliminarlos (Priority: P2)

El administrador necesita que el sistema muestre únicamente las capacidades del flujo ajustado (ingreso, vaciado, salida, reportes). Las funcionalidades, menús y pantallas que no forman parte de este flujo se **ocultan** de la navegación y del uso cotidiano, pero permanecen en el sistema (sin eliminarse), conservando datos e historial.

**Why this priority**: Simplificar la interfaz reduce errores y capacitación, pero ocultar en lugar de eliminar protege la información histórica y permite reactivar capacidades si el negocio lo requiere. Es transversal y se aplica una vez definidos los módulos vigentes.

**Independent Test**: Se puede probar revisando la navegación de cada rol y verificando que solo aparecen las opciones del flujo ajustado; y confirmando que los datos de los módulos ocultos siguen existiendo (consultables por un mecanismo administrativo o por reactivación) y no fueron borrados.

**Acceptance Scenarios**:

1. **Given** un usuario operativo, **When** ingresa al sistema, **Then** en su menú solo aparecen las opciones correspondientes al flujo ajustado (ingreso, vaciado, salida, reportes) y no las funcionalidades ocultas.
2. **Given** un módulo marcado como no utilizado, **When** se oculta, **Then** sus datos históricos permanecen almacenados y no se eliminan del sistema.
3. **Given** una funcionalidad oculta, **When** la administración decide reactivarla en el futuro, **Then** puede volver a hacerse visible sin pérdida de información ni necesidad de reconstruir datos.

---

### Edge Cases

- ¿Qué ocurre si se intenta despachar una cantidad mayor al saldo disponible de una referencia? El sistema debe rechazar la salida e informar el saldo disponible.
- ¿Qué ocurre si la fecha de salida es anterior a la fecha de ingreso de la mercancía? El sistema debe advertir o impedir según política operativa (se asume que se permite fecha de salida igual o posterior al ingreso; fechas anteriores requieren confirmación).
- ¿Qué ocurre si no se adjuntan las fotografías de evidencia (mercancía/conductor) en la salida? Ambas son obligatorias (FR-008); el sistema impide confirmar la salida hasta que estén las dos.
- ¿Qué pasa con el consecutivo de la Orden de Salida si una salida se anula? El consecutivo no se reutiliza; queda registro de la anulación.
- ¿Qué formato de archivo se acepta para documentos (BL, DIM, Lista de empaque) y fotografías? Se asumen formatos estándar (PDF e imágenes JPG/PNG) con un tamaño máximo razonable.
- ¿Qué ocurre con registros históricos que pertenecen a módulos que se ocultan? Permanecen accesibles para reportes y trazabilidad; no se pierden.
- ¿Qué pasa si dos despachadores intentan despachar simultáneamente la misma referencia dejando el saldo en negativo? El sistema debe garantizar que el saldo nunca quede por debajo de cero.

## Requirements *(mandatory)*

### Functional Requirements

#### Ingreso de mercancía
- **FR-001**: El sistema MUST exigir como obligatorios en el ingreso de mercancía: BL, contenedor, cliente, ubicación de almacenamiento, tipo de mercancía, referencia del producto, detalle/descripción de la mercancía, unidad de medida, peso y cantidad ingresada.
- **FR-001a**: El sistema MUST ofrecer el ingreso de mercancía como un único formulario consolidado que captura todos los campos obligatorios y los adjuntos en una sola operación, creando por debajo los registros internos necesarios; el flujo anterior por pasos (Solicitudes y Gate-In separado) se oculta del uso cotidiano conservando sus datos históricos.
- **FR-002**: El sistema MUST permitir adjuntar al ingreso los documentos soporte: BL, DIM y Lista de empaque, y mantenerlos disponibles para consulta y descarga.
- **FR-003**: El sistema MUST registrar la cantidad ingresada en el inventario del cliente asociándola a su referencia, ubicación y unidad de medida.
- **FR-004**: El sistema MUST conservar el peso y el tipo de mercancía como parte de la información del ingreso.

#### Vaciado / Desembalaje
- **FR-005**: El sistema MUST permitir, por cada contenedor recibido, cargar una o varias fotografías de la recepción.
- **FR-006**: El sistema MUST permitir registrar las novedades encontradas durante la recepción (tipo y descripción), vinculadas al contenedor/mercancía.

#### Salida de mercancía
- **FR-007**: El sistema MUST exigir como obligatorios para registrar una salida: cliente, referencia del producto, cantidad por despachar, fecha de salida y orden de salida autorizada.
- **FR-007a**: El sistema MUST ofrecer la salida de mercancía como un único formulario operado por el rol despachador/operativo que, al confirmarse, descuenta el inventario y genera la Orden de Salida (ODC) como documento oficial. El flujo anterior de Entregas (Orden de Cargue) / Tarja y su documento "tarja" se ocultan del uso cotidiano conservando sus datos históricos.
- **FR-008**: El sistema MUST exigir, como evidencia obligatoria para confirmar la salida, la fotografía de la mercancía despachada Y la foto del conductor; no se confirma la salida si falta alguna de las dos.
- **FR-009**: El sistema MUST permitir registrar observaciones o novedades en la salida.
- **FR-010**: Al confirmar una salida, el sistema MUST descontar automáticamente las unidades despachadas del inventario y actualizar los saldos disponibles.
- **FR-011**: El sistema MUST impedir que una salida deje el saldo de una referencia por debajo de cero e informar el saldo disponible cuando la cantidad solicitada lo exceda.
- **FR-012**: Por cada salida, el sistema MUST registrar qué usuario realizó la operación y la fecha y hora del movimiento (trazabilidad).

#### Orden de Salida (ODC)
- **FR-013**: El sistema MUST generar el documento "Orden de Salida" con un consecutivo en formato ODC-### que no se reutilice. La Orden de Salida generada y firmada constituye la "orden de salida autorizada" exigida en FR-007; no existe un paso de aprobación separado por otro usuario.
- **FR-014**: La Orden de Salida MUST incluir en su encabezado: razón social de la empresa, título "Orden de Salida", consecutivo, cliente, NIT del cliente, fecha de salida y logo de la empresa.
- **FR-015**: La Orden de Salida MUST incluir una tabla "Detalle de la carga" con una fila por línea de despacho que muestre contenedor, descripción, observaciones y cantidad, además del total de unidades.
- **FR-016**: La Orden de Salida MUST incluir la sección "Datos del conductor y vehículo" con nombre del conductor, cédula, placa del vehículo, transportador y destino.
- **FR-017**: La Orden de Salida MUST mostrar la fotografía del conductor y la fotografía de la carga, y presentar los espacios para firma del conductor y firma de la empresa.

#### Control automático e inventario
- **FR-018**: El sistema MUST mantener saldos de inventario consistentes con la suma de ingresos menos salidas por cliente y referencia.
- **FR-019**: El sistema MUST mantener la trazabilidad de cada movimiento (ingreso, vaciado, salida) con responsable y marca temporal.

#### Reportes
- **FR-020**: El sistema MUST ofrecer un reporte de inventario actual por cliente.
- **FR-021**: El sistema MUST ofrecer reportes de ingresos, salidas, historial de movimientos y novedades.
- **FR-022**: El sistema MUST ofrecer acceso a las evidencias fotográficas y a la trazabilidad de la mercancía.

#### Ocultar (no eliminar) lo no utilizado
- **FR-023**: El sistema MUST ocultar de la navegación y del uso cotidiano las funcionalidades, menús y pantallas que no formen parte del flujo ajustado. En concreto se ocultan: Solicitudes, Gate-In separado, Entregas (Orden de Cargue)/Tarja, Transferencias, Salida de contenedor (Gate-Out), e Importación histórica de inventario + Pendientes por completar. Permanecen visibles: Ingreso, Vaciado, Almacenamiento/Inventario, Salida (ODC), Reportes, Trazabilidad, y la administración (Usuarios, Ubicaciones y Productos como soporte/catálogo).
- **FR-024**: El sistema MUST conservar (sin eliminar) los datos, el historial y la capacidad técnica de las funcionalidades ocultas.
- **FR-025**: El sistema MUST permitir que una funcionalidad oculta pueda volver a habilitarse en el futuro sin pérdida de información.
- **FR-026**: La información histórica perteneciente a módulos ocultos MUST permanecer disponible para los reportes y la trazabilidad.

#### Permisos y acceso
- **FR-027**: El sistema MUST respetar los roles existentes (cliente, operativo/coordinador, supervisor, administrador) para determinar quién puede registrar ingresos, vaciados y salidas, generar la Orden de Salida y consultar reportes.

### Key Entities *(include if feature involves data)*

- **Ingreso de mercancía**: Representa la entrada de mercancía de un contenedor al inventario. Atributos clave: BL, contenedor, cliente, ubicación de almacenamiento, tipo de mercancía, referencia del producto, descripción, unidad de medida, peso, cantidad ingresada, fecha/hora y responsable. Relacionado con documentos soporte y con el inventario del cliente.
- **Documento soporte**: Archivo adjunto al ingreso (BL, DIM, Lista de empaque). Atributos: tipo de documento, archivo, fecha de carga. Relacionado con el ingreso de mercancía.
- **Referencia / Inventario**: Mercancía identificada por referencia de producto, con cantidad disponible (saldo), unidad de medida, ubicación y cliente propietario. Se incrementa con ingresos y disminuye con salidas.
- **Vaciado / Recepción**: Registro del desembalaje de un contenedor. Relacionado con fotografías y novedades.
- **Novedad**: Incidencia encontrada (avería, faltante, daño visible) durante vaciado o salida. Atributos: tipo, descripción, cantidad afectada, responsable. Relacionada con la mercancía/contenedor.
- **Salida de mercancía**: Despacho de inventario. Atributos clave: cliente, referencia(s), cantidad por despachar, fecha de salida, orden de salida autorizada, observaciones, responsable, fecha/hora. Relacionada con evidencias (foto mercancía y foto conductor) y con la Orden de Salida.
- **Orden de Salida (ODC)**: Documento oficial de la salida. Atributos: consecutivo (ODC-###), cliente, NIT, fecha de salida, detalle de carga (contenedor, descripción, observaciones, cantidad, total), datos del conductor y vehículo (nombre, cédula, placa, transportador, destino), fotografías (conductor y carga), firmas.
- **Evidencia fotográfica**: Fotografía asociada a ingreso, vaciado o salida que respalda la operación. Atributos: imagen, momento de captura, operación relacionada.
- **Movimiento / Trazabilidad**: Registro de cada cambio operativo (ingreso, vaciado, salida) con responsable y marca temporal, base de los reportes de historial y trazabilidad.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: El 100% de los ingresos guardados contienen todos los campos obligatorios y, cuando aplica, sus documentos soporte (BL, DIM, Lista de empaque).
- **SC-002**: El 100% de las salidas confirmadas descuentan correctamente el inventario, de modo que el saldo por cliente y referencia siempre coincide con ingresos menos salidas y nunca es negativo.
- **SC-003**: Toda salida confirmada queda atribuida a un usuario con fecha y hora, verificable en el 100% de los casos.
- **SC-004**: La Orden de Salida generada reproduce el formato de referencia (encabezado con consecutivo/cliente/NIT/fecha, detalle de carga con total, datos de conductor y vehículo, fotografías y firmas) en una verificación visual contra la muestra entregada.
- **SC-005**: Un operador puede completar el registro de un ingreso típico en menos de 5 minutos y el de una salida típica en menos de 5 minutos.
- **SC-006**: Tras el ajuste, la navegación de cada rol muestra únicamente las opciones del flujo vigente (ingreso, vaciado, salida, reportes); cero módulos no utilizados visibles en el uso cotidiano.
- **SC-007**: Cero pérdida de información histórica: todos los registros previos a los módulos ocultados siguen consultables en reportes y trazabilidad.
- **SC-008**: Los reportes requeridos (inventario actual por cliente, ingresos, salidas, historial de movimientos, novedades, evidencias y trazabilidad) están disponibles y reflejan los movimientos registrados.

## Assumptions

- El sistema continúa siendo de uso interno de Carga Trans Xpress, con los roles ya existentes (cliente, coordinador/operativo, supervisor, administrador) gestionados por el control de acceso actual.
- "Ocultar, no eliminar" se interpreta como retirar las opciones de la navegación y deshabilitar su uso cotidiano (por ejemplo mediante banderas de visibilidad/permiso), conservando tablas, datos y código. No implica borrar migraciones ni datos.
- La lista de módulos a ocultar quedó definida en Clarificaciones (FR-023): Solicitudes, Gate-In separado, Entregas/Tarja, Transferencias, Gate-Out e Importación histórica + Pendientes. Productos permanece visible como catálogo de administración.
- La "orden de salida autorizada" es el documento ODC generado por el sistema y firmado (conductor/empresa); la autorización es implícita, sin paso de aprobación separado (ver Clarificaciones y FR-013).
- Las evidencias fotográficas de la salida (mercancía y conductor) son obligatorias para confirmar el despacho (ver Clarificaciones y FR-008).
- El NIT mostrado en la Orden de Salida corresponde al del cliente; la razón social/NIT de la empresa emisora (Carga Trans Xpress) se toma de la configuración del sistema.
- Los formatos aceptados para documentos son PDF y para fotografías JPG/PNG, con límites de tamaño estándar.
- El consecutivo de la Orden de Salida es único y secuencial a nivel de empresa, continuando la numeración existente (la muestra indica ODC-570).
- No se incorporan nuevas dependencias externas; el ajuste reutiliza las capacidades ya presentes (adjuntos, generación de documentos PDF, control de acceso, auditoría).
