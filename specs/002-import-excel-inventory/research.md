# Phase 0 Research — Importación de Inventario Histórico desde Excel

**Date**: 2026-05-21
**Plan**: [plan.md](./plan.md)
**Spec**: [spec.md](./spec.md)

Esta fase resuelve los `NEEDS CLARIFICATION` técnicos derivados del Technical Context y las preguntas de diseño que el plan deja explícitas. No quedan marcadores abiertos al cerrar este documento.

---

## R1. Lectura eficiente de un Excel multihoja de ≈ 20.870 filas

**Decision**: Usar **Maatwebsite/Excel 3.1** con `WithMultipleSheets` en el import raíz y `WithHeadingRow` + `WithChunkReading` (chunk = 500 filas) en cada hoja-cliente. Cada hoja procesa sus chunks dentro de una `DB::transaction()` propia.

**Rationale**:
- Maatwebsite/Excel ya está instalado (`composer.json`) — Principio II.
- `WithChunkReading` activa el modo `PhpSpreadsheet` "cell caching" lo que mantiene el uso de memoria acotado (~ 100 MB para todo el archivo en pruebas comunitarias con archivos de tamaño similar), evitando subir `memory_limit` o `max_execution_time` del PHP del cliente.
- Una transacción por **hoja** (no por chunk ni por archivo completo) es el balance correcto: si una hoja falla la rollback no destruye lo ya importado de otras hojas, y un fallo a mitad de hoja deja la hoja entera fuera (no a medio importar) — apoya SC-007.
- Chunk de 500 fue elegido sobre 1.000 o 2.000 por dejar margen al `database` queue driver (cada chunk genera puntos de progreso reportables).

**Alternatives considered**:
- `PhpSpreadsheet` directo sin Maatwebsite — más control, más código boilerplate, viola Principio V (KISS).
- `box/spout` (lectura streaming) — más rápido y de menor huella de memoria, pero introduce dependencia nueva. Rechazado por Principio II.
- Procesar todo el archivo en una sola transacción — un solo fallo aborta 20K filas, daña SC-007 sin ganar nada.
- Un job por hoja (23 jobs) — más complejo de coordinar para reportar resultado consolidado al usuario. Rechazado por Principio V.

---

## R2. Modo dry-run sin tocar la base de datos

**Decision**: El servicio orquestador `InventarioImportService` recibe un enum `ImportMode { VALIDAR, IMPORTAR }`. En modo `VALIDAR` los resolvers (Cliente, Ubicación, Contenedor) operan en una **caché en memoria** (`Collection` por tipo) que simula la búsqueda y la creación sin tocar la BD; solo se persisten registros en `import_batches` y `import_row_results` (auditoría del dry-run). En modo `IMPORTAR` los mismos resolvers usan repositorios reales.

**Rationale**:
- Permite reutilizar el 100 % del pipeline de mapeo y validación entre ambos modos (Principio IV/DRY).
- Persistir `import_row_results` incluso en dry-run permite descargar el reporte (FR-012) y reabrirlo después; el flag `dry_run = true` en `import_batches` lo distingue claramente.
- La caché en memoria evita falsos positivos del tipo "cliente X aparece en 3 hojas distintas" (la primera lo crearía y las dos siguientes lo verían existir).

**Alternatives considered**:
- Usar `DB::transaction(fn() => …; throw $rollback;)` con un wrapper que hace rollback al final del dry-run — funciona pero deja IDs autoincrementables consumidos, fuga de basura en tablas auxiliares, y bloquea filas durante minutos. Rechazado.
- Tener un servicio paralelo "Estimator" sin reglas reales — duplica la lógica, viola DRY.

---

## R3. Modelado del estado `PENDIENTE_HISTORICO`

