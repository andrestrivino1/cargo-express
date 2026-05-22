# Web Routes Contract: Sistema de Trazabilidad de Carga

**Date**: 2026-03-21
**Framework**: Laravel 11 (web routes, Blade views)
**Auth**: Laravel Breeze (session-based)
**RBAC**: Spatie Permission middleware
**Notificaciones**: Email (Laravel Notifications, canal `mail`)

---

## Route Groups

All routes require authentication (`auth` middleware) unless noted.

### Dashboard

| Method | URI | Controller@Method | Middleware | Descripción |
|--------|-----|-------------------|-----------|-------------|
| GET | `/dashboard` | DashboardController@index | auth | Dashboard principal según rol |

---

### Módulo 1 — Solicitudes

| Method | URI | Controller@Method | Middleware | Descripción |
|--------|-----|-------------------|-----------|-------------|
| GET | `/solicitudes` | SolicitudController@index | permission:solicitudes.view | Listar solicitudes |
| GET | `/solicitudes/create` | SolicitudController@create | permission:solicitudes.create | Formulario nueva solicitud |
| POST | `/solicitudes` | SolicitudController@store | permission:solicitudes.create | Guardar solicitud + documentos (FR-001, FR-002) |
| GET | `/solicitudes/{solicitud}` | SolicitudController@show | permission:solicitudes.view | Detalle de solicitud |
| GET | `/solicitudes/{solicitud}/asignar` | SolicitudController@asignar | permission:solicitudes.asignar | Formulario asignar vehículo/conductor |
| POST | `/solicitudes/{solicitud}/orden-servicio` | OrdenServicioController@store | permission:solicitudes.asignar | Generar orden de servicio (FR-004, FR-005) |
| GET | `/solicitudes/{solicitud}/orden-servicio/pdf` | OrdenServicioController@pdf | permission:solicitudes.view | Descargar PDF de orden de servicio |

---

### Módulo 2 — Gate In

| Method | URI | Controller@Method | Middleware | Descripción |
|--------|-----|-------------------|-----------|-------------|
| GET | `/gate-in` | GateInController@index | permission:gate-in.view | Listar contenedores pendientes de ingreso |
| GET | `/gate-in/create` | GateInController@create | permission:gate-in.registrar | Formulario de ingreso |
| POST | `/gate-in` | GateInController@store | permission:gate-in.registrar | Registrar ingreso + fotos (FR-006 a FR-009) |
| GET | `/gate-in/{gateEvent}/pdf` | GateInController@resumenPdf | permission:gate-in.view | Descargar PDF resumen de ingreso |
| GET | `/contenedores/{contenedor}/referencias` | ReferenciaController@index | permission:referencias.view | Listar referencias del contenedor |
| GET | `/contenedores/{contenedor}/referencias/create` | ReferenciaController@create | permission:referencias.registrar | Formulario registrar referencias |
| POST | `/contenedores/{contenedor}/referencias` | ReferenciaController@store | permission:referencias.registrar | Guardar referencias (FR-010, FR-011) |
| GET | `/contenedores/{contenedor}/sticker` | StickerController@show | permission:referencias.view | Generar/ver sticker de marcación (FR-012) |
| GET | `/contenedores/{contenedor}/sticker/print` | StickerController@print | permission:referencias.view | PDF del sticker para impresión |

---

### Módulo 3 — Vaciado

| Method | URI | Controller@Method | Middleware | Descripción |
|--------|-----|-------------------|-----------|-------------|
| GET | `/vaciado` | VaciadoController@index | permission:vaciado.view | Listar órdenes de vaciado |
| GET | `/vaciado/create` | VaciadoController@create | permission:vaciado.programar | Formulario programar vaciado |
| POST | `/vaciado` | VaciadoController@store | permission:vaciado.programar | Crear orden de vaciado (FR-013, FR-014) |
| GET | `/vaciado/{ordenVaciado}` | VaciadoController@show | permission:vaciado.view | Detalle de orden de vaciado |
| PATCH | `/vaciado/{ordenVaciado}/iniciar` | VaciadoController@iniciar | permission:vaciado.ejecutar | Iniciar vaciado — estado "En vaciado" (FR-015) |
| PATCH | `/vaciado/{ordenVaciado}/finalizar` | VaciadoController@finalizar | permission:vaciado.ejecutar | Finalizar vaciado |
| POST | `/vaciado/{ordenVaciado}/novedades` | NovedadController@store | permission:vaciado.ejecutar | Registrar novedad + fotos (FR-016, FR-017, FR-018) |
| GET | `/vaciado/{ordenVaciado}/novedades/pdf` | VaciadoController@novedadesPdf | permission:vaciado.view | Descargar PDF reporte de novedades |

