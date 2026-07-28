# Feature Specification: Reorganización de roles + módulos Citas y Portero

**Feature Branch**: `009-roles-citas-portero`
**Created**: 2026-07-27
**Status**: Draft
**Input**: User description: "se realizaran varios cambios en donde crearemos unos modulos nuevos y se modificaran algunos permisos como también se crearan otros. 1. Definir un nuevo rol para que este haga ingresos y salidas. 2. Nuevo rol llamado citas y nuevo modulo citas que tendra un formulario que pedira lo siguiente: tipo, tamaño, numero contenedor, fecha posible de llegada, nombre conductor y placa, tener opción de poner el contenedor full o vacio, cedula del conductor, nombre empresa. 3. El rol portero cambia, este tendra un modulo nuevo que se llama portero y hara lo siguiente: el portero revisa si tiene citas para la fecha actual; toma foto del vehiculo, foto contenedor, sello, tiquete. 4. El rol supervisor se encargara del vaciado y definir la ubicación de los productos. 5. El cliente solo vera el almacenamiento de sus productos. 6. Oculta los roles coordinador, despachador, gerente, operador."

## Contexto del problema

Hoy la operación no tiene control en la puerta: no existe ningún registro previo de qué vehículos se esperan ni cuándo, el portero no tiene contra qué contrastar lo que llega, y la evidencia fotográfica de la llegada (vehículo, contenedor, sello, tiquete) se captura de forma dispersa o no se captura.

Además, el mapa de roles creció por acumulación: hay ocho roles (`cliente`, `portero`, `operador`, `coordinador`, `supervisor`, `despachador`, `gerente`, `administrador`), varios de los cuales quedaron sin uso real tras la consolidación del flujo Ingreso/Salida, y responsabilidades que hoy están mezcladas (el portero registra ingresos y salidas completos; el cliente ve reportes y entregas que no le corresponden).

### Cadena operativa definida

El orden de los tres eslabones es:

1. **Ingreso** — se registra primero. Es la declaración documental de la mercancía: BL, cliente, documentos (BL, DIM, lista de empaque), los contenedores y sus referencias.
2. **Cita** — se agenda después, sobre un ingreso ya registrado. Define qué contenedor de ese ingreso llega, qué día se espera y quién lo trae (conductor, cédula, placa, empresa), además de tipo, tamaño y condición (full/vacío).
3. **Portero** — es el último paso. Cuando el vehículo se presenta en la puerta, el portero valida que **tenga cita para la fecha actual** y captura las cuatro evidencias fotográficas obligatorias.

Esta funcionalidad agrega los eslabones 2 y 3, y reorganiza quién puede operar cada uno.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Agendar la llegada de un contenedor ya registrado (Priority: P1)

Con un ingreso ya registrado en el sistema, una persona con el rol **citas** agenda la llegada física de uno de sus contenedores: selecciona el ingreso y el contenedor, indica de qué tipo y tamaño es, si viene full o vacío, qué día se espera, y quién lo trae (conductor, cédula, placa y empresa transportadora). La cita queda visible para consulta y edición mientras no haya sido atendida.

**Why this priority**: Es el insumo del control de portería. Sin citas agendadas, el portero no tiene contra qué validar y la operación sigue igual que hoy. Entrega valor por sí sola desde el día uno: visibilidad de qué vehículos se esperan y qué día.

**Independent Test**: Se puede probar completo tomando un ingreso existente, creando una cita para uno de sus contenedores con todos los datos, consultándola en el listado y editándola, sin necesidad de que existan los módulos de portería ni los cambios de roles.

**Acceptance Scenarios**:

1. **Given** un usuario con rol citas autenticado y un ingreso registrado con contenedores, **When** abre el módulo Citas, selecciona el contenedor de ese ingreso y diligencia tipo, tamaño, fecha posible de llegada, condición (full/vacío), nombre del conductor, cédula del conductor, placa y nombre de la empresa, **Then** el sistema guarda la cita, la asocia al ingreso y al contenedor, y la muestra en el listado con estado "Programada".
2. **Given** un usuario con rol citas, **When** intenta guardar una cita sin alguno de los datos obligatorios, **Then** el sistema no guarda y señala exactamente qué campos faltan, conservando lo ya digitado.
3. **Given** una cita ya creada con estado "Programada", **When** el usuario de citas corrige la fecha esperada o los datos del conductor, **Then** el sistema guarda los cambios y deja registro de quién los hizo y cuándo.
4. **Given** un contenedor de un ingreso que ya tiene una cita en estado Programada, **When** el usuario intenta agendarle otra cita, **Then** el sistema advierte de la cita existente antes de permitir continuar.
5. **Given** un usuario con rol citas, **When** intenta entrar a cualquier otro módulo operativo (ingreso, salida, vaciado, inventario), **Then** el sistema le niega el acceso salvo la consulta de ingresos estrictamente necesaria para seleccionar el contenedor a agendar.
6. **Given** un listado con varias citas, **When** el usuario filtra por fecha esperada, BL, número de contenedor, placa o estado, **Then** el sistema muestra solo las citas que coinciden.