**Decision**: Tabla polimórfica `import_pending_records (id, pendienteable_type, pendienteable_id, import_batch_id, campos_pendientes JSON, completado_at, completado_por_id)`. Cada entidad afectada (Contenedor, OrdenServicio, Solicitud, OrdenCargue, Tarja, User) usa el trait `HasImportPendingFields` que expone `pendingFields(): array` y `completarPendientes(array $data, User $by)`.

Adicionalmente cada tabla afectada recibe la columna `import_batch_id` (FK nullable) que apunta a `import_batches.id`. La presencia de **un registro vivo** (sin `completado_at`) en `import_pending_records` es la fuente de verdad: si no existe, el registro está completo aunque tenga `import_batch_id`.

**Rationale**:
- Una sola tabla concentra la cola de trabajo "pendientes de completar" (FR-023) — basta una query con paginación.
- Permite agregar/cambiar el catálogo de campos pendientes sin migraciones (vive en JSON + en el código).
- El trait centraliza el método de completado — un solo lugar valida que los datos cumplan tipo, dispara evento `RegistroCompletado` y borra/marca el pendiente.

**Alternatives considered**:
- Columna `pending_fields JSON NULL` en cada tabla — disperso, la query "todos los pendientes" se vuelve `UNION` de 6 tablas, mala con paginación. Rechazado por Principio VII (escalabilidad).
- Columna `status ENUM('completo','pendiente')` por tabla — no comunica qué campos faltan, viola Principio I.
- Usar valores sentinela en cada columna (`'PENDIENTE_HISTORICO'` literal en `placa_vehiculo`, etc.) — contamina datos, rompe queries existentes que esperan placas válidas. Rechazado.

---

## R4. Procesamiento asíncrono con Laravel Queue

**Decision**: Job único `ProcesarImportacionInventario` despachado al driver `database` ya configurado por defecto en `config/queue.php`. El job recibe `import_batch_id` y el path del archivo en `storage/app/imports/{batch_id}.xlsx`; al terminar dispara la notification `ImportacionFinalizada` al usuario y emite un evento que la UI puede escuchar vía polling de `/admin/importaciones/{id}`.

**Rationale**:
- `composer dev` ya levanta `php artisan queue:listen` (revisado en `composer.json`), así que la infraestructura de cola está operativa en dev sin pasos extra.
- Un solo job mantiene el control del progreso simple: el job actualiza `import_batches.estado` y campos de progreso al final de cada hoja.
- La notification Laravel ya está estandarizada en la feature 001 (`app/Notifications/`) — Principio II.

**Alternatives considered**:
- Un job por hoja (orquestación con `Bus::chain` o `Bus::batch`) — más complejo, más infraestructura, sin ganancia clara para 22 hojas que un solo job hace en < 3 min. Rechazado por KISS.
- Procesamiento sincrónico para archivos pequeños y asincrónico para grandes — bifurcación innecesaria, viola Principio V.

---

## R5. Parseo de fechas heterogéneas (`9/4/2026`, `13/02/2026`, `15-03-2026`)

**Decision**: Utility `DateParser::parse(string|int $value): ?Carbon`. Intenta en orden: (1) entero numérico Excel → `Date::excelToDateTimeObject` de PhpSpreadsheet, (2) `Carbon::createFromFormat` iterando los formatos `'d/m/Y'`, `'d/m/y'`, `'d-m-Y'`, `'d-m-y'`, `'Y-m-d'`. Si todos fallan, devuelve `null` y `RowValidator` lo clasifica como error tipado `FECHA_INVALIDA`.

**Rationale**:
- Cubre todos los formatos observados en muestras del archivo real (sección "Contexto del archivo origen" del spec).
- Día primero porque la convención colombiana es D/M/Y (Assumption en spec).
- Iteración con corto-circuito al primer match — costo ínfimo dado el chunk size.

**Alternatives considered**:
- `Carbon::parse()` sin formato — falla impredecible en `2/5/2026` (lo interpreta como Y-M-D en algunos locales). Rechazado.
- Heurística por separador (`/` vs `-`) — más reglas, mismo resultado. Innecesaria.

---

