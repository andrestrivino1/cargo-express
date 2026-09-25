# Contrato del ledger de inventario

**Feature**: 010-ajustes-ingreso-almacen-ruta

El ledger (`movimientos_inventario`) es la única explicación de por qué una referencia tiene el disponible que tiene. Este contrato fija qué escribe cada operación nueva y qué debe seguir cumpliéndose después.

---

## 1. Invariante maestro

Para toda referencia, vigente o retirada:

```text
cantidad_actual = Σ(movimientos de suma) − Σ(movimientos de resta)

suma  = entrada, ajuste_positivo
resta = salida, ajuste_negativo, baja
```

Es lo que verifica SC-004. Cualquier operación que toque `cantidad_actual` sin escribir su movimiento rompe el contrato.

---

## 2. Tipos y efectos

| Tipo | Dirección | `cantidad` | `saldo_resultante` | `documentable` | `observaciones` |
|------|-----------|------------|--------------------|----------------|-----------------|
| `entrada` | + | unidades declaradas | disponible tras la entrada | `Ingreso` | opcional |
| `salida` | − | unidades despachadas | disponible tras la salida | `Tarja` / `Transferencia` | opcional |
| `ajuste_positivo` **(nuevo)** | + | `delta` (magnitud) | nuevo disponible | `Ingreso` | `"Corrección de cantidad declarada: {antes} → {después}"` |
| `ajuste_negativo` **(nuevo)** | − | `delta` (magnitud) | nuevo disponible | `Ingreso` | igual que el positivo |
| `baja` **(nuevo)** | − | disponible al momento del retiro | `0` | `null` | `"Retiro del inventario"` |

`cantidad` es `unsignedInteger`: guarda siempre la magnitud, nunca un negativo. La dirección la da el tipo.

---

## 3. Escrituras por operación

### Corrección de cantidad (US1)

Por cada referencia cuyo valor cambió, dentro de la transacción del `PUT /ingreso/{id}`:

```text
1. calcular consumido = cantidad_inicial − cantidad_actual
2. validar nueva_declarada ≥ consumido            → si no, abortar TODA la transacción
3. cantidad_inicial = nueva_declarada
   cantidad_actual  = nueva_declarada − consumido
4. AuditoriaService::registrarCambios(referencia, usuario)   ← antes de save()
5. save()
6. MovimientoInventarioService::registrarAjuste(referencia, delta, usuario, ingreso, observacion)
```

- `delta = 0` → **no se ejecuta ninguno de los pasos 3 a 6** para esa referencia.
- El servicio de movimientos elige el tipo por el signo del delta; el llamador no decide el tipo (SRP).

### Retiro de referencia (US2)

Dentro de una transacción:

```text
1. disponible = cantidad_actual
2. referencia.retirado_por    = usuario.id
   referencia.cantidad_actual = 0
3. AuditoriaService::registrarCambios(referencia, usuario)   ← captura el diff completo
4. save()
5. si disponible > 0 → registrarBaja(referencia, disponible, usuario, "Retiro del inventario")
6. referencia.delete()      ← soft delete: escribe deleted_at
```

El orden importa: la auditoría se registra con el modelo aún "sucio" (es como funciona `AuditoriaService`), y el soft delete va al final para que los pasos anteriores operen sobre una referencia todavía resoluble.

---

## 4. Efectos sobre consumidores existentes

| Consumidor | Comportamiento esperado tras la feature |
|------------|------------------------------------------|
| `ReporteController::ingresos` (`tipo = entrada`) | **No** incluye ajustes ni bajas. Una corrección de digitación no se cuenta como mercancía recibida |
| `ReporteController::salidas` (`tipo = salida`) | **No** incluye ajustes ni bajas |
| Reporte general de Movimientos | Incluye los tres tipos nuevos, con etiqueta y color propios |
| `IngresoMercanciaService::bloqueosParaEliminar` (`tipo != entrada`) | Los tipos nuevos **cuentan** como bloqueo: un ingreso ya corregido o con bajas no se borra en silencio. Comportamiento deseado |
| `MovimientoTipo::label()` / `::color()` | Se extienden: `Ajuste (+)` / `Ajuste (−)` / `Baja`, con colores distinguibles de entrada y salida |

---

## 5. Casos que los tests deben recorrer

| # | Escenario | Verificación |
|---|-----------|--------------|
| L1 | Corregir 5 → 8 sin movimientos previos | 1 movimiento `ajuste_positivo` de 3, `saldo_resultante` 8; invariante maestro se cumple |
| L2 | Corregir 5 → 8 con 2 despachadas | `ajuste_positivo` de 3, `saldo_resultante` 6; el movimiento `salida` previo intacto |
| L3 | Corregir 10 → 8 con 4 despachadas | `ajuste_negativo` de 2, `saldo_resultante` 4 |
| L4 | Corregir 5 → 1 con 2 despachadas | 0 movimientos escritos; transacción abortada |
| L5 | Guardar sin cambiar cantidades | 0 movimientos, 0 registros de auditoría |
| L6 | Retirar referencia con 7 disponibles | 1 movimiento `baja` de 7, `saldo_resultante` 0, `cantidad_actual` 0 |
| L7 | Retirar referencia con 0 disponibles | Sin movimiento `baja`; el retiro procede igual |
| L8 | Retirar referencia con salidas previas | Los movimientos `salida` anteriores siguen resolviendo su referencia (`withTrashed`) |
| L9 | Reporte de Ingresos tras una corrección | El total no cambió por el ajuste |
| L10 | Eliminar un ingreso completo | `forceDelete`: no quedan filas en `referencias` ni con `withTrashed()` |