---

### User Story 2 - Control de portería contra la cita del día (Priority: P1)

El portero abre su módulo y ve únicamente las citas cuya fecha esperada es **hoy**. Cuando un vehículo se presenta en la puerta, el portero **valida que ese vehículo tenga cita** para la fecha actual: lo busca en la lista por placa o por número de contenedor y confirma que corresponde. Luego captura las cuatro evidencias obligatorias: foto del vehículo, foto del contenedor, foto del sello y foto del tiquete. Al confirmar, la cita queda marcada como atendida con hora, evidencias y responsable.

**Why this priority**: Es el control físico que hoy no existe y la razón principal de la funcionalidad. Depende de que existan citas (US1), pero una vez agendadas entrega valor inmediato: nadie entra sin cita y queda trazabilidad fotográfica del momento exacto en que la carga llega al patio.

**Independent Test**: Con al menos una cita agendada para hoy, un usuario portero puede localizarla por placa, adjuntar las cuatro fotos y cerrarla como atendida; se verifica que la cita cambia de estado y que las cuatro imágenes quedan asociadas y consultables.

**Acceptance Scenarios**:

1. **Given** un portero autenticado y citas programadas para hoy, ayer y mañana, **When** abre el módulo Portero, **Then** el sistema muestra las de hoy en la sección accionable, las de mañana en "Próximas citas", y ninguna de ayer.
1b. **Given** un portero viendo una cita futura, **When** intenta confirmar la llegada, **Then** el sistema lo impide e indica la fecha en que la confirmación se habilita, ofreciéndole reportar una novedad si el vehículo ya está en la puerta.
2. **Given** un portero con un vehículo en la puerta, **When** busca por placa o por número de contenedor dentro de las citas del día, **Then** el sistema le muestra la cita correspondiente con los datos esperados (contenedor, conductor, cédula, empresa, condición) para contrastarlos con lo que tiene enfrente.
3. **Given** un portero viendo la cita de un contenedor que acaba de llegar, **When** captura las cuatro fotos (vehículo, contenedor, sello, tiquete) y confirma la llegada, **Then** el sistema marca la cita como "Atendida", registra la fecha y hora real de llegada, el usuario que atendió, y conserva las cuatro fotos identificadas por su tipo.
4. **Given** un portero que intenta confirmar una llegada, **When** falta al menos una de las cuatro fotos obligatorias, **Then** el sistema no permite confirmar e indica cuál evidencia falta.
5. **Given** un vehículo que se presenta y **no** aparece en las citas del día, **When** el portero lo busca por placa o contenedor, **Then** el sistema indica explícitamente que no tiene cita para hoy y le permite dejar constancia de la novedad, sin crear una cita atendida.
6. **Given** un portero, **When** no hay ninguna cita programada para la fecha actual, **Then** el sistema muestra un mensaje claro de "sin citas para hoy" en lugar de una pantalla vacía.
7. **Given** una cita ya marcada como atendida, **When** el portero la vuelve a abrir, **Then** el sistema muestra las evidencias registradas en modo consulta y no permite volver a confirmarla como llegada nueva.
8. **Given** un portero autenticado, **When** intenta entrar a los módulos de ingreso, salida, vaciado o inventario, **Then** el sistema le niega el acceso.

---

### User Story 3 - Rol `operaciones` dedicado a ingresos y salidas (Priority: P2)

Existe un rol llamado **`operaciones`** cuya única responsabilidad es registrar los ingresos de mercancía y las salidas de mercancía (Orden de Salida / ODC). Esa responsabilidad deja de estar en el portero, que a partir de ahora solo hace control de portería y pierde por completo el acceso a esos módulos.

**Why this priority**: Separa la portería (control físico en la puerta) del registro documental de ingreso/salida, que hoy están mezclados en un mismo rol. Es un cambio de permisos sin nueva pantalla, por eso va después de los módulos nuevos.

