# Implementation Plan: Reorganización de roles + módulos Citas y Portero

**Branch**: `009-roles-citas-portero` | **Date**: 2026-07-27 | **Spec**: [spec.md](./spec.md)
**Input**: Feature specification from `/specs/009-roles-citas-portero/spec.md`

## Summary

Se agregan dos módulos nuevos —**Citas** (agendamiento de la llegada física de un contenedor ya declarado en un ingreso) y **Portero** (validación en puerta con cuatro evidencias fotográficas obligatorias)— y se reorganiza la matriz de roles: rol nuevo `citas`, rol nuevo `operaciones` (ingreso + salida), el `portero` queda limitado a su módulo, el `supervisor` concentra vaciado + ubicación, el `cliente` se restringe a su propio almacenamiento, y cuatro roles (`coordinador`, `despachador`, `gerente`, `operador`) se retiran del selector sin borrarse.

**Enfoque técnico**: dos tablas nuevas para citas y llegadas, una tercera para novedades de puerta, y reutilización total de la infraestructura existente (`photos` polimórfica con `categoria`, middleware `modulo:`, permisos Spatie, migraciones idempotentes de permisos al estilo `2026_06_25_000009`). El módulo Ingreso **no se modifica** (decisión R-002 del spec). Dos decisiones de diseño evitan trabajo innecesario: el estado **Vencida se deriva en lectura** en vez de depender de un cron (no hay scheduler en el hosting), y el retiro de roles se implementa con una **bandera de configuración** análoga a `config/modulos.php`.

## Technical Context

**Language/Version**: PHP 8.2
**Primary Dependencies**: Laravel 12, Spatie Laravel-Permission 6.25 (RBAC), Laravel Breeze (auth de sesión), Blade + Bootstrap 5.3. **Sin dependencias nuevas** (hosting compartido sin SSH; `composer install` no es viable en producción)
**Storage**: MySQL 8 (dev) / MariaDB (prod). **3 tablas nuevas**: `citas`, `porteria_registros`, `porteria_novedades`. Reutiliza `photos` (polimórfica, columna `categoria` ya existente). **Sin cambios de esquema en tablas existentes**
**Testing**: PHPUnit (Feature + Unit), SQLite en memoria con `RefreshDatabase`
**Target Platform**: Aplicación web servida en hosting compartido GoDaddy (cPanel). El módulo Portero se usa desde **navegador móvil**
**Project Type**: Aplicación web monolítica Laravel (MVC + capa de servicios)
**Performance Goals**: Portero localiza una cita en < 15 s (SC-012) y completa el control en < 3 min (SC-003) desde móvil con conectividad irregular. Listados paginados
**Constraints**:
- **Sin scheduler/cron disponible** → ningún requisito puede depender de una tarea programada
- **Sin SSH en producción** → los cambios de permisos y roles viajan como migraciones idempotentes, no como comandos de seeder
- Subidas de imagen limitadas a 10 MB por archivo (límite ya usado en Salida)
**Scale/Scope**: ~8 roles, 2 módulos nuevos, 47 requisitos funcionales, 7 historias de usuario. Volumen operativo bajo (decenas de citas por día)

## Constitution Check

*GATE: revisado antes de Phase 0 y de nuevo tras Phase 1.*

| Principio | Estado | Cómo se cumple |
|-----------|--------|----------------|
| I. Código limpio | ✅ PASS | Servicios por debajo de 40 líneas por método; los estados y categorías se nombran vía enums, no strings sueltos |
| II. Convención sobre configuración | ⚠️ DESVIACIÓN JUSTIFICADA | La constitución describe una estructura TypeScript (`src/modules/`, `main.ts`, `shipment-service.ts`). El proyecto real es Laravel/PHP. Se aplica el propio principio II —"se adoptan las convenciones del framework principal"— y se sigue la estructura Laravel ya establecida en las features 001-008. Ver Complexity Tracking |
| III. Responsabilidad única | ✅ PASS | Controladores solo orquestan; `CitaService` y `PorteriaService` contienen la lógica; la persistencia vía Eloquent como en el resto del proyecto |
| IV. No duplicidad | ✅ PASS | Estados, tipos, tamaños y categorías de foto se centralizan en enums (`app/Enums/`). Se reutilizan `HasPhotos`, middleware `modulo:`, y el patrón de migración de permisos existente. El scoping de cliente se implementa **una vez** en `InventarioService`, no repetido en los tres puntos de entrada |
| V. Simplicidad (KISS) | ✅ PASS | "Vencida" derivado en lectura en vez de cron + columna. Confirmación de llegada atómica en un POST en vez de máquina de estados con borradores. Retiro de roles con una bandera de config en vez de una tabla nueva |
| VI. Código testeable | ✅ PASS | Servicios con dependencias inyectadas por constructor (patrón ya usado en `IngresoMercanciaService`). Cobertura objetivo: ≥80% en los dos servicios nuevos, ≥60% en controladores. Matriz rol × módulo cubierta por tests de integración |
| VII. Escalabilidad | ✅ PASS | Listados paginados; índices en `fecha_esperada`, `placa`, `numero_contenedor` y `estado` para la búsqueda del portero. Sin procesos asíncronos nuevos (no hay cola con worker en el hosting) |

