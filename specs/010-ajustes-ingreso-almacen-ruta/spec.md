# Feature Specification: Corrección de cantidades en ingreso, retiro de productos en almacenamiento y landing del sitio

**Feature Branch**: `010-ajustes-ingreso-almacen-ruta`
**Created**: 2026-09-25
**Status**: Draft
**Input**: User description: "1. En ingreso al editar permitir modificar las cantidades de los contenedores. 2. En almacenamiento poder eliminar productos del cliente. 3. Solucionar ruta al poner la url del sitio"

## Contexto del problema

Tres ajustes independientes sobre la operación ya en producción. Los tres nacen de errores que hoy no tienen salida dentro del sistema y obligan a tocar la base de datos a mano o a convivir con datos equivocados.

1. **La cantidad declarada en el ingreso es inmutable.** Al editar un ingreso se pueden corregir el BL, el cliente, la fecha y agregar imágenes o una referencia nueva, pero las cantidades de las referencias ya registradas en cada contenedor se muestran **solo como lectura** (5 / 5, 10 / 10). Cuando el vaciado revela que llegaron 8 y no 5 —error de digitación o de la lista de empaque— no hay forma de corregirlo: la única alternativa es eliminar el ingreso completo (bloqueado en cuanto la mercancía se movió) o dejar el inventario descuadrado para siempre.

2. **No se puede sacar mercancía del almacenamiento salvo despachándola.** La pantalla de almacenamiento lista las referencias del cliente y permite editar código, descripción, unidad y ubicación, pero no permite **retirar** una referencia del inventario. Los registros creados por error —importaciones duplicadas, referencias fantasma, mercancía que nunca llegó— se quedan en el listado, en los exportables y en la vista del cliente indefinidamente.

3. **La URL del sitio no lleva a la aplicación.** Al entrar a la dirección del sitio, un visitante aterriza en la página de bienvenida genérica del framework: un logotipo enorme, enlaces a documentación externa y un enlace público de registro. No es la cara de la empresa, no lleva a ninguna parte útil, y expone una puerta de alta de usuarios que la operación no usa (los usuarios los crea el administrador y quedan obligados a cambiar credenciales en su primer ingreso).

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Corregir la cantidad recibida de una referencia (Priority: P1)

Una persona autorizada abre la edición de un ingreso ya registrado y ve, agrupadas por contenedor, todas las referencias del BL. La columna de cantidad deja de ser un texto fijo y pasa a ser un campo editable: corrige el número de la referencia que llegó mal digitada, guarda, y el inventario del cliente refleja de inmediato la cantidad correcta. El cambio queda registrado en la auditoría con el valor anterior, el nuevo, quién lo hizo y cuándo.

La corrección se permite **también cuando la mercancía ya se movió**, que es justo cuando el error suele detectarse: lo disponible se ajusta en la misma diferencia que lo declarado, conservando lo que ya salió.

**Why this priority**: Es la corrección que hoy no tiene salida alguna dentro del sistema y que contamina todo lo que viene después —inventario, reportes, órdenes de salida y lo que ve el cliente—. Sin ella, un error de digitación en el ingreso se arrastra hasta que alguien edita la base de datos a mano.

**Independent Test**: Se prueba de punta a punta editando un ingreso con referencias intactas, cambiando una cantidad y verificando en almacenamiento que la referencia muestra el valor corregido y que la auditoría registró el cambio. No depende de las otras dos historias.

**Acceptance Scenarios**:

1. **Given** un ingreso con una referencia declarada en 5 unidades y sin ningún movimiento posterior a la entrada, **When** la persona autorizada edita el ingreso y cambia la cantidad a 8, **Then** la referencia queda en 8 unidades declaradas y 8 disponibles, y el inventario del cliente muestra 8.
2. **Given** ese mismo ingreso, **When** la persona guarda la corrección, **Then** la auditoría registra el cambio de cantidad con valor anterior, valor nuevo, usuario y fecha.
3. **Given** un ingreso con varias referencias en varios contenedores, **When** la persona corrige la cantidad de dos de ellas en un mismo guardado, **Then** ambas quedan corregidas y las demás permanecen exactamente como estaban.
4. **Given** una referencia cuya cantidad se intenta dejar en cero o en un número negativo, **When** la persona guarda, **Then** el sistema rechaza el guardado con un mensaje claro y ninguna referencia del ingreso se modifica.
5. **Given** una referencia declarada en 5 de la que ya salieron 2 unidades (3 disponibles), **When** la persona corrige la cantidad declarada a 8, **Then** la referencia queda en 8 declaradas y 6 disponibles: lo disponible se ajusta en la misma diferencia (+3) y lo ya despachado se conserva intacto.
6. **Given** esa misma referencia declarada en 5 con 2 unidades ya despachadas, **When** la persona intenta corregir la cantidad declarada a 1, **Then** el sistema rechaza el guardado porque no se puede declarar menos de lo que ya salió, e indica cuántas unidades ya fueron despachadas.
7. **Given** una persona sin permiso de edición de ingresos, **When** intenta abrir la edición o enviar una corrección de cantidad, **Then** el sistema se lo impide y no se altera ningún dato.