**Independent Test**: Crear un usuario con el nuevo rol y verificar que puede entrar y operar los módulos Ingreso y Salida y ningún otro; y que un usuario portero ya no puede.

**Acceptance Scenarios**:

1. **Given** un usuario con rol `operaciones`, **When** inicia sesión, **Then** ve en su navegación los módulos Ingreso y Salida y puede crear registros en ambos.
2. **Given** ese mismo usuario, **When** intenta acceder a los módulos Citas, Portero, Vaciado o administración de usuarios, **Then** el sistema le niega el acceso.
3. **Given** un usuario con rol portero, **When** intenta crear, editar o consultar un ingreso o una salida, **Then** el sistema le niega el acceso en todos los casos.
4. **Given** un administrador, **When** crea o edita un usuario, **Then** el rol `operaciones` aparece disponible para asignación.

---

### User Story 4 - Supervisor a cargo del vaciado y la ubicación (Priority: P2)

El rol supervisor concentra el vaciado de contenedores y la definición de dónde queda ubicada físicamente la mercancía en el patio o bodega.

**Why this priority**: Consolida en un solo responsable dos tareas que hoy están repartidas entre `operador` y `supervisor`; es prerrequisito para poder retirar el rol `operador` (US6) sin dejar huérfana la capacidad de ubicar mercancía.

**Independent Test**: Con un usuario supervisor, programar y ejecutar un vaciado y luego asignar ubicación a las referencias resultantes, verificando que ambas acciones son permitidas y que el inventario refleja la ubicación asignada.

**Acceptance Scenarios**:

1. **Given** un usuario supervisor, **When** abre el módulo de Vaciado, **Then** puede programar, iniciar, finalizar un vaciado y registrar novedades.
2. **Given** un usuario supervisor con referencias vaciadas sin ubicación, **When** usa la función de ubicar, **Then** puede asignar la ubicación física y el inventario queda actualizado con esa ubicación.
3. **Given** un usuario supervisor, **When** intenta crear un ingreso o una salida de mercancía, **Then** el sistema le niega la acción.

---

### User Story 5 - El cliente ve solo el almacenamiento de sus productos (Priority: P3)

Un cliente que inicia sesión ve exclusivamente el inventario almacenado que le pertenece: qué productos tiene, en qué cantidad y dónde están. No ve mercancía de otros clientes ni módulos operativos internos.

**Why this priority**: Es una corrección de alcance y privacidad sobre una vista que ya existe; el riesgo de exposición de datos entre clientes lo hace importante, pero no bloquea la operación diaria.

**Independent Test**: Con dos clientes que tengan mercancía distinta, iniciar sesión con cada uno y verificar que cada quien ve solo lo suyo y que ningún módulo operativo adicional aparece en su navegación.

**Acceptance Scenarios**:

1. **Given** dos clientes con mercancía almacenada, **When** el cliente A inicia sesión y consulta su almacenamiento, **Then** ve únicamente las referencias y cantidades cuyo titular es el cliente A.
2. **Given** un cliente autenticado, **When** intenta acceder por enlace directo a un registro de mercancía de otro cliente, **Then** el sistema le niega el acceso.
3. **Given** un cliente autenticado, **When** revisa su navegación, **Then** solo aparece el módulo de almacenamiento/inventario propio, sin módulos operativos internos.
4. **Given** un cliente sin mercancía almacenada, **When** consulta su almacenamiento, **Then** el sistema muestra un mensaje de "sin mercancía almacenada" en lugar de una tabla vacía o un error.

---

### User Story 6 - Retirar de circulación los roles no usados (Priority: P3)

Los roles `coordinador`, `despachador`, `gerente` y `operador` dejan de estar disponibles: no aparecen al crear o editar usuarios y nadie nuevo puede quedar asignado a ellos. El histórico y la información asociada no se borran.

**Why this priority**: Es limpieza organizativa. Reduce la confusión al asignar roles, pero no habilita ninguna capacidad nueva, por eso va de último.

**Independent Test**: Un administrador abre el formulario de crear usuario y verifica que la lista de roles seleccionables ya no incluye los cuatro roles retirados, mientras que los registros históricos hechos por usuarios de esos roles siguen consultables.

**Acceptance Scenarios**:

