# Phase 1 — Modelo de datos

**Feature**: 010-ajustes-ingreso-almacen-ruta
**Fecha**: 2026-09-25

Resumen del impacto: **ninguna tabla nueva**, 2 columnas nuevas en `referencias`, 3 valores nuevos en una columna `string` existente. Todo lo demás es comportamiento sobre datos que ya existen.

---

## 1. `referencias` — cambios de esquema

Migración `2026_09_25_000001_add_retiro_to_referencias_table.php`.

| Columna | Tipo | Nulo | Descripción |
|---------|------|------|-------------|
| `deleted_at` | `timestamp` | sí | Marca de retiro. La crea `$table->softDeletes()` (sin índice: casi todas las filas la tienen nula). Su presencia saca la referencia del inventario vigente en todas las consultas del modelo |
| `retirado_por` | `foreignId → users.id` | sí | Quién ejecutó el retiro |

```php
Schema::table('referencias', function (Blueprint $table) {
    $table->softDeletes();
    $table->foreignId('retirado_por')->nullable()->after('fecha_salida')->constrained('users');
});
```

No hay columna de motivo: el retiro se confirma, no se diligencia (ver *Assumptions* del spec). La constancia de la baja es **quién** (`retirado_por`), **cuándo** (`deleted_at`) y el movimiento `baja` en el ledger.

**Down**: elimina la clave foránea, la columna y `deleted_at`. Reversible sin pérdida de datos vigentes (las filas retiradas volverían a estar visibles, que es el estado previo a la feature).

### Columnas existentes que participan (sin cambio de tipo)

| Columna | Papel en esta feature |
|---------|----------------------|
| `cantidad_inicial` | Lo declarado al ingresar. **Es lo que edita la US1.** No se toca al retirar |
| `cantidad_actual` | Lo disponible. Se recalcula al corregir (US1) y se lleva a 0 al retirar (US2) |
| `fecha_salida` | Sin cambios. El retiro no es una salida de mercancía |

---

## 2. Modelo `Referencia`

```php
class Referencia extends Model
{
    use Auditable, SoftDeletes;   // SoftDeletes es lo nuevo

    protected $fillable = [ /* … */ 'retirado_por' ];

    public function retiradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'retirado_por');
    }
}
```

`Auditable` ya estaba: la auditoría del retiro y de las correcciones se registra con el `AuditoriaService` existente, que sabe saltarse los guardados sin cambios reales (edge case "corrección sin cambio real" del spec).

### Estados de la referencia

```text
        crearReferencia()                  InventarioService::retirar()
  (—) ───────────────────▶ [ VIGENTE ] ──────────────────────────────▶ [ RETIRADA ]
                                │                                            │
                                │ corregir cantidad (US1)                    │ (sin transición de vuelta:
                                └── se mantiene VIGENTE                       reversar el retiro está
                                                                              fuera de alcance)
```

| Estado | Cómo se reconoce | Qué se puede hacer |
|--------|------------------|--------------------|
| **Vigente** | `deleted_at IS NULL` | Aparece en inventario, exportables y vista del cliente. Seleccionable en salida, transferencia, vaciado y ubicación. Su cantidad es corregible desde el ingreso |
| **Retirada** | `deleted_at IS NOT NULL` | Fuera de todos los listados vigentes. No seleccionable en ninguna operación nueva. Su historial sigue consultable. Visible solo con el filtro explícito de almacenamiento, con responsable y fecha |

El borrado físico sigue existiendo y es otra cosa: solo ocurre al **eliminar un ingreso completo** o al consolidar duplicados en Pendientes, y usa `forceDelete()`.

---

## 3. Invariantes de cantidad

Son la regla central de la US1. Se definen aquí una sola vez y se usan tanto en la validación como en el cálculo.

```text
consumido        = cantidad_inicial − cantidad_actual        (todo lo que salió, se transfirió o se descontó por novedad)
delta            = nueva_declarada − cantidad_inicial
nueva_disponible = nueva_declarada − consumido               (equivale a cantidad_actual + delta)

REGLA 1 (FR-003)  nueva_declarada debe ser entero ≥ 1
REGLA 2 (FR-004)  nueva_declarada ≥ consumido, si no → rechazo con el número de unidades ya consumidas
REGLA 3 (FR-010)  delta = 0 → no se escribe nada: ni movimiento, ni auditoría
```

Por qué `consumido` se deriva y no se consulta: todo lo que descuenta mercancía ya baja `cantidad_actual`, así que la resta captura salidas, transferencias y novedades a la vez, y seguirá capturando cualquier mecanismo futuro (ver `research.md` D-003).

### Verificación contra los escenarios del spec

