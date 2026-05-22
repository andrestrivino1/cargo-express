# Tareas: Sistema de Trazabilidad de Carga

**Input**: Documentos de diseño en `/specs/001-cargo-traceability-system/`
**Prerequisitos**: plan.md, spec.md, research.md, data-model.md, contracts/web-routes.md

**Tests**: No solicitados explícitamente — tareas de test omitidas. Agregar después con TDD si se necesita.

**Organización**: Tareas agrupadas por historia de usuario (7 historias) para permitir implementación y pruebas independientes.

## Formato: `[ID] [P?] [Historia] Descripción`

- **[P]**: Puede ejecutarse en paralelo (archivos diferentes, sin dependencias)
- **[Historia]**: Historia de usuario a la que pertenece (ej. US1, US2, US3)
- Incluye rutas exactas de archivos en las descripciones

---

## Fase 1: Setup (Infraestructura compartida)

**Propósito**: Inicialización del proyecto, scaffolding Laravel, dependencias y configuración

- [ ] T001 Crear proyecto Laravel 11 con `composer create-project laravel/laravel cargo_express` y configurar `.env` según `quickstart.md` (APP_TIMEZONE=America/Bogota, APP_LOCALE=es, BD, QUEUE, MAIL)
- [ ] T002 Instalar scaffolding de autenticación con `composer require laravel/breeze --dev && php artisan breeze:install blade` y configurar Bootstrap 5 (`npm install bootstrap @popperjs/core bootstrap-icons && npm run build`)
- [ ] T003 Instalar dependencias principales: `spatie/laravel-permission`, `barryvdh/laravel-dompdf`, `maatwebsite/excel`, `picqer/php-barcode-generator`, `simplesoftwareio/simple-qrcode`, `intervention/image`
- [ ] T004 Publicar configuraciones de proveedores: Spatie Permission y DomPDF
- [ ] T005 Configurar enlace de almacenamiento (`php artisan storage:link`), tabla de colas (`php artisan queue:table`) y broadcasting (`php artisan install:broadcasting` para Reverb)
- [ ] T006 Crear layout principal `resources/views/layouts/app.blade.php` con Bootstrap 5.3: navbar, navegación lateral para los 7 módulos, mensajes flash y footer

---

## Fase 2: Fundacional (Prerequisitos bloqueantes)

**Propósito**: Infraestructura central que DEBE completarse antes de que CUALQUIER historia de usuario pueda implementarse

**⚠️ CRÍTICO**: Ningún trabajo de historias de usuario puede comenzar hasta que esta fase esté completa

- [ ] T007 Crear Enums: `app/Enums/SolicitudEstado.php` (pendiente, asignada, en_proceso, completada, cancelada), `app/Enums/OrdenServicioEstado.php` (activa, en_ejecucion, completada, cancelada), `app/Enums/ContenedorEstado.php` (solicitado, en_patio, en_vaciado, vaciado_completado, fuera_de_patio), `app/Enums/OrdenVaciadoEstado.php` (programada, en_proceso, completada, cancelada), `app/Enums/NovedadTipo.php` (averia, faltante, dano_visible), `app/Enums/OrdenCargueEstado.php` (pendiente, programada, en_proceso, completada, cancelada), `app/Enums/GateEventTipo.php` (gate_in, gate_out)
- [ ] T008 [P] Crear migración y modelo `app/Models/UbicacionPatio.php` con campos: modulo, posicion, descripcion, activa. Índice único en (modulo, posicion). Archivo: `database/migrations/xxxx_create_ubicaciones_patio_table.php`
- [ ] T009 [P] Crear modelo polimórfico `app/Models/Photo.php` con campos: photoable_type, photoable_id, ruta, nombre, tamaño. Trait `app/Traits/HasPhotos.php` con relación `morphMany`. Archivo: `database/migrations/xxxx_create_photos_table.php`
- [ ] T010 [P] Crear `database/seeders/RolesAndPermissionsSeeder.php` definiendo 8 roles (cliente, portero, operador, coordinador, supervisor, despachador, gerente, administrador) y 18 permisos según la matriz en `contracts/web-routes.md`. Asignar permisos a cada rol.
- [ ] T011 [P] Crear `database/seeders/UbicacionPatioSeeder.php` con módulos y posiciones de ejemplo para desarrollo
- [ ] T012 [P] Crear `database/seeders/AdminUserSeeder.php` con usuario administrador por defecto (admin@cargoexpress.com)
- [ ] T013 Agregar trait `HasRoles` a `app/Models/User.php`, agregar migración de campo `phone`, configurar `$fillable`
- [ ] T014 Crear `app/Services/NotificacionService.php` con método `notificarCliente(User $cliente, Notification $notification)` que despacha notificaciones por email vía cola. Archivo: `app/Services/NotificacionService.php`
- [ ] T015 Crear `app/Http/Controllers/DashboardController.php` con método `index()` mostrando dashboard según rol. Vista: `resources/views/dashboard.blade.php` con tarjetas resumen (solicitudes pendientes, contenedores en patio, despachos pendientes)
- [ ] T016 Registrar todos los grupos de rutas en `routes/web.php` con middleware auth y permission según `contracts/web-routes.md` (métodos de controller vacíos — solo stubs). Incluir rutas admin para ubicaciones y usuarios.
- [ ] T017 Ejecutar todas las migraciones y seeders: `php artisan migrate && php artisan db:seed`

**Checkpoint**: Base lista — autenticación funciona, roles/permisos sembrados, layout renderizado, rutas registradas. La implementación de historias de usuario puede comenzar.

---

## Fase 3: Historia de Usuario 1 — Solicitud y asignación de retiro de contenedor (Prioridad: P1) 🎯 MVP