1. **Given** un administrador, **When** abre el formulario de crear o editar usuario, **Then** los roles `coordinador`, `despachador`, `gerente` y `operador` no aparecen entre las opciones seleccionables.
2. **Given** un intento de asignar directamente uno de los roles retirados, **When** se envía la solicitud, **Then** el sistema la rechaza.
3. **Given** un usuario que ya tenía uno de los roles retirados antes del cambio, **When** inicia sesión, **Then** sigue operando con los mismos permisos que tenía, sin bloqueos ni migración automática.
4. **Given** un administrador, **When** consulta el listado de usuarios, **Then** puede identificar cuáles siguen asignados a un rol retirado para reasignarlos.
5. **Given** registros históricos creados por usuarios de esos roles, **When** se consultan los módulos o la trazabilidad, **Then** la información sigue visible e íntegra, incluyendo el nombre del usuario que la registró.
6. **Given** las funciones de edición administrativa que hoy están reservadas a administrador y coordinador, **When** se retira el rol coordinador, **Then** el administrador conserva íntegra la capacidad de editar esos registros.

---

### User Story 7 - Seguimiento de citas desde el ingreso (Priority: P3)

Quien registró un ingreso puede ver, sobre ese mismo ingreso, en qué va cada uno de sus contenedores: si ya tiene cita agendada, para qué fecha, si ya llegó y con qué evidencia fotográfica.

**Why this priority**: Cierra el ciclo de la cadena Ingreso → Cita → Portero y hace detectable el caso de un ingreso cuyos contenedores nadie agendó. No es indispensable para operar, por eso va al final.

**Independent Test**: Con un ingreso que tenga un contenedor agendado y otro sin agendar, abrir el ingreso y verificar que se distingue el estado de cada uno y que la evidencia de portería del contenedor ya llegado es consultable.

**Acceptance Scenarios**:

1. **Given** un ingreso con dos contenedores, uno con cita Programada y otro sin cita, **When** se consulta el ingreso, **Then** el sistema muestra el estado de cita de cada contenedor y señala el que no tiene cita.
2. **Given** un contenedor cuya cita ya fue atendida en portería, **When** se consulta el ingreso, **Then** el sistema muestra la fecha y hora real de llegada y permite ver las cuatro evidencias fotográficas.

---

### Edge Cases

- **Llegada sin cita**: un vehículo se presenta en portería y no aparece en la lista del día. El sistema debe dejar constancia de ese intento y ofrecer una salida clara (no permitir el paso silenciosamente ni obligar al portero a improvisar).
- **Cita agendada para otro día**: el contenedor llega antes o después de la fecha esperada. Como el módulo solo lista la fecha actual, esa cita no es visible para el portero; debe existir una forma explícita de reprogramar o de buscar la cita fuera del día.
- **Ingreso sin cita**: se registra un ingreso y nadie le agenda cita a sus contenedores. Esos contenedores nunca aparecerán en el módulo del portero; debe ser detectable desde el ingreso (FR-047).
- **Contenedor con más de una cita**: se agenda dos veces el mismo contenedor, o el mismo contenedor regresa en otra fecha (caso legítimo: sale vacío y vuelve).
- **Datos que no coinciden en la puerta**: el conductor o la placa que llegan no son los agendados. El portero debe poder ver la diferencia (FR-016) y dejar constancia.
- **Cita vencida sin atender**: pasa el día esperado y la cita nunca fue confirmada en portería; no puede quedar como "Programada" indefinidamente.
- **Fotos pesadas o subida interrumpida**: el portero opera desde un dispositivo móvil con conectividad irregular; una carga fallida no debe dejar la cita a medio confirmar ni perder las fotos ya subidas.
- **Placa o cédula con formato irregular**: datos digitados desde el teléfono, con espacios, guiones o mayúsculas/minúsculas inconsistentes; la búsqueda del portero debe funcionar igual.
- **Usuario asignado a un rol retirado** que intenta iniciar sesión después del cambio.
- **Fecha esperada en el pasado** al crear una cita (agendamiento retroactivo para regularizar una llegada ya ocurrida).
- **Cliente titular de mercancía que además tiene un usuario operativo**: el alcance restringido del rol cliente no debe verse afectado.

## Requirements *(mandatory)*

### Functional Requirements

#### Módulo Citas

