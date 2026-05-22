# Feature Specification: Importación de Inventario Histórico desde Excel

**Feature Branch**: `002-import-excel-inventory`
**Created**: 2026-05-21
**Status**: Draft
**Input**: User description: "necesito que me ayudes con un excel que tengo y validar si puedo ingresar esa información al sistema ya que es data real y quisiera ver como se comporta se encuentra en mi carpeta de descargas y se llama INVENTARIO TOTAL CONTROLCARGA 27022026"

## Contexto del archivo origen

El archivo `INVENTARIO TOTAL CONTROLCARGA 27022026.xlsx` (Descargas del usuario) contiene la operación real del patio al corte del 27/02/2026:

- **23 hojas en total**, 22 con datos (≈ 20.870 filas) y 1 hoja vacía (`Hoja1`).
- Cada hoja con datos representa un **cliente** (ej. ADUVIDRIOS 116 SAS, CORVIT MEDELLIN SAS, FACTORY GLASS SAS, PULIDOS Y BISELADOS SAS, VIDRIOS J&P SAS, etc.).
- Existe al menos una hoja duplicada manual (`Copia de LADRILLOS Y TUBOS DEL …`) que debe tratarse como ruido.
- Cada fila representa una **línea de mercancía dentro de un contenedor**, con columnas: fecha del documento, ubicación/módulo+bloque, cliente, mercancía, número de referencia, detalle (dimensiones/calibres/láminas), observación, unidades, número de contenedor, fecha de depósito, y una **secuencia variable de pares (FECHA DE DESPACHO, DESPACHO)** que representan eventos históricos de despacho, terminando en una columna de **INVENTARIO** o **Inventario físico** con el saldo actual.
- Los encabezados **varían entre hojas** (algunas tienen columna `Mercancia`, otras no; algunas tienen 3 pares de despacho, otras 6; algunas tienen columna en blanco al inicio).
- Hay heterogeneidad de fechas (`9/4/2026`, `13/02/2026`, `15-03-2026`) y de notación de ubicación (`Modulo6 Bloque B`, `Modulo 2 Bloque A`, `Modulo 3-Bloque C`).

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Diagnóstico de compatibilidad del Excel antes de importar (Priority: P1)

El usuario (rol administrador/coordinador) sube el archivo Excel real al sistema y recibe un **reporte de diagnóstico** que indica, sin escribir nada en la base de datos: cuántas filas son importables tal cual, cuántas tienen problemas y de qué tipo, y un resumen consolidado por cliente y por contenedor. El reporte le permite decidir si el archivo está listo para importar o si hay que corregirlo en origen primero.

**Why this priority**: Es exactamente lo que el usuario pidió ("validar si puedo ingresar esa información ... ver cómo se comporta"). Sin este paso, cualquier carga directa podría dejar el inventario en un estado inconsistente que es muy costoso de revertir cuando hablamos de ≈20.870 filas reales de operación.

**Independent Test**: Cargar el archivo `INVENTARIO TOTAL CONTROLCARGA 27022026.xlsx` y verificar que el sistema devuelve, sin persistir cambios, un reporte con: total de filas leídas, total importables, total con errores agrupadas por tipo de error (cliente no encontrado, fecha inválida, contenedor faltante, etc.), distribución por hoja/cliente, y conteo de contenedores y referencias únicas detectadas.

**Acceptance Scenarios**:

1. **Given** el archivo Excel original sin modificar, **When** el administrador lo sube en modo "Validar", **Then** el sistema muestra un reporte con conteos por hoja, lista de hojas ignoradas (vacías o duplicadas), filas importables y filas con error clasificado por tipo, **sin** crear ni modificar registros en la base de datos.
2. **Given** una hoja con encabezado distinto al estándar (ej. `FACTORY GLASS SAS` con columna en blanco al inicio o sin la columna `Mercancia`), **When** se valida, **Then** el sistema reconoce los campos por nombre de encabezado (no por posición) y reporta cualquier columna obligatoria que falte.
3. **Given** filas con fecha de depósito vacía o número de contenedor vacío, **When** se valida, **Then** esas filas se marcan como no importables con razón específica y el resto sí se contabiliza como importable.
4. **Given** un cliente cuyo nombre en la hoja no coincide exactamente con un usuario `cliente` existente, **When** se valida, **Then** el sistema reporta los clientes "no encontrados" en una sección dedicada, sugiriendo coincidencias aproximadas si existen.
5. **Given** filas con valores en pares de despacho histórico, **When** se valida, **Then** el reporte cuenta cuántos eventos históricos de despacho se detectaron por hoja y advierte si la suma `unidades − ΣDespacho` no coincide con `Inventario físico` (inconsistencia interna del Excel).