**Objetivo**: Cliente crea solicitud de retiro adjuntando documentos. Coordinador asigna vehículo/conductor y genera orden de servicio automáticamente.

**Prueba independiente**: Crear solicitud con documento adjunto → verificar que coordinador recibe notificación email → asignar vehículo/conductor → verificar que se genera orden de servicio vinculada.

### Implementación de Historia de Usuario 1

- [ ] T018 [P] [US1] Crear migración y modelo `app/Models/Solicitud.php` con campos según data-model (cliente_id FK, numero_contenedor, naviera, puerto_origen, descripcion, estado ENUM, fecha_solicitud). Relaciones: `belongsTo(User)`, `hasMany(Documento)`, `hasOne(OrdenServicio)`. Archivo: `database/migrations/xxxx_create_solicitudes_table.php`
- [ ] T019 [P] [US1] Crear migración y modelo `app/Models/Documento.php` con campos según data-model (solicitud_id FK, nombre, ruta, tipo_mime, tamaño). Relación: `belongsTo(Solicitud)`. Archivo: `database/migrations/xxxx_create_documentos_table.php`
- [ ] T020 [P] [US1] Crear migración y modelo `app/Models/OrdenServicio.php` con campos según data-model (solicitud_id FK UNIQUE, coordinador_id FK, vehiculo, conductor, conductor_documento, cita_puerto, estado ENUM). Relaciones: `belongsTo(Solicitud)`, `belongsTo(User)`, `hasOne(Contenedor)`. Archivo: `database/migrations/xxxx_create_ordenes_servicio_table.php`
- [ ] T021 [P] [US1] Crear migración y modelo `app/Models/Contenedor.php` con campos según data-model (orden_servicio_id FK, numero, placa_vehiculo, tipo, estado ENUM, fecha_ingreso, fecha_salida, limpieza_registrada, destino_salida). Relaciones: `belongsTo(OrdenServicio)`, `hasMany(GateEvent)`, `hasMany(Referencia)`, `hasMany(OrdenVaciado)`. Transiciones de estado como métodos. Archivo: `database/migrations/xxxx_create_contenedores_table.php`
- [ ] T022 [US1] Crear `app/Http/Requests/StoreSolicitudRequest.php` con validación: numero_contenedor required|max:20, documentos required|array, documentos.* file|mimes:pdf,jpg,png|max:10240
- [ ] T023 [US1] Crear `app/Services/SolicitudService.php` con métodos: `crear(array $data, User $cliente): Solicitud` (guarda solicitud + documentos en Storage, despacha NuevaSolicitudNotification a coordinadores), `listarPorCliente(User $cliente)`, `listarTodas()`
- [ ] T024 [US1] Crear `app/Notifications/NuevaSolicitudNotification.php` con `via()` retornando `['mail']`, `toMail()` con detalles de la solicitud (número contenedor, cliente, fecha). En cola vía `ShouldQueue`.
- [ ] T025 [US1] Implementar `app/Http/Controllers/SolicitudController.php`: `index()` lista solicitudes (filtradas por cliente si rol=cliente vía Policy scope), `create()` muestra formulario, `store()` llama SolicitudService::crear, `show()` muestra detalle con documentos, `asignar()` muestra formulario de asignación con vehículos/conductores disponibles
- [ ] T026 [US1] Crear `app/Http/Requests/StoreOrdenServicioRequest.php` con validación: vehiculo required|max:20, conductor required|max:255, conductor_documento max:20, cita_puerto required|date|after:now
- [ ] T027 [US1] Crear `app/Http/Controllers/OrdenServicioController.php` con `store()`: valida, crea OrdenServicio + Contenedor (estado=solicitado), actualiza estado de Solicitud a 'asignada'. FR-004: solo muestra vehículos disponibles para la fecha. FR-005: genera orden vinculada automáticamente.
- [ ] T028 [P] [US1] Crear vistas Blade: `resources/views/solicitudes/index.blade.php` (tabla con badges de estado, filtros), `resources/views/solicitudes/create.blade.php` (formulario con carga de archivos), `resources/views/solicitudes/show.blade.php` (detalle con lista de documentos + links de descarga + botón descargar PDF orden de servicio), `resources/views/solicitudes/asignar.blade.php` (formulario de asignación con selects de vehículo/conductor + selector de fecha)
- [ ] T029 [US1] Crear plantilla PDF `resources/views/pdf/orden-servicio.blade.php` con layout de tabla CSS inline: logo, número de orden, datos de solicitud (contenedor, cliente, naviera), vehículo asignado, conductor, cédula, cita en puerto, fecha de generación. Agregar método `pdf()` en `OrdenServicioController` que genera descarga vía DomPDF. Ruta: `GET /solicitudes/{solicitud}/orden-servicio/pdf`
- [ ] T030 [US1] Crear `app/Policies/SolicitudPolicy.php` para limitar scope de clientes a sus propias solicitudes. Registrar en `AuthServiceProvider`.
- [ ] T031 [US1] Crear `database/factories/SolicitudFactory.php`, `database/factories/OrdenServicioFactory.php`, `database/factories/ContenedorFactory.php` para desarrollo y pruebas

**Checkpoint**: US1 completa — cliente crea solicitud con documentos, coordinador asigna vehículo/conductor, orden de servicio y contenedor generados automáticamente, notificación email enviada, PDF de orden de servicio descargable.

---

## Fase 4: Historia de Usuario 2 — Ingreso al patio (Gate In) y registro de contenido (Prioridad: P1) 🎯 MVP

**Objetivo**: Portero registra ingreso del contenedor validando orden de servicio. Operador registra referencias/cantidades y genera sticker de marcación.