- **FR-001**: El sistema MUST ofrecer un módulo Citas donde se agende la llegada física de un contenedor **perteneciente a un ingreso ya registrado**, capturando: tipo de contenedor, tamaño, número de contenedor, fecha posible de llegada, condición (full o vacío), nombre del conductor, cédula del conductor, placa del vehículo y nombre de la empresa transportadora.
- **FR-002**: El sistema MUST permitir seleccionar el ingreso y el contenedor a agendar a partir de los ingresos ya registrados, en lugar de volver a digitar el número de contenedor, y MUST dejar la cita asociada a ese ingreso y contenedor.
- **FR-003**: El sistema MUST exigir todos los campos de FR-001 como obligatorios para poder guardar una cita, y MUST indicar con precisión cuáles faltan cuando no se cumplan.
- **FR-004**: El sistema MUST normalizar placa y cédula a un formato consistente antes de guardar, de modo que las búsquedas del portero no dependan de espacios, guiones ni mayúsculas.
- **FR-005**: El sistema MUST permitir que la condición del contenedor sea exactamente una de dos opciones: full o vacío.
- **FR-006**: El sistema MUST mantener un estado por cita con al menos los valores: Programada, Atendida, Vencida y Cancelada.
- **FR-007**: El sistema MUST permitir listar y filtrar citas por fecha esperada, BL, número de contenedor, placa y estado.
- **FR-008**: El sistema MUST permitir editar y cancelar una cita en estado Programada, y MUST impedir editar los datos de una cita ya Atendida.
- **FR-009**: El sistema MUST registrar, para cada cita, quién la creó y cuándo, y quién la modificó por última vez y cuándo.
- **FR-010**: El sistema MUST marcar como Vencida toda cita que siga en estado Programada después de terminado su día esperado, sin borrar su información, y MUST permitir reprogramarla a una nueva fecha.
- **FR-011**: El sistema MUST advertir —sin bloquear— cuando se agende un contenedor que ya tiene una cita en estado Programada.

#### Rol Citas

- **FR-012**: El sistema MUST ofrecer un rol `citas` cuyo alcance sea el módulo Citas (consultar, crear, editar y cancelar citas), más la consulta de ingresos estrictamente necesaria para seleccionar el contenedor a agendar.
- **FR-013**: El sistema MUST negar al rol `citas` la creación y edición de ingresos, y el acceso a los módulos de salida, vaciado, inventario, reportes y administración.

#### Módulo Portero

- **FR-014**: El sistema MUST ofrecer un módulo Portero que liste las citas de la fecha actual —las accionables— y, en una sección aparte, las **citas futuras** para seguimiento. Las citas de fechas pasadas quedan fuera del módulo.
- **FR-014a**: El sistema MUST distinguir visualmente ambas secciones y MUST impedir confirmar la llegada de una cita futura, indicando la fecha en que se habilitará. Un vehículo que se adelanta se resuelve reprogramando la cita, no confirmándola antes de tiempo.
- **FR-015**: El sistema MUST permitir al portero localizar la cita de un vehículo que se presenta en la puerta buscando por placa o por número de contenedor dentro de las citas del día.
- **FR-016**: El sistema MUST mostrar al portero los datos esperados de la cita (contenedor, tipo, tamaño, condición, conductor, cédula, empresa) para que los contraste con el vehículo que tiene enfrente antes de confirmar.
- **FR-017**: El sistema MUST indicar explícitamente cuando un vehículo buscado no tiene cita para la fecha actual.
- **FR-018**: El sistema MUST permitir al portero, sobre una cita del día, capturar cuatro evidencias fotográficas identificadas por tipo: vehículo, contenedor, sello y tiquete.
- **FR-019**: El sistema MUST exigir las cuatro fotos de FR-018 antes de permitir confirmar la llegada, e indicar cuál falta si el intento es incompleto.
- **FR-020**: Al confirmar una llegada, el sistema MUST registrar la fecha y hora reales del evento y el usuario portero que lo atendió, y MUST cambiar el estado de la cita a Atendida.
- **FR-021**: El sistema MUST impedir que una cita ya Atendida vuelva a confirmarse como llegada, permitiendo únicamente su consulta con las evidencias asociadas.
- **FR-022**: El sistema MUST permitir al portero dejar constancia de un vehículo que se presenta sin cita para la fecha actual, sin que ello cree una cita atendida.
- **FR-023**: El sistema MUST conservar las cuatro fotos asociadas a la cita de forma consultable posteriormente, cada una identificada por su tipo, y MUST hacerlas visibles desde el ingreso al que pertenece el contenedor.
- **FR-024**: El sistema MUST permitir la captura de fotos desde un dispositivo móvil y MUST conservar las evidencias ya subidas si la confirmación falla a mitad de proceso.
- **FR-025**: El sistema MUST mostrar un mensaje explícito cuando no existan citas para la fecha actual.

#### Rol Portero (modificado)