## R6. Detección de encabezados con variantes y columna en blanco al inicio

**Decision**: `ExcelHeaderResolver::resolve(array $primeraFila): HeaderMap`. Para cada columna canónica (fecha_documento, ubicacion, cliente, mercancia, referencia, detalle, observacion, unidad, contenedor, fecha_deposito, inventario_fisico) mantiene una lista de aliases (`['fecha documento', 'fecha documentos', 'fecha']` etc.). Normaliza cada celda del header (lowercase, trim, colapso de espacios) y matchea contra los aliases. Identifica los pares `(fecha_despacho_N, despacho_N)` por orden de aparición.

**Rationale**:
- Coincidencia por **alias explícito** en lugar de Levenshtein/fuzzy mantiene el comportamiento predecible y testeable (Principio VI).
- La columna en blanco al inicio (caso `FACTORY GLASS SAS`) se resuelve naturalmente: el resolver no exige índice fijo, simplemente recorre todas las columnas.

**Alternatives considered**:
- Configuración estática por nombre de hoja — falla si el usuario renombra hojas. Rechazado.
- Fuzzy matching (Levenshtein) — abre falsos positivos (`'Cliente'` vs `'Conductor'` distan 3 caracteres). Rechazado por Principio I (predecible > inteligente).

---

## R7. Idempotencia y detección de duplicados al reimportar

**Decision**: La unicidad funcional de un contenedor importado es `(numero_normalizado, cliente_id)`. Al iniciar un import, el servicio carga en memoria un `Set` con esos pares ya existentes en `contenedores` (origen importación) y compara cada fila. El `import_batches` guarda `archivo_hash` (sha256 del archivo subido) para detectar reimportación literal del mismo archivo y avisar al usuario.

Política al detectar duplicado configurada por el usuario en el formulario de subida: `omitir`, `actualizar_saldo`, `abortar`. La opción `actualizar_saldo` actualiza solo `cantidad_actual` de la Referencia y crea un evento de auditoría.

**Rationale**:
- Combinación contenedor+cliente cubre el caso real (un mismo número de contenedor podría aparecer en hojas distintas — FR-009, en ese caso es conflicto y se rechaza).
- Hash del archivo da una capa adicional barata de detección de "ya subiste este mismo archivo".

**Alternatives considered**:
- Solo por hash de archivo — no detecta reimport de un archivo corregido (mismo contenedor, diferente Excel). Rechazado.
- Unique constraint a nivel BD sobre `(numero, cliente_id)` — rompería el flujo operativo normal donde un cliente puede recibir el mismo contenedor en dos viajes distintos. Rechazado.

---

## R8. Forzar cambio de password y email en primer login

**Decision**: Dos columnas booleanas en `users`: `requiere_cambio_password` y `email_placeholder`. Un middleware nuevo `ForzarCambioPasswordYEmail` registrado como alias `primer_login` en `bootstrap/app.php`. Se aplica al grupo de rutas autenticadas; si cualquiera de los dos flags está activo, redirige a `/primer-login/password` o `/primer-login/email` según corresponda y rechaza cualquier otra ruta excepto logout y POST de los formularios. Al actualizar password se setea `password_actualizada_at = now()` y `requiere_cambio_password = false`; al confirmar email se setea `email_placeholder = false`.

**Rationale**:
- Reusa el pipeline middleware estándar de Laravel — Principio II.
- Dos flags independientes permiten validar cada paso por separado y cumplir SC-009.
- `password_actualizada_at` da auditoría para reportes de cumplimiento.

**Alternatives considered**:
- Un solo flag `primer_login = true` que cubra ambos pasos — pierde granularidad si en el futuro se quiere forzar solo uno de los dos. Aceptable pero menos extensible.
- Página intermedia con ambos formularios juntos — peor UX, más validaciones cruzadas. Rechazado por KISS y por SC-009 que pide validar cada uno separadamente.

---