**Prueba independiente**: Registrar Gate In con placa, número y fotos → verificar estado cambia a "En patio" → registrar referencias → generar sticker PDF con código de barras.

### Implementación de Historia de Usuario 2

- [ ] T032 [P] [US2] Crear migración y modelo `app/Models/GateEvent.php` con campos según data-model (contenedor_id FK, tipo ENUM, usuario_id FK, hora, estado_fisico, notas). Relaciones: `belongsTo(Contenedor)`, `belongsTo(User)`, `morphMany(Photo)`. Usar trait `HasPhotos`. Archivo: `database/migrations/xxxx_create_gate_events_table.php`
- [ ] T033 [P] [US2] Crear migración y modelo `app/Models/Referencia.php` con campos según data-model (contenedor_id FK, cliente_id FK, codigo, descripcion, cantidad_inicial, cantidad_actual, unidad_medida, ubicacion_patio_id FK nullable, fecha_ingreso, fecha_salida nullable). Relaciones: `belongsTo(Contenedor)`, `belongsTo(User)`, `belongsTo(UbicacionPatio)`, `hasMany(Novedad)`, `hasMany(TarjaDetalle)`. Archivo: `database/migrations/xxxx_create_referencias_table.php`
- [ ] T034 [US2] Crear `app/Http/Requests/StoreGateInRequest.php` con validación: orden_servicio_id required|exists, placa required|max:20, numero_contenedor required|max:20, fotos array, fotos.* image|mimes:jpg,png,webp|max:5120. Regla custom: validar que orden_servicio esté activa (FR-006).
- [ ] T035 [US2] Crear `app/Services/GateInService.php` con métodos: `registrarIngreso(array $data, User $portero): GateEvent` (valida OrdenServicio activa FR-006, crea GateEvent tipo=gate_in, guarda fotos vía HasPhotos, actualiza estado de Contenedor a 'en_patio' FR-009, establece fecha_ingreso), `listarPendientes()` retorna contenedores con órdenes activas y estado=solicitado
- [ ] T036 [US2] Implementar `app/Http/Controllers/GateInController.php`: `index()` lista contenedores pendientes de ingreso, `create()` muestra formulario con búsqueda de orden_servicio, `store()` llama GateInService::registrarIngreso con carga de fotos
- [ ] T037 [US2] Crear `app/Http/Requests/StoreReferenciaRequest.php` con validación: referencias required|array, referencias.*.codigo required|max:100, referencias.*.cantidad required|integer|min:1, referencias.*.descripcion nullable|max:255, referencias.*.unidad_medida nullable|max:50
- [ ] T038 [US2] Implementar `app/Http/Controllers/ReferenciaController.php`: `index()` lista referencias del contenedor, `create()` muestra formulario de ingreso masivo, `store()` crea múltiples registros de Referencia (FR-010, FR-011) estableciendo cantidad_actual = cantidad_inicial y fecha_ingreso = now
- [ ] T039 [US2] Crear `app/Http/Controllers/StickerController.php` con métodos `show()` y `print()`. `print()` genera PDF de etiqueta (4"x6") vía DomPDF con: número contenedor, referencia, nombre cliente, código de barras (picqer), fecha. Vista: `resources/views/pdf/sticker.blade.php` con layout de tabla CSS inline. FR-012
- [ ] T040 [US2] Crear plantilla PDF `resources/views/pdf/resumen-ingreso.blade.php` con layout de tabla CSS inline: logo, número contenedor, placa, portero que registró, fecha/hora ingreso, fotos del estado físico, tabla de referencias ingresadas (código, descripción, cantidad, unidad). Agregar método `resumenPdf()` en `GateInController`. Ruta: `GET /gate-in/{gateEvent}/pdf`
- [ ] T041 [P] [US2] Crear vistas Blade: `resources/views/gate-in/index.blade.php` (tabla de contenedores pendientes), `resources/views/gate-in/create.blade.php` (formulario con carga de fotos, validación de orden_servicio), `resources/views/contenedores/referencias/index.blade.php` (tabla de referencias + botón descargar PDF resumen de ingreso), `resources/views/contenedores/referencias/create.blade.php` (formulario de ingreso masivo de referencias)
- [ ] T042 [US2] Crear `database/factories/GateEventFactory.php`, `database/factories/ReferenciaFactory.php`

**Checkpoint**: US2 completa — portero registra gate-in con fotos (bloqueado si no hay orden activa FR-006), estado cambia a "en_patio", operador registra referencias, sticker PDF generado, resumen de ingreso descargable en PDF.

---

## Fase 5: Historia de Usuario 3 — Vaciado: programación y registro de novedades (Prioridad: P2)

**Objetivo**: Supervisor programa orden de vaciado. Operador registra novedades (avería, faltante, daño) con fotos. Cliente notificado automáticamente por email.

**Prueba independiente**: Crear orden de vaciado → iniciar (estado "En vaciado") → registrar novedad con foto → verificar que cliente recibe email con detalle de novedad.

### Implementación de Historia de Usuario 3

