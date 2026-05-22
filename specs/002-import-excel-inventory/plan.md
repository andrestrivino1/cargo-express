# Implementation Plan: Importación de Inventario Histórico desde Excel

**Branch**: `002-import-excel-inventory` | **Date**: 2026-05-21 | **Spec**: [spec.md](./spec.md)
**Input**: Feature specification from `/specs/002-import-excel-inventory/spec.md`

## Summary

Cargar el archivo real `INVENTARIO TOTAL CONTROLCARGA 27022026.xlsx` (≈ 20.870 filas, 22 hojas con datos) al sistema en dos modos: **validar** (dry-run que reporta sin escribir) e **importar** (persiste con trazabilidad del lote de origen). Como los datos históricos no contienen los registros padre que el modelo actual exige (Solicitud, OrdenServicio, vehículo, conductor, despachador) ni los clientes existen previamente como usuarios, se aplica un patrón único de **"importar ahora, completar al consultar"**: las entidades faltantes se crean con campos marcados `PENDIENTE_HISTORICO` y un registro polimórfico de "pendientes" enumera los campos a diligenciar; la primera acción operativa sobre el registro abre un formulario que los completa. Los clientes nuevos se auto-crean con password genérica y se les fuerza cambio de password + actualización de email en su primer login. Saldo actual y eventos históricos de despacho se cargan en la misma operación; el saldo persistido es el valor literal del Excel, sin recálculos.

## Technical Context

**Language/Version**: PHP 8.2
**Primary Dependencies**: Laravel 12, Maatwebsite/Excel 3.1 (ya instalado), Spatie Laravel-Permission 6.25 (RBAC ya en uso), Barryvdh/Laravel-DomPDF 3.1 (export PDF), Laravel Queue driver `database` (ya configurado por defecto), Laravel Breeze (auth)
**Storage**: MySQL 8 (ya en uso; mismas tablas de feature 001 + 3 tablas nuevas para el lote de importación, resultados por fila y pendientes polimórficos)
**Testing**: PHPUnit 11 + Mockery (ya configurado). Cobertura objetivo del Constitución: 80 % servicios, 60 % controladores
**Target Platform**: Aplicación web Laravel servida por el stack del cliente (XAMPP local en dev según `which php` ⇒ `/c/xampp/php/php`; producción según `deploy_build.ps1`)
**Project Type**: Aplicación web monolítica Laravel — MVC + capa de Servicios (estructura ya establecida por feature 001-cargo-traceability-system)
**Performance Goals**: SC-001 = procesar ≈ 20.870 filas y producir reporte de validación en < 3 min; SC-007 = atomicidad o recuperación clara ante fallos
**Constraints**: No bloquear la UI durante la carga (FR-015) ⇒ procesamiento asíncrono obligatorio vía cola; lectura del Excel debe ser memoria-eficiente (chunked) por el tamaño combinado de 23 hojas
**Scale/Scope**: 1 archivo de ≈ 20K filas como caso real inmediato; ≤ 50K filas / 50 MB como tope soportado (FR de Edge Cases). 22 clientes nuevos potenciales en el primer import. 3 entregables de UI nuevos: importación, "pendientes de completar", forzar cambio de password

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principio | Cumplimiento del plan |
|---|---|
| I. Código Limpio sobre Código Rápido | El plan divide responsabilidades en servicios ≤ 300 LOC y métodos ≤ 40 LOC; nada de hacks: el sentinel `PENDIENTE_HISTORICO` es una decisión explícita modelada con tabla polimórfica + bandera, no un workaround |
| II. Convención sobre Configuración | Se respeta el layout Laravel ya establecido por feature 001 (Models, Services, Http/Controllers, Exports, Imports, Jobs, Notifications). Misma versión del framework, mismos paquetes ya en `composer.json` — sin tecnologías nuevas |
| III. Responsabilidad Única (SRP) | Servicios separados por responsabilidad: `ExcelHeaderResolver`, `ClienteResolver`, `UbicacionResolver`, `RowValidator`, `FilaMapper`, `InventarioImportService` (orquestador), `ReportBuilder`. El controlador solo orquesta HTTP↔Job |
| IV. No Duplicidad (DRY) | El patrón "pendiente de completar" se modela una sola vez (tabla polimórfica `import_pending_records` + trait `HasImportPendingFields`) y lo consumen Contenedor, OrdenServicio, Solicitud, Tarja, OrdenCargue, User. Validadores y parsers de fecha son utilidades compartidas |
| V. Simplicidad (KISS) | Se elige el camino más simple: lectura por hoja en chunks de 500 filas (no streaming exótico), sentinel string + tabla pendientes (no esquema dinámico), job único en cola (no orquestación de varios jobs anidados). Sin abstracciones especulativas |
| VI. Código Testeable | Toda dependencia se inyecta vía constructor (servicios resolvers, repositorios). El job recibe el servicio orquestador inyectado. Se proveen tests unitarios por servicio y de integración para el flujo dry-run y persistente |
| VII. Escalabilidad | Procesamiento asíncrono obligatorio vía `database` queue ya configurada (`composer dev` ya levanta `queue:listen`). Lectura chunked. Inserciones batch con `DB::transaction` por hoja para acotar el blast radius de un fallo. Sin estado compartido en memoria entre hojas |

