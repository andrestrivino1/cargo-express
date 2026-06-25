# Feature Specification: Ajustes a ingreso y vaciado

**Feature Branch**: `006-ajustes-ingreso-vaciado`
**Created**: 2026-06-25
**Status**: Draft
**Input**: User description: "se realizaran los siguientes ajustes: 1. El vaciado permitir mas de una foto. 2. Cuando hago un ingreso se agrega 1 BL pero este puede tener mas de un contenedor y cada contenedor puede tener diferentes referencias o las mismo. 3. Agregar la fecha en el ingreso que pueda ser anterior a la fecha en que se crea"

## Resumen

Tres ajustes sobre el flujo operativo ya implementado (feature 005):

1. **Vaciado con varias fotos**: permitir adjuntar más de una fotografía durante el vaciado/desembalaje, no solo al crearlo.
2. **Ingreso por BL con varios contenedores**: un ingreso parte de **un solo BL**, pero ese BL puede agrupar **varios contenedores**, y cada contenedor tiene sus **propias referencias** (que pueden coincidir o diferir entre contenedores).
3. **Fecha de ingreso retroactiva**: capturar en el ingreso una fecha que puede ser **anterior** a la fecha en que se registra, para reflejar la fecha real de llegada de la mercancía.

Son cambios incrementales que no eliminan datos ni rompen los ingresos ya registrados.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Registrar un ingreso de un BL con varios contenedores (Priority: P1)

El operador registra un ingreso indicando un único BL y sus documentos soporte (BL, DIM, Lista de empaque). Bajo ese BL agrega uno o varios contenedores; para cada contenedor captura su número y tipo de mercancía, y una o varias referencias de producto (descripción, unidad de medida, peso, cantidad y, **opcionalmente**, ubicación). Dos contenedores del mismo BL pueden tener las mismas referencias o referencias distintas. La ubicación de almacenamiento puede dejarse vacía al ingresar y asignarse después. Al guardar, la mercancía de cada referencia de cada contenedor entra al inventario del cliente.

**Why this priority**: Es el cambio estructural principal. Hoy un ingreso equivale a un solo contenedor; en la operación real un BL ampara varios contenedores que llegan juntos. Sin esto, el operador tendría que crear varios ingresos para un mismo BL, duplicando documentos y perdiendo la agrupación.

**Independent Test**: Se puede probar registrando un ingreso con un BL y dos contenedores —uno con dos referencias y otro con una que repite código del primero— y verificando que ambos contenedores quedan asociados al mismo BL, que cada referencia entra al inventario con su cantidad, y que los documentos del BL quedan disponibles para todo el ingreso.

**Acceptance Scenarios**:

1. **Given** un operador en el formulario de ingreso, **When** ingresa un BL, agrega dos contenedores y al menos una referencia por contenedor y guarda, **Then** el sistema registra ambos contenedores bajo el mismo BL y suma cada referencia al inventario del cliente.
2. **Given** un ingreso en captura, **When** dos contenedores distintos usan el mismo código de referencia, **Then** el sistema lo permite y mantiene las cantidades separadas por contenedor.
3. **Given** un ingreso con varios contenedores, **When** se consultan los documentos soporte (BL, DIM, Lista de empaque), **Then** están disponibles a nivel del BL/ingreso (no se exige cargarlos por contenedor).
4. **Given** un operador que intenta guardar, **When** el BL no tiene al menos un contenedor con al menos una referencia, **Then** el sistema impide guardar e indica lo que falta.
5. **Given** un operador registrando un ingreso, **When** deja una o varias referencias **sin ubicación** y guarda, **Then** el sistema acepta el ingreso, la mercancía entra al inventario y las referencias quedan "sin ubicar" para asignarles ubicación después.

---

### User Story 2 - Registrar el ingreso con fecha anterior a hoy (Priority: P2)

El operador captura en el ingreso la fecha real de llegada de la mercancía, que puede ser anterior a la fecha en que está registrando. El sistema usa esa fecha como fecha de ingreso para inventario, reportes y trazabilidad, conservando además la marca de cuándo se creó el registro.

**Why this priority**: La mercancía suele registrarse después de llegar. Permitir una fecha retroactiva hace que el inventario, los reportes de ingresos y los días de almacenamiento reflejen la realidad. Es importante pero independiente del cambio de estructura del BL.

**Independent Test**: Se puede probar registrando un ingreso con una fecha de días atrás y verificando que esa fecha aparece como fecha de ingreso en el inventario y reportes, y que los cálculos basados en fecha (p. ej. días en almacenamiento) la usan.

**Acceptance Scenarios**:

1. **Given** un operador en el formulario de ingreso, **When** selecciona una fecha de ingreso anterior a hoy y guarda, **Then** el sistema acepta la fecha y la registra como fecha de ingreso de la mercancía.
2. **Given** un ingreso registrado con fecha retroactiva, **When** se consulta el inventario o el reporte de ingresos, **Then** se muestra la fecha capturada, no la fecha/hora de creación del registro.
3. **Given** un operador, **When** intenta poner una fecha de ingreso posterior a hoy, **Then** el sistema lo advierte o lo impide (no se ingresa mercancía con fecha futura).

---

### User Story 3 - Adjuntar varias fotos durante el vaciado (Priority: P3)

Durante el vaciado/desembalaje de un contenedor, el operador puede adjuntar más de una fotografía como evidencia, tanto al iniciar/crear el vaciado como mientras está en proceso. Todas las fotos quedan asociadas al vaciado y disponibles para consulta y trazabilidad.

**Why this priority**: Refuerza la evidencia de la recepción. El registro de novedades ya admite varias fotos; este ajuste garantiza que el vaciado en sí también permita varias y que se puedan sumar a lo largo del proceso, no solo al crearlo.

**Independent Test**: Se puede probar abriendo un vaciado, adjuntando dos o más fotos en una sola carga y, posteriormente, agregando otra foto al vaciado en proceso; luego verificando que todas quedan guardadas y visibles.

**Acceptance Scenarios**:

1. **Given** un operador creando un vaciado, **When** selecciona varias fotos a la vez y guarda, **Then** todas quedan asociadas al vaciado.
2. **Given** un vaciado ya creado y en proceso, **When** el operador agrega una o más fotos adicionales, **Then** se suman a las existentes sin reemplazarlas.
3. **Given** un vaciado con varias fotos, **When** se consulta su detalle o la trazabilidad, **Then** se ven todas las fotografías cargadas.

---

### Edge Cases

- ¿Qué pasa si un BL tiene un solo contenedor? El sistema lo permite (es el caso mínimo) y sigue funcionando como hasta ahora.
- ¿Qué pasa con los ingresos históricos de un solo contenedor creados antes de este ajuste? Permanecen válidos y consultables; no se pierden ni se rompen.
- ¿Qué pasa si dos contenedores del mismo BL repiten el mismo número de contenedor? El sistema debe advertir/impedir números de contenedor duplicados dentro del mismo ingreso.
- ¿Qué pasa si se intenta guardar un contenedor sin referencias, o una referencia con cantidad cero o negativa? El sistema lo impide.
- ¿Qué pasa si una referencia se ingresa sin ubicación? Se permite; queda "sin ubicar" y aparece como pendiente de ubicar en el inventario para asignarle ubicación luego.
- ¿Qué pasa si la fecha de ingreso es muy antigua (p. ej. meses atrás)? Se permite; queda registrada tal cual (no hay tope inferior salvo política operativa).
- ¿Qué pasa si la fecha de ingreso queda posterior a la fecha de una salida de esa mercancía? El sistema debe evitar inconsistencias (la salida no puede ser anterior al ingreso).
- ¿Qué pasa si no se adjunta ninguna foto al vaciado? Se permite; las fotos del vaciado son opcionales.
- ¿Cuántas fotos se pueden subir por vaciado? No hay un tope rígido; cada archivo respeta el límite de tamaño vigente.

## Requirements *(mandatory)*

### Functional Requirements

#### Ingreso por BL con varios contenedores
- **FR-001**: El sistema MUST permitir registrar un ingreso a partir de **un solo BL** que agrupe **uno o varios contenedores**.
- **FR-002**: Por cada contenedor del ingreso, el sistema MUST capturar su número, su tipo de mercancía y **una o varias referencias** (descripción, unidad de medida, peso, cantidad y ubicación opcional).
- **FR-002a**: El sistema MUST permitir registrar el ingreso con la **ubicación de almacenamiento vacía** (opcional) en una o todas las referencias; esas referencias quedan "sin ubicar" y pueden recibir ubicación posteriormente desde el módulo de inventario, sin volver a registrar el ingreso.
- **FR-003**: El sistema MUST permitir que distintos contenedores del mismo BL tengan referencias iguales (mismo código) o diferentes, manteniendo las cantidades por contenedor.
- **FR-004**: Los documentos soporte (BL, DIM, Lista de empaque) MUST asociarse al BL/ingreso y aplicar a todos sus contenedores (no se exige cargarlos por contenedor).
- **FR-005**: El sistema MUST exigir, para guardar un ingreso, al menos un contenedor con al menos una referencia válida.
- **FR-006**: El sistema MUST impedir números de contenedor duplicados dentro del mismo ingreso.
- **FR-007**: Al guardar, el sistema MUST sumar al inventario del cliente cada referencia de cada contenedor con su cantidad.

