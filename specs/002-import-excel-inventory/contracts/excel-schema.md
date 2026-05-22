# Excel Source Schema Contract

**Date**: 2026-05-21
**Plan**: [../plan.md](../plan.md)
**Reference file**: `INVENTARIO TOTAL CONTROLCARGA 27022026.xlsx` (≈ 20.870 filas, 23 hojas)

Este documento es el **contrato leído por `ExcelHeaderResolver`**: define qué columnas reconoce el sistema, qué aliases acepta para cada una, y qué se considera obligatorio para que una fila sea importable.

---

## Hojas

| Categoría | Cómo se identifica | Acción |
|---|---|---|
| Hoja de cliente | Cualquier pestaña que **no** sea `Hoja1` y que no comience con `Copia de` | Se procesa |
| Hoja vacía | `max_row ≤ 1` o sin celdas con datos | Se ignora, se registra `HOJA_VACIA` |
| Hoja de respaldo manual | Nombre comienza con `Copia de` (case-insensitive) | Se ignora por defecto, se registra `HOJA_COPIA` |
| Hoja duplicada por contenido | Misma cantidad de filas y mismos primeros 10 contenedores que otra hoja ya procesada | Se ignora, se registra `HOJA_DUPLICADA` |

El usuario puede futuro-extender mediante un checkbox "incluir hojas de respaldo" en el formulario; por defecto desactivado.

---

## Columnas canónicas y aliases

El resolver normaliza cada celda del header: `mb_strtolower`, `trim`, colapso de espacios internos a uno solo. La comparación es contra la **lista de aliases** de cada columna canónica.

| Columna canónica | Obligatoria | Aliases aceptados (normalizados) |
|---|---|---|
| `fecha_documento` | No | `fecha documentos`, `fecha documento`, `fecha` |
| `ubicacion` | No (se intenta deducir de otras filas si falta) | `ubicación`, `ubicacion`, `modulo`, `módulo` |
| `cliente` | Sí | `cliente` |
| `mercancia` | No | `mercancia`, `mercancía` |
| `referencia` | Sí | `#referencia`, `referencia`, `ref` |
| `detalle` | No | `detalle`, `detalles` |
| `observacion` | No | `observación`, `observacion`, `observaciones` |
| `unidad` | Sí (cantidad inicial) | `unidad`, `unidades`, `cantidad` |
| `contenedor` | Sí | `contenedor`, `#contenedor`, `nro contenedor`, `número contenedor` |
| `fecha_deposito` | Sí | `fecha deposito`, `fecha depósito`, `fecha de depósito`, `fecha` (si no se usó arriba) |
| `inventario_fisico` | Sí | `inventario fisico`, `inventario físico`, `inventario`, `saldo`, `saldo actual` |

Los pares de despacho histórico se detectan recorriendo las columnas restantes: se buscan parejas consecutivas de columnas donde la primera matchee `fecha de despacho` / `fecha despacho` y la segunda matchee `despacho`. Se aceptan entre 0 y 8 pares por hoja.

**Tolerancias adicionales**:
- Columnas en blanco al inicio o entre columnas se ignoran silenciosamente.
- Columnas con encabezado no reconocido se reportan en el `resumen` del batch como "columnas no usadas: [...]" — solo como nota, no es error.
- Si una columna obligatoria falta en la hoja, **todas** las filas de esa hoja se marcan como `HOJA_SIN_COLUMNAS_REQUERIDAS` y la hoja se reporta entera como rechazada (no se intenta importar nada de ella).

---

## Reglas por fila importable

Una fila es `importable` si y solo si cumple **todas** estas reglas:

| Regla | Validación | Si falla |
|---|---|---|
| Cliente no vacío | `trim(cliente) !== ''` | `error.tipo = CLIENTE_NO_RESUELTO` (subcaso "vacío") |
| Contenedor no vacío | `trim(contenedor) !== ''` | `error.tipo = CONTENEDOR_FALTANTE` |
| Fecha de depósito parseable | `DateParser::parse(...)` no nulo | `error.tipo = FECHA_INVALIDA` |
| Unidad numérica > 0 | `is_numeric() && (int) > 0` | `error.tipo = CANTIDAD_INVALIDA` |
| Inventario físico numérico ≥ 0 | `is_numeric() && (int) >= 0` | `error.tipo = CANTIDAD_INVALIDA` (subcaso "saldo") |

Reglas que NO impiden la importación pero generan advertencia:

| Regla | Si se incumple |
|---|---|
| `unidad − Σ despachos = inventario_fisico` | `advertencia.tipo = SALDO_INCONSISTENTE` |
| Cada par de despacho tiene fecha + cantidad | El par incompleto se omite y se registra `advertencia.tipo = DESPACHO_INCOMPLETO`. La fila igual se importa con los pares válidos |

---

## Normalizaciones aplicadas al mapear a entidades

| Origen Excel | Destino sistema | Transformación |
|---|---|---|
| `contenedor` (`MRKU 9517467 `) | `Contenedor.numero` | `mb_strtoupper(preg_replace('/\s+/', '', $v))` |
| `ubicacion` (`Modulo 3-Bloque C`) | `UbicacionPatio.modulo` + `posicion` | Regex `/m[oó]dulo\s*([\w-]+)[\s-]+bloque\s*(\w+)/i` ⇒ `modulo='3'`, `posicion='C'`. Si no matchea, se guarda el texto entero como `modulo` y `posicion='S/N'`, registrando `advertencia.tipo = UBICACION_NO_NORMALIZADA` |
| `cliente` (`ADUVIDRIOS 116 SAS`) | `User.name` | Se respeta tal cual (con trim) |
| Email placeholder de cliente auto-creado | `User.email` | `Str::slug($name) . '@cargo-express.placeholder'` |
| `mercancia` + `#referencia` + `detalle` | `Referencia.descripcion` | `trim(mercancia . ' / ' . referencia . ' / ' . detalle)` colapsando separadores vacíos |
| `#referencia` | `Referencia.codigo` | Tal cual (trim) |
| `unidad` | `Referencia.cantidad_inicial` | (int) |
| `inventario_fisico` | `Referencia.cantidad_actual` | (int), **sin recálculo** (FR-030) |
| `fecha_deposito` | `Referencia.fecha_ingreso` | `DateParser::parse(...)` |
| Par (`fecha_despacho_N`, `despacho_N`) válido | 1 OrdenCargue + 1 Tarja + 1 TarjaDetalle retroactivas | `fecha_despacho = parse(...)`, `cantidad_entregada = (int)`. La Tarja y la OrdenCargue se crean con campos `PENDIENTE_HISTORICO`: `despachador_id, vehiculo, conductor, observaciones` |

---

## Campos pendientes generados por la importación

Cuando se crea un registro padre sintético, se enlaza al `ImportPendingRecord` con los siguientes `campos_pendientes`:

| Entidad creada | `campos_pendientes` registrados |
|---|---|
| Solicitud sintética por contenedor | `["naviera","puerto_origen","descripcion"]` |
| OrdenServicio sintética | `["vehiculo","conductor","conductor_documento","cita_puerto"]` |
| Contenedor (campos no provistos por Excel) | `["placa_vehiculo","tipo","destino_salida"]` |
| Tarja retroactiva por par de despacho | `["despachador_id","vehiculo","conductor","observaciones"]` |
| OrdenCargue retroactiva | `["despachador_id","notas"]` |
| User cliente auto-creado | (manejado vía middleware `primer_login`, no por la cola de pendientes) |
