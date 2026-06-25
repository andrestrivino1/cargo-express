# Implementation Plan: Ajuste de requerimientos operativos

**Branch**: `005-ajuste-requerimientos-operativos` | **Date**: 2026-06-25 | **Spec**: [spec.md](./spec.md)
**Input**: Feature specification from `/specs/005-ajuste-requerimientos-operativos/spec.md`

## Summary

Alinear el sistema con el instructivo de Carga Trans Xpress consolidando el flujo operativo en cuatro capacidades visibles: **Ingreso de mercancía** (un solo formulario con BL, contenedor, cliente, ubicación, tipo, referencia, descripción, unidad/peso, cantidad + adjuntos BL/DIM/Lista de empaque), **Vaciado** (fotos + novedades, ya existente), **Salida de mercancía** (un solo formulario del despachador que descuenta inventario, exige foto de mercancía y de conductor, y genera la **Orden de Salida ODC**), y **Reportes** (inventario por cliente, ingresos, salidas, movimientos, novedades, evidencias y trazabilidad).

Enfoque técnico: reutilizar el esquema y los servicios existentes (Laravel 12, modelos `Contenedor`/`Referencia`/`OrdenCargue`/`Tarja`, `HasPhotos`, capa de Servicios, DomPDF, Spatie RBAC), agregando solo las columnas y artefactos imprescindibles. Se introduce un **registro de movimientos de inventario** (ledger) como fuente única para trazabilidad y reportes, un **consecutivo ODC** secuencial, y un mecanismo de **visibilidad de módulos** (config + sidebar + guard) para *ocultar sin eliminar* Solicitudes, Gate-In separado, Entregas/Tarja, Transferencias, Gate-Out e Importación histórica/Pendientes, conservando datos e historial.

## Technical Context

**Language/Version**: PHP 8.2 + Laravel 12
**Primary Dependencies**: Spatie Laravel-Permission 6.25 (RBAC), Laravel Breeze (auth de sesión), Barryvdh/Laravel-DomPDF 3.1 (PDF), Maatwebsite/Excel 3.1 (export). **Sin nuevas dependencias** (hosting compartido sin SSH).
**Storage**: MySQL 8 — reutiliza tablas existentes; agrega columnas a `contenedores`, `referencias`, `tarjas`, `users`, `photos`, y **1 tabla nueva** `movimientos_inventario` + **1 tabla nueva** `secuencias` (contador ODC).
**Testing**: PHPUnit (suite ya presente en `tests/`), pruebas de Feature (HTTP) e integración para descuento de inventario, consecutivo ODC y validaciones obligatorias.
**Target Platform**: Hosting compartido GoDaddy (Linux, Apache, sin colas externas garantizadas; driver de cola `database` disponible).
**Project Type**: Web monolito Laravel (Blade + Bootstrap 5.3, server-side rendering).
**Performance Goals**: Operación interna de una bodega — decenas de usuarios concurrentes, miles de referencias. Registro de ingreso/salida < 5 min (SC-005); generación de ODC/PDF sincrónica < 3 s.
**Constraints**: Sin `composer install` en producción (no agregar paquetes). Descuento de inventario atómico y nunca negativo. "Ocultar, no eliminar": cero borrado de datos/migraciones.
**Scale/Scope**: 1 bodega, ~4 capacidades visibles, ~6 módulos a ocultar. Volumen bajo-medio; paginación e índices en consultas de reportes.

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principio | Cumplimiento en este plan |
|-----------|---------------------------|
| **I. Código limpio** | Lógica de negocio en servicios (`IngresoMercanciaService`, `SalidaMercanciaService`, `ConsecutivoService`, `MovimientoInventarioService`); funciones < 40 líneas; nombres autoexplicativos. |
| **II. Convención sobre configuración** | Se siguen las convenciones **Laravel** del proyecto (app/Http/Controllers, app/Services, app/Models, database/migrations, resources/views), no la estructura `src/modules` genérica de la constitución. *Ver Complexity Tracking — desviación justificada por stack real.* |
| **III. SRP** | Controladores orquestan y validan vía FormRequest; servicios contienen reglas; persistencia vía Eloquent. Un servicio por capacidad. |
| **IV. DRY** | Se reutilizan `HasPhotos`, `Auditable`, enums centralizados (se añade `MovimientoTipo`, `DocumentoCategoria`); el descuento de inventario se centraliza en un único servicio (hoy duplicable). |
| **V. KISS** | Se extiende el esquema con columnas en lugar de crear módulos paralelos; se reutilizan `OrdenCargue`/`Tarja`/`Referencia`. Solo 2 tablas nuevas. |
| **VI. Testeable** | Servicios con dependencias inyectables; pruebas de integración para descuento de inventario, no-negativos, consecutivo y obligatoriedad de evidencias. Objetivo de cobertura: ≥80% en servicios nuevos. |
| **VII. Escalabilidad** | Reportes paginados e indexados; descuento bajo transacción con bloqueo de fila; ledger de movimientos para evitar recálculos costosos. |
| **Seguridad** | RBAC Spatie verificado por ruta; validación/sanitización vía FormRequest; uploads validados (mime/tamaño); secretos por entorno. |