**Estándares de Código**: Nomenclatura mixta — código PHP/Laravel usa snake_case para tablas (constitución lo exige) y PSR (camelCase métodos, PascalCase clases); archivos PHP son `PascalCase.php` por convención Composer/PSR-4 (en lugar del `kebab-case` que la constitución sugiere para TS) — esto es una desviación **justificada por Principio II** (convención del framework manda).

**Estándares de Seguridad**: La password genérica de auto-creación se guarda con `bcrypt`; el flag `requiere_cambio_password` bloquea acceso vía middleware hasta cumplirse; la subida del Excel valida MIME + extensión + tamaño máximo; el endpoint requiere rol `administrador` o `coordinador` (Spatie middleware `role`).

**Resultado del gate**: ✅ PASA. Sin violaciones que justificar.

## Project Structure

### Documentation (this feature)

```text
specs/002-import-excel-inventory/
├── plan.md              # Este archivo
├── research.md          # Phase 0: decisiones técnicas (memoria, parsing, jobs)
├── data-model.md        # Phase 1: tablas nuevas + columnas añadidas
├── quickstart.md        # Phase 1: pasos para validar e importar el archivo real
├── contracts/
│   ├── http-routes.md       # Rutas web (importación, pendientes, primer login)
│   ├── excel-schema.md      # Contrato de columnas esperadas del Excel origen
│   └── pending-fields.md    # Catálogo de campos PENDIENTE_HISTORICO por entidad
├── checklists/
│   └── requirements.md  # Ya creado en /speckit.specify
└── tasks.md             # Lo crea /speckit.tasks
```

### Source Code (repository root)

Se extiende el árbol Laravel ya establecido por feature 001. Solo se listan nuevos archivos o modificaciones; lo no listado queda intacto.