## R9. Inconsistencias internas del Excel (`Σ Despachos ≠ Unidades − Inventario físico`)

**Decision**: Calcular la diferencia por fila durante la validación y registrarla como `ImportRowResult` con estado `ADVERTENCIA` (no `ERROR`), enmarcada en `tipo = SALDO_INCONSISTENTE`. La fila igual se importa, y `cantidad_actual` se persiste **como aparece en `Inventario físico`** (FR-030), no se recalcula. La advertencia queda en el reporte.

**Rationale**:
- El usuario eligió Q3=B (importar saldo + historial); confiar en el saldo del Excel respeta la decisión "el Excel es la fuente de verdad para el saldo actual al corte 27/02/2026".
- Reportar como advertencia (no error) evita bloquear filas que pueden ser correctas con un despacho histórico mal capturado.

**Alternatives considered**:
- Bloquear la fila — pierde inventario real por errores menores de captura. Rechazado.
- Recalcular `cantidad_actual = cantidad_inicial - Σ despachos` — contradice FR-030. Rechazado.

---

## R10. Carga de archivo grande (subida HTTP)

**Decision**: Formulario `multipart/form-data` con límite `50 MB` validado en el FormRequest. El archivo se guarda en `storage/app/imports/{uuid}.xlsx` antes de despachar el job; el job lo lee desde ahí. Configuración requerida en `php.ini` del cliente: `upload_max_filesize = 64M`, `post_max_size = 64M`, `max_execution_time = 60` (basta para subir, el procesamiento es asíncrono). Estos valores se documentan en `quickstart.md` (no se cambian programáticamente).

**Rationale**:
- 50 MB cubre el archivo real (que estará por debajo de 10 MB) con margen para casos futuros.
- Persistir antes de despachar evita perder el archivo si el queue worker no está corriendo cuando se sube.
- Configurar PHP es responsabilidad del entorno, no del código (Principio II).

**Alternatives considered**:
- Subida resumable (tus / chunks) — complejidad innecesaria para 50 MB. Rechazado por KISS.
- Borrar el archivo tras procesar — sí, pero después de éxito; en caso de fallo se conserva para reintento manual.

---

## R11. Tests sobre el archivo real ≈ 20K filas

**Decision**: El test de integración del happy-path **no** usa el archivo real. Se genera con `ImportBatchFactory` + `Excel::fake()` un Excel sintético con 3 hojas y 50 filas que cubre: encabezado variante, una hoja con columna inicial en blanco, fechas en formatos heterogéneos, una fila con cantidad inválida (`'#'`), un cliente nuevo a auto-crear, un contenedor histórico con pares de despacho. El archivo real se usa solo en una **prueba de humo manual** documentada en `quickstart.md`.

**Rationale**:
- 20K filas en CI = test lento, frágil y no aporta cobertura distinta a un fixture controlado.
- El fixture sintético cubre los casos descritos en los Edge Cases del spec.

**Alternatives considered**:
- Test con el archivo real cargado como fixture — lento, archivo pesa MB en el repo. Rechazado.
- Solo unit tests — perdería verificación del flujo Excel→DB completo. Rechazado por Principio VI.

---

## Resumen de decisiones técnicas no obvias

| Tema | Decisión |
|---|---|
| Granularidad de transacción | Por hoja (no por archivo, no por chunk) |
| Cache durante dry-run | En memoria — la BD no se toca salvo `import_batches`/`import_row_results` |
| `PENDIENTE_HISTORICO` | Tabla polimórfica `import_pending_records` (no sentinel en columnas) |
| Encabezados variantes | Aliases explícitos, sin fuzzy |
| Duplicados | `(numero_contenedor_normalizado, cliente_id)` + hash del archivo |
| Inconsistencia saldo Excel | Advertencia, importa con valor literal del Excel |
| Fixture de test | Sintético, no el archivo real (que va a quickstart manual) |

Sin `NEEDS CLARIFICATION` pendientes. Listo para Phase 1.