**Desviaciones declaradas** (ver Complexity Tracking):
- Estructura de directorios Laravel en lugar de `src/modules` (constitución II).
- Autenticación de **sesión (Breeze)** en lugar de JWT — **pre-existente**, no introducida aquí.
- Reportes/PDF **sincrónicos** (no por colas) — justificado por volumen bajo y patrón ya establecido; FR no exige asíncrono.

Resultado: **PASS** (con desviaciones documentadas y justificadas).

## Project Structure

### Documentation (this feature)

```text
specs/005-ajuste-requerimientos-operativos/
├── plan.md              # Este archivo (/speckit.plan)
├── research.md          # Fase 0 (/speckit.plan)
├── data-model.md        # Fase 1 (/speckit.plan)
├── quickstart.md        # Fase 1 (/speckit.plan)
├── contracts/           # Fase 1 (/speckit.plan) — contratos HTTP de los módulos
│   ├── ingreso.md
│   ├── salida-odc.md
│   ├── reportes.md
│   └── visibilidad-modulos.md
└── tasks.md             # Fase 2 (/speckit.tasks — NO lo crea /speckit.plan)
```

### Source Code (repository root)

Monolito Laravel existente. Los archivos **nuevos** y **modificados** previstos:

```text
app/
├── Http/
│   ├── Controllers/
│   │   ├── IngresoMercanciaController.php        # NUEVO (formulario consolidado de ingreso)
│   │   ├── SalidaMercanciaController.php         # NUEVO (formulario consolidado + ODC)
│   │   ├── ReporteController.php                 # MOD (inventario por cliente, ingresos, salidas, movimientos)
│   │   └── (Solicitud/GateIn/GateOut/Entrega/Transferencia/Importacion controllers SIN cambios → se ocultan por ruta+menú)
│   ├── Requests/
│   │   ├── StoreIngresoMercanciaRequest.php      # NUEVO
│   │   └── StoreSalidaMercanciaRequest.php       # NUEVO
│   └── Middleware/
│       └── ModuloVisible.php                     # NUEVO (guard de módulos ocultos)
├── Services/
│   ├── IngresoMercanciaService.php               # NUEVO
│   ├── SalidaMercanciaService.php                # NUEVO
│   ├── ConsecutivoService.php                    # NUEVO (secuencia ODC)
│   ├── MovimientoInventarioService.php           # NUEVO (ledger entradas/salidas)
│   └── ReporteService.php                        # MOD (nuevos reportes)
├── Models/
│   ├── MovimientoInventario.php                  # NUEVO
│   ├── Secuencia.php                             # NUEVO
│   ├── Contenedor.php                            # MOD (+bl, +tipo_mercancia, +HasPhotos)
│   ├── Referencia.php                            # MOD (+peso)
│   ├── Tarja.php                                 # MOD (+conductor_cedula, +transportador, +destino, +consecutivo_odc, +HasPhotos)
│   ├── User.php                                  # MOD (+nit)
│   └── Photo.php                                 # MOD (+categoria)
├── Enums/
│   ├── MovimientoTipo.php                        # NUEVO (entrada|salida)
│   └── DocumentoCategoria.php                    # NUEVO (bl|dim|lista_empaque|foto_mercancia|foto_conductor)
config/
└── modulos.php                                   # NUEVO (banderas de visibilidad)
database/migrations/
├── 2026_06_25_000001_create_movimientos_inventario_table.php   # NUEVO
├── 2026_06_25_000002_create_secuencias_table.php               # NUEVO
├── 2026_06_25_000003_add_ingreso_fields_to_contenedores.php    # NUEVO (bl, tipo_mercancia)
├── 2026_06_25_000004_add_peso_to_referencias.php               # NUEVO
├── 2026_06_25_000005_add_salida_fields_to_tarjas.php           # NUEVO
├── 2026_06_25_000006_add_nit_to_users.php                      # NUEVO
└── 2026_06_25_000007_add_categoria_to_photos.php               # NUEVO
resources/views/
├── ingreso/                                      # NUEVO (formulario consolidado)
├── salida/                                       # NUEVO (formulario consolidado)
├── pdf/orden-salida.blade.php                    # NUEVO (formato ODC de la imagen)
├── reportes/                                     # MOD (nuevos reportes)
└── layouts/app.blade.php                         # MOD (sidebar: ocultar módulos)
routes/web.php                                    # MOD (rutas nuevas + guard de ocultos)
tests/Feature/
├── IngresoMercanciaTest.php                      # NUEVO
├── SalidaMercanciaTest.php                       # NUEVO
├── OrdenSalidaPdfTest.php                         # NUEVO
├── MovimientoInventarioTest.php                  # NUEVO
└── VisibilidadModulosTest.php                    # NUEVO
```