**Seguridad** (sección de la constitución):
- Autorización RBAC verificada en cada ruta vía middleware `permission:` — se mantiene.
- Validación de entrada: todos los formularios pasan por FormRequest.
- El scoping de cliente (FR-035) cierra un hueco real: hoy `AlmacenamientoController` acepta `cliente_id` desde el request sin verificar propiedad.

**Nota sobre JWT**: la constitución menciona JWT con refresh token; el proyecto usa autenticación de sesión de Laravel Breeze desde la feature 001. No se cambia en esta feature — es una desviación preexistente, fuera de alcance.

**Resultado del gate (pre-Phase 0)**: PASS con una desviación documentada (estructura de directorios).

### Re-evaluación post-diseño (Phase 1)

Revisado contra `data-model.md` y `contracts/`. Sin violaciones nuevas:

| Principio | Verificación sobre el diseño concreto |
|-----------|---------------------------------------|
| III. SRP | `CitaService` y `PorteriaService` no se solapan: uno agenda, el otro confirma. `PorteriaNovedad` es tabla aparte en vez de un campo de estado más en `citas` |
| IV. DRY | Las 3 tablas nuevas no duplican columnas existentes; las fotos van a `photos` con `categoria` ya disponible; el scoping de cliente vive en un solo método de `InventarioService` |
| V. KISS | El diseño final **no** agregó: cron, tabla de idempotencia, borradores de subida, ni columna `vencida`. Cada uno fue evaluado y descartado con motivo (research.md D-001, D-003, D-005, D-008) |
| VI. Testeable | La matriz rol × permiso quedó escrita como contrato verificable en `contracts/matriz-roles.md`, con 11 casos negativos explícitos que `MatrizRolesTest` debe recorrer |
| VII. Escalabilidad | Índices definidos para las dos consultas calientes del portero (por fecha y por placa/contenedor); listados paginados; sin trabajos asíncronos nuevos (no hay worker en el hosting) |

**Hallazgo de seguridad confirmado durante el diseño**: `AlmacenamientoController::index/exportExcel/exportPdf` toma `cliente_id` de `$request->only([...])` sin verificar propiedad, de modo que hoy un cliente autenticado puede consultar el inventario de otro cambiando el parámetro. FR-035 lo cierra; es la parte de la feature con mayor prioridad de seguridad.

**Resultado del gate (post-Phase 1)**: PASS, misma desviación única ya justificada.

## Project Structure

### Documentation (this feature)

```text
specs/009-roles-citas-portero/
├── plan.md              # Este archivo
├── spec.md              # Especificación aprobada
├── research.md          # Phase 0 — decisiones técnicas
├── data-model.md        # Phase 1 — entidades y esquema
├── quickstart.md        # Phase 1 — puesta en marcha y validación manual
├── contracts/
│   ├── rutas.md         # Contrato de rutas HTTP + permisos
│   └── matriz-roles.md  # Contrato de la matriz rol × permiso × módulo
├── checklists/
│   └── requirements.md  # Checklist de calidad del spec (aprobado)
└── tasks.md             # Phase 2 — generado por /speckit.tasks
```

### Source Code (repository root)