- **FR-026**: El sistema MUST limitar el rol `portero` al módulo Portero y MUST retirarle todo acceso a los módulos Ingreso y Salida, incluida la consulta de solo lectura.
- **FR-027**: El sistema MUST negar al rol `portero` la creación y edición de citas.

#### Rol `operaciones` (ingresos y salidas)

- **FR-028**: El sistema MUST ofrecer un rol llamado `operaciones` cuyo alcance sean los módulos Ingreso y Salida de mercancía (consultar y registrar en ambos).
- **FR-029**: El sistema MUST negar al rol `operaciones` el acceso a los módulos Citas, Portero, Vaciado, administración de usuarios y demás módulos administrativos.
- **FR-030**: El sistema MUST ofrecer el rol `operaciones` como opción asignable al crear o editar un usuario.

#### Rol Supervisor

- **FR-031**: El sistema MUST otorgar al rol `supervisor` la capacidad de programar, iniciar y finalizar vaciados y registrar novedades de vaciado.
- **FR-032**: El sistema MUST otorgar al rol `supervisor` la capacidad de consultar el inventario y asignar la ubicación física de la mercancía.
- **FR-033**: El sistema MUST negar al rol `supervisor` la creación de ingresos y salidas de mercancía.

#### Rol Cliente

- **FR-034**: El sistema MUST limitar el rol `cliente` a la consulta del almacenamiento de su propia mercancía.
- **FR-035**: El sistema MUST filtrar toda consulta de un usuario cliente para que devuelva exclusivamente los registros cuyo titular es ese cliente, incluido el acceso por enlace directo a un registro individual.
- **FR-036**: El sistema MUST retirar del alcance del rol `cliente` los módulos que no correspondan a la consulta de su almacenamiento.
- **FR-037**: El sistema MUST mostrar un mensaje explícito a un cliente sin mercancía almacenada, en lugar de una vista vacía o un error.

#### Roles retirados

- **FR-038**: El sistema MUST dejar de ofrecer los roles `coordinador`, `despachador`, `gerente` y `operador` como opciones asignables al crear o editar usuarios.
- **FR-039**: El sistema MUST rechazar cualquier intento de asignar uno de los roles retirados a un usuario, incluso si la solicitud se envía directamente.
- **FR-040**: El sistema MUST conservar intactos los datos históricos y la autoría de los registros creados por usuarios de los roles retirados.
- **FR-041**: El sistema MUST permitir que los usuarios ya asignados a un rol retirado sigan operando con los permisos que tienen hoy, hasta que un administrador les asigne un rol vigente. El retiro afecta la asignación de roles nuevos, no las asignaciones existentes.
- **FR-042**: El sistema MUST permitir a un administrador identificar qué usuarios siguen con un rol retirado, para poder reasignarlos.
- **FR-043**: El sistema MUST garantizar que ninguna capacidad hoy reservada a `administrador` junto con un rol retirado quede inaccesible: el administrador MUST conservar acceso completo a esas funciones (en particular, la edición administrativa de registros).
- **FR-044**: El sistema MUST poder revertir el retiro de un rol sin pérdida de información, del mismo modo que hoy se ocultan y reactivan módulos.

#### Transversales

- **FR-045**: El sistema MUST mostrar a cada usuario únicamente los módulos a los que su rol tiene acceso, sin enlaces de navegación que lleven a una pantalla de acceso denegado.
- **FR-046**: El sistema MUST conservar en el rol `administrador` acceso a la totalidad de módulos y capacidades, incluidos los nuevos módulos Citas y Portero.
- **FR-047**: El sistema MUST permitir consultar, desde un ingreso, el estado de las citas de sus contenedores (agendada, atendida, vencida) y la evidencia de portería asociada.

### Key Entities

