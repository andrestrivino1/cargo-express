# Research: Sistema de Trazabilidad de Carga

**Date**: 2026-03-21
**Status**: Complete
**Spec**: [spec.md](spec.md)

---

## ~~1. Integración WhatsApp Business API~~ — REMOVIDO

> Decisión del usuario: no se usará WhatsApp. Todas las notificaciones se envían por **email** usando Laravel Notifications (canal `mail`).

---

## 2. RBAC con Spatie Permission

**Decision**: `spatie/laravel-permission v6` con 8 roles y permisos granulares.
**Rationale**: Estándar de facto para RBAC en Laravel. Permisos como unidad atómica (`containers.gate-in`, `inventory.update`, `reports.export`), roles como agrupaciones. Middleware `permission:` en rutas, `@can()` en Blade.
**Best Practices**:
- Nunca verificar roles en lógica de negocio — siempre permisos.
- Enum PHP para nombres de roles (evitar magic strings).
- `RolesAndPermissionsSeeder` para definir la matriz.
**Alternatives Considered**:
- Bouncer: Similar capacidad, comunidad más pequeña.
- Gates/Policies solos: Demasiado manual para 8 roles.

---

## 3. Subida de fotos y documentos

**Decision**: Storage facade de Laravel con disco `public` (local). Estructura: `containers/{id}/gate-in/`, `containers/{id}/novedades/`, `containers/{id}/gate-out/`.
**Rationale**: Cambiar a S3 es un cambio de una línea en `.env` (`FILESYSTEM_DISK=s3`). Validación: `image|mimes:jpg,png,webp|max:5120`. Usar `intervention/image v3` para redimensionar.
**Best Practices**:
- Filenames únicos con `Str::uuid()`.
- Modelo polimórfico `Photo` o trait `HasPhotos` para adjuntar a múltiples entidades.
**Alternatives Considered**:
- S3 desde el inicio: Agrega costo/complejidad para desarrollo.
- Spatie Media Library: Más completo pero más pesado de lo necesario.

---

## 4. Generación de PDF

**Decision**: `barryvdh/laravel-dompdf` para tirillas, tarjas y reportes.
**Rationale**: PHP puro — sin binarios externos (Chrome, wkhtmltopdf). Contenido simple: texto, tablas, logo, código de barras. Rendimiento adecuado para documentos on-demand. Para generación batch, usar Jobs en cola.
**Best Practices**:
- Vistas dedicadas en `resources/views/pdf/` con CSS inline (DomPDF no soporta flexbox, usar tablas).
- `picqer/php-barcode-generator` para códigos de barras en tirillas.
**Alternatives Considered**:
- Browsershot (Puppeteer): Mejor rendering pero requiere Node.js. Overkill para recibos.
- Snappy (wkhtmltopdf): Deprecado/sin mantenimiento. Evitar.

---

## 5. Exportación Excel

**Decision**: `maatwebsite/excel v3.1` (Laravel Excel).
**Rationale**: Paquete dominante (12M+ descargas), soporte completo Laravel 11 + PHP 8.2. Concerns `FromQuery`, `WithHeadings`, `WithStyles`, `ShouldQueue` para reportes grandes.
**Best Practices**:
- `ShouldQueue` para exports > 1000 filas.
- Cachear reportes frecuentes (resúmenes diarios).
**Alternatives Considered**:
- openspout/openspout: Más rápido para 100k+ filas. Usar si hay problemas de rendimiento.
- Fast Excel (rap2hpoutre): Wrapper ligero sobre OpenSpout.

---

## 6. Inventario en tiempo real

**Decision**: Laravel Reverb (WebSocket server nativo de Laravel 11) + polling como fallback.
**Rationale**: Gratuito, self-hosted, integración nativa con Broadcasting. Sin costo mensual (Pusher cobra $49+/mes). WebSockets entregan en milisegundos (SLA de 30s se cumple fácilmente).
**Implementation**:
- `php artisan install:broadcasting` para configurar Reverb.
- Eventos: `InventoryUpdated`, `ContainerMoved`, `GateInCompleted`.
- Frontend: Laravel Echo con JS vanilla — `Echo.channel('inventory').listen(...)`.
- Fallback: `setInterval` fetch cada 15s.
**Alternatives Considered**:
- Pusher: Funciona pero cuesta dinero.
- Polling solo (cada 15-30s): Más simple y suficiente para el SLA. Considerar si WebSocket es muy complejo.
- Soketi: Open-source pero Reverb es la opción nativa Laravel.

---

## 7. Colas y Jobs asíncronos

**Decision**: Driver `database` para inicio. Migrar a Redis al escalar.
**Rationale**: Cero infraestructura adicional — solo tabla `jobs` en MySQL. Suficiente para volumen moderado (cientos de notificaciones/día). Supervisord en producción para `queue:work`.
**Use Cases**: Notificaciones por email, generación PDF, exportación Excel.
**Best Practices**:
- Colas nombradas: `->onQueue('notifications')`, `->onQueue('reports')`.
- Workers separados por cola con diferente concurrencia.
- Definir `tries`, `backoff`, `timeout` en cada Job.
**Alternatives Considered**:
- Redis: Mejor rendimiento. Agregar al superar ~1000 jobs/hora.
- SQS: AWS-managed, overkill salvo infraestructura AWS.
- Horizon: Dashboard excelente. Agregar al migrar a Redis.

---

## 8. Impresión de stickers/etiquetas

**Decision**: PDF con dimensiones fijas (4"x6") generado con DomPDF + diálogo de impresión del navegador.
**Rationale**: Sin integración de hardware desde el servidor. PDF dimensionado a la etiqueta, abierto en nueva pestaña, el usuario imprime a su impresora de etiquetas configurada en el SO.
**Content**: Número de contenedor, BL, cliente, código de barras/QR, fecha, consecutivo.
**Packages**: `simplesoftwareio/simple-qrcode` o `picqer/php-barcode-generator`.
**Alternatives Considered**:
- JSPrintManager / QZ Tray: Impresión directa sin diálogo. Mejor UX para alto volumen pero setup más complejo.
- ZPL generation: Ideal para Zebra pero requiere bridge en el cliente.
- CSS `@media print`: Funciona para etiquetas simples sin generar PDF — `window.print()` directo.

---

## Matriz de decisiones

| Tema | Decisión | Complejidad | Dependencia externa |
|------|----------|-------------|---------------------|
| ~~WhatsApp~~ | Removido — solo email | N/A | Ninguna |
| RBAC | Spatie Permission v6 | Baja | Ninguna |
| Archivos | Storage facade + local | Baja | Ninguna |
| PDF | DomPDF (barryvdh) | Baja | Ninguna |
| Excel | Maatwebsite Excel v3.1 | Baja | Ninguna |
| Tiempo real | Laravel Reverb | Media | Ninguna |
| Colas | Database driver → Redis | Baja | Ninguna |
| Etiquetas | PDF dimensionado + print | Baja-Media | Ninguna |

> **Nota**: Sin dependencias de servicios externos. Todo corre en servidor propio. Notificaciones vía email (SMTP).