---

### Módulo 4 — Almacenamiento e Inventario

| Method | URI | Controller@Method | Middleware | Descripción |
|--------|-----|-------------------|-----------|-------------|
| GET | `/inventario` | AlmacenamientoController@index | permission:inventario.view | Inventario en tiempo real (FR-022, FR-023) |
| GET | `/inventario/export/excel` | AlmacenamientoController@exportExcel | permission:inventario.exportar | Exportar inventario Excel (FR-024) |
| GET | `/inventario/export/pdf` | AlmacenamientoController@exportPdf | permission:inventario.exportar | Exportar inventario PDF |
| GET | `/referencias/{referencia}/ubicar` | AlmacenamientoController@ubicar | permission:inventario.ubicar | Formulario asignar ubicación |
| PATCH | `/referencias/{referencia}/ubicacion` | AlmacenamientoController@asignarUbicacion | permission:inventario.ubicar | Asignar ubicación (FR-019, FR-020, FR-021) |

---

### Módulo 5 — Gate Out

| Method | URI | Controller@Method | Middleware | Descripción |
|--------|-----|-------------------|-----------|-------------|
| GET | `/gate-out` | GateOutController@index | permission:gate-out.view | Contenedores listos para salida |
| GET | `/gate-out/{contenedor}` | GateOutController@show | permission:gate-out.view | Detalle pre-salida |
| PATCH | `/gate-out/{contenedor}/limpieza` | GateOutController@registrarLimpieza | permission:gate-out.registrar | Registrar limpieza y destino (FR-027, FR-028) |
| POST | `/gate-out/{contenedor}` | GateOutController@store | permission:gate-out.registrar | Registrar salida + fotos (FR-029, FR-030, FR-031, FR-032) |
| GET | `/gate-out/{contenedor}/tirilla` | GateOutController@tirilla | permission:gate-out.view | Ver/descargar tirilla PDF |
| GET | `/gate-out/export/excel` | GateOutController@exportExcel | permission:gate-out.view | Exportar historial de salidas Excel |

---

### Módulo 6 — Entrega de Mercancía

| Method | URI | Controller@Method | Middleware | Descripción |
|--------|-----|-------------------|-----------|-------------|
| GET | `/entregas` | EntregaController@index | permission:entregas.view | Listar órdenes de cargue |
| GET | `/entregas/create` | EntregaController@create | permission:entregas.crear | Formulario nueva orden de cargue |
| POST | `/entregas` | EntregaController@store | permission:entregas.crear | Registrar orden de cargue (FR-033, FR-034, FR-035) |
| GET | `/entregas/{ordenCargue}` | EntregaController@show | permission:entregas.view | Detalle de orden |
| GET | `/entregas/export/excel` | EntregaController@exportExcel | permission:entregas.view | Exportar historial de entregas Excel |
| POST | `/entregas/{ordenCargue}/tarja` | TarjaController@store | permission:entregas.despachar | Generar tarja de entrega (FR-036, FR-037, FR-038) |
| GET | `/tarjas/{tarja}` | TarjaController@show | permission:entregas.view | Ver tarja generada |
| GET | `/tarjas/{tarja}/pdf` | TarjaController@pdf | permission:entregas.view | Descargar tarja PDF |

---

### Módulo 7 — Trazabilidad y Reportes

