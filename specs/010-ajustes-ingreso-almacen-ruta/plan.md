# Implementation Plan: Corrección de cantidades en ingreso, retiro de productos en almacenamiento y landing del sitio

**Branch**: `010-ajustes-ingreso-almacen-ruta` | **Date**: 2026-09-25 | **Spec**: [spec.md](./spec.md)
**Input**: Feature specification from `/specs/010-ajustes-ingreso-almacen-ruta/spec.md`

## Summary

Tres ajustes independientes sobre módulos ya en producción:

1. **Corrección de cantidades en el ingreso** — la tabla de referencias del BL en `ingreso/editar` deja de ser solo lectura: cada cantidad pasa a ser un input que viaja en el mismo `PUT` del ingreso. La corrección se permite aunque la mercancía ya se haya movido: lo disponible se ajusta en la misma diferencia que lo declarado y se rechaza declarar menos de lo ya consumido.
2. **Retiro de referencias en almacenamiento** — acción de baja por fila que saca la referencia del inventario vigente conservando todo su historial.
3. **Entrada al sitio** — la raíz deja de servir la página `welcome` de Laravel y redirige al login; se elimina el registro público de usuarios.

**Enfoque técnico**: el punto de apoyo del retiro es **`SoftDeletes` de Laravel sobre `Referencia`** en lugar de una bandera booleana. Hay ~20 lugares distintos que construyen consultas sobre `Referencia` (inventario, exportables, reportes, salida, transferencias, vaciado, entregas, dashboard, ubicación); una bandera obligaría a agregar el filtro en cada uno y basta olvidar uno para que mercancía retirada reaparezca en un exportable o quede seleccionable en una orden de salida. `SoftDeletes` aplica la exclusión por convención en todas, y hace que `findOrFail`/`find` de los flujos operativos fallen solos sobre una referencia retirada, cubriendo FR-015 sin código nuevo. A cambio exige dos ajustes puntuales: convertir los dos borrados físicos existentes en `forceDelete()` y marcar `withTrashed()` en las seis relaciones históricas que apuntan a `Referencia`, para que el historial siga renderizando.

El ledger gana tres tipos de movimiento (`ajuste_positivo`, `ajuste_negativo`, `baja`); la columna `tipo` es `string(20)`, no un enum de base de datos, así que **no requiere migración**. Los reportes de Ingresos y Salidas filtran por tipo exacto, de modo que los tipos nuevos no los contaminan. **Una sola migración de esquema** (`referencias`: `deleted_at` y `retirado_por`) y **una migración idempotente de permisos** (`inventario.retirar`), siguiendo el patrón ya establecido por no haber SSH en producción.

## Technical Context

**Language/Version**: PHP 8.2
**Primary Dependencies**: Laravel 12, Spatie Laravel-Permission 6.25 (RBAC), Laravel Breeze (auth de sesión), Blade + Bootstrap 5.3, Maatwebsite/Excel 3.1, Barryvdh DomPDF 3.1. **Sin dependencias nuevas** (hosting compartido sin SSH; `composer install` no es viable en producción)
**Storage**: MySQL 8 (dev) / MariaDB (prod). **Sin tablas nuevas**. 2 columnas nuevas en `referencias` (`deleted_at`, `retirado_por`). La columna `movimientos_inventario.tipo` ya es `string(20)`: los 3 tipos nuevos no tocan el esquema
**Testing**: PHPUnit (Feature + Unit), SQLite en memoria con `RefreshDatabase`
**Target Platform**: Aplicación web servida en hosting compartido GoDaddy (cPanel)
**Project Type**: Aplicación web monolítica Laravel (MVC + capa de servicios)
**Performance Goals**: Corrección de cantidad completa en < 2 min (SC-001). Listados paginados de a 20, sin cambios en el costo de consulta: `SoftDeletes` agrega `deleted_at is null` a cada consulta; no se indexa la columna porque casi todas las filas la tienen nula y un índice ahí no discrimina nada
**Constraints**:
- **Sin SSH en producción** → los permisos viajan como migración idempotente, no como `db:seed`
- **Sin worker de cola** → todo el trabajo es síncrono dentro del request
- **Compatibilidad hacia atrás del ledger** → ninguna consulta existente sobre `movimientos_inventario` puede romperse al aparecer tipos nuevos
**Scale/Scope**: 3 historias de usuario, 24 requisitos funcionales, 21 escenarios de aceptación. 2 migraciones, 3 archivos nuevos, ~18 archivos modificados, 4 archivos eliminados. Volumen operativo bajo (decenas de referencias por ingreso)