```text
app/
├── Enums/
│   ├── CitaEstado.php                    # NUEVO — Programada|Atendida|Vencida|Cancelada
│   ├── CitaCondicion.php                 # NUEVO — Full|Vacio
│   ├── TipoContenedor.php                # NUEVO — Dry|Reefer|OpenTop|FlatRack|Tank
│   ├── TamanoContenedor.php              # NUEVO — 20|40|40HC|45
│   └── PorteriaFotoCategoria.php         # NUEVO — Vehiculo|Contenedor|Sello|Tiquete
├── Models/
│   ├── Cita.php                          # NUEVO
│   ├── PorteriaRegistro.php              # NUEVO
│   └── PorteriaNovedad.php               # NUEVO
├── Services/
│   ├── CitaService.php                   # NUEVO — alta, edición, cancelación, listado
│   ├── PorteriaService.php               # NUEVO — citas del día, confirmar llegada, novedad
│   ├── InventarioService.php             # MODIFICADO — scoping por cliente (FR-035)
│   └── RolesDisponibles.php              # NUEVO — roles asignables según config
├── Http/
│   ├── Controllers/
│   │   ├── CitaController.php            # NUEVO
│   │   ├── PorteriaController.php        # NUEVO
│   │   ├── AlmacenamientoController.php  # MODIFICADO — pasa el usuario al servicio
│   │   └── UserController.php            # MODIFICADO — filtra roles retirados
│   └── Requests/
│       ├── StoreCitaRequest.php          # NUEVO
│       ├── UpdateCitaRequest.php         # NUEVO
│       ├── StorePorteriaLlegadaRequest.php  # NUEVO
│       └── StorePorteriaNovedadRequest.php  # NUEVO
config/
├── modulos.php                           # MODIFICADO — + citas, porteria
└── roles.php                             # NUEVO — roles retirados (reversible)

database/
├── migrations/
│   ├── 2026_07_27_000001_create_citas_table.php               # NUEVO
│   ├── 2026_07_27_000002_create_porteria_registros_table.php  # NUEVO
│   ├── 2026_07_27_000003_create_porteria_novedades_table.php  # NUEVO
│   └── 2026_07_27_000004_reorganizar_roles_y_permisos.php     # NUEVO — idempotente
└── seeders/
    └── RolesAndPermissionsSeeder.php     # MODIFICADO — refleja el estado final

resources/views/
├── citas/                                # NUEVO — index, create, edit, show
├── porteria/                             # NUEVO — index, show, novedad
├── ingreso/show.blade.php                # MODIFICADO — estado de citas (FR-047)
└── layouts/app.blade.php                 # MODIFICADO — sidebar por permiso, no solo por módulo

routes/web.php                            # MODIFICADO — grupos citas y porteria

tests/
├── Feature/
│   ├── CitasTest.php                     # NUEVO — US1
│   ├── PorteriaTest.php                  # NUEVO — US2
│   ├── MatrizRolesTest.php               # NUEVO — US3, US4, US6 (rol × módulo)
│   ├── ClienteAlcanceTest.php            # NUEVO — US5 (seguridad)
│   └── IngresoCitasTest.php              # NUEVO — US7
└── Unit/
    ├── CitaServiceTest.php               # NUEVO
    └── PorteriaServiceTest.php           # NUEVO
```

**Structure Decision**: se mantiene la estructura Laravel estándar ya consolidada en las features 001-008 (`app/Models`, `app/Services`, `app/Http/Controllers`, `app/Http/Requests`, `app/Enums`, `resources/views/<modulo>`, `database/migrations`). No se introduce la estructura `src/modules/<module>/` de la constitución porque el proyecto no es TypeScript y reorganizarlo rompería ocho features previas — ver Complexity Tracking.

## Orden de implementación sugerido

Las historias son independientes y se pueden entregar por separado. La constitución limita los PR a 400 líneas, así que conviene separar:

| PR | Alcance | Historias | Riesgo |
|----|---------|-----------|--------|
| 1 | Enums + migraciones de tablas + modelos + `config/modulos.php` | Base de US1/US2 | Bajo |
| 2 | Módulo Citas completo (servicio, controlador, requests, vistas, rutas) | US1 | Bajo |
| 3 | Módulo Portero completo | US2 | Medio — fotos en móvil |
| 4 | Migración de roles y permisos + `config/roles.php` + filtro en `UserController` + sidebar por permiso | US3, US4, US6 | **Alto** — toca el acceso de todos los usuarios |
| 5 | Scoping de cliente en `InventarioService` | US5 | Medio — seguridad |
| 6 | Estado de citas visible desde el ingreso | US7 | Bajo |

El PR 4 es el más delicado: cambia permisos en producción. Debe desplegarse con la migración idempotente y validarse con la matriz rol × módulo antes de dar por cerrado.

## Complexity Tracking

| Violación | Por qué es necesaria | Alternativa más simple rechazada porque |
|-----------|---------------------|----------------------------------------|
| Estructura de directorios distinta a la de la constitución (`app/` de Laravel en vez de `src/modules/<module>/`) | La constitución describe un stack TypeScript/Node que no corresponde al proyecto real (Laravel/PHP desde la feature 001). Su propio principio II ordena adoptar las convenciones del framework principal | Migrar a `src/modules/` obligaría a reescribir la estructura de ocho features ya entregadas y en producción, sin ningún beneficio funcional. La constitución debería actualizarse para reflejar el stack real (fuera del alcance de esta feature) |
| Tres tablas nuevas en vez de reutilizar `gate_events` para la llegada en portería | `gate_events` pertenece a la cadena vieja Gate-In/Gate-Out, hoy oculta (`config/modulos.php`), y su semántica (entrada/salida de contenedor ya ingresado) no corresponde a la validación de una cita | Reutilizar `gate_events` acoplaría el módulo nuevo a una cadena que se decidió retirar, y mezclaría dos significados en la misma tabla (violando SRP) |
| `config/roles.php` nuevo en vez de una columna en la tabla `roles` | Consistencia con el mecanismo ya probado de `config/modulos.php`: reversible, sin migración, sin tocar el esquema de Spatie | Una columna `disponible` en `roles` obligaría a migrar una tabla de terceros (Spatie) y a mantener el valor sincronizado; la bandera de config se revierte editando un archivo |
