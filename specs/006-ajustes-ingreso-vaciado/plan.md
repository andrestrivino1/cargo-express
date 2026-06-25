# Implementation Plan: Ajustes a ingreso y vaciado

**Branch**: `006-ajustes-ingreso-vaciado` | **Date**: 2026-06-25 | **Spec**: [spec.md](./spec.md)
**Input**: Feature specification from `/specs/006-ajustes-ingreso-vaciado/spec.md`

## Summary

Cuatro ajustes incrementales sobre los módulos de Ingreso y Vaciado de la feature 005:

1. **Ingreso por BL con varios contenedores**: introducir un registro padre **Ingreso** que ampara un BL (con sus documentos y fecha) y agrupa **uno o varios contenedores**, cada uno con sus propias referencias.
2. **Fecha de ingreso retroactiva**: capturar una fecha de ingreso (≤ hoy) que se propaga a contenedores y referencias y se usa en inventario/reportes, conservando la marca de creación para auditoría.
3. **Vaciado con varias fotos**: permitir agregar fotos a un vaciado ya creado/en proceso (la carga múltiple al crear ya existe).
4. **Ubicación opcional en el ingreso**: permitir referencias "sin ubicar" y asignarles ubicación después desde inventario.

Enfoque: reutilizar el esquema y servicios de la feature 005, agregando **1 tabla nueva** `ingresos` (padre) + `ingreso_id` en `contenedores`, ajustando el `IngresoMercanciaService`/Request/vistas, y agregando una acción para sumar fotos al vaciado. Sin eliminar datos; los ingresos y vaciados previos siguen válidos.

## Technical Context

**Language/Version**: PHP 8.2 + Laravel 12
**Primary Dependencies**: Spatie Laravel-Permission 6.25 (RBAC), Laravel Breeze, Barryvdh/Laravel-DomPDF 3.1, Maatwebsite/Excel 3.1. **Sin nuevas dependencias**.
**Storage**: MySQL 8 / MariaDB (prod). **1 tabla nueva** `ingresos` + columna `ingreso_id` (nullable) en `contenedores`. `referencias.ubicacion_patio_id` ya es nullable. `contenedores.fecha_ingreso` y `referencias.fecha_ingreso` ya existen.
**Testing**: PHPUnit (SQLite :memory: + RefreshDatabase), pruebas de Feature para ingreso multi-contenedor, fecha retroactiva, ubicación opcional y fotos de vaciado.
**Target Platform**: Hosting compartido GoDaddy/cPanel (deploy por zip; migraciones aplicadas vía dump SQL actualizado).
**Project Type**: Web monolito Laravel (Blade + Bootstrap 5.3).
**Performance Goals**: Operación interna de bodega; registro de un ingreso multi-contenedor < 5 min.
**Constraints**: Sin `composer install` en prod. Compatibilidad: ingresos/vaciados previos intactos. Fecha de ingreso ≤ hoy.
**Scale/Scope**: Ajustes acotados a 2 módulos existentes; 1 tabla nueva.

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principio | Cumplimiento |
|-----------|--------------|
| **I. Código limpio** | Lógica en `IngresoMercanciaService` (reescrito) y un método nuevo en `VaciadoController`/`VaciadoService` para sumar fotos; funciones < 40 líneas. |
| **II. Convención** | Convenciones Laravel del proyecto (Controllers/Services/Models/Requests/migrations/Blade). |
| **III. SRP** | Controlador orquesta + FormRequest valida; servicio arma Ingreso→Contenedores→Referencias→ledger. |
| **IV. DRY** | Reutiliza `HasPhotos` y `MovimientoInventarioService`; la entrada de inventario sigue centralizada. |
| **V. KISS** | 1 sola tabla nueva (`ingresos`) justificada por la agrupación BL→contenedores con atributos compartidos (BL, docs, fecha, cliente). El resto son columnas/ajustes. |
| **VI. Testeable** | Servicios inyectables; pruebas de integración para multi-contenedor, fecha, ubicación opcional y fotos. |
| **VII. Escalabilidad** | Inserciones bajo transacción; listados paginados. |
| **Seguridad** | RBAC `ingreso.*`/`vaciado.*` por ruta; validación por FormRequest; uploads validados. |

**Desviaciones declaradas**: Estructura Laravel en lugar de `src/modules` (constitución II) — pre-existente. Resultado: **PASS**.

## Project Structure

### Documentation (this feature)

```text
specs/006-ajustes-ingreso-vaciado/
├── plan.md
├── research.md
├── data-model.md
├── quickstart.md
├── contracts/
│   ├── ingreso.md
│   └── vaciado-fotos.md
└── tasks.md   # /speckit.tasks
```

### Source Code (repository root)

Archivos **nuevos** y **modificados** previstos:

```text
app/
├── Http/
│   ├── Controllers/
│   │   ├── IngresoMercanciaController.php     # MOD (index/show por Ingreso)
│   │   └── VaciadoController.php              # MOD (+agregarFotos)
│   └── Requests/
│       ├── StoreIngresoMercanciaRequest.php  # MOD (contenedores[] anidados, ubicación opcional, fecha ≤ hoy)
│       └── AgregarFotosVaciadoRequest.php     # NUEVO
├── Services/
│   ├── IngresoMercanciaService.php           # MOD (crea Ingreso padre + N contenedores + referencias)
│   └── VaciadoService.php                     # MOD (+agregarFotos)
├── Models/
│   ├── Ingreso.php                            # NUEVO (padre BL)
│   └── Contenedor.php                         # MOD (+ingreso_id, relación ingreso())
database/migrations/
├── 2026_06_26_000001_create_ingresos_table.php          # NUEVO
└── 2026_06_26_000002_add_ingreso_id_to_contenedores.php # NUEVO
resources/views/
├── ingreso/
│   ├── create.blade.php                       # MOD (repetidor anidado contenedores→referencias, fecha, ubicación opcional)
│   ├── index.blade.php                        # MOD (lista por Ingreso/BL)
│   └── show.blade.php                         # MOD (Ingreso con sus contenedores y referencias)
├── vaciado/
│   └── show.blade.php                         # MOD (form "agregar fotos")
routes/web.php                                 # MOD (POST /vaciado/{ordenVaciado}/fotos)
tests/Feature/
├── IngresoMultiContenedorTest.php             # NUEVO
├── IngresoFechaYUbicacionTest.php             # NUEVO
└── VaciadoFotosTest.php                       # NUEVO
```

**Structure Decision**: Monolito Laravel existente; se respeta la capa de Servicios. La agrupación BL se modela con una tabla padre `ingresos`.

## Complexity Tracking

| Desviación | Por qué es necesaria | Alternativa rechazada porque |
|------------|----------------------|------------------------------|
| Tabla nueva `ingresos` (padre BL) | Un BL agrupa varios contenedores con atributos compartidos (BL, documentos, fecha, cliente). Normalizar evita duplicar el BL/fecha/documentos en cada contenedor y permite adjuntar los documentos una sola vez. | Repetir `bl`/`fecha`/documentos en cada `contenedor` (denormalizado) complica la consulta del ingreso, la carga única de documentos y la fecha compartida; rompe la relación 1 ingreso = 1 BL. |
| `ingreso_id` nullable en `contenedores` | Compatibilidad con contenedores históricos (sin ingreso) y con los ingresos de un solo contenedor de la feature 005. | Hacerlo obligatorio rompería los datos previos. |