## Constitution Check

*GATE: revisado antes de Phase 0 y de nuevo tras Phase 1.*

| Principio | Estado | Cómo se cumple |
|-----------|--------|----------------|
| I. Código limpio | ✅ PASS | La corrección de cantidades se aísla en un método privado de `IngresoMercanciaService`; el retiro en un método de `InventarioService`. Ningún método supera 40 líneas. Los tipos de movimiento son casos de enum, no strings sueltos |
| II. Convención sobre configuración | ⚠️ DESVIACIÓN JUSTIFICADA | La constitución describe una estructura TypeScript (`src/modules/`, `main.ts`). El proyecto real es Laravel/PHP: se aplica el propio principio II —"se adoptan las convenciones del framework principal"— y se sigue la estructura Laravel de las features 001-009. Ver Complexity Tracking. En esta feature el principio pesa a favor del diseño: se elige `SoftDeletes` (convención del framework) sobre una bandera propia |
| III. Responsabilidad única | ✅ PASS | Los controladores solo orquestan: `AlmacenamientoController::retirar` valida por FormRequest y delega en `InventarioService::retirar`. El ledger lo escribe únicamente `MovimientoInventarioService` |
| IV. No duplicidad | ✅ PASS (con deuda preexistente acotada) | Los 3 tipos de movimiento nuevos se declaran una sola vez en `MovimientoTipo`. La exclusión de retiradas **no se repite** en ~20 consultas: vive una sola vez en el trait `SoftDeletes` del modelo. Deuda preexistente: `InventarioService::consultarInventario`, `::exportarInventarioPdf` e `InventarioExport` duplican el armado de filtros; esta feature **no la aumenta** (el filtro nuevo solo toca el listado) ni la resuelve (fuera de alcance) |
| V. Simplicidad (KISS) | ✅ PASS | La corrección viaja en el `PUT` que ya existe, sin endpoint ni pantalla nuevos. La raíz del sitio se resuelve con un `Route::redirect` de una línea, delegando el caso autenticado en el middleware `guest` que ya hace ese trabajo. Sin tabla nueva, sin columna `cantidad` con signo, sin máquina de estados |
| VI. Código testeable | ✅ PASS | `InventarioService` pasa a recibir `MovimientoInventarioService` y `AuditoriaService` por constructor (patrón ya usado en `IngresoMercanciaService`). Cobertura objetivo: ≥80% en los métodos nuevos de servicio, ≥60% en controladores. 3 archivos de test de integración nuevos |
| VII. Escalabilidad | ✅ PASS | Sin procesos nuevos. El único índice nuevo es el de la foránea `retirado_por`; `deleted_at` no se indexa (baja selectividad). Listados siguen paginados. El retiro es O(1): una actualización, un movimiento, un registro de auditoría |

**Seguridad** (sección de la constitución):

- **Autorización RBAC en cada ruta**: la corrección de cantidades hereda el `role:administrador|coordinador` que ya protege `ingreso.editar`/`ingreso.update`; el retiro estrena el permiso `inventario.retirar`, verificado en la ruta y de nuevo en el `authorize()` del FormRequest.
- **Validación de entrada**: las cantidades pasan por FormRequest; la pertenencia de cada referencia al ingreso editado se valida en `withValidator`, igual que hoy se valida el contenedor de la referencia nueva. Sin esa validación, un `referencias[<id ajeno>]` permitiría alterar mercancía de otro BL.
- **Reducción de superficie**: eliminar el registro público cierra un alta de usuarios abierta a internet. Es el cambio de mayor impacto de seguridad de la feature.
- **Alcance del cliente**: el rol `cliente` no recibe `inventario.retirar` y su filtro de inventario sigue forzado a su propio `cliente_id` en `InventarioService::filtrosConAlcance`; el filtro "incluir retiradas" queda condicionado al permiso, no al request.

