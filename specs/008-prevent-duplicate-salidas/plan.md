# Implementation Plan: Prevenir salidas de mercancía duplicadas (ODC)

**Branch**: `008-prevent-duplicate-salidas` | **Date**: 2026-07-08 | **Spec**: [spec.md](spec.md)
**Input**: Feature specification from `/specs/008-prevent-duplicate-salidas/spec.md`

## Summary

Se están creando salidas duplicadas (caso real ODC-620/621) porque el registro de salida no tiene barrera de idempotencia en el servidor: el guard previo es solo del navegador. La solución añade una **barrera server-side race-safe**: una tabla `idempotency_keys` con índice **UNIQUE** sobre un token UUID que viaja en el formulario; en el `store`, la primera sentencia de la transacción que crea la salida inserta el token — si viola el UNIQUE, es un reenvío y se redirige a la ODC ya creada; si no, se crea la salida y se asocia el `resource_id`. La lógica se encapsula en un `IdempotencyService` inyectable; el controlador solo orquesta y captura una excepción tipada para redirigir. Sin dependencias nuevas (1 migración), compatible con hosting compartido sin SSH.

## Technical Context

**Language/Version**: PHP 8.2 + Laravel 12
**Primary Dependencies**: Spatie Laravel-Permission 6.25 (RBAC), Laravel Breeze (auth de sesión), Barryvdh DomPDF, Maatwebsite Excel — **sin dependencias nuevas**
**Storage**: MySQL 8 (dev) / MariaDB (prod). **1 tabla nueva** `idempotency_keys`. Sin cambios a tablas existentes. Driver de sesión/cache: `database`.
**Testing**: PHPUnit (Feature tests bajo `tests/Feature/`)
**Target Platform**: Hosting compartido Linux (GoDaddy cPanel), sin acceso SSH → sin `composer install` en prod
**Project Type**: Aplicación web (monolito Laravel + Blade, Bootstrap 5.3)
**Performance Goals**: Registro de salida en pocos segundos incluyendo 2 fotos; la idempotencia añade solo 1 INSERT indexado + 1 UPDATE
**Constraints**: Debe ser **race-safe** ante doble-submit concurrente; sin dependencias nuevas; funcionar bajo driver de sesión `database` (sin bloqueo de sesión fiable)
**Scale/Scope**: Base de usuarios interna (operadores de patio); el caso dominante es reenvío secuencial del mismo formulario

## Constitution Check

*GATE: Debe pasar antes de Phase 0. Re-evaluado tras Phase 1.*

| Principio | Cumplimiento |
|---|---|
| I. Código limpio (funciones ≤ 40 líneas, archivos ≤ 300) | ✅ Lógica en `IdempotencyService` con métodos cortos; `registrar()` sigue orquestando sin engordar (se añaden 2 llamadas). |
| II. Convención sobre configuración | ✅ Migración/modelo/servicio/FormRequest siguen las convenciones Laravel ya usadas en el repo. |
| III. Responsabilidad Única | ✅ Controlador orquesta y redirige; idempotencia en su propio servicio; creación de salida en `SalidaMercanciaService`. |
| IV. DRY | ✅ Un único mecanismo de idempotencia, genérico por `scope` (reutilizable por Ingreso a futuro sin duplicar). |
| V. KISS | ✅ Una tabla + insert-first; sin abstracciones especulativas; purga de tokens pospuesta (YAGNI). |
| VI. Testeable | ✅ `IdempotencyService` inyectado; test de integración `SalidaIdempotenciaTest` cubre el flujo crítico (creación/descuento de inventario). |
| VII. Escalabilidad | ✅ Sin estado compartido salvo BD; barrera en índice UNIQUE (correcta bajo concurrencia y escalado horizontal); columnas indexadas. |

**Seguridad**: la entrada `idempotency_key` se valida (`required|uuid`); RBAC (`permission:salida.crear`) intacto; sin datos sensibles nuevos.

**Resultado del gate**: ✅ PASA. Sin violaciones que justificar.

## Project Structure

### Documentation (this feature)

```text
specs/008-prevent-duplicate-salidas/
├── plan.md              # Este archivo
├── spec.md              # Especificación
├── research.md          # Decisiones de diseño (Phase 0)
├── data-model.md        # Entidad idempotency_keys (Phase 1)
├── quickstart.md        # Guía de implementación y verificación (Phase 1)
├── contracts/
│   └── salida-store.md   # Contrato del endpoint/formulario (Phase 1)
└── tasks.md             # (lo genera /speckit.tasks — NO en este paso)
```

### Source Code (repository root)

Estructura real del proyecto (Laravel, no la estructura genérica de la plantilla):

```text
app/
├── Http/
│   ├── Controllers/
│   │   └── SalidaMercanciaController.php     # MOD: create() pasa token; store() captura duplicado
│   └── Requests/
│       └── StoreSalidaMercanciaRequest.php   # MOD: regla idempotency_key
├── Services/
│   ├── SalidaMercanciaService.php            # MOD: reservar/asociar en la transacción
│   └── IdempotencyService.php                # NUEVO
├── Models/
│   └── IdempotencyKey.php                     # NUEVO
└── Exceptions/
    └── SalidaDuplicadaException.php           # NUEVO

database/
└── migrations/
    └── 2026_07_08_000001_create_idempotency_keys_table.php   # NUEVO

resources/
└── views/
    └── salida/
        └── create.blade.php                   # MOD: hidden idempotency_key (conserva guard JS)

tests/
└── Feature/
    └── SalidaIdempotenciaTest.php             # NUEVO
```

**Structure Decision**: Monolito Laravel existente. Se respeta la separación controlador → servicio → modelo ya presente en el módulo de Salida. La barrera vive en un servicio propio (`IdempotencyService`) para SRP y reutilización.

## Complexity Tracking

> Sin violaciones a la constitución. Sección no aplica.