| Escenario | declarada | disponible | consumido | nueva | Resultado |
|-----------|-----------|------------|-----------|-------|-----------|
| US1-1 (sin movimientos) | 5 | 5 | 0 | 8 | 8 / 8, `ajuste_positivo` de 3 |
| US1-5 (2 ya despachadas) | 5 | 3 | 2 | 8 | 8 / 6, `ajuste_positivo` de 3 |
| US1-6 (por debajo de lo salido) | 5 | 3 | 2 | 1 | Rechazo: "ya se despacharon 2 unidades" |
| Edge (corregir a la baja) | 10 | 6 | 4 | 8 | 8 / 4, `ajuste_negativo` de 2 |
| Edge (sin cambio) | 5 | 5 | 0 | 5 | Nada escrito |

---

## 4. `movimientos_inventario` — valores nuevos, esquema intacto

`tipo` es `string(20)`: los tres valores nuevos no requieren migración.

| Caso del enum | Valor | Dirección | Cuándo se escribe | `saldo_resultante` |
|---------------|-------|-----------|-------------------|--------------------|
| `Entrada` | `entrada` | + | (existente) al crear la referencia | `cantidad_actual` tras la entrada |
| `Salida` | `salida` | − | (existente) ODC, transferencia, novedad | `cantidad_actual` tras la salida |
| `AjustePositivo` | `ajuste_positivo` | + | Corrección al alza (US1) | nueva `cantidad_actual` |
| `AjusteNegativo` | `ajuste_negativo` | − | Corrección a la baja (US1) | nueva `cantidad_actual` |
| `Baja` | `baja` | − | Retiro de la referencia (US2) | `0` |

- `cantidad` guarda siempre la **magnitud** del delta (la columna es `unsignedInteger`).
- `documentable` apunta al `Ingreso` en el ajuste —es el documento desde el que se corrige— y queda nulo en la baja, que no nace de un documento operativo.
- `observaciones` lleva un texto fijo en la baja (`"Retiro del inventario"`) y el texto de la corrección en el ajuste (`"Corrección de cantidad declarada: 5 → 8"`).

**Efecto en consumidores existentes** (verificado):

- `ReporteController::ingresos`/`salidas` filtran por tipo exacto → no se contaminan.
- El reporte general de Movimientos los muestra; `MovimientoTipo::label()`/`color()` se extienden para que se rendericen con nombre y color propios.
- `IngresoMercanciaService::bloqueosParaEliminar` cuenta movimientos `!= Entrada`: los tipos nuevos entran en el conteo, y eso es lo correcto —un ingreso ya corregido o con bajas no debe borrarse en silencio—.

---

## 5. Relaciones que deben mirar el historial (`withTrashed`)

Seis relaciones apuntan a `Referencia` desde registros históricos. Sin `withTrashed()` devolverían `null` en cuanto la referencia se retire, y el historial —que la decisión Q2 manda conservar— se vería vacío.

| Modelo | Relación | Qué se rompería sin el ajuste |
|--------|----------|-------------------------------|
| `MovimientoInventario` | `referencia()` | El reporte de Movimientos mostraría filas sin código ni cliente |
| `TarjaDetalle` | `referencia()` | Las órdenes de salida ya emitidas perderían el detalle (rompe SC-005) |
| `Novedad` | `referencia()` | El histórico de novedades de vaciado quedaría incompleto |
| `Transferencia` | `referenciaOrigen()` | La constancia de transferencia perdería el origen |
| `Transferencia` | `referenciaDestino()` | Ídem, el destino |
| `ImportRowResult` | `referencia()` | El resultado de una importación pasada dejaría de enlazar |

---

## 6. Permisos

| Permiso | Estado | Roles | Protege |
|---------|--------|-------|---------|
| `inventario.ver` | existente | cliente, supervisor, operador, coordinador, administrador, gerente | Listado de almacenamiento |
| `inventario.ubicar` | existente | supervisor, operador | Asignación de ubicación |
| `inventario.retirar` | **NUEVO** | administrador, coordinador | Retiro de referencias **y** visibilidad del filtro de retiradas |
| (rol `administrador\|coordinador`) | existente | — | Corrección de cantidades: hereda el control de `ingreso.editar`/`ingreso.update`, sin permiso nuevo |

El rol `cliente` no recibe `inventario.retirar` bajo ninguna circunstancia (FR-019), y su alcance de consulta sigue forzado a su propio `cliente_id` en `InventarioService::filtrosConAlcance`.

---

## 7. Entidades sin cambios

`Contenedor`, `Ingreso`, `Tarja`/`TarjaDetalle`, `Transferencia`, `Novedad`, `CambioAuditoria` y `Producto` no cambian de esquema. `CambioAuditoria` recibe registros nuevos (correcciones y retiros) por el mecanismo polimórfico que ya usa.