**Nota sobre JWT**: la constitución menciona JWT con refresh token; el proyecto usa autenticación de sesión de Breeze desde la feature 001. Desviación preexistente, fuera de alcance.

**Resultado del gate (pre-Phase 0)**: PASS con una desviación documentada (estructura de directorios).

### Re-evaluación post-diseño (Phase 1)

Revisado contra `data-model.md` y `contracts/`. Sin violaciones nuevas:

| Principio | Verificación sobre el diseño concreto |
|-----------|---------------------------------------|
| III. SRP | `InventarioService::retirar` no escribe el ledger a mano: delega en `MovimientoInventarioService::registrarBaja`. `IngresoMercanciaService` no decide el tipo de ajuste: pasa el delta y el servicio de movimientos elige `ajuste_positivo`/`ajuste_negativo` |
| IV. DRY | El invariante "consumido = `cantidad_inicial` − `cantidad_actual`" se define una sola vez (`data-model.md`) y se usa tanto en la validación como en el cálculo del nuevo disponible. Los 6 `withTrashed()` son declaraciones de relación, no lógica repetida |
| V. KISS | El diseño final **no** agregó: tabla de retiros, columna de estado, endpoint AJAX por referencia, ni columna `cantidad` con signo. Cada uno fue evaluado y descartado con motivo (research.md D-001, D-002, D-004) |
| VI. Testeable | Los contratos de ruta y de ledger quedaron escritos como tablas verificables en `contracts/`, con los casos negativos explícitos que los tests deben recorrer |
| VII. Escalabilidad | `deleted_at` indexado; el retiro y la corrección son operaciones puntuales dentro de la transacción que ya existe |

**Hallazgos confirmados durante el diseño** (detalle en `research.md`):

1. `IngresoMercanciaService::eliminar` y `PendientesCompletarController` borran referencias con `->delete()`. Con `SoftDeletes` eso dejaría filas huérfanas apuntando a contenedores ya borrados: **ambos deben pasar a `forceDelete()`**. Es el riesgo de regresión más alto de la feature y tiene test propio.
2. Seis relaciones `belongsTo(Referencia::class)` necesitan `withTrashed()`, o el historial de una referencia retirada (movimientos, tarjas, transferencias, novedades) renderizaría vacío.
3. `ProductoController::destroy` bloquea borrar un producto del catálogo si tiene referencias; con `SoftDeletes` las retiradas dejan de contar. Es el comportamiento correcto y se documenta como cambio esperado.

**Resultado del gate (post-Phase 1)**: PASS, misma desviación única ya justificada.

## Project Structure

### Documentation (this feature)

```text
specs/010-ajustes-ingreso-almacen-ruta/
├── plan.md              # Este archivo
├── spec.md              # Especificación aprobada (3 aclaraciones resueltas)
├── research.md          # Phase 0 — decisiones técnicas D-001..D-008
├── data-model.md        # Phase 1 — esquema, invariantes y transiciones
├── quickstart.md        # Phase 1 — puesta en marcha, validación manual y despliegue
├── contracts/
│   ├── rutas.md         # Contrato de rutas HTTP + permisos + códigos de respuesta
│   └── ledger.md        # Contrato del ledger de inventario (tipos y efectos)
├── checklists/
│   └── requirements.md  # Checklist de calidad del spec (13/13)
└── tasks.md             # Phase 2 — generado por /speckit.tasks
```

### Source Code (repository root)

