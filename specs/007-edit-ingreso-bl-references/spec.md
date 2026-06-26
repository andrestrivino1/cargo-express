# Feature Specification: Editar ingreso con referencias e imágenes del BL

**Feature Branch**: `007-edit-ingreso-bl-references`  
**Created**: 2026-06-26  
**Status**: Draft  
**Input**: User description: "al importar el archivo excel veo en ingreso que quedaron varios ingresos que les falta el BL y es entendible pero cuando voy a editar solo muestra lo de la imagen pero recuerda que el BL puede venir con varias referencias y se necesitan las imagenes entonces es ideal que es información se muestre al editar y se pueda agregar allí mismo"

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Completar el BL viendo todo el contexto del ingreso (Priority: P1)

Tras importar el archivo Excel, varios ingresos quedan con un BL provisional (el número de contenedor) marcado como "por confirmar". El operador abre el ingreso para editarlo y necesita ver, en la misma pantalla, todas las referencias (mercancía) que llegaron asociadas a ese BL —porque un mismo BL puede traer varias referencias— para confirmar que está escribiendo el BL correcto y guardar el dato real.

**Why this priority**: Es el problema central reportado. Hoy la pantalla de edición solo muestra BL, Cliente y Fecha de ingreso, sin las referencias asociadas, por lo que el operador edita "a ciegas" y no puede verificar a qué mercancía corresponde el BL que está confirmando. Sin esto, la corrección de los ingresos importados es lenta y propensa a error.

**Independent Test**: Importar un Excel que genere un ingreso con BL provisional y varias referencias, abrir ese ingreso en edición y confirmar que se listan todas las referencias del BL junto a los campos editables; escribir el BL real y guardar; verificar que el ingreso queda confirmado conservando sus referencias.

**Acceptance Scenarios**:

1. **Given** un ingreso creado por importación con BL provisional y 3 referencias asociadas, **When** el operador abre la pantalla de edición, **Then** se muestran los campos editables (BL, Cliente, Fecha de ingreso) y, además, la lista de las 3 referencias asociadas a ese BL con sus datos clave.
2. **Given** la pantalla de edición con las referencias visibles, **When** el operador escribe el BL real y guarda, **Then** el ingreso queda guardado con el BL real, deja de estar marcado como "por confirmar" y conserva todas sus referencias.
3. **Given** un ingreso con un solo contenedor/una sola referencia, **When** el operador abre la edición, **Then** se muestra esa referencia sin errores y la pantalla funciona igual que con múltiples referencias.

---

### User Story 2 - Agregar las imágenes del BL durante la edición (Priority: P1)

El operador necesita adjuntar las imágenes/fotos correspondientes al BL (documento del BL, soporte de la mercancía, etc.) en el momento de editar el ingreso, ya que los ingresos creados por importación llegan sin imágenes. Debe poder subir una o varias imágenes desde la misma pantalla de edición y ver las que ya están adjuntas.

**Why this priority**: El usuario indica explícitamente "se necesitan las imágenes... que se pueda agregar allí mismo". Es parte esencial del flujo de completar un ingreso importado. Sin imágenes, el registro queda incompleto frente a los requisitos operativos del patio.

**Independent Test**: Abrir un ingreso en edición, adjuntar dos imágenes, guardar, y verificar que las imágenes quedan asociadas al ingreso y se muestran al volver a abrir la edición o el detalle del ingreso.

**Acceptance Scenarios**:

1. **Given** un ingreso sin imágenes en la pantalla de edición, **When** el operador selecciona y sube una o varias imágenes y guarda, **Then** las imágenes quedan asociadas al ingreso y son visibles posteriormente.
2. **Given** un ingreso que ya tiene imágenes adjuntas, **When** el operador abre la edición, **Then** se muestran las imágenes existentes y puede agregar imágenes adicionales sin borrar las previas.
3. **Given** el operador adjunta un archivo que no es una imagen válida o supera el tamaño permitido, **When** intenta guardar, **Then** el sistema rechaza ese archivo con un mensaje claro y no pierde el resto de los datos ya ingresados.

---

### User Story 3 - Agregar referencias faltantes al BL durante la edición (Priority: P2)

Cuando el BL trae varias referencias pero alguna no quedó registrada en la importación (o el operador necesita añadir una), el operador puede agregar referencias adicionales al ingreso desde la misma pantalla de edición, sin tener que ir a otro módulo.

**Why this priority**: El usuario menciona "se pueda agregar allí mismo". Completa la capacidad de edición, pero es secundario respecto a ver las referencias y subir imágenes (P1): el caso más común es que las referencias ya existan por la importación.

**Independent Test**: Abrir un ingreso en edición, agregar una nueva referencia con sus datos mínimos, guardar y verificar que queda asociada al BL del ingreso.

**Acceptance Scenarios**:

1. **Given** un ingreso en edición con referencias existentes, **When** el operador agrega una nueva referencia con los datos requeridos y guarda, **Then** la referencia queda asociada al mismo BL/ingreso y aparece junto a las demás.
2. **Given** el operador intenta agregar una referencia con datos incompletos, **When** guarda, **Then** el sistema indica qué datos faltan y no guarda la referencia inválida.

---

### Edge Cases

