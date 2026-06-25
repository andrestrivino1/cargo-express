# Research: Ajuste de requerimientos operativos

**Feature**: 005-ajuste-requerimientos-operativos
**Date**: 2026-06-25
**Phase**: 0 — Outline & Research

Las decisiones de alcance se resolvieron en `/speckit.clarify` (ver sección Clarifications del spec). Aquí se consolidan las decisiones **técnicas** de implementación. No quedan marcadores `NEEDS CLARIFICATION`.

---

## D1. Ingreso consolidado sobre el esquema existente

- **Decisión**: Crear `IngresoMercanciaController` + `IngresoMercanciaService` + `StoreIngresoMercanciaRequest` que, en una sola transacción, creen/actualicen el `Contenedor` (con `bl`, `tipo_mercancia`, cliente) y sus `Referencia` (código, descripción, unidad_medida, peso, cantidad, ubicación), registren el movimiento de **entrada** en el ledger y adjunten los documentos (BL, DIM, Lista de empaque) como `Photo` con `categoria`.
- **Rationale**: Reutiliza tablas y relaciones ya probadas (`contenedores`, `referencias`, `photos`) en lugar de crear un módulo paralelo (KISS/DRY). El "formulario único" decidido en Q1 se logra orquestando estos modelos desde un servicio.
- **Alternativas**: (a) Tabla nueva `ingresos` — rechazada: duplica `contenedores`/`referencias`. (b) Mantener el flujo Solicitud→Gate-In y solo añadir campos — rechazada en Q1.

## D2. Almacenamiento de documentos del ingreso (BL, DIM, Lista de empaque)

- **Decisión**: Reutilizar la tabla polimórfica `photos` + trait `HasPhotos`, añadiendo columna `categoria` (nullable). Adjuntar al `Contenedor`. Categorías: `bl`, `dim`, `lista_empaque` (documentos) y `foto_mercancia`, `foto_conductor` (fotos de salida).
- **Rationale**: `photos` ya distingue `tipo` (foto/documento) y tiene `mime_type`, `tamaño`, `ruta`. Añadir `categoria` permite etiquetar el rol del archivo sin una tabla nueva (DRY). El modelo `Documento` (legado, atado a `Solicitud`) queda para historial y no se extiende.
- **Alternativas**: (a) Tabla `documentos_ingreso` — rechazada: duplica `photos`. (b) Usar `Documento` legado — rechazada: está atada a `Solicitud`, que se oculta.

## D3. Salida consolidada y descuento de inventario atómico

- **Decisión**: `SalidaMercanciaController` + `SalidaMercanciaService` reutilizan `OrdenCargue`/`Tarja`/`TarjaDetalle`. El servicio, en una **transacción** con `lockForUpdate()` sobre cada `Referencia`, valida saldo suficiente, decrementa `cantidad_actual`, registra el movimiento de **salida** en el ledger (con saldo resultante, usuario y timestamp) y asigna el consecutivo ODC. El descuento de inventario se **centraliza** aquí (hoy vive suelto en `EntregaService::generarTarja`).
- **Rationale**: Cumple FR-010/FR-011/FR-012/FR-018/FR-019. El bloqueo de fila evita saldos negativos en concurrencia (Edge Case). Centralizar el descuento elimina duplicación (DRY) y da una sola razón de cambio (SRP).
- **Alternativas**: (a) Decremento directo sin lock — rechazada: condición de carrera. (b) Tabla `salidas` nueva — rechazada: `OrdenCargue`/`Tarja` ya modelan el despacho.

## D4. Consecutivo ODC (secuencia segura y continua)

- **Decisión**: Tabla `secuencias` (`clave` único, `valor` entero). `ConsecutivoService::siguiente('odc')` incrementa bajo transacción con `lockForUpdate()` y devuelve el número. Sembrar `odc = 570` (la muestra es ODC-570) para continuar la numeración. Guardar `consecutivo_odc` en la `Tarja`/salida; nunca se reutiliza.
- **Rationale**: FR-013 exige consecutivo único, no reutilizable y a prueba de concurrencia. Un contador con bloqueo es la solución más simple y robusta en MySQL sobre hosting compartido.
- **Alternativas**: (a) `MAX(consecutivo)+1` — rechazada: duplicados en concurrencia, y se rompe si se anula el de mayor número. (b) `AUTO_INCREMENT` de una tabla dedicada — viable, pero menos legible para sembrar/continuar desde 570.

## D5. Ledger de movimientos de inventario

- **Decisión**: Tabla `movimientos_inventario`: `referencia_id`, `tipo` (`entrada`|`salida`, enum `MovimientoTipo`), `cantidad`, `saldo_resultante`, `usuario_id`, `documento_tipo`/`documento_id` (referencia opcional al ingreso o a la salida/ODC), `observaciones`, `created_at`. Escrito por `MovimientoInventarioService` desde ingreso y salida.
- **Rationale**: Fuente única para "historial de movimientos", "ingresos", "salidas" y para verificar que el saldo = entradas − salidas (FR-018/FR-019/FR-021). Registra responsable y fecha/hora por movimiento.
- **Alternativas**: Derivar de `referencias` + `tarja_detalles` — rechazada: no captura saldo resultante ni unifica entradas/salidas; complica los reportes.