#### Fecha de ingreso
- **FR-008**: El sistema MUST permitir capturar en el ingreso una **fecha de ingreso** que puede ser anterior a la fecha en que se registra.
- **FR-009**: El sistema MUST usar la fecha de ingreso capturada como fecha de la mercancía para inventario, reportes de ingresos y cálculos por fecha (p. ej. días en almacenamiento).
- **FR-010**: El sistema MUST conservar de forma independiente la marca temporal de creación del registro (para auditoría), distinta de la fecha de ingreso capturada.
- **FR-011**: El sistema MUST impedir (o advertir) una fecha de ingreso **posterior** a la fecha actual.

#### Vaciado con varias fotos
- **FR-012**: El sistema MUST permitir adjuntar **varias fotografías** a un vaciado en una sola operación de carga.
- **FR-013**: El sistema MUST permitir agregar fotografías adicionales a un vaciado **ya creado / en proceso**, sumándolas a las existentes sin reemplazarlas.
- **FR-014**: Todas las fotografías de un vaciado MUST quedar asociadas a él y disponibles en su detalle y en la trazabilidad.

#### Compatibilidad
- **FR-015**: El sistema MUST mantener válidos y consultables los ingresos y vaciados registrados antes de estos ajustes, sin pérdida de datos.

### Key Entities *(include if feature involves data)*

- **Ingreso (por BL)**: Agrupa la entrada de mercancía amparada por un BL. Atributos clave: BL, fecha de ingreso (retroactiva posible), cliente, documentos soporte (BL, DIM, Lista de empaque), responsable y marca de creación. Contiene uno o varios contenedores.
- **Contenedor**: Pertenece a un ingreso/BL. Atributos: número, tipo de mercancía. Contiene una o varias referencias.
- **Referencia / Inventario**: Mercancía identificada por código dentro de un contenedor, con descripción, unidad de medida, peso, cantidad y ubicación **opcional** (puede quedar "sin ubicar"). Se incrementa con el ingreso. Dos contenedores pueden tener el mismo código de referencia con cantidades independientes.
- **Documento soporte**: Archivo (BL, DIM, Lista de empaque) asociado al ingreso/BL, aplicable a todos sus contenedores.
- **Vaciado / Recepción**: Desembalaje de un contenedor; admite varias fotografías de evidencia, agregables durante el proceso.
- **Evidencia fotográfica**: Fotografía asociada al vaciado (varias por vaciado).

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Un operador puede registrar en una sola operación un ingreso con un BL y al menos dos contenedores, cada uno con sus referencias, sin crear ingresos separados.
- **SC-002**: El 100% de las referencias de cada contenedor de un ingreso multi-contenedor entran al inventario del cliente con la cantidad capturada.
- **SC-003**: La fecha de ingreso mostrada en inventario y reportes coincide con la fecha capturada por el operador (no con la fecha de creación) en el 100% de los ingresos con fecha retroactiva.
- **SC-004**: El sistema rechaza el 100% de los intentos de fecha de ingreso futura y de números de contenedor duplicados dentro de un mismo ingreso.
- **SC-005**: Un vaciado puede contener dos o más fotografías, verificable en su detalle, y se pueden agregar fotos después de creado.
- **SC-006**: Cero pérdida de información: todos los ingresos y vaciados previos al ajuste siguen consultables.
- **SC-007**: Un ingreso se puede guardar con referencias sin ubicación; esas referencias quedan disponibles en el inventario como "sin ubicar" y pueden recibir ubicación después.

## Assumptions

- La fecha de ingreso es **una sola por ingreso/BL** y aplica a todos sus contenedores y referencias. Si más adelante se requiere fecha por contenedor, se ajustará en clarificación.
- El **tipo de mercancía** se captura **por contenedor** (cada contenedor puede traer mercancía distinta). El BL y los documentos soporte son a nivel del ingreso.
- La fecha de ingreso permitida es **menor o igual a hoy**; no se permiten fechas futuras. No se define un tope inferior (puede ser de semanas/meses atrás).
- Las fotografías del vaciado siguen siendo **opcionales** y respetan el límite de tamaño por archivo ya vigente; no hay un número máximo rígido de fotos.
- El cliente se captura a nivel de ingreso. La **ubicación es opcional** por referencia: puede asignarse en el ingreso o dejarse vacía para ubicarla después desde el módulo de inventario.
- Estos ajustes reutilizan el módulo de Ingreso y de Vaciado existentes (feature 005) y no introducen módulos nuevos ni eliminan los actuales.
- Se mantiene el control de acceso por rol vigente para quién registra ingresos y vaciados.