- **Ingreso sin contenedores ni referencias** (p. ej. un placeholder vacío de la importación): la pantalla debe mostrar el bloque de referencias vacío con un mensaje claro ("sin referencias") y permitir igualmente confirmar el BL y subir imágenes.
- **BL con múltiples contenedores**: las referencias pueden estar distribuidas en varios contenedores del mismo BL; todas deben mostrarse agrupadas bajo el ingreso.
- **Guardar con errores de validación**: si falla la validación del BL/Cliente/Fecha o de una imagen/referencia, no se deben perder los demás datos ya cargados en el formulario.
- **Permisos**: solo los roles autorizados para editar ingresos pueden ver y usar estas nuevas capacidades.
- **Imágenes grandes o muchas a la vez**: el sistema debe comportarse de forma predecible (límite de tamaño/cantidad) y avisar si se excede.
- **Confirmación del BL**: al guardar un BL real sobre uno provisional, el ingreso deja de marcarse como "por confirmar" aunque en la misma operación se hayan agregado imágenes o referencias.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: La pantalla de edición de un ingreso MUST mostrar, además de los campos editables actuales (BL, Cliente, Fecha de ingreso), la lista de todas las referencias asociadas a ese ingreso/BL (incluyendo las distribuidas en varios contenedores del mismo BL).
- **FR-002**: Para cada referencia listada, el sistema MUST mostrar sus datos clave identificables por el operador (al menos: identificador/código de la referencia, producto/descripción, cantidad y, si aplica, contenedor al que pertenece).
- **FR-003**: El operador MUST poder confirmar/editar el BL real, el Cliente y la Fecha de ingreso desde esta misma pantalla, conservando las referencias e imágenes asociadas.
- **FR-004**: Al guardar un BL real sobre un ingreso con BL provisional, el sistema MUST marcar el ingreso como confirmado (dejar de señalarlo como "por confirmar").
- **FR-005**: El operador MUST poder adjuntar una o varias imágenes al ingreso desde la pantalla de edición.
- **FR-006**: La pantalla de edición MUST mostrar las imágenes ya asociadas al ingreso, y al agregar nuevas imágenes el sistema MUST conservar las existentes (no reemplazarlas).
- **FR-007**: El sistema MUST validar los archivos de imagen (tipo de archivo permitido y tamaño máximo) y rechazar con un mensaje claro los que no cumplan, sin descartar el resto de los datos del formulario.
- **FR-008**: El operador MUST poder agregar nuevas referencias al ingreso/BL desde la pantalla de edición, asociándolas al mismo BL.
- **FR-009**: El sistema MUST validar los datos mínimos de una referencia nueva e impedir guardar referencias incompletas, indicando qué datos faltan.
- **FR-010**: Si la validación falla en cualquier parte (BL/Cliente/Fecha, imágenes o referencias), el sistema MUST conservar los datos ya ingresados por el operador y mostrar los errores correspondientes.
- **FR-011**: Solo los usuarios con permiso para editar ingresos MUST poder ver y utilizar las capacidades de visualización de referencias, carga de imágenes y adición de referencias.
- **FR-012**: Cuando un ingreso no tenga referencias asociadas, el sistema MUST mostrar el bloque de referencias vacío con un mensaje claro y permitir igualmente confirmar el BL y subir imágenes.
- **FR-013**: Esta iteración se limita a **ver** referencias e imágenes existentes y a **agregar** nuevas; editar o eliminar referencias e imágenes ya existentes desde esta pantalla está **fuera de alcance** (se completan en otros módulos si hiciera falta).
- **FR-014**: Las imágenes se adjuntan al **BL/ingreso completo** (una única galería de imágenes por ingreso); no se manejan imágenes por referencia individual en esta iteración.

### Key Entities *(include if feature involves data)*

- **Ingreso**: registro de entrada de mercancía identificado por un BL; puede estar marcado como "BL por confirmar" cuando proviene de importación. Tiene un Cliente y una Fecha de ingreso. Agrupa uno o más contenedores y puede tener imágenes asociadas.
- **Referencia**: unidad de mercancía (código/identificador, producto/descripción, cantidad) que pertenece a un contenedor y, por tanto, al BL/ingreso. Un mismo BL puede tener varias referencias.
- **Contenedor**: agrupa referencias dentro de un ingreso; un BL/ingreso puede tener uno o varios contenedores.
- **Imagen/Foto**: archivo gráfico (documento del BL, soporte de mercancía) asociado al ingreso/BL completo (una galería por ingreso).

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: El 100% de los ingresos creados por importación con BL provisional pueden completarse (BL real + imágenes) sin salir de la pantalla de edición.
- **SC-002**: Al abrir un ingreso en edición, el operador ve todas las referencias asociadas a ese BL en la misma pantalla, sin navegar a otro módulo.
- **SC-003**: Un operador puede confirmar el BL y adjuntar imágenes de un ingreso importado en menos de 2 minutos por ingreso.
- **SC-004**: Reducción del retrabajo: ningún ingreso importado queda con BL provisional ni sin imágenes por falta de un lugar donde completarlos, eliminando la necesidad de usar módulos separados para ver referencias o cargar fotos.
- **SC-005**: En el 100% de los intentos con datos inválidos (imagen no permitida o referencia incompleta), el sistema lo informa y conserva el resto de la información ya ingresada.

## Assumptions

- Las referencias de un BL ya existen en el sistema tras la importación (creadas bajo los contenedores del ingreso); el caso principal es **verlas** durante la edición, y agregar nuevas es secundario.
- El mecanismo de carga de imágenes reutiliza el enfoque ya usado en otros módulos del sistema (p. ej. el flujo de vaciado), incluyendo los tipos de archivo y límites de tamaño existentes, salvo que se indique lo contrario.
- "BL provisional" corresponde a ingresos importados marcados como "por confirmar"; confirmar implica reemplazar el BL provisional por el real.
- Los permisos de edición de ingresos ya existentes (RBAC) gobiernan el acceso a estas nuevas capacidades.
- Esta iteración se centra en **ver** referencias y **agregar** imágenes/referencias; editar o eliminar referencias/imágenes existentes queda fuera de alcance (FR-013).
- Las imágenes se gestionan a nivel de ingreso/BL completo, no por referencia (FR-014).