---

### User Story 2 - Importación efectiva del inventario actual (Priority: P2)

Una vez el usuario revisa el reporte de diagnóstico y decide proceder, ejecuta la importación, que crea en la base de datos los registros necesarios (cliente, ubicación, contenedor, referencia) para reflejar el inventario actual del patio al 27/02/2026, preservando trazabilidad de la fuente.

**Why this priority**: Es el objetivo final del usuario, pero depende de que P1 confirme que el archivo está limpio. Importar primero sin diagnóstico arriesga corromper la operación real.

**Independent Test**: Tras un diagnóstico sin errores, ejecutar la importación y verificar que la cantidad de contenedores, referencias y saldos en el sistema coincide con los conteos del reporte; que cada referencia importada queda enlazada a su cliente, contenedor y ubicación de patio; y que se puede consultar el inventario en la pantalla existente filtrando por cliente.

**Acceptance Scenarios**:

1. **Given** un reporte de diagnóstico con 0 filas en error, **When** el administrador confirma la importación, **Then** todas las filas importables quedan persistidas como entidades de inventario y el sistema muestra un resumen "X contenedores, Y referencias, Z unidades importadas" coincidente con el reporte previo.
2. **Given** un reporte de diagnóstico con filas en error, **When** el administrador confirma, **Then** las filas válidas se importan y las filas en error se exportan a un archivo descargable para corrección, sin abortar el proceso completo.
3. **Given** una importación ejecutada, **When** se intenta volver a importar el mismo archivo, **Then** el sistema detecta los contenedores ya cargados (por número de contenedor + cliente) y los reporta como duplicados, dando opción de omitirlos o actualizar el saldo.
4. **Given** una fila con módulo "Modulo 3-Bloque C", **When** se importa, **Then** el sistema crea (o reutiliza) la ubicación de patio correspondiente y la referencia queda asociada a ella, normalizando la notación.
5. **Given** una importación completada, **When** ocurre un fallo a mitad del proceso, **Then** el sistema deja la base de datos en el estado previo a la importación (operación atómica) o, si la atomicidad completa no es viable por volumen, deja un punto de recuperación claro y un log de qué se importó.

---

### User Story 3 - Importación del historial completo de despachos en la misma operación (Priority: P2)

La misma importación de la User Story 2 persiste también los **eventos históricos de despacho** (pares FECHA DE DESPACHO, DESPACHO de cada fila) como órdenes de cargue, tarjas y detalles retroactivos, dejando los campos faltantes marcados como `PENDIENTE_HISTORICO` para completarse al consultar.

**Why this priority**: El usuario eligió que la primera importación incluya saldo actual + historial completo en una sola operación (Q3 resuelto = B). El completado de campos faltantes (despachador, vehículo, etc.) se hace de forma progresiva cuando un usuario abre el registro, igual que con los contenedores históricos.

**Independent Test**: Tras la importación, verificar que: (a) cada par (fecha, cantidad) histórico aparece como Tarja+TarjaDetalle retroactiva enlazada a la referencia correcta; (b) los saldos de referencia coinciden con `Inventario físico` del Excel (no recalculados); (c) al abrir una tarja retroactiva se muestra el formulario de completado de campos `PENDIENTE_HISTORICO`.

**Acceptance Scenarios**:

1. **Given** una fila importada con 3 pares de despacho rellenos, **When** la importación termina, **Then** existen 3 Tarjas retroactivas con sus TarjaDetalle apuntando a la Referencia correcta, con la fecha del Excel y campos faltantes marcados `PENDIENTE_HISTORICO`.
2. **Given** un par de despacho con cantidad pero sin fecha (o viceversa), **When** se importa, **Then** ese par se omite y se reporta como "evento histórico inconsistente" en el reporte final.
3. **Given** una tarja retroactiva recién importada, **When** un despachador la abre, **Then** el sistema le pide diligenciar despachador, vehículo, conductor y observaciones antes de cerrar/imprimir.
4. **Given** un usuario consulta el inventario, **When** lee el saldo de una referencia importada, **Then** el saldo mostrado es exactamente el `Inventario físico` del Excel (no recalculado).

---