- **Cita**: llegada física prevista de un contenedor que ya fue declarado en un ingreso. Atributos: tipo de contenedor, tamaño, condición (full/vacío), fecha posible de llegada, estado (Programada/Atendida/Vencida/Cancelada), datos del transporte (nombre del conductor, cédula, placa, empresa), autoría (quién creó/modificó y cuándo). Relaciones: pertenece a un ingreso y a uno de sus contenedores; puede tener asociado un registro de llegada en portería.
- **Registro de llegada en portería**: confirmación física de que la cita se materializó. Atributos: fecha y hora reales de llegada, portero responsable, observaciones. Relación: pertenece a una cita y agrupa sus evidencias fotográficas.
- **Evidencia fotográfica de portería**: imagen capturada en la puerta, identificada por su tipo (vehículo, contenedor, sello, tiquete). Relación: pertenece a un registro de llegada.
- **Novedad de portería**: constancia de un vehículo que se presentó sin cita para la fecha actual. Atributos: placa o contenedor reportado, fecha y hora, portero responsable, observación. No genera cita ni llegada atendida.
- **Rol**: conjunto de capacidades asignable a un usuario, con un indicador de disponibilidad que permite retirarlo de circulación sin eliminarlo.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Un usuario de citas registra una cita completa en menos de 2 minutos desde que abre el módulo.
- **SC-002**: El 100 % de las llegadas confirmadas en portería quedan con las cuatro evidencias fotográficas (vehículo, contenedor, sello, tiquete) asociadas y consultables.
- **SC-003**: Un portero completa el control de una llegada —localizar la cita, capturar las cuatro fotos y confirmar— en menos de 3 minutos desde un dispositivo móvil.
- **SC-004**: El módulo Portero separa las citas de hoy de las futuras: 0 citas de fechas pasadas aparecen en su listado, y 0 citas futuras pueden confirmarse como llegadas.
- **SC-005**: Un usuario cliente no accede a ningún registro de mercancía de otro cliente: 0 accesos exitosos en pruebas de acceso cruzado, incluidos intentos por enlace directo.
- **SC-006**: Cada rol accede únicamente a los módulos de su alcance: 0 accesos exitosos a módulos fuera de su alcance en la matriz completa de rol × módulo.
- **SC-007**: Tras el cambio, 0 registros históricos se pierden o quedan sin autoría identificable.
- **SC-008**: El 100 % de las citas no atendidas al terminar su día esperado quedan en estado Vencida; 0 citas quedan "Programadas" con fecha pasada.
- **SC-009**: Un administrador conserva acceso al 100 % de las funciones que antes compartía con roles retirados.
- **SC-010**: Un intento de confirmación de llegada sin las cuatro fotos es rechazado el 100 % de las veces, con indicación de la evidencia faltante.
- **SC-011**: El 100 % de las citas queda vinculada a un ingreso y a un contenedor existentes; 0 citas quedan sin ingreso asociado.
- **SC-012**: El portero localiza la cita de un vehículo por placa o por número de contenedor en menos de 15 segundos, sin importar cómo se hayan digitado esos datos (espacios, guiones, mayúsculas).
- **SC-013**: Desde cualquier ingreso se puede determinar, sin salir de la pantalla, cuáles de sus contenedores tienen cita y cuáles ya llegaron.

## Assumptions

- **Orden de la cadena**: Ingreso → Cita → Portero, confirmado por el usuario. El ingreso es la declaración documental (BL, cliente, documentos, contenedores, referencias); la cita agenda la llegada física de un contenedor de ese ingreso; la portería valida esa llegada.
- **Vínculo cita ↔ ingreso**: la cita se crea seleccionando un contenedor de un ingreso ya registrado; el número de contenedor no se vuelve a digitar. Tipo, tamaño y condición (full/vacío) sí se capturan en la cita porque el ingreso no los registra hoy.
- **Fecha posible de llegada**: se captura como fecha (día), no como hora exacta, y puede ser una fecha pasada (agendamiento retroactivo para regularizar).
- **Tipo y tamaño de contenedor**: se seleccionan de una lista predefinida coherente con la nomenclatura ya usada en el sistema, no como texto libre.
- **Cancelación y reprogramación**: el rol citas puede cancelar o cambiar la fecha de una cita mientras esté en estado Programada; una cita Vencida puede reprogramarse a una nueva fecha.
- **Alcance del rol citas**: puede ver y editar todas las citas del sistema, no solo las que creó.
- **Vehículo sin cita**: la constancia que deja el portero (FR-022) es un registro de novedad para revisión posterior, no una cita creada sobre la marcha; agendar corresponde al rol citas.
- **Módulo Ingreso sin cambios** (R-002): el ingreso se registra con la mercancía ya contada en inventario, aunque el vehículo llegue después. Las diferencias se corrigen en el vaciado vía novedades.
- **Retiro de roles**: se implementa con el mismo criterio ya usado para ocultar módulos —una bandera de configuración reversible—, sin borrar el rol ni sus asignaciones históricas en la base de datos. Las asignaciones vigentes siguen funcionando (R-004).
- **Administrador**: no se ve afectado por ningún retiro de rol y conserva acceso total, incluidos los módulos nuevos.
- **Evidencias fotográficas**: se conservan bajo las mismas reglas de almacenamiento y retención que las fotos ya existentes en el sistema (ingreso, vaciado, salida).
- **Cliente**: "almacenamiento de sus productos" se interpreta como la vista de inventario/almacenamiento existente, restringida a la mercancía cuyo titular es ese cliente.