```text
app/
├── Enums/
│   └── MovimientoTipo.php                       # MOD — + AjustePositivo, AjusteNegativo, Baja (label + color)
├── Http/
│   ├── Controllers/
│   │   ├── AlmacenamientoController.php         # MOD — retirar(); index() pasa incluir_retiradas
│   │   ├── PendientesCompletarController.php    # MOD — delete() -> forceDelete() en el borrado de duplicados
│   │   └── Auth/RegisteredUserController.php    # ELIMINADO — no hay alta pública de usuarios
│   └── Requests/
│       ├── UpdateIngresoRequest.php             # MOD — reglas de referencias[] + validación de pertenencia y mínimo
│       └── RetirarReferenciaRequest.php         # NUEVO — authorize por permiso (el retiro no recibe datos)
├── Models/
│   ├── Referencia.php                           # MOD — SoftDeletes, fillable, relación retiradoPor()
│   ├── MovimientoInventario.php                 # MOD — referencia()->withTrashed()
│   ├── TarjaDetalle.php                         # MOD — referencia()->withTrashed()
│   ├── Novedad.php                              # MOD — referencia()->withTrashed()
│   ├── Transferencia.php                        # MOD — referenciaOrigen()/referenciaDestino()->withTrashed()
│   └── ImportRowResult.php                      # MOD — referencia()->withTrashed()
└── Services/
    ├── MovimientoInventarioService.php          # MOD — registrarAjuste(delta) + registrarBaja()
    ├── IngresoMercanciaService.php              # MOD — aplicarCorrecciones(); eliminar() usa forceDelete()
    └── InventarioService.php                    # MOD — retirar(); consultarInventario() acepta incluir_retiradas

database/
├── migrations/
│   ├── 2026_09_25_000001_add_retiro_to_referencias_table.php    # NUEVO — deleted_at, retirado_por
│   └── 2026_09_25_000002_crear_permiso_retirar_inventario.php   # NUEVO — inventario.retirar (idempotente)
└── seeders/
    └── RolesAndPermissionsSeeder.php            # MOD — inventario.retirar para instalaciones nuevas

resources/views/
├── ingreso/partials/
│   └── _referencias.blade.php                   # MOD — la celda Cantidad pasa a input (dentro del form ya existente)
├── almacenamiento/
│   └── index.blade.php                          # MOD — botón Retirar + modal de confirmación + filtro/columna de retiradas
├── auth/register.blade.php                      # ELIMINADO
└── welcome.blade.php                            # ELIMINADO

routes/
├── web.php                                      # MOD — raíz redirige a /login; DELETE /inventario/{referencia}
└── auth.php                                     # MOD — fuera las dos rutas de register

tests/
├── Feature/
│   ├── Auth/RegistrationTest.php                # ELIMINADO — la funcionalidad deja de existir
│   ├── IngresoCorregirCantidadTest.php          # NUEVO — US1 (7 escenarios)
│   ├── InventarioRetiroTest.php                 # NUEVO — US2 (8 escenarios) + regresión de forceDelete
│   └── EntradaSitioTest.php                     # NUEVO — US3 (6 escenarios)
└── Unit/Services/
    └── MovimientoInventarioAjusteTest.php       # NUEVO — elección de tipo y saldo resultante
```

**Structure Decision**: se mantiene la estructura Laravel estándar ya consolidada en las features 001-009 (`app/Http/Controllers`, `app/Http/Requests`, `app/Models`, `app/Services`, `app/Enums`, `database/migrations`, `resources/views`, `routes`, `tests/Feature` + `tests/Unit`). No se crean directorios nuevos: las tres historias caen sobre módulos existentes.

## Complexity Tracking

| Violation | Why Needed | Simpler Alternative Rejected Because |
|-----------|------------|-------------------------------------|
| Estructura de directorios distinta a la de la constitución (`app/` de Laravel en vez de `src/modules/`) | La constitución fue redactada para un stack TypeScript; el proyecto es Laravel/PHP desde la feature 001 y ya tiene nueve features entregadas sobre esa estructura | Migrar a `src/modules/` rompería autoload, convenciones del framework, migraciones y las nueve features previas, sin ningún beneficio funcional. El propio principio II ("se adoptan las convenciones del framework principal") respalda mantenerla |
| Tres tipos nuevos en `MovimientoTipo` en vez de uno solo | `movimientos_inventario.cantidad` es `unsignedInteger`: la dirección del movimiento no puede ir en el signo, tiene que ir en el tipo. Y la baja necesita distinguirse del ajuste para que el reporte de movimientos sea legible | Un único `ajuste` con cantidad con signo exigiría alterar una columna de una tabla viva en producción (MariaDB) sin ganar nada. Reutilizar `entrada`/`salida` inflaría los reportes de Ingresos y Salidas con correcciones que no son mercancía moviéndose |