- [ ] T043 [P] [US3] Crear migración y modelo `app/Models/OrdenVaciado.php` con campos según data-model (contenedor_id FK, supervisor_id FK, fecha_programada, fecha_inicio, fecha_fin, estado ENUM, notas). Relaciones: `belongsTo(Contenedor)`, `belongsTo(User)`, `hasMany(Novedad)`. Archivo: `database/migrations/xxxx_create_ordenes_vaciado_table.php`
- [ ] T044 [P] [US3] Crear migración y modelo `app/Models/Novedad.php` con campos según data-model (orden_vaciado_id FK, operador_id FK, tipo ENUM, descripcion, referencia_id FK nullable). Relaciones: `belongsTo(OrdenVaciado)`, `belongsTo(User)`, `belongsTo(Referencia)`, `morphMany(Photo)`. Usar trait `HasPhotos`. Archivo: `database/migrations/xxxx_create_novedades_table.php`
- [ ] T045 [US3] Crear `app/Http/Requests/StoreOrdenVaciadoRequest.php` con validación: contenedor_id required|exists (debe estar en estado 'en_patio' FR-013), fecha_programada required|date|after:now
- [ ] T046 [US3] Crear `app/Services/VaciadoService.php` con métodos: `programar(array $data, User $supervisor): OrdenVaciado` (valida contenedor en_patio FR-013, crea orden FR-014), `iniciar(OrdenVaciado $orden)` (establece fecha_inicio, actualiza estado contenedor a 'en_vaciado' FR-015), `finalizar(OrdenVaciado $orden)` (establece fecha_fin, actualiza estado contenedor a 'vaciado_completado'), `registrarNovedad(OrdenVaciado $orden, array $data, User $operador): Novedad` (crea novedad + fotos FR-016/FR-017, despacha NovedadRegistradaNotification FR-018)
- [ ] T047 [US3] Crear `app/Notifications/NovedadRegistradaNotification.php` con `via()` retornando `['mail']`, `toMail()` con detalles de novedad (tipo, descripción, número contenedor, link para ver). En cola vía `ShouldQueue`.
- [ ] T048 [US3] Implementar `app/Http/Controllers/VaciadoController.php`: `index()` lista órdenes de vaciado, `create()` muestra formulario (solo contenedores en_patio), `store()` llama VaciadoService::programar, `show()` muestra detalle de orden con novedades + botón descargar PDF de novedades, `iniciar()` llama VaciadoService::iniciar, `finalizar()` llama VaciadoService::finalizar
- [ ] T049 [US3] Crear `app/Http/Requests/StoreNovedadRequest.php` con validación: tipo required|in:averia,faltante,dano_visible, descripcion required|string, referencia_id nullable|exists, fotos required|array, fotos.* image|mimes:jpg,png,webp|max:5120
- [ ] T050 [US3] Implementar `app/Http/Controllers/NovedadController.php` con `store()`: valida, llama VaciadoService::registrarNovedad
- [ ] T051 [US3] Crear plantilla PDF `resources/views/pdf/reporte-novedades.blade.php` con layout de tabla CSS inline: logo, número contenedor, cliente, fecha de vaciado, tabla de novedades (tipo, descripción, referencia afectada, operador, fecha), fotos de evidencia referenciadas. Agregar método `novedadesPdf()` en `VaciadoController`. Ruta: `GET /vaciado/{ordenVaciado}/novedades/pdf`. Sirve como evidencia formal para reclamos.
- [ ] T052 [P] [US3] Crear vistas Blade: `resources/views/vaciado/index.blade.php` (tabla de órdenes con estado), `resources/views/vaciado/create.blade.php` (formulario con select de contenedor + selector de fecha), `resources/views/vaciado/show.blade.php` (detalle de orden con lista de novedades, galería de fotos, botones de acción iniciar/finalizar, formulario de novedad con carga de fotos, botón descargar PDF novedades)
- [ ] T053 [US3] Crear `database/factories/OrdenVaciadoFactory.php`, `database/factories/NovedadFactory.php`

**Checkpoint**: US3 completa — supervisor programa vaciado, operador registra novedades con fotos, cliente recibe notificación email automáticamente, reporte PDF de novedades descargable como evidencia.

---

## Fase 6: Historia de Usuario 4 — Almacenamiento: ubicación en patio e inventario en tiempo real (Prioridad: P2)

**Objetivo**: Operador asigna ubicación por módulo a mercancía. Supervisor consulta inventario filtrado en tiempo real. Sistema calcula días de almacenamiento automáticamente.

**Prueba independiente**: Asignar ubicación a referencia → verificar notificación email al cliente → consultar inventario filtrado por cliente/módulo/fechas → verificar días de almacenamiento calculados → exportar reporte Excel.

### Implementación de Historia de Usuario 4