## Dependencies

- Los módulos Salida, Vaciado e Inventario ya existen y siguen operando; esta funcionalidad cambia **quién** accede a ellos, no cómo funcionan.
- El módulo Ingreso ya existe y **no se modifica** (decisión R-002): sigue exigiendo fecha de ingreso menor o igual a hoy, marcando el contenedor como "en patio" y registrando la entrada de inventario en el mismo acto. Las citas y el control de portería se construyen encima sin alterar ese comportamiento. Las consecuencias están en "Riesgos aceptados".
- El módulo de Vaciado y sus novedades es el mecanismo con el que se corrigen las diferencias entre lo declarado en el ingreso y lo que realmente llega; sus limitaciones actuales están documentadas en "Riesgos aceptados".
- El mecanismo de visibilidad reversible de módulos ya existe y se extiende al concepto de roles retirados.
- El sistema de captura y consulta de fotos asociadas a registros ya existe y se reutiliza para las evidencias de portería.
- Las funciones de edición administrativa hoy reservadas a `administrador` junto con `coordinador` deben revisarse antes de retirar ese rol (ver FR-043).

## Out of Scope

- Notificaciones automáticas al transportador o al cliente sobre la cita (confirmaciones, recordatorios).
- Agendamiento por franjas horarias, cupos por hora o control de aforo del patio.
- Autogestión de citas por parte del cliente o del transportador.
- Reconocimiento automático de placa o de número de contenedor a partir de las fotos.
- Cambios en la lógica interna de Ingreso, Salida, Vaciado o Inventario más allá de los permisos de acceso.
- Migración o reasignación masiva de los usuarios que hoy tienen roles retirados.

## Clarificaciones resueltas

- **R-001** (orden de la cadena): el **Ingreso se registra primero**, después se agenda la **Cita** sobre ese ingreso, y de último el **Portero** valida que el vehículo que llegó tenga cita para la fecha actual. La cita no crea ni prellena el ingreso: al revés, parte de él.
- **R-002** (momento del inventario): **el módulo Ingreso no cambia**. La mercancía se cuenta como inventario y el contenedor queda en patio en el momento en que se registra el ingreso, igual que hoy. La justificación operativa es que las diferencias entre lo declarado y lo real se detectan y corrigen en el **vaciado**, mediante las novedades. Ver "Riesgos aceptados".
- **R-003** (rol de ingresos y salidas): el rol nuevo se llama **`operaciones`** y su alcance son los módulos Ingreso y Salida. El rol `portero` **pierde por completo** el acceso a Ingreso y Salida, incluso de solo lectura.
- **R-004** (usuarios con roles retirados): las cuentas ya asignadas a `coordinador`, `despachador`, `gerente` u `operador` **conservan sus permisos actuales** hasta que un administrador las reasigne manualmente. No se bloquea el acceso ni se migran automáticamente.

## Riesgos aceptados

Derivados de R-002. Ninguno bloquea la decisión, pero quedan registrados porque el inventario declarado no coincidirá con el físico entre el registro del ingreso y el vaciado:

- **El inventario cuenta mercancía que aún no llegó**: entre el registro del ingreso y la llegada confirmada en portería, el sistema reporta como almacenada una mercancía que físicamente no está en el patio. Es visible para el cliente en su vista de almacenamiento.
- **Las novedades de vaciado solo descuentan, nunca suman**: la corrección de inventario en el vaciado resta la cantidad afectada y no permite valores por encima de lo declarado. Si en el vaciado aparece **más** mercancía de la registrada en el ingreso, no existe forma de corregir hacia arriba.
- **Las novedades de vaciado no quedan en el ledger de movimientos**: la corrección ajusta el saldo de la referencia directamente, sin registrar un movimiento de inventario. El ledger y el saldo quedan desalineados en cada novedad.
- **La notificación de novedad no alcanza al cliente en el flujo nuevo**: el aviso al cliente depende de una cadena de datos que solo existe en la cadena vieja (solicitud → orden de servicio), hoy oculta. Los contenedores creados desde el módulo Ingreso no la tienen, por lo que el cliente no se entera de la novedad.

Corregir estos cuatro puntos está **fuera del alcance** de esta funcionalidad; son condiciones preexistentes del módulo de vaciado.
