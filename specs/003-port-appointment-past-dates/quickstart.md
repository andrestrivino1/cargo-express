# Quickstart: Permitir fechas anteriores en los campos de fecha de registro operativo

**Feature**: 003-port-appointment-past-dates
**Date**: 2026-06-01

## Objetivo

Permitir registrar fechas **pasadas** en los tres campos de fecha de registro operativo, para reflejar inventario histórico importado, de forma uniforme.

## Cambios de código (resumen)

**1. `app/Http/Requests/StoreOrdenServicioRequest.php`**
```php
// antes
'cita_puerto' => ['required', 'date', 'after:now'],
// después
'cita_puerto' => ['required', 'date'],
```

**2. `app/Http/Requests/StoreOrdenCargueRequest.php`**
```php
// antes
'fecha_despacho' => ['required', 'date', 'after:today'],
// después
'fecha_despacho' => ['required', 'date'],
```

**3. `app/Http/Requests/StoreOrdenVaciadoRequest.php`** (conservar el `withValidator` de estado "En Patio")
```php
// antes
'fecha_programada' => ['required', 'date', 'after:today'],
// después
'fecha_programada' => ['required', 'date'],
```

**4. `resources/views/vaciado/create.blade.php`** — eliminar el atributo `min` del input de fecha programada:
```blade
{{-- quitar esta línea --}}
min="{{ now()->addDay()->format('Y-m-d') }}"
```

No se requieren migraciones, cambios de modelo ni de controladores. No se modifican `fecha_solicitud`, `fecha_corte` ni el rango de reportes.

## Verificación manual

Para cada flujo, iniciar sesión con el rol correspondiente y:

1. **Cita en puerto**: asignar una orden de servicio con cita en puerto **anterior a hoy** → se guarda sin error.
2. **Fecha de despacho**: crear una orden de cargue con fecha de despacho **anterior a hoy** → se guarda sin error.
3. **Fecha programada (vaciado)**: en el formulario de vaciado, el control de fecha permite seleccionar una fecha **anterior a hoy** y se guarda sin error (con un contenedor "En Patio").

En los tres, verificar además:
- Campo vacío → error "campo obligatorio".
- Fecha futura → sigue guardando normalmente.

## Verificación automatizada

```bash
php artisan test --filter=FechasRegistro
```

Casos cubiertos por flujo (cita en puerto, despacho, vaciado):

- ✅ Fecha pasada válida → registro creado y persistido.
- ✅ Campo vacío → falla validación.
- ✅ Formato inválido → falla validación.
- ✅ Fecha futura → registro creado (sin regresión).
- ✅ Vaciado: contenedor que no está "En Patio" → sigue siendo rechazado (validación de negocio intacta).

## Criterios de aceptación (referencia)

- FR-001/002/003: aceptan fecha pasada en cita en puerto, despacho y vaciado.
- FR-004: los campos siguen obligatorios.
- FR-005: sigue validando formato.
- FR-006: siguen aceptando fechas futuras.
- FR-007: persiste la fecha tal cual se ingresa.
- FR-008: criterio uniforme en todos los flujos.
- FR-009: validaciones de negocio no temporales intactas.