- [ ] T054 [US4] Crear `app/Services/InventarioService.php` con métodos: `asignarUbicacion(Referencia $ref, UbicacionPatio $ubicacion)` (actualiza ubicacion_patio_id FR-019/FR-020, despacha UbicacionAsignadaNotification FR-021), `consultarInventario(array $filtros)` (query con joins según data-model, filtros: cliente_id, codigo, modulo, fecha_desde, fecha_hasta FR-023, calcula dias_almacenamiento al vuelo FR-025/FR-026, paginado), `exportarInventario(array $filtros)` (retorna exportación Excel FR-024), `exportarInventarioPdf(array $filtros)` (retorna PDF del inventario filtrado)
- [ ] T055 [US4] Crear `app/Notifications/UbicacionAsignadaNotification.php` con `via()` retornando `['mail']`, `toMail()` con detalles de ubicación (módulo, posición, referencia, contenedor). En cola vía `ShouldQueue`.
- [ ] T056 [US4] Implementar `app/Http/Controllers/AlmacenamientoController.php`: `index()` muestra inventario en tiempo real con filtros (FR-022/FR-023), `exportExcel()` llama InventarioService::exportarInventario y retorna descarga Excel (FR-024), `exportPdf()` llama InventarioService::exportarInventarioPdf y retorna descarga PDF, `ubicar()` muestra formulario para asignar ubicación, `asignarUbicacion()` llama InventarioService::asignarUbicacion. Rutas: `GET /inventario/export/excel`, `GET /inventario/export/pdf`
- [ ] T057 [US4] Crear `app/Exports/InventarioExport.php` implementando `FromQuery`, `WithHeadings`, `WithStyles`, `ShouldQueue` (Maatwebsite Excel). Incluir columnas: referencia, contenedor, cliente, módulo, posición, cantidad, días almacenamiento.
- [ ] T058 [US4] Crear plantilla PDF `resources/views/pdf/inventario.blade.php` con layout de tabla CSS inline: logo, fecha de generación, filtros aplicados, tabla de inventario (referencia, contenedor, cliente, módulo, posición, cantidad actual, días almacenamiento), totales por cliente. Para impresión y firma del cliente.
- [ ] T059 [US4] Crear `app/Events/InventoryUpdated.php` implementando `ShouldBroadcast` en canal `inventory`. Despachar desde InventarioService en asignación de ubicación y cambios de stock. Listener frontend en `resources/js/inventory.js` usando Laravel Echo.
- [ ] T060 [P] [US4] Crear vistas Blade: `resources/views/almacenamiento/index.blade.php` (tabla de inventario con filtros: select de cliente, input de referencia, select de módulo, selectores de rango de fechas. Auto-actualización vía Reverb/polling. Columna días almacenamiento. Botones exportar Excel y PDF), `resources/views/almacenamiento/ubicar.blade.php` (formulario con selects de módulo + posición)
- [ ] T061 [US4] Crear `app/Policies/ReferenciaPolicy.php` para limitar scope de clientes a su propio inventario. Registrar en `AuthServiceProvider`.

**Checkpoint**: US4 completa — operador asigna ubicaciones, cliente notificado por email, inventario muestra datos en tiempo real con filtros, días calculados automáticamente, exportación Excel y PDF funciona.

---

## Fase 7: Historia de Usuario 5 — Salida del contenedor vacío (Gate Out) (Prioridad: P2)

**Objetivo**: Operador registra limpieza y destino del contenedor. Portero registra salida con fotos. Sistema genera tirilla de soporte PDF y la envía al cliente por email.

**Prueba independiente**: Registrar limpieza + destino → registrar Gate Out con fotos → verificar estado "Fuera de patio" → verificar tirilla PDF generada → verificar email enviado al cliente.

### Implementación de Historia de Usuario 5

- [ ] T062 [US5] Crear `app/Services/GateOutService.php` con métodos: `registrarLimpieza(Contenedor $contenedor, bool $limpiado, string $destino)` (actualiza limpieza_registrada FR-027 y destino_salida FR-028), `registrarSalida(Contenedor $contenedor, array $data, User $portero): GateEvent` (crea GateEvent tipo=gate_out con fotos FR-029, actualiza estado a 'fuera_de_patio' FR-031, establece fecha_salida, genera tirilla PDF FR-030, despacha TirillaGateOutNotification FR-032), `listarSalidas(array $filtros)` (consulta de salidas por período, cliente, destino)
- [ ] T063 [US5] Crear `app/Notifications/TirillaGateOutNotification.php` con `via()` retornando `['mail']`, `toMail()` con tirilla PDF adjunta. En cola vía `ShouldQueue`. FR-032
- [ ] T064 [US5] Implementar `app/Http/Controllers/GateOutController.php`: `index()` lista contenedores listos para salida (estado vaciado_completado) + historial de salidas con filtros (fecha, cliente, destino), `show()` muestra detalle pre-salida, `registrarLimpieza()` llama GateOutService::registrarLimpieza, `store()` llama GateOutService::registrarSalida, `tirilla()` retorna descarga PDF de tirilla, `exportExcel()` exporta historial de salidas filtrado en Excel. Ruta: `GET /gate-out/export/excel`
- [ ] T065 [US5] Crear plantilla PDF `resources/views/pdf/tirilla.blade.php` con layout de tabla CSS inline: logo, número contenedor, placa, cliente, fecha ingreso, fecha salida, destino, estado limpieza, código de barras (picqer). Generación DomPDF en GateOutService.
- [ ] T066 [US5] Crear `app/Exports/SalidasExport.php` implementando `FromQuery`, `WithHeadings`, `WithStyles` (Maatwebsite Excel). Columnas: número contenedor, placa, cliente, fecha ingreso, fecha salida, destino, estado limpieza, portero que registró. Filtrable por rango de fechas y cliente.
- [ ] T067 [P] [US5] Crear vistas Blade: `resources/views/gate-out/index.blade.php` (contenedores listos para salida + pestaña historial de salidas con filtros y botón exportar Excel), `resources/views/gate-out/show.blade.php` (detalle pre-salida con formulario de limpieza + select de destino + carga de fotos + botón registrar Gate Out)

**Checkpoint**: US5 completa — limpieza y destino registrados, gate-out con fotos, estado cambia a "fuera_de_patio", tirilla PDF generada y enviada por email al cliente, historial de salidas exportable en Excel.

---

## Fase 8: Historia de Usuario 6 — Entrega de mercancía al cliente (Prioridad: P2)

**Objetivo**: Despachador gestiona órdenes de cargue y programa despachos. Genera tarja de entrega que descuenta inventario automáticamente.

**Prueba independiente**: Registrar orden de cargue → programar despacho → generar tarja con referencias/cantidades → verificar inventario descontado automáticamente → descargar tarja PDF.

### Implementación de Historia de Usuario 6