---

### User Story 2 - Retirar un producto del cliente del almacenamiento (Priority: P2)

Una persona autorizada localiza en el listado de almacenamiento una referencia que no debería estar ahí —cargada dos veces, fantasma, o mercancía que nunca llegó—, ejecuta la acción de retirarla desde la fila, confirma en un diálogo que le informa qué referencia y de qué cliente se va a retirar. La referencia deja de aparecer en el inventario, en los exportables de Excel y PDF, en la pantalla de ubicación y en la consulta del cliente.

El retiro **no borra la historia**: los movimientos, las órdenes de salida, las transferencias y los vaciados en que esa referencia participó siguen intactos y siguen siendo consultables. La referencia sale del inventario vigente, no del registro histórico.

**Why this priority**: Es limpieza de datos con impacto directo en lo que el cliente ve, pero a diferencia de la historia 1 no corrompe la operación en curso: una referencia sobrante se puede ignorar mientras tanto. Toca más superficies del sistema —listados, exportables, ubicación, salidas, reportes— por lo que conviene abordarla después.

**Independent Test**: Se prueba creando una referencia de prueba, retirándola desde almacenamiento y verificando que desaparece del listado, de los exportables y de la vista del cliente, que no se puede seleccionar para salida ni ubicación, y que su historial anterior sigue visible en trazabilidad. No depende de las otras dos historias.

**Acceptance Scenarios**:

1. **Given** una referencia en inventario, **When** la persona autorizada ejecuta el retiro y confirma, **Then** la referencia desaparece del listado de almacenamiento y el sistema muestra un mensaje de éxito que la identifica.
2. **Given** esa misma referencia retirada, **When** se generan los exportables de inventario en Excel y PDF y el cliente consulta su almacenamiento, **Then** la referencia no aparece en ninguno y no suma en los totales.
3. **Given** una referencia que ya fue despachada, transferida o tuvo novedades de vaciado, **When** la persona la retira, **Then** el retiro procede igualmente y todo su historial previo —movimientos, órdenes de salida, transferencias, vaciados— permanece consultable sin cambios.
4. **Given** una referencia retirada, **When** alguien arma una orden de salida, una transferencia o asigna ubicaciones en patio, **Then** la referencia retirada no aparece como opción seleccionable.
5. **Given** una referencia con unidades disponibles al momento del retiro, **When** se confirma el retiro, **Then** el historial de inventario registra la baja de esas unidades con usuario y fecha, de modo que la existencia del cliente sigue cuadrando con sus movimientos.
6. **Given** el diálogo de confirmación abierto, **When** la persona cancela, **Then** no se retira nada y permanece en el listado con sus filtros intactos.
7. **Given** una persona autorizada consultando el almacenamiento, **When** activa el filtro de referencias retiradas, **Then** ve las referencias retiradas marcadas como tales, con quién las retiró y cuándo.
8. **Given** una persona sin permiso para retirar en almacenamiento —incluido el cliente, que solo consulta—, **When** consulta el listado, **Then** no ve la acción de retirar, y si intenta ejecutarla por otra vía el sistema se lo impide.

---

### User Story 3 - Llegar al acceso de la aplicación escribiendo la dirección del sitio (Priority: P3)

Alguien escribe la dirección del sitio en el navegador y llega a la pantalla de acceso de Cargo Express. Quien ya tiene sesión abierta no se queda en esa pantalla: el sistema lo lleva directo a su tablero, como hoy. Nadie ve la página genérica del framework, el logotipo ajeno ni enlaces a documentación externa, y tampoco hay una puerta abierta de registro de usuarios: las cuentas las crea el administrador.