**Structure Decision**: Monolito Laravel existente con capa de Servicios ya consolidada (`app/Services/*`). Se respeta esa estructura (principio II adaptado al stack real). No se crean carpetas `src/modules`; los "módulos" del dominio se representan como conjuntos Controller+Service+Model+Views+Request, igual que el resto del proyecto.

## Complexity Tracking

| Violación / Desviación | Por qué es necesaria | Alternativa más simple rechazada porque |
|------------------------|----------------------|------------------------------------------|
| Estructura Laravel en vez de `src/modules` (constitución II) | El proyecto ya es un monolito Laravel con convenciones establecidas; reescribir la estructura sería un refactor mayor sin valor de negocio. | Adoptar `src/modules` rompería la convención del framework y todo el código existente (constitución V/II). |
| Tabla nueva `movimientos_inventario` (ledger) | Garantiza trazabilidad por movimiento (FR-019) y reportes de ingresos/salidas/historial (FR-021) sin recálculos frágiles. | Derivar movimientos de `referencias`+`tarja_detalles` mezcla responsabilidades, dificulta el reporte de "historial de movimientos" y no registra el saldo resultante ni el responsable de forma uniforme. |
| Tabla nueva `secuencias` (consecutivo ODC) | El ODC requiere un consecutivo único, secuencial y a prueba de concurrencia que continúe la numeración existente (ODC-570+). | `max(consecutivo)+1` sin bloqueo es propenso a duplicados en concurrencia; un contador con bloqueo de fila es simple y seguro. |
| Auth de sesión (Breeze) en vez de JWT (seguridad) | Pre-existente en el sistema; cambiarla excede el alcance del ajuste. | Migrar a JWT ahora introduce riesgo y trabajo no solicitado. |