- [ ] T068 [P] [US6] Crear migración y modelo `app/Models/OrdenCargue.php` con campos según data-model (cliente_id FK, despachador_id FK nullable, fecha_despacho, estado ENUM, notas). Relaciones: `belongsTo(User, 'cliente_id')`, `belongsTo(User, 'despachador_id')`, `hasMany(Tarja)`. Archivo: `database/migrations/xxxx_create_ordenes_cargue_table.php`
- [ ] T069 [P] [US6] Crear migración y modelo `app/Models/Tarja.php` con campos según data-model (orden_cargue_id FK, despachador_id FK, fecha_entrega, observaciones). Relaciones: `belongsTo(OrdenCargue)`, `belongsTo(User)`, `hasMany(TarjaDetalle)`. Archivo: `database/migrations/xxxx_create_tarjas_table.php`
- [ ] T070 [P] [US6] Crear migración y modelo `app/Models/TarjaDetalle.php` con campos según data-model (tarja_id FK, referencia_id FK, cantidad_entregada, ubicacion_origen_id FK). Relaciones: `belongsTo(Tarja)`, `belongsTo(Referencia)`, `belongsTo(UbicacionPatio)`. Archivo: `database/migrations/xxxx_create_tarja_detalles_table.php`
- [ ] T071 [US6] Crear `app/Http/Requests/StoreOrdenCargueRequest.php` con validación: cliente_id required|exists, fecha_despacho required|date|after:now. Crear `app/Http/Requests/StoreTarjaRequest.php` con validación: detalles required|array, detalles.*.referencia_id required|exists, detalles.*.cantidad_entregada required|integer|min:1 (regla custom: <= cantidad_actual), detalles.*.ubicacion_origen_id required|exists
- [ ] T072 [US6] Crear `app/Services/EntregaService.php` con métodos: `crearOrdenCargue(array $data): OrdenCargue` (FR-033, FR-034, genera orden de salida FR-035), `generarTarja(OrdenCargue $orden, array $detalles, User $despachador): Tarja` (crea Tarja + TarjaDetalle FR-036, descuenta Referencia.cantidad_actual por cada detalle FR-037, registra despachador FR-038, despacha evento InventoryUpdated). Usa transacción BD para atomicidad. `listarEntregas(array $filtros)` (consulta de entregas por período, cliente, despachador)
- [ ] T073 [US6] Implementar `app/Http/Controllers/EntregaController.php`: `index()` lista órdenes de cargue con filtros (fecha, cliente, estado) + botón exportar Excel, `create()` muestra formulario, `store()` llama EntregaService::crearOrdenCargue, `show()` muestra detalle de orden con referencias disponibles para tarja, `exportExcel()` exporta historial de entregas filtrado. Ruta: `GET /entregas/export/excel`
- [ ] T074 [US6] Implementar `app/Http/Controllers/TarjaController.php`: `store()` llama EntregaService::generarTarja, `show()` muestra detalle de tarja, `pdf()` genera tarja PDF vía DomPDF
- [ ] T075 [US6] Crear plantilla PDF `resources/views/pdf/tarja.blade.php` con layout de tabla CSS inline: logo, número tarja, orden de cargue, cliente, despachador, fecha, tabla de referencias (código, descripción, cantidad entregada, ubicación origen), total unidades. FR-036
- [ ] T076 [US6] Crear `app/Exports/EntregasExport.php` implementando `FromQuery`, `WithHeadings`, `WithStyles` (Maatwebsite Excel). Columnas: número orden, cliente, despachador, fecha despacho, estado, referencias entregadas (resumen), cantidades totales. Filtrable por rango de fechas y cliente.
- [ ] T077 [P] [US6] Crear vistas Blade: `resources/views/entregas/index.blade.php` (tabla de órdenes con filtros + botón exportar Excel), `resources/views/entregas/create.blade.php` (formulario con select de cliente + selector de fecha), `resources/views/entregas/show.blade.php` (detalle de orden con tabla de referencias, formulario de generación de tarja con inputs de cantidad por referencia), `resources/views/tarjas/show.blade.php` (detalle de tarja con link de descarga PDF)
- [ ] T078 [US6] Crear `database/factories/OrdenCargueFactory.php`, `database/factories/TarjaFactory.php`, `database/factories/TarjaDetalleFactory.php`

**Checkpoint**: US6 completa — despachador crea orden de cargue, genera tarja, inventario descontado automáticamente, tarja PDF descargable, historial de entregas exportable en Excel.

---

## Fase 9: Historia de Usuario 7 — Trazabilidad completa y reportes de operación (Prioridad: P3)

**Objetivo**: Gerente consulta historial completo de cualquier contenedor. Administrador genera reportes exportables (Excel/PDF) para facturación y auditoría.

**Prueba independiente**: Buscar contenedor por número → verificar secuencia completa de eventos con fotos/documentos → generar reporte filtrado por cliente y fechas → exportar en Excel y PDF → verificar días de almacenamiento en reporte.

### Implementación de Historia de Usuario 7