**Why this priority**: Es la de menor alcance funcional —no toca datos de la operación— pero es la primera impresión del sistema y hoy da la sensación de un sitio sin terminar, además de exponer un alta de usuarios que nadie usa. Se resuelve de forma aislada y no bloquea ni depende de nada más.

**Independent Test**: Se prueba entrando a la dirección del sitio sin sesión y con sesión, verificando el destino en cada caso, y comprobando que la opción de registro ya no está disponible por ninguna vía. No depende de las otras dos historias.

**Acceptance Scenarios**:

1. **Given** un visitante sin sesión iniciada, **When** entra a la dirección del sitio, **Then** llega a la pantalla de acceso de la aplicación, con la identidad visual de Cargo Express y sin enlaces a documentación externa.
2. **Given** un usuario con sesión activa, **When** entra a la dirección del sitio, **Then** llega a su tablero sin ver el formulario de acceso.
3. **Given** un usuario que debe completar su primer inicio de sesión, **When** entra a la dirección del sitio, **Then** el sistema lo lleva a completar ese paso antes que a cualquier otra pantalla.
4. **Given** un visitante sin sesión en la pantalla de acceso, **When** la observa, **Then** no encuentra ninguna opción para registrarse por su cuenta.
5. **Given** alguien que conoce la dirección directa de la pantalla de registro, **When** intenta abrirla, **Then** el sistema no le permite crear una cuenta.
6. **Given** un usuario cuya sesión expiró, **When** entra a la dirección del sitio, **Then** llega a la pantalla de acceso, no a un error.

---

### Edge Cases

- **Cantidad corregida por debajo de lo ya despachado**: si una referencia tiene 10 declaradas y 6 disponibles porque salieron 4, y alguien intenta corregir lo declarado a 3, el sistema lo rechaza: no se puede declarar menos de lo que ya salió.
- **Corrección sin cambio real**: si la persona guarda el formulario sin tocar ninguna cantidad, no debe generarse ningún registro de auditoría ni alterarse el inventario.
- **Guardado parcial**: si una de las cantidades del formulario es inválida, ninguna de las demás debe quedar aplicada —el guardado es todo o nada—.
- **Corrección de una referencia ya retirada**: una referencia retirada del inventario no se corrige desde el ingreso; el formulario no la ofrece como editable.
- **Retiro de la última referencia de un contenedor**: el contenedor y el ingreso permanecen; queda un contenedor sin referencias vigentes, situación que la vista de referencias del BL ya contempla.
- **Retiro de una referencia comprometida en una orden de salida ya emitida**: la orden pasada sigue mostrando la referencia tal como estaba; lo que se impide es incluirla en órdenes nuevas.
- **Retiro de una referencia con cantidad disponible mayor que cero**: la baja debe quedar explicada en el historial de inventario, para que las existencias del cliente sigan cuadrando con la suma de sus movimientos.
- **Dos personas editando el mismo ingreso a la vez**: la última corrección guardada es la que queda, y la auditoría debe permitir reconstruir la secuencia de ambas.
- **Eliminación de un ingreso con referencias retiradas**: las reglas actuales que bloquean el borrado de un ingreso ya movido siguen aplicando; un retiro previo no habilita el borrado del ingreso.

## Requirements *(mandatory)*

### Functional Requirements

#### Corrección de cantidades en el ingreso

- **FR-001**: Al editar un ingreso, el sistema MUST presentar las referencias vigentes de cada contenedor con su cantidad en un campo editable, en lugar de un valor de solo lectura.
- **FR-002**: El sistema MUST permitir corregir la cantidad de varias referencias, de uno o varios contenedores, en un mismo guardado.
- **FR-003**: El sistema MUST aceptar únicamente cantidades enteras mayores o iguales a 1, y MUST rechazar el guardado completo si alguna cantidad enviada es inválida, sin aplicar ninguno de los cambios del formulario.
- **FR-004**: El sistema MUST rechazar una cantidad declarada inferior a la cantidad ya consumida de esa referencia (lo despachado, transferido o descontado por novedades), indicando cuántas unidades ya salieron.
- **FR-005**: Al corregir la cantidad declarada de una referencia, el sistema MUST ajustar su cantidad disponible en la misma diferencia, de modo que lo disponible quede igual a la nueva cantidad declarada menos lo ya consumido.
- **FR-006**: El sistema MUST permitir la corrección tanto en referencias intactas como en referencias que ya tuvieron movimientos posteriores a la entrada.
- **FR-007**: El sistema MUST registrar en la auditoría cada cantidad corregida, con valor anterior, valor nuevo, usuario responsable y fecha y hora.
- **FR-008**: El sistema MUST dejar la diferencia explicada en el historial de movimientos de inventario, de forma que la cantidad disponible de la referencia siga siendo reconstruible a partir de sus movimientos.
- **FR-009**: El sistema MUST restringir la corrección de cantidades a las mismas personas que hoy pueden editar un ingreso, y MUST impedirla a cualquier otra, tanto ocultando el acceso como rechazando el envío directo.
- **FR-010**: El sistema MUST conservar sin alteración las referencias cuya cantidad no fue modificada en el guardado.

