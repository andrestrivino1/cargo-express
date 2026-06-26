# Implementation Plan: Editar ingreso con referencias e imágenes del BL

**Branch**: `007-edit-ingreso-bl-references` | **Date**: 2026-06-26 | **Spec**: [spec.md](./spec.md)
**Input**: Feature specification from `/specs/007-edit-ingreso-bl-references/spec.md`

## Summary

Enriquecer la pantalla de edición de un Ingreso (BL) para que, además de los campos actuales (BL, Cliente, Fecha de ingreso), muestre todas las referencias asociadas al BL (distribuidas en uno o varios contenedores), permita adjuntar y ver imágenes a nivel del ingreso/BL, y permita agregar nuevas referencias a un contenedor del ingreso. El objetivo es que los ingresos creados por importación (BL provisional, sin imágenes) puedan completarse sin salir de la edición.

Enfoque técnico: reutilizar la infraestructura existente —trait `HasPhotos` ya presente en `Ingreso`, relación `Ingreso → Contenedor → Referencia`, y el patrón de carga de fotos del módulo de vaciado (`fotos[]` con validación `image|mimes|max`)—. La lógica de negocio (adjuntar fotos, crear referencia + movimiento de inventario) se traslada al `IngresoMercanciaService`; el controlador solo orquesta. **No se requieren cambios de esquema de base de datos.**

## Technical Context

**Language/Version**: PHP 8.2 + Laravel 12  
**Primary Dependencies**: Spatie Laravel-Permission 6.25 (RBAC), Laravel Breeze (auth de sesión), Blade + Bootstrap 5.3. Sin nuevas dependencias.  
**Storage**: MySQL 8 / MariaDB (prod). Reutiliza tablas existentes (`ingresos`, `contenedores`, `referencias`, `photos`, `movimientos_inventario`). **Sin migraciones nuevas.** Archivos de imagen en `Storage::disk('public')` bajo `ingresos/{id}`.  
**Testing**: PHPUnit (Feature tests sobre rutas `ingreso.edit`/`ingreso.update`; Unit sobre `IngresoMercanciaService`).  
**Target Platform**: Hosting compartido (GoDaddy/cPanel) sin SSH; deploy por zip. Navegador de escritorio para operadores.  
**Project Type**: Web application (monolito Laravel, vistas Blade server-rendered).  
**Performance Goals**: Edición de un ingreso con N referencias (típico < 50) renderiza y guarda en tiempo interactivo (< 1 s percibido). Carga de imágenes acotada por límite de tamaño.  
**Constraints**: Sin paquetes que requieran `composer install` en prod (hosting sin SSH). Reutilizar trait `HasPhotos` y patrón de fotos de vaciado. Mantener funciones < 40 líneas y archivos < 300 líneas (constitución).  
**Scale/Scope**: 1 vista modificada (`ingreso/editar.blade.php`), 1 FormRequest ampliado, 1 método de servicio nuevo (o ampliado), 1–2 acciones de controlador. Volumen operativo: decenas–cientos de ingresos por lote de importación.

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principio | Estado | Justificación |
|---|---|---|
| I. Código limpio (<40 líneas/func, <300 líneas/archivo) | ✅ | La lógica vive en métodos cortos del servicio; la vista se mantiene modular. `editar.blade.php` actual son 60 líneas; el bloque de referencias e imágenes se mantiene acotado (si crece, se extrae a `@include` parciales). |
| II. Convención sobre configuración | ✅ | Se siguen convenciones Laravel ya usadas en el proyecto (FormRequest, Service, Eloquent, Blade). Sin nuevas configuraciones. |
| III. Responsabilidad única (SRP) | ✅ | Controlador solo orquesta; la creación de referencias + movimiento de inventario y la carga de fotos viven en `IngresoMercanciaService`. |
| IV. No duplicidad (DRY) | ✅ | Reutiliza `HasPhotos::guardarFotos`, el patrón de validación de fotos de `UpdateOrdenVaciadoRequest`, y la lógica de creación de referencia ya existente en `registrar()` (se extrae a un método privado reutilizable `crearReferencia()`). |
| V. Simplicidad (KISS) | ✅ | Sin tablas ni columnas nuevas. Imágenes a nivel de ingreso (no por referencia) — la opción más simple acordada (FR-014). Edición/eliminación de existentes fuera de alcance (FR-013). |
| VI. Código testeable | ✅ | Dependencias inyectadas (servicio recibe `MovimientoInventarioService`). Se añaden Feature tests para edit/update y Unit tests para los métodos de servicio nuevos. |
| VII. Escalabilidad | ✅ | Eager-loading de `contenedores.referencias` evita N+1. Sin colas necesarias (operación interactiva de bajo volumen). |
| Seguridad: RBAC en cada endpoint | ✅ | Rutas ya protegidas por `role:administrador|coordinador`; `UpdateIngresoRequest::authorize()` lo refuerza. Validación/sanitización de todas las entradas (incluye `mimes`/`max` en archivos). |

**Resultado del Gate**: PASS. Sin violaciones que requieran registro en *Complexity Tracking*.

## Project Structure

### Documentation (this feature)

```text
specs/007-edit-ingreso-bl-references/
├── plan.md              # This file (/speckit.plan command output)
├── spec.md              # Feature specification
├── research.md          # Phase 0 output (/speckit.plan command)
├── data-model.md        # Phase 1 output (/speckit.plan command)
├── quickstart.md        # Phase 1 output (/speckit.plan command)
├── contracts/           # Phase 1 output (/speckit.plan command)
│   └── ingreso-edit-ui.md   # Contrato de la pantalla/form de edición
├── checklists/
│   └── requirements.md
└── tasks.md             # Phase 2 output (/speckit.tasks command - NOT created by /speckit.plan)
```

### Source Code (repository root)

Monolito Laravel existente. Archivos afectados (todos ya existen salvo donde se indique):

```text
app/
├── Http/
│   ├── Controllers/
│   │   └── IngresoMercanciaController.php      # update() pasa fotos al servicio; (opcional) acción para agregar referencia
│   └── Requests/
│       └── UpdateIngresoRequest.php            # + reglas: fotos[] (image|mimes|max) y referencia nueva (nullable)
├── Models/
│   ├── Ingreso.php                             # ya usa HasPhotos (sin cambios) — relación fotos disponible
│   ├── Contenedor.php                          # relación referencias() (sin cambios)
│   └── Referencia.php                          # (sin cambios)
└── Services/
    └── IngresoMercanciaService.php             # + actualizar() / agregarFotos() / crearReferencia() reutilizable

resources/views/ingreso/
└── editar.blade.php                            # + bloque referencias (lista), + carga de imágenes (galería + file input),
                                                #   + (opcional) sub-form/repeater agregar referencia. enctype multipart.

tests/
├── Feature/
│   └── IngresoEditarTest.php                   # NUEVO: ver referencias, subir fotos, agregar referencia, RBAC, validación
└── Unit/
    └── IngresoMercanciaServiceTest.php         # NUEVO/ampliado: agregarFotos, crearReferencia
```

**Structure Decision**: Web application monolítica (Laravel + Blade). No aplica separación frontend/backend ni `src/modules` de la constitución genérica; el proyecto real usa la estructura estándar de Laravel (`app/Http`, `app/Services`, `app/Models`, `resources/views`), que es la convención adoptada (Principio II). La feature se implementa modificando los archivos del módulo Ingreso existente y reutilizando el módulo de fotos (`HasPhotos`/`photos`).

## Complexity Tracking

> No hay violaciones de la constitución que justificar. Sección no aplicable.