- [ ] T079 [US7] Crear `app/Services/TrazabilidadService.php` con métodos: `buscarContenedor(string $numero): ?Contenedor` (carga eager de todas las relaciones: ordenServicio.solicitud.documentos, gateEvents.photos, referencias.ubicacionPatio, ordenesVaciado.novedades.photos FR-039), `obtenerHistorial(Contenedor $contenedor): Collection` (retorna secuencia cronológica de eventos con fecha, hora, usuario, tipo, fotos, documentos FR-040/FR-041), `exportarHistorialPdf(Contenedor $contenedor)` (genera PDF con historial completo del contenedor)
- [ ] T080 [US7] Crear `app/Services/ReporteService.php` con métodos: `generarReporteOperacion(array $filtros)` (query con filtros: cliente_id, fecha_desde, fecha_hasta, tipo_evento FR-042. Retorna movimientos, novedades, resumen de días almacenamiento por cliente FR-044), `exportarExcel(array $filtros)` (retorna Excel vía Maatwebsite FR-043), `exportarPdf(array $filtros)` (retorna PDF vía DomPDF FR-043)
- [ ] T081 [US7] Crear `app/Exports/ReporteOperacionExport.php` implementando `FromQuery`, `WithHeadings`, `WithStyles`, `WithMultipleSheets` (Maatwebsite Excel). Hojas: Movimientos, Novedades, Resumen Días Almacenamiento. FR-043/FR-044
- [ ] T082 [US7] Crear `app/Jobs/GenerarReporteAsync.php` implementando `ShouldQueue` para generación de reportes pesados. Guarda archivo generado en Storage, notifica al usuario cuando está listo. Cola: 'reports'.
- [ ] T083 [US7] Implementar `app/Http/Controllers/TrazabilidadController.php`: `index()` muestra formulario de búsqueda (por número de contenedor FR-039), `show()` muestra línea de tiempo completa con eventos, fotos, documentos en orden cronológico (FR-040/FR-041) + botón descargar PDF del historial, `historialPdf()` genera descarga PDF del historial completo del contenedor. Ruta: `GET /trazabilidad/{contenedor}/pdf`
- [ ] T084 [US7] Implementar `app/Http/Controllers/ReporteController.php`: `index()` muestra panel de reportes, `operacion()` muestra formulario de filtros (cliente, rango de fechas, tipo de evento), `export()` acepta parámetro de formato (excel/pdf) y llama ReporteService según corresponda
- [ ] T085 [US7] Crear plantilla PDF `resources/views/pdf/historial-contenedor.blade.php` con CSS inline: logo, número contenedor, cliente, datos de solicitud, línea de tiempo de eventos (solicitud, gate-in, vaciado, novedades, ubicación, gate-out) con fechas, horas, usuarios responsables, resumen de referencias y días almacenamiento. Para auditoría completa de un contenedor específico.
- [ ] T086 [US7] Crear plantilla PDF `resources/views/pdf/reporte-operacion.blade.php` con CSS inline: encabezado con filtros aplicados, tabla de movimientos, tabla de novedades, resumen de días almacenamiento por cliente. Logo y fecha.
- [ ] T087 [P] [US7] Crear vistas Blade: `resources/views/trazabilidad/index.blade.php` (formulario de búsqueda con input de número de contenedor), `resources/views/trazabilidad/show.blade.php` (línea de tiempo vertical con tarjetas de evento: fecha, hora, usuario, tipo, galería de fotos, links de documentos + botón descargar PDF historial), `resources/views/reportes/index.blade.php` (panel de reportes con tarjetas), `resources/views/reportes/operacion.blade.php` (formulario de filtros con select de cliente, rango de fechas, checkboxes de tipo de evento, botones de exportar Excel/PDF)

**Checkpoint**: US7 completa — trazabilidad completa de contenedor con línea de tiempo, fotos, documentos. Historial del contenedor descargable en PDF. Reportes filtrables y exportables en Excel/PDF con resumen de días almacenamiento.

---

## Fase 10: Pulido y aspectos transversales

**Propósito**: Mejoras que afectan múltiples historias de usuario

- [ ] T088 [P] Implementar CRUD admin para ubicaciones en `app/Http/Controllers/UbicacionPatioController.php` con vistas `resources/views/admin/ubicaciones/` (index, formulario crear/editar). Según rutas admin en `contracts/web-routes.md`.
- [ ] T089 [P] Implementar gestión admin de usuarios en `app/Http/Controllers/UserController.php` con vistas `resources/views/admin/usuarios/` (index, formulario crear/editar con asignación de rol). Según rutas admin en `contracts/web-routes.md`.
- [ ] T090 [P] Crear componentes Blade reutilizables en `resources/views/components/`: `status-badge.blade.php` (estado con colores), `photo-gallery.blade.php` (grilla con lightbox), `file-upload.blade.php` (drag-and-drop), `date-range-filter.blade.php`, `pagination.blade.php`, `export-buttons.blade.php` (botones Excel/PDF reutilizables)
- [ ] T091 [P] Configurar canales de broadcasting Reverb en `routes/channels.php` según contratos: `inventory` (público), `container.{id}` (privado, autorizado), `notifications.{userId}` (privado, autorizado)
- [ ] T092 Agregar polling de respaldo del lado del cliente en `resources/js/inventory.js`: `setInterval` fetch cada 15s cuando la conexión WebSocket falla
- [ ] T093 Validar ciclo completo de extremo a extremo: solicitud → gate-in → vaciado → almacenamiento → gate-out → entrega. Verificar todos los estados, notificaciones, documentos, descargas PDF/Excel y actualizaciones de inventario según SC-001 a SC-008
- [ ] T094 Ejecutar `php artisan route:list` para verificar que todas las rutas coincidan con `contracts/web-routes.md`. Ejecutar los pasos de validación de quickstart.md.

---

## Dependencias y orden de ejecución

### Dependencias entre fases

- **Setup (Fase 1)**: Sin dependencias — puede comenzar inmediatamente
- **Fundacional (Fase 2)**: Depende de la finalización del Setup — BLOQUEA todas las historias de usuario
- **Historias de usuario (Fases 3–9)**: Todas dependen de la fase Fundacional
  - US1 y US2 (P1) deben completarse primero — son el MVP
  - US3–US6 (P2) pueden proceder en paralelo después de US1+US2
  - US7 (P3) se beneficia de que todas las historias previas tengan datos pero puede iniciar después de la Base
- **Pulido (Fase 10)**: Depende de que todas las historias estén completas

### Dependencias entre historias de usuario