#### Retiro de productos en almacenamiento

- **FR-011**: El listado de almacenamiento MUST ofrecer, por fila, una acción para retirar la referencia del inventario, visible únicamente para quien tiene permiso de ejecutarla.
- **FR-012**: El sistema MUST pedir confirmación explícita antes de retirar, identificando en el diálogo la referencia y el cliente afectados. No pide motivo: el retiro se confirma, no se diligencia.
- **FR-013**: El retiro MUST proceder siempre, tenga o no la referencia movimientos previos, y MUST conservar íntegro todo su historial: movimientos de inventario, órdenes de salida, transferencias, vaciados y registros de auditoría.
- **FR-014**: Tras el retiro, el sistema MUST excluir la referencia del listado de almacenamiento, de los exportables de inventario, de la pantalla de asignación de ubicación y de la consulta del cliente, y MUST NOT contarla en los totales de existencias vigentes.
- **FR-015**: El sistema MUST impedir que una referencia retirada sea seleccionada en órdenes de salida, transferencias, vaciados o cualquier operación nueva sobre inventario.
- **FR-016**: El sistema MUST registrar la baja de las unidades disponibles al momento del retiro en el historial de movimientos de inventario, con usuario responsable y fecha y hora, de modo que las existencias del cliente sigan cuadrando con la suma de sus movimientos.
- **FR-017**: El sistema MUST registrar el retiro en la auditoría, con los datos de la referencia, el usuario responsable y la fecha y hora.
- **FR-018**: El sistema MUST permitir a los perfiles autorizados consultar las referencias retiradas mediante un filtro explícito en almacenamiento, mostrando quién las retiró y cuándo.
- **FR-019**: El sistema MUST restringir el retiro a los perfiles autorizados y MUST impedirlo al cliente, que solo consulta su mercancía.
- **FR-020**: Retirar una referencia MUST NOT eliminar ni alterar su contenedor ni el ingreso del que proviene.

#### Entrada al sitio

- **FR-021**: Al entrar a la dirección raíz del sitio, el sistema MUST llevar al visitante a la pantalla de acceso de la aplicación.
- **FR-022**: Cuando quien entra a la dirección raíz tiene sesión activa, el sistema MUST llevarlo a su tablero sin mostrarle el formulario de acceso, respetando el paso obligatorio de primer inicio de sesión cuando aplique.
- **FR-023**: El sistema MUST NOT mostrar en la entrada al sitio la página genérica del framework, su logotipo ni enlaces a documentación externa.
- **FR-024**: El sistema MUST NOT ofrecer registro público de usuarios, ni como enlace visible ni por acceso directo a la pantalla de registro; las cuentas las crea el administrador.

### Key Entities *(include if data involved)*