| Method | URI | Controller@Method | Middleware | Descripción |
|--------|-----|-------------------|-----------|-------------|
| GET | `/trazabilidad` | TrazabilidadController@index | permission:trazabilidad.view | Búsqueda de contenedores (FR-039) |
| GET | `/trazabilidad/{contenedor}` | TrazabilidadController@show | permission:trazabilidad.view | Historial completo del contenedor (FR-040, FR-041) |
| GET | `/trazabilidad/{contenedor}/pdf` | TrazabilidadController@historialPdf | permission:trazabilidad.view | Descargar PDF historial completo del contenedor |
| GET | `/reportes` | ReporteController@index | permission:reportes.view | Panel de reportes |
| GET | `/reportes/operacion` | ReporteController@operacion | permission:reportes.generar | Formulario filtros de reporte (FR-042) |
| GET | `/reportes/operacion/export` | ReporteController@export | permission:reportes.generar | Exportar reporte Excel/PDF (FR-043, FR-044) |

---

### Catálogos (Admin)

| Method | URI | Controller@Method | Middleware | Descripción |
|--------|-----|-------------------|-----------|-------------|
| GET | `/admin/ubicaciones` | UbicacionPatioController@index | role:administrador | Gestionar ubicaciones del patio |
| POST | `/admin/ubicaciones` | UbicacionPatioController@store | role:administrador | Crear ubicación |
| PUT | `/admin/ubicaciones/{ubicacion}` | UbicacionPatioController@update | role:administrador | Editar ubicación |
| DELETE | `/admin/ubicaciones/{ubicacion}` | UbicacionPatioController@destroy | role:administrador | Desactivar ubicación |
| GET | `/admin/usuarios` | UserController@index | role:administrador | Gestionar usuarios |
| POST | `/admin/usuarios` | UserController@store | role:administrador | Crear usuario |
| PUT | `/admin/usuarios/{user}` | UserController@update | role:administrador | Editar usuario/rol |

---

## Permission Matrix

| Permiso | cliente | portero | operador | coordinador | supervisor | despachador | gerente | admin |
|---------|---------|---------|----------|-------------|------------|-------------|---------|-------|
| solicitudes.view | ✓ (propias) | | | ✓ | ✓ | | ✓ | ✓ |
| solicitudes.create | ✓ | | | | | | | ✓ |
| solicitudes.asignar | | | | ✓ | | | | ✓ |
| gate-in.view | | ✓ | ✓ | ✓ | ✓ | | ✓ | ✓ |
| gate-in.registrar | | ✓ | | | | | | ✓ |
| referencias.view | ✓ (propias) | | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| referencias.registrar | | | ✓ | | | | | ✓ |
| vaciado.view | | | ✓ | | ✓ | | ✓ | ✓ |
| vaciado.programar | | | | | ✓ | | | ✓ |
| vaciado.ejecutar | | | ✓ | | | | | ✓ |
| inventario.view | ✓ (propias) | | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| inventario.ubicar | | | ✓ | | | | | ✓ |
| inventario.exportar | | | | | ✓ | | ✓ | ✓ |
| gate-out.view | | ✓ | ✓ | | ✓ | | ✓ | ✓ |
| gate-out.registrar | | ✓ | | | | | | ✓ |
| entregas.view | ✓ (propias) | | | | | ✓ | ✓ | ✓ |
| entregas.crear | ✓ | | | | | | | ✓ |
| entregas.despachar | | | | | | ✓ | | ✓ |
| trazabilidad.view | ✓ (propias) | | | ✓ | ✓ | | ✓ | ✓ |
| reportes.view | | | | | ✓ | | ✓ | ✓ |
| reportes.generar | | | | | | | ✓ | ✓ |

> **Nota**: "propias" indica que el cliente solo ve registros asociados a su cuenta. Implementado via Policy scope en Eloquent.

---

## Broadcasting Channels (Laravel Reverb)

| Canal | Tipo | Evento | Descripción |
|-------|------|--------|-------------|
| `inventory` | Public | `InventoryUpdated` | Actualización de inventario (FR-022) |
| `container.{id}` | Private | `ContainerStatusChanged` | Cambio de estado de contenedor |
| `notifications.{userId}` | Private | `NewNotification` | Notificaciones personales |