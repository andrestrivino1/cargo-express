# Quickstart: Prevenir salidas duplicadas (idempotencia)

**Feature**: 008-prevent-duplicate-salidas

## Qué se construye

Una barrera de idempotencia en el servidor para que un mismo intento de registro de salida cree **una sola** Orden de Salida (ODC), aunque el formulario se envíe varias veces.

## Piezas a tocar

| Archivo | Cambio |
|---|---|
| `database/migrations/xxxx_create_idempotency_keys_table.php` | **Nuevo**. Tabla con `token` UNIQUE, `scope`, `usuario_id`, `resource_id` nullable, `created_at`. |
| `app/Models/IdempotencyKey.php` | **Nuevo**. Modelo Eloquent. |
| `app/Services/IdempotencyService.php` | **Nuevo**. `reservar()`, `asociarRecurso()`, `recursoReservado()`. |
| `app/Exceptions/SalidaDuplicadaException.php` | **Nuevo**. Lleva el id de la tarja existente. |
| `app/Services/SalidaMercanciaService.php` | Inyectar `IdempotencyService`; `reservar` como 1ª sentencia de la transacción de `registrar`, `asociarRecurso` al final; recibir `idempotency_key` en `$data`. |
| `app/Http/Requests/StoreSalidaMercanciaRequest.php` | Regla `idempotency_key => required|uuid`. |
| `app/Http/Controllers/SalidaMercanciaController.php` | `create()` genera y pasa `$idempotencyKey`; `store()` captura `SalidaDuplicadaException` → redirige a la ODC existente con mensaje informativo. |
| `resources/views/salida/create.blade.php` | Campo oculto `idempotency_key` con `old('idempotency_key', $idempotencyKey)`. (El guard JS existente se conserva.) |
| `tests/Feature/SalidaIdempotenciaTest.php` | **Nuevo**. Casos C1–C5 del contrato. |

## Cómo verificar (manual)

1. `php artisan migrate` (en prod: agregar la migración al procedimiento de despliegue habitual; ver memoria de dumps SQL).
2. Abrir **Nueva Salida**, llenar todo, y hacer **doble clic** en "Registrar salida" (o enviar, esperar, y volver atrás + reenviar).
3. Verificar: se generó **un solo** consecutivo ODC; el inventario de las referencias bajó **una sola vez**; el reenvío aterriza en la misma ODC con mensaje "el despacho ya estaba registrado".
4. Registrar dos salidas **distintas** del mismo cliente → ambas se crean (no se bloquean).

## Cómo verificar (automático)

```bash
php artisan test --filter=SalidaIdempotenciaTest
```

Casos cubiertos:
- **C1**: primer POST crea 1 salida + 1 fila `idempotency_keys` con `resource_id`.
- **C2**: segundo POST con el mismo token → misma tarja, sin nueva salida, sin doble descuento, sin avanzar consecutivo.
- **C3**: dos tokens distintos, mismo cliente → dos salidas.
- **C4**: token ausente/ inválido → error de validación, nada creado.
- **C5**: saldo insuficiente → transacción revertida, token queda libre.

## Notas de despliegue

- **Sin dependencias nuevas** (hosting compartido sin SSH): solo 1 migración.
- Incluir la nueva tabla en el procedimiento de dump/migración de producción (ver memoria `sql_dump_actualizar_procedimiento`: agregar migración pendiente + prelude `DROP TABLE IF EXISTS idempotency_keys` import-safe).
- La limpieza histórica de la ODC-621 duplicada es un paso aparte (SQL manual), fuera de esta feature.