- **Referencia**: la mercancía de un cliente dentro de un contenedor. Tiene un código, una descripción, una unidad de medida, una **cantidad declarada** (lo que se registró al ingresar), una **cantidad disponible** (lo que queda tras salidas, transferencias y novedades) y su ubicación en patio. Gana un **estado de retiro** —vigente o retirada, con responsable y fecha—, que determina si sigue contando como existencia. Es el registro que se corrige en la historia 1 y el que se retira en la historia 2.
- **Contenedor**: agrupa las referencias de un mismo ingreso. No cambia en esta funcionalidad; solo es el marco en que se presentan las referencias al editar.
- **Ingreso**: la declaración documental de un BL, con su cliente, su fecha y sus contenedores. Es el punto de entrada de la corrección de cantidades.
- **Movimiento de inventario**: el asiento que explica por qué la cantidad disponible de una referencia es la que es. Recibe dos casos nuevos: el ajuste por corrección de cantidad y la baja por retiro.
- **Registro de auditoría**: la constancia de qué cambió, quién lo cambió y cuándo. Aplica tanto a la corrección de cantidades como al retiro de referencias.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Una cantidad mal registrada en un ingreso se corrige de punta a punta en menos de 2 minutos, sin intervención técnica sobre la base de datos.
- **SC-002**: El 100 % de las correcciones de cantidad y de los retiros de referencia quedan reconstruibles desde la auditoría: qué valor había, cuál quedó, quién y cuándo.
- **SC-003**: Tras una corrección de cantidad, el inventario del cliente, los exportables y la consulta del cliente muestran el mismo valor, sin discrepancias entre pantallas.
- **SC-004**: En el 100 % de los casos, la cantidad disponible de una referencia se puede reconstruir sumando sus movimientos, también después de una corrección o un retiro.
- **SC-005**: El 100 % del historial previo de una referencia retirada —órdenes de salida, transferencias, vaciados— sigue consultable después del retiro.
- **SC-006**: Las solicitudes de corrección o limpieza de inventario que hoy se resuelven por fuera del sistema se reducen a cero.
- **SC-007**: El 100 % de los intentos de corregir cantidades o retirar referencias por parte de perfiles no autorizados son rechazados, incluso enviando la petición directamente.
- **SC-008**: Un visitante que escribe la dirección del sitio llega a la pantalla de acceso de Cargo Express en un solo paso, y ninguna vía del sitio permite crear una cuenta sin el administrador.

## Assumptions

- **Alcance de "cantidades de los contenedores"**: se entiende como la cantidad de cada **referencia** dentro de cada contenedor —que es lo que el sistema registra y lo que muestra hoy la vista de edición—, no un total por contenedor, dato que no existe de forma independiente.
- **Alcance de "productos del cliente" en almacenamiento**: se entiende como las **referencias en inventario** que lista la pantalla de almacenamiento, no el catálogo de productos, que ya cuenta con su propia gestión y borrado.
- **"Eliminar" se implementa como retiro**: por decisión tomada en la aclaración de esta especificación, la referencia sale del inventario vigente pero su historial se conserva. No hay borrado físico de referencias desde almacenamiento.
- **El retiro no pide motivo**: se evaluó exigir una explicación corta y la operación decidió que no hace falta. El diálogo solo informa qué referencia y de qué cliente se va a retirar; la constancia de la baja es quién la hizo, cuándo, y el movimiento en el historial de inventario.
- **"Siempre que ingresen a la URL muestre el login"**: se interpreta como que la dirección raíz siempre apunta a la pantalla de acceso. A quien ya tiene sesión, esa pantalla lo lleva a su tablero sin pedirle credenciales de nuevo —mostrarle un formulario de acceso a alguien que ya entró sería un retroceso, no una mejora—.
- **Perfiles autorizados**: la corrección de cantidades hereda exactamente el mismo alcance de permisos que hoy tiene la edición de ingresos, y el retiro en almacenamiento el mismo alcance que hoy tiene la edición de referencias en inventario. No se crean perfiles nuevos; si se requiere un permiso propio para el retiro, se otorga a los mismos perfiles que ya editan inventario.
- **Auditoría**: se reutiliza el mecanismo de auditoría de cambios ya existente en el sistema; no se define uno nuevo.
- **Alcance de la historia 3**: se limita al destino de la dirección raíz y al cierre del registro público. No incluye rediseñar la pantalla de acceso ni el tablero.
- **Sin cambios en el flujo operativo**: ninguna de las tres historias altera la cadena Ingreso → Cita → Portería → Salida; son correcciones sobre datos ya registrados y sobre la entrada al sitio.

## Out of Scope

- Corregir cantidades desde la pantalla de almacenamiento: la corrección se hace en el ingreso, que es la fuente del dato.
- Agregar o quitar contenedores completos al editar un ingreso.
- Reversar un retiro (devolver una referencia retirada al inventario vigente) desde la interfaz.
- Borrado físico de referencias.
- Rediseñar la pantalla de acceso, el tablero o la identidad visual del sistema.
- Cambiar el catálogo de productos o su gestión.
- Habilitar cualquier forma de auto-registro o recuperación de cuenta distinta de la actual.