- **US1 (P1)**: Puede iniciar después de la Base — Sin dependencias de otras historias
- **US2 (P1)**: Puede iniciar después de la Base — Usa modelo Contenedor de US1 (migración compartida)
- **US3 (P2)**: Puede iniciar después de la Base — Usa Contenedor de US1, Referencia de US2
- **US4 (P2)**: Puede iniciar después de la Base — Usa Referencia de US2, UbicacionPatio de la Base
- **US5 (P2)**: Puede iniciar después de la Base — Usa Contenedor de US1, GateEvent de US2
- **US6 (P2)**: Puede iniciar después de la Base — Usa Referencia de US2, UbicacionPatio de la Base
- **US7 (P3)**: Puede iniciar después de la Base — Lee de todos los modelos anteriores (sin escrituras)

### Dentro de cada historia de usuario

- Modelos/migraciones antes de servicios
- Servicios antes de controladores
- Form Requests en paralelo con servicios
- Controladores antes de vistas
- Vistas pueden paralelizarse cuando están marcadas [P]

### Oportunidades de paralelismo

- T008, T009, T010, T011, T012 (modelos/seeders Base) — todos en paralelo
- T018, T019, T020, T021 (modelos US1) — todos en paralelo
- T032, T033 (modelos US2) — en paralelo
- T043, T044 (modelos US3) — en paralelo
- T068, T069, T070 (modelos US6) — todos en paralelo
- US3, US4, US5, US6 pueden iniciar en paralelo después de US1+US2 (si el equipo tiene capacidad)

---

## Ejemplo de paralelismo: Historia de Usuario 1

```bash
# Lanzar todos los modelos de US1 en paralelo:
Tarea: T018 "Crear modelo Solicitud en app/Models/Solicitud.php"
Tarea: T019 "Crear modelo Documento en app/Models/Documento.php"
Tarea: T020 "Crear modelo OrdenServicio en app/Models/OrdenServicio.php"
Tarea: T021 "Crear modelo Contenedor en app/Models/Contenedor.php"

# Luego secuencialmente:
Tarea: T022 "Crear StoreSolicitudRequest" (después de modelos)
Tarea: T023 "Crear SolicitudService" (después de modelos)
Tarea: T025 "Implementar SolicitudController" (después de servicio)
Tarea: T028 "Crear vistas Blade" (en paralelo con T027)
```

---

## Estrategia de implementación

### MVP primero (Historias de Usuario 1 + 2)

1. Completar Fase 1: Setup
2. Completar Fase 2: Fundacional (CRÍTICO — bloquea todas las historias)
3. Completar Fase 3: US1 — Solicitudes
4. Completar Fase 4: US2 — Gate In
5. **PARAR y VALIDAR**: Probar ciclo solicitud → gate-in de forma independiente
6. Desplegar/demo si está listo — ingreso básico de contenedores operativo

### Entrega incremental

1. Setup + Base → Framework listo
2. US1 + US2 → **MVP**: Solicitud + Gate In (ingreso de contenedores operativo)
3. US3 → Vaciado (descargue con novedades)
4. US4 → Almacenamiento (inventario con actualizaciones en tiempo real)
5. US5 → Gate Out (salida de contenedor con tirilla)
6. US6 → Entregas (despacho de mercancía con tarja)
7. US7 → Trazabilidad + Reportes (visibilidad completa + auditoría)
8. Pulido → Herramientas admin, componentes, broadcasting, validación

### Estrategia de equipo en paralelo

Con múltiples desarrolladores después de la Base:

- Desarrollador A: US1 (Solicitudes) → US3 (Vaciado)
- Desarrollador B: US2 (Gate In) → US4 (Almacenamiento)
- Desarrollador C: US5 (Gate Out) → US6 (Entregas)
- Desarrollador D: US7 (Trazabilidad) → Pulido

---

## Resumen

| Fase | Historia | Tareas | Prioridad | Descargas agregadas |
|------|----------|--------|-----------|---------------------|
| Fase 1 | Setup | T001–T006 (6) | — | — |
| Fase 2 | Base | T007–T017 (11) | — | — |
| Fase 3 | US1 — Solicitudes | T018–T031 (14) | P1 🎯 | +PDF orden de servicio |
| Fase 4 | US2 — Gate In | T032–T042 (11) | P1 🎯 | +PDF resumen de ingreso |
| Fase 5 | US3 — Vaciado | T043–T053 (11) | P2 | +PDF reporte de novedades |
| Fase 6 | US4 — Almacenamiento | T054–T061 (8) | P2 | +PDF inventario |
| Fase 7 | US5 — Gate Out | T062–T067 (6) | P2 | +Excel historial salidas |
| Fase 8 | US6 — Entregas | T068–T078 (11) | P2 | +Excel historial entregas |
| Fase 9 | US7 — Trazabilidad | T079–T087 (9) | P3 | +PDF historial contenedor |
| Fase 10 | Pulido | T088–T094 (7) | — | — |
| **Total** | | **94 tareas** | | **+7 descargas nuevas** |

---

## Notas

- Las tareas [P] son de archivos diferentes, sin dependencias
- La etiqueta [Historia] vincula la tarea a una historia de usuario específica para trazabilidad
- Cada historia de usuario es completable y testeable de forma independiente
- Hacer commit después de cada tarea o grupo lógico siguiendo Conventional Commits
- Detenerse en cualquier checkpoint para validar la historia de forma independiente
- Todas las notificaciones vía email (sin WhatsApp)
- Todos los PDFs vía DomPDF con CSS inline (sin flexbox)
- Todas las exportaciones vía Maatwebsite Excel
- Tiempo real vía Laravel Reverb + polling de respaldo