### Edge Cases

- **Hojas duplicadas o de respaldo manual** (ej. `Copia de LADRILLOS Y TUBOS DEL …`): se detectan por nombre y se ignoran por defecto, listándose en el reporte para que el usuario confirme.
- **Hoja vacía** (`Hoja1`): se ignora automáticamente.
- **Encabezado en una columna distinta** (ej. la primera columna en blanco): el sistema busca encabezados por nombre dentro de las primeras filas, no por índice fijo.
- **Fechas en formato inconsistente** (`9/4/2026`, `13/02/2026`, `15-03-2026`, `2/05/2026`): el parser intenta múltiples formatos D/M/Y y reporta los que no logra interpretar.
- **Número de contenedor con espacios o caracteres atípicos**: se normaliza a mayúsculas sin espacios para detectar duplicados.
- **Mismo número de contenedor en hojas de clientes distintos**: se reporta como conflicto a resolver, no se importa silenciosamente.
- **Cantidad `Inventario físico = 0`**: la línea se importa como histórica con saldo cero (puede ser útil para trazabilidad de despachos pasados).
- **Símbolos en celdas numéricas** (`#` como cantidad observado en `VIDRIOS J&P SAS`): se marca como error de tipo en lugar de interpretarse como cero.
- **Cliente que aparece en sheet name pero no como usuario en el sistema**: se reporta en sección "Clientes a auto-crear" antes de importar, mostrando el email placeholder que recibirá; al confirmar la importación el sistema los crea con password genérica y los marca para forzar cambio de password + actualización de email en su primer login (FR-024 a FR-026).
- **Archivos > 50 MB o > 50.000 filas**: el sistema debe procesarlos de forma asíncrona (no bloquear la UI) y notificar al finalizar.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: El sistema DEBE permitir a usuarios con rol `administrador` o `coordinador` subir un archivo Excel (`.xlsx`) de inventario desde una pantalla dedicada.
- **FR-002**: El sistema DEBE ofrecer dos modos: **Validar (dry-run)** que no escribe en la base de datos, e **Importar** que sí persiste.
- **FR-003**: El sistema DEBE identificar dinámicamente cada hoja del Excel, contar sus filas de datos e ignorar hojas vacías y hojas marcadas como copia/respaldo, listándolas explícitamente en el reporte.
- **FR-004**: El sistema DEBE detectar los encabezados de cada hoja por **nombre de columna** (no por índice), tolerando: presencia/ausencia de columna `Mercancia`, variantes de capitalización, columnas en blanco al inicio y entre 2 y 8 pares de columnas `FECHA DE DESPACHO` / `DESPACHO`.
- **FR-005**: El sistema DEBE mapear cada fila importable a las entidades del modelo existente: cliente (User con rol cliente), contenedor, ubicación de patio (módulo + bloque), referencia (con descripción derivada de Mercancía + #Referencia + Detalle), cantidad inicial, cantidad actual y fecha de ingreso.
- **FR-006**: El sistema DEBE normalizar la notación de ubicación (`Modulo6 Bloque B`, `Modulo 2 Bloque A`, `Modulo 3-Bloque C`) a una representación canónica `módulo + posición` antes de buscar o crear la ubicación de patio.
- **FR-007**: El sistema DEBE normalizar el número de contenedor a mayúsculas y sin espacios antes de buscar duplicados.
- **FR-008**: El sistema DEBE detectar duplicados de contenedor (mismo número + mismo cliente) entre el Excel y los datos ya cargados, y reportarlos sin importar silenciosamente.
- **FR-009**: El sistema DEBE detectar y reportar como **conflicto** los casos donde un mismo número de contenedor aparece en hojas de clientes distintos.
- **FR-010**: El sistema DEBE intentar parsear fechas en al menos los formatos `d/M/yyyy`, `dd/MM/yyyy`, `d-M-yyyy`, `dd-MM-yyyy` (con día primero, convención colombiana) y reportar las celdas que no logre interpretar.
- **FR-011**: El sistema DEBE producir un **reporte de validación** con, como mínimo: total de filas leídas, hojas procesadas vs. ignoradas, filas importables, filas con error agrupadas por tipo de error, contenedores y referencias únicos detectados, conteo de unidades totales, y un listado de clientes no resueltos contra la tabla de usuarios.
- **FR-012**: El sistema DEBE permitir descargar el reporte de validación en formato Excel y/o PDF para revisión offline.
- **FR-013**: El sistema DEBE permitir descargar un archivo separado con **solo las filas en error** y la razón de cada error, en el mismo formato del Excel original, para que el usuario corrija en origen y reintente.
- **FR-014**: El sistema DEBE registrar quién ejecuta la importación, cuándo, con qué archivo (nombre + hash), y un resumen del resultado (X importadas, Y rechazadas), conservable como auditoría.
- **FR-015**: El sistema DEBE procesar la validación y la importación de archivos con ≥20.000 filas sin bloquear la sesión del usuario (procesamiento asíncrono con notificación al terminar).
- **FR-016**: El sistema DEBE verificar inconsistencias internas del Excel y reportarlas como advertencia (no error bloqueante): cuando `Σ Despachos ≠ Unidades − Inventario físico` por fila.
- **FR-017**: El sistema DEBE importar los pares (FECHA DE DESPACHO, DESPACHO) como eventos históricos de salida vinculados a la referencia, sin volver a descontar saldo (porque el saldo importado ya los refleja). Detalle del comportamiento en FR-028–FR-031.
- **FR-018**: El sistema DEBE permitir reintentar la importación del mismo archivo y detectar contenedores y referencias ya cargados, dando opción de **omitir**, **actualizar saldo** o **abortar**.
- **FR-019**: El sistema DEBE preservar trazabilidad del origen: cada referencia importada DEBE quedar marcada como proveniente de "carga histórica 27/02/2026" para distinguirla de registros creados por el flujo operativo normal.

#### Registros padre sintéticos para contenedores históricos (Q1 resuelto)

- **FR-020**: El sistema DEBE crear, por cada contenedor histórico importado, una `Solicitud` y una `OrdenServicio` sintéticas que sirvan como padres mínimos para satisfacer el modelo. Los campos obligatorios del modelo (placa_vehiculo, conductor, cita_puerto, vehiculo, etc.) se rellenan con un marcador `PENDIENTE_HISTORICO`.
- **FR-021**: Cada `Solicitud`, `OrdenServicio` y `Contenedor` creado por la importación DEBE marcarse con un flag `origen = importacion_historica_27_02_2026` (o equivalente persistente) que permita identificarlo posteriormente.
- **FR-022**: Cuando un usuario abra (vista de detalle) un registro marcado como `importacion_historica_*` que contenga campos con valor `PENDIENTE_HISTORICO`, el sistema DEBE mostrarle un formulario de **completado progresivo** solicitando esos datos faltantes, antes de permitirle continuar con cualquier acción operativa sobre el registro (programar vaciado, gate out, etc.).
- **FR-023**: El sistema DEBE listar en una pantalla/panel de "Pendientes de completar" todos los registros importados con campos `PENDIENTE_HISTORICO`, para que coordinador/administrador pueda procesarlos en lote.

#### Auto-creación de clientes con password genérica (Q2 resuelto)

- **FR-024**: Cuando una hoja del Excel referencia un cliente que no existe como `User` con rol `cliente`, el sistema DEBE crear automáticamente ese usuario con: `name` = nombre del cliente tal como aparece en la hoja, `email` = placeholder derivado del nombre (slug + dominio del sistema), `password` = contraseña genérica preconfigurada, rol `cliente`.
- **FR-025**: Todo usuario cliente auto-creado por la importación DEBE quedar marcado con un flag `requiere_cambio_password = true` (o equivalente) de modo que, al iniciar sesión por primera vez, el sistema lo redirija obligatoriamente a una pantalla de cambio de contraseña antes de permitir acceso al resto de la aplicación.
- **FR-026**: Todo usuario cliente auto-creado DEBE marcarse también con `email_placeholder = true` (o equivalente), de modo que el sistema le pida actualizar su email real durante (o inmediatamente después de) el cambio de contraseña.
- **FR-027**: El reporte de validación DEBE listar explícitamente qué clientes serán auto-creados (con el email placeholder que recibirán) antes de la importación, para que el administrador los revise.

#### Importación completa incluyendo historial de despachos (Q3 resuelto)

- **FR-028**: La importación DEBE procesar en una sola ejecución: (a) el saldo actual de cada referencia (`Inventario físico`), y (b) el historial completo de despachos contenido en los pares `(FECHA DE DESPACHO, DESPACHO)` de cada fila.
- **FR-029**: Cada par `(fecha, cantidad)` histórico DEBE persistirse como una `OrdenCargue` + `Tarja` + `TarjaDetalle` retroactiva, con la fecha indicada como `fecha_despacho` y los campos faltantes (despachador_id, vehículo, conductor, observaciones) marcados como `PENDIENTE_HISTORICO` siguiendo el mismo patrón de FR-022.
- **FR-030**: El cálculo de `cantidad_actual` de cada Referencia DEBE basarse directamente en el valor `Inventario físico` del Excel — **no** se debe recalcular como `cantidad_inicial − Σ despachos` (porque puede haber inconsistencias en el origen). Las inconsistencias detectadas se reportan como advertencia (FR-016) pero no alteran el saldo persistido.
- **FR-031**: Cuando un usuario abra una `OrdenCargue` / `Tarja` retroactiva con campos `PENDIENTE_HISTORICO`, aplica el mismo formulario de completado progresivo de FR-022.

### Key Entities

- **Archivo de importación**: el `.xlsx` cargado por el usuario; se identifica por nombre, hash de contenido, fecha de carga y usuario que lo cargó.
- **Hoja de cliente**: cada pestaña del Excel que representa el inventario de un cliente específico; se mapea a un usuario con rol cliente.
- **Línea de inventario importada**: cada fila con datos; corresponde a una **Referencia** del modelo, asociada a un contenedor, una ubicación de patio y un cliente.
- **Evento histórico de despacho**: cada par (fecha, cantidad) presente dentro de una fila; corresponde retroactivamente a un movimiento de salida sobre la referencia.
- **Reporte de validación**: resultado del modo dry-run; contiene conteos, agrupaciones, listas de hojas ignoradas, clientes no resueltos, conflictos de contenedor y filas en error con motivo.
- **Registro de auditoría de importación**: quién importó, cuándo, con qué archivo (hash), y resumen del resultado; persistente para trazabilidad operativa.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: El usuario puede subir el archivo `INVENTARIO TOTAL CONTROLCARGA 27022026.xlsx` (≈ 20.870 filas, 22 hojas con datos) y recibir el reporte de validación en menos de **3 minutos**, sin que la sesión web se bloquee.
- **SC-002**: El reporte de validación clasifica el **100 %** de las filas en una de tres categorías: importable, con error (con motivo específico) o ignorada (con razón).
- **SC-003**: Tras una importación exitosa, el conteo de contenedores, referencias y unidades en el sistema coincide exactamente con el conteo "importables" del reporte previo (varianza 0).
- **SC-004**: Reintentar la importación del mismo archivo no crea duplicados: el sistema detecta el **100 %** de los contenedores ya cargados.
- **SC-005**: Las filas con datos defectuosos se exportan a un archivo de errores que el usuario puede corregir y reimportar, con al menos **un campo de "motivo" por fila** indicando qué hay que arreglar.
- **SC-006**: El administrador puede demostrar la trazabilidad de cualquier referencia importada hasta el archivo origen y la fila específica del Excel.
- **SC-007**: La importación es **reversible o atómica**: si falla a mitad, el inventario del sistema queda en un estado consistente y documentado (sin filas a medio importar invisibles para el operador).
- **SC-008**: El **100 %** de los registros importados con campos `PENDIENTE_HISTORICO` son visibles en una pantalla de "Pendientes de completar" y bloquean acciones operativas hasta ser diligenciados.
- **SC-009**: El **100 %** de los clientes auto-creados son forzados a cambiar password y actualizar email en su primer login antes de acceder a cualquier otra pantalla del sistema.

## Assumptions

- El usuario tiene acceso al archivo en su carpeta de Descargas y lo subirá mediante la UI del sistema (no se asume integración directa con su sistema de archivos local).
- La operación se ejecuta sobre la base de datos de producción del cliente, por lo que la fase de validación previa es indispensable.
- Las fechas en el Excel siguen la convención colombiana (día primero).
- Las 22 hojas con datos corresponden a 22 clientes reales del negocio, aunque algunas puedan no existir aún como usuarios `cliente` en el sistema.
- El número de contenedor (ej. `MRKU9517467`) es el identificador natural para detectar duplicados en combinación con el cliente.
- La columna `Inventario físico` (o `INVENTARIO`) refleja el saldo correcto a la fecha de corte 27/02/2026; los pares de despacho previos ya están descontados en ese saldo.
- La hoja `Copia de LADRILLOS Y TUBOS DEL …` es un respaldo manual del usuario y no debe importarse (se confirma en la validación).