## D6. Documento PDF "Orden de Salida" (ODC)

- **Decisión**: Nueva vista `resources/views/pdf/orden-salida.blade.php` renderizada con DomPDF, replicando la imagen: encabezado (razón social, "ORDEN DE SALIDA", `ODC-###`, Cliente, NIT, Fecha de salida, logo); tabla "Detalle de la carga" (Contenedor, Descripción, Observaciones, Cantidad + Total); "Datos del conductor y vehículo" (Nombre, Cédula, Placa, Transportador, Destino); las dos fotos (conductor y carga) embebidas; espacios de firma (conductor / empresa). El PDF `tarja.blade.php` queda oculto pero intacto (historial).
- **Rationale**: FR-013..FR-017 y SC-004. DomPDF ya se usa para todos los documentos del sistema; embeber imágenes desde `storage` es patrón conocido.
- **Alternativas**: Modificar `tarja.blade.php` — rechazada: se desea conservar el documento histórico y el formato ODC es sustancialmente distinto.

## D7. Datos faltantes para el ODC (NIT, transportador, cédula, destino)

- **Decisión**: Añadir `nit` (nullable) a `users` (clientes). Añadir a `tarjas`: `conductor_cedula`, `transportador`, `destino`, `consecutivo_odc`. La razón social y NIT **de la empresa emisora** (901615219-4) se leen de `config/empresa.php`/`.env` (config existente o nueva clave), no de la BD.
- **Rationale**: La imagen muestra NIT del cliente y datos del transportador/conductor que hoy no se capturan. Columnas nullable evitan romper datos históricos.
- **Alternativas**: Tabla `transportadores` catálogo — rechazada por ahora (regla de tres / KISS); se captura como texto. Puede normalizarse luego si se repite.

## D8. Evidencias obligatorias en la salida

- **Decisión**: `StoreSalidaMercanciaRequest` exige `foto_mercancia` y `foto_conductor` (imágenes JPG/PNG, tamaño máx. configurable). El servicio no confirma la salida si falta alguna. Se guardan como `Photo` (`categoria` = `foto_mercancia`/`foto_conductor`) atadas a la `Tarja`.
- **Rationale**: Q5 = ambas obligatorias; FR-008. Validación en FormRequest (constitución: validación de entrada).
- **Alternativas**: Opcionales — rechazada en Q5.

## D9. Ocultar módulos sin eliminar

- **Decisión**: Archivo `config/modulos.php` con banderas booleanas por módulo (p. ej. `'solicitudes' => false`). El sidebar (`layouts/app.blade.php`) muestra cada ítem solo si su bandera es `true`. Middleware `ModuloVisible` protege las rutas de módulos ocultos devolviendo 404 (parece inexistente) sin eliminar controladores ni datos. Reactivar = cambiar la bandera a `true`.
- **Rationale**: Cumple FR-023..FR-026 y Q4. No toca migraciones ni borra datos; reversible en un solo punto. Los reportes/trazabilidad siguen leyendo las tablas ocultas (FR-026).
- **Alternativas**: (a) Borrar rutas/controladores — rechazada: viola "no eliminar". (b) Permisos Spatie por módulo — viable pero más pesado; las banderas de config son más simples para visibilidad global (KISS). Se mantiene RBAC para el control de acceso por rol.

## D10. Reportes requeridos

- **Decisión**: Extender `ReporteService`/`ReporteController` con: **inventario actual por cliente** (agregado de `referencias.cantidad_actual` por cliente), **ingresos** y **salidas** (desde `movimientos_inventario`), **historial de movimientos** (ledger paginado), **novedades** (ya existe base en `novedades`), **evidencias fotográficas + trazabilidad** (desde `photos` + `TrazabilidadService`). Export a PDF/Excel con los mecanismos existentes.
- **Rationale**: FR-020..FR-022, SC-008. Reutiliza ledger (D5) y servicios existentes (`TrazabilidadService`, `ReporteService`).
- **Alternativas**: Reportes ad-hoc por consulta directa en controladores — rechazada: viola SRP.

## D11. Testing

- **Decisión**: Pruebas de Feature (HTTP) por historia: validación de campos obligatorios (ingreso/salida), adjuntos, descuento de inventario y no-negatividad (con concurrencia simulada), unicidad/continuidad del consecutivo ODC, contenido del PDF ODC (bloques presentes), visibilidad de módulos (ocultos → 404 / ausentes en menú; visibles → 200), y consistencia saldo = entradas − salidas.
- **Rationale**: Constitución VI (≥80% en servicios nuevos; integración para lógica crítica de inventario).
- **Alternativas**: Solo pruebas unitarias — insuficiente para el flujo transaccional.

---

**Salida de Fase 0**: Todas las incógnitas resueltas. Listo para Fase 1 (data-model, contracts, quickstart).