```text
app/
├── Models/
│   ├── ImportBatch.php                       # NEW
│   ├── ImportRowResult.php                   # NEW
│   ├── ImportPendingRecord.php               # NEW (polimórfico)
│   ├── User.php                              # MOD: campos requiere_cambio_password, email_placeholder, password_actualizada_at + trait HasImportPendingFields
│   ├── Solicitud.php                         # MOD: import_batch_id + trait HasImportPendingFields
│   ├── OrdenServicio.php                     # MOD: idem
│   ├── Contenedor.php                        # MOD: idem
│   ├── OrdenCargue.php                       # MOD: idem
│   └── Tarja.php                             # MOD: idem
├── Traits/
│   └── HasImportPendingFields.php            # NEW (compartido — Principio IV/DRY)
├── Imports/                                  # NEW carpeta (Maatwebsite)
│   ├── InventarioHistoricoImport.php         # WithMultipleSheets — selecciona hojas a procesar
│   └── Sheets/
│       └── ClienteSheetImport.php            # WithHeadingRow + WithChunkReading
├── Exports/
│   ├── ReporteValidacionExport.php           # NEW (resumen de batch)
│   └── FilasErroneasExport.php               # NEW (filas en error replicando estructura del original)
├── Services/
│   └── Importacion/                          # NEW módulo de servicios
│       ├── InventarioImportService.php       # Orquestador
│       ├── ExcelHeaderResolver.php           # Detecta columnas por nombre, tolera variantes
│       ├── ClienteResolver.php               # Busca o auto-crea User cliente
│       ├── UbicacionResolver.php             # Normaliza "Modulo 3-Bloque C" y busca/crea
│       ├── ContenedorResolver.php            # Crea Solicitud+OrdenServicio sintéticas y Contenedor
│       ├── ReferenciaMapper.php              # Construye Referencia con descripción derivada
│       ├── HistorialDespachoMapper.php       # Genera OrdenCargue+Tarja+TarjaDetalle retroactivas
│       ├── RowValidator.php                  # Reglas por fila — fechas, números, requeridos
│       ├── DateParser.php                    # Parser tolerante d/M/y, dd/MM/yyyy, d-M-y, dd-MM-yyyy
│       ├── PendingFieldsRegistrar.php        # Registra los campos PENDIENTE_HISTORICO por entidad
│       └── ImportReportBuilder.php           # Construye resumen y persiste ImportRowResult
├── Jobs/                                     # NEW carpeta
│   └── ProcesarImportacionInventario.php     # Queued job — modo validate|persist
├── Notifications/
│   └── ImportacionFinalizada.php             # NEW — notifica al usuario que disparó la importación
├── Http/
│   ├── Controllers/
│   │   ├── ImportacionInventarioController.php   # NEW
│   │   └── PendientesCompletarController.php     # NEW (lista + completar)
│   └── Middleware/
│       └── ForzarCambioPasswordYEmail.php    # NEW — bloquea acceso hasta cumplirse
├── Enums/
│   ├── ImportEstado.php                      # NEW (validando|validado|importando|importado|fallido)
│   ├── ImportRowEstado.php                   # NEW (importado|error|advertencia|ignorado)
│   └── OrigenImportacion.php                 # NEW (carga_historica_27_02_2026 inicial; extensible)
└── Policies/                                 # (no cambia — RBAC vía Spatie)

bootstrap/
└── app.php                                   # MOD: registrar alias 'requiere_cambio_password' para el middleware

config/
└── importacion.php                           # NEW — password genérica, dominio placeholder, sentinels

database/
├── migrations/
│   ├── 2026_05_21_100000_add_pending_fields_to_users_table.php       # NEW
│   ├── 2026_05_21_100100_create_import_batches_table.php             # NEW
│   ├── 2026_05_21_100200_create_import_row_results_table.php         # NEW
│   ├── 2026_05_21_100300_create_import_pending_records_table.php     # NEW
│   └── 2026_05_21_100400_add_import_batch_id_to_operational_tables.php  # NEW (solicitudes, ordenes_servicio, contenedores, ordenes_cargue, tarjas)
├── seeders/
│   └── (ninguno nuevo — la importación NO es un seed; los seeders no deben ejecutar imports)
└── factories/
    └── ImportBatchFactory.php                # NEW (para tests)

resources/
└── views/
    ├── importacion/
    │   ├── index.blade.php                   # NEW: pantalla principal con form de subida + listado de batches recientes
    │   ├── reporte.blade.php                 # NEW: detalle del batch tras validar
    │   └── _form_subida.blade.php            # NEW: partial form (modo radio: validar | importar)
    ├── pendientes/
    │   ├── index.blade.php                   # NEW: listado paginado de registros con campos pendientes
    │   └── completar/
    │       ├── contenedor.blade.php          # NEW
    │       ├── orden-servicio.blade.php      # NEW
    │       ├── tarja.blade.php               # NEW
    │       └── _campos_comunes.blade.php     # NEW partial reutilizable
    └── auth/
        ├── primer-login-password.blade.php   # NEW
        └── primer-login-email.blade.php      # NEW

routes/
└── web.php                                   # MOD: rutas /admin/importaciones/*, /pendientes/*, /primer-login/*

tests/
├── Feature/
│   ├── Importacion/
│   │   ├── ValidarExcelTest.php              # NEW (dry-run, sin escribir)
│   │   ├── ImportarExcelTest.php             # NEW (persiste + idempotencia)
│   │   └── HistorialDespachosTest.php        # NEW (User Story 3)
│   ├── Pendientes/
│   │   └── CompletarRegistroTest.php         # NEW
│   └── Auth/
│       └── PrimerLoginForzadoTest.php        # NEW
└── Unit/
    └── Services/Importacion/
        ├── ExcelHeaderResolverTest.php       # NEW (variantes de encabezado)
        ├── DateParserTest.php                # NEW (formatos heterogéneos)
        ├── UbicacionResolverTest.php         # NEW (normalización)
        ├── ClienteResolverTest.php           # NEW (auto-crear vs reusar)
        └── RowValidatorTest.php              # NEW (clasificación de errores)
```

**Structure Decision**: Aplicación web monolítica Laravel siguiendo el layout ya en uso por la feature 001-cargo-traceability-system. Se introducen **dos carpetas nuevas conformes a la convención Laravel** (`app/Imports/`, `app/Jobs/`) y un **submódulo de servicios** `app/Services/Importacion/` por la cantidad de servicios cohesionados (8 clases). La opción "Module per business unit" de la constitución (`src/modules/<module>/`) NO se adopta porque el proyecto ya tomó la decisión opuesta en la feature 001 (controladores y servicios planos bajo `app/`); cambiar ahora violaría Principio II (consistencia con la convención del proyecto).

## Complexity Tracking

> Sin violaciones del Constitution Check. No aplica.

| Violation | Why Needed | Simpler Alternative Rejected Because |
|-----------|------------|-------------------------------------|
| — | — | — |
