# cargo_express Development Guidelines

Auto-generated from all feature plans. Last updated: 2026-06-25

## Active Technologies
- PHP 8.2 + Laravel 12, Maatwebsite/Excel 3.1 (ya instalado), Spatie Laravel-Permission 6.25 (RBAC ya en uso), Barryvdh/Laravel-DomPDF 3.1 (export PDF), Laravel Queue driver `database` (ya configurado por defecto), Laravel Breeze (auth) (002-import-excel-inventory)
- MySQL 8 (ya en uso; mismas tablas de feature 001 + 3 tablas nuevas para el lote de importación, resultados por fila y pendientes polimórficos) (002-import-excel-inventory)
- PHP 8.2 + Laravel 12 + Laravel Validation (FormRequest), Spatie Laravel-Permission (RBAC), Blade + Bootstrap 5.3 (vistas) (003-port-appointment-past-dates)
- MySQL 8 — tabla `ordenes_servicio`, columna `cita_puerto` (datetime). Sin cambios de esquema. (003-port-appointment-past-dates)
- PHP 8.2 + Laravel 12 + Spatie Laravel-Permission 6.25 (RBAC, ya en uso), Laravel Breeze (auth), Blade + Bootstrap 5.3. **Sin nuevas dependencias** (la auditoría se implementa con una tabla propia; en hosting compartido sin SSH no conviene añadir paquetes que requieran `composer install`). (004-admin-edit-records)
- MySQL 8. Se agrega **1 tabla nueva** `cambios_auditoria` (auditoría polimórfica). No se altera el esquema de los módulos existentes. (004-admin-edit-records)
- PHP 8.2 + Laravel 12 + Spatie Laravel-Permission 6.25 (RBAC), Laravel Breeze (auth de sesión), Barryvdh/Laravel-DomPDF 3.1 (PDF), Maatwebsite/Excel 3.1 (export). **Sin nuevas dependencias** (hosting compartido sin SSH). (005-ajuste-requerimientos-operativos)
- MySQL 8 — reutiliza tablas existentes; agrega columnas a `contenedores`, `referencias`, `tarjas`, `users`, `photos`, y **1 tabla nueva** `movimientos_inventario` + **1 tabla nueva** `secuencias` (contador ODC). (005-ajuste-requerimientos-operativos)
- PHP 8.2 + Laravel 12 + Spatie Laravel-Permission 6.25 (RBAC), Laravel Breeze, Barryvdh/Laravel-DomPDF 3.1, Maatwebsite/Excel 3.1. **Sin nuevas dependencias**. (006-ajustes-ingreso-vaciado)
- MySQL 8 / MariaDB (prod). **1 tabla nueva** `ingresos` + columna `ingreso_id` (nullable) en `contenedores`. `referencias.ubicacion_patio_id` ya es nullable. `contenedores.fecha_ingreso` y `referencias.fecha_ingreso` ya existen. (006-ajustes-ingreso-vaciado)

- PHP 8.2+ con Laravel 11 + Laravel 11, Bootstrap 5.3, Laravel Breeze (auth), Spatie Laravel-Permission (RBAC), Maatwebsite Excel (exportación), DomPDF (exportación PDF), Laravel Notifications (WhatsApp/email) (001-cargo-traceability-system)

## Project Structure

```text
src/
tests/
```

## Commands

# Add commands for PHP 8.2+ con Laravel 11

## Code Style

PHP 8.2+ con Laravel 11: Follow standard conventions

## Recent Changes
- 006-ajustes-ingreso-vaciado: Added PHP 8.2 + Laravel 12 + Spatie Laravel-Permission 6.25 (RBAC), Laravel Breeze, Barryvdh/Laravel-DomPDF 3.1, Maatwebsite/Excel 3.1. **Sin nuevas dependencias**.
- 005-ajuste-requerimientos-operativos: Added PHP 8.2 + Laravel 12 + Spatie Laravel-Permission 6.25 (RBAC), Laravel Breeze (auth de sesión), Barryvdh/Laravel-DomPDF 3.1 (PDF), Maatwebsite/Excel 3.1 (export). **Sin nuevas dependencias** (hosting compartido sin SSH).
- 004-admin-edit-records: Added PHP 8.2 + Laravel 12 + Spatie Laravel-Permission 6.25 (RBAC, ya en uso), Laravel Breeze (auth), Blade + Bootstrap 5.3. **Sin nuevas dependencias** (la auditoría se implementa con una tabla propia; en hosting compartido sin SSH no conviene añadir paquetes que requieran `composer install`).


<!-- MANUAL ADDITIONS START -->
<!-- MANUAL ADDITIONS END -->
