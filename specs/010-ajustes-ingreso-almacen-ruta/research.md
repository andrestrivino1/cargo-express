# Phase 0 — Research & Decisiones técnicas

**Feature**: 010-ajustes-ingreso-almacen-ruta
**Fecha**: 2026-09-25

La especificación llegó sin marcadores `[NEEDS CLARIFICATION]`: las tres preguntas abiertas se resolvieron en `/speckit.clarify` (Q1 = ajustar lo disponible por la misma diferencia, Q2 = retiro conservando historial, Q3 = cerrar el registro público). Este documento resuelve las decisiones **técnicas** que se derivan de esas respuestas.

---

## D-001 — El retiro se implementa con `SoftDeletes`, no con una bandera

**Decisión**: `Referencia` usa el trait `SoftDeletes` de Laravel (`deleted_at`), más una columna propia `retirado_por`.

**Rationale**:

El inventario se consulta desde muchos sitios. El barrido del código encontró **~20 puntos** que construyen consultas sobre `Referencia`:

| Dónde | Qué hace |
|-------|----------|
| `InventarioService::consultarInventario` | Listado de almacenamiento |
| `InventarioService::exportarInventarioPdf` | Exportable PDF |
| `InventarioExport` / `Sheets\ResumenSheet` | Exportable Excel |
| `ReporteService` (2 consultas) | Reportes de inventario y ocupación |
| `AlmacenamientoController::ubicar` | Referencias sin ubicación |
| `SalidaMercanciaController` / `SalidaMercanciaService` | Selección de mercancía para la ODC |
| `TransferenciaController` (2) / `TransferenciaService` (2) | Origen y destino de transferencias |
| `VaciadoService`, `EntregaController`, `EntregaService` | Vaciado y entregas |
| `StoreTarjaRequest` | Validación de la tarja |
| `DashboardController` (2) | Contadores |

Con una columna booleana (`retirada`), cada uno de esos puntos tendría que agregar `->where('retirada', false)`. Basta olvidar uno para que mercancía retirada reaparezca en un exportable del cliente (viola FR-014) o siga siendo seleccionable en una orden de salida (viola FR-015). Es un riesgo que crece con cada feature futura.

`SoftDeletes` invierte el problema: la exclusión vive en el modelo y aplica sola a las 20 consultas y a las que vengan. Además, `Referencia::findOrFail()` y `::find()` —que es como los flujos de salida, transferencia, vaciado y tarja resuelven la referencia— empiezan a fallar o devolver `null` sobre una referencia retirada **sin escribir una línea**, cubriendo FR-015 por construcción.

Es también lo que pide el principio II de la constitución: adoptar la convención del framework antes que inventar uno propio.

**Alternativas consideradas**:

| Alternativa | Por qué se descarta |
|-------------|---------------------|
| Columna booleana `retirada` + filtro en cada consulta | 20 puntos de aplicación, uno por olvidar. Es exactamente la duplicación que prohíbe el principio IV |
| Columna `retirada_at` + *global scope* propio | Mismo efecto que `SoftDeletes` pero escrito a mano, sin `withTrashed()`, `restore()` ni soporte de la comunidad. Reinventar una rueda que el framework trae |
| Mover la fila a una tabla histórica `referencias_retiradas` | Rompe las claves foráneas de `movimientos_inventario`, `tarja_detalles`, `novedades` y `transferencias`. Contradice directamente la decisión Q2 de conservar el historial |
| Borrado físico con copia del registro en la auditoría | Es la opción C que el usuario **no** eligió: perdería el respaldo de salidas y transferencias pasadas |

**Consecuencias que hay que atender (no son opcionales)**:

1. **Dos borrados físicos existentes dejarían de serlo.** `IngresoMercanciaService::eliminar` (`Referencia::whereIn('id', $ids)->delete()`) y `PendientesCompletarController` (`Referencia::where('contenedor_id', $dup->id)->delete()`) pasarían a soft-delete, dejando filas apuntando a contenedores ya borrados. **Ambos deben usar `forceDelete()`.** Es el mayor riesgo de regresión de la feature y lleva test propio.
2. **Seis relaciones necesitan `withTrashed()`**: `MovimientoInventario::referencia`, `TarjaDetalle::referencia`, `Novedad::referencia`, `Transferencia::referenciaOrigen`, `Transferencia::referenciaDestino`, `ImportRowResult::referencia`. Sin eso, el reporte de movimientos y la trazabilidad mostrarían filas con la referencia vacía justo después de un retiro — lo contrario de "el historial se conserva" (FR-013, SC-005).
3. **`ProductoController::destroy`** bloquea borrar un producto del catálogo si `$producto->referencias()->exists()`. Con soft deletes, las retiradas dejan de contar y el producto pasa a ser borrable. Es el comportamiento correcto (no hay mercancía vigente que lo use) y se documenta como cambio esperado, no como efecto colateral.

---

## D-002 — Tres tipos nuevos de movimiento, cero migraciones de esquema en el ledger

**Decisión**: agregar a `MovimientoTipo` los casos `AjustePositivo` (`ajuste_positivo`), `AjusteNegativo` (`ajuste_negativo`) y `Baja` (`baja`).

**Rationale**:

- La columna es `$table->string('tipo', 20)`, **no** un `enum` de base de datos. Agregar casos es un cambio solo de código: ningún `ALTER TABLE` sobre una tabla viva en producción.
- `movimientos_inventario.cantidad` es `unsignedInteger`, así que un delta negativo no cabe: la dirección tiene que ir en el tipo. De ahí que el ajuste se parta en dos casos en vez de uno.
- Los reportes de **Ingresos** y **Salidas** (`ReporteController::ingresos`/`salidas`) filtran por tipo exacto (`->where('tipo', $tipo)`), de modo que los tipos nuevos **no los contaminan**: una corrección de cantidad no se cuenta como mercancía ingresada. Aparecen únicamente en el reporte general de Movimientos, que es donde deben verse.
- `IngresoMercanciaService::bloqueosParaEliminar` cuenta movimientos `!= Entrada` para bloquear el borrado de un ingreso. Los tipos nuevos entran en ese conteo automáticamente y **eso es lo correcto**: un ingreso cuya mercancía ya fue corregida o dada de baja no debe poder borrarse en silencio.

**Alternativas consideradas**:

| Alternativa | Por qué se descarta |
|-------------|---------------------|
| Un solo tipo `ajuste` con `cantidad` con signo | Exige alterar la columna a entero con signo en MySQL 8 y MariaDB en producción, sin SSH, para no ganar nada |
| Reutilizar `entrada`/`salida` con `observaciones` explicativas | El reporte de Ingresos pasaría a incluir correcciones de digitación como si fuera mercancía que llegó. Rompe SC-003 de facto |
| Un solo tipo `ajuste` y deducir el signo comparando `saldo_resultante` con el movimiento anterior | Obliga a leer la fila previa para interpretar cada fila. Ilegible y frágil |

---

## D-003 — El invariante de cantidades: consumido = `cantidad_inicial` − `cantidad_actual`

**Decisión**: "lo ya consumido" de una referencia se calcula como `cantidad_inicial - cantidad_actual`, sin consultar `tarja_detalles`, `transferencias` ni `novedades`.

**Rationale**: todo lo que descuenta mercancía (salidas, transferencias, novedades de vaciado) ya lo hace bajando `cantidad_actual`. La diferencia contra `cantidad_inicial` es, por construcción, la suma de todo lo consumido, venga de donde venga. Consultar las tres tablas daría el mismo número con tres joins de más y con el riesgo de olvidar una fuente futura.

De ahí salen las dos reglas de la US1:

```text
consumido       = cantidad_inicial_actual − cantidad_actual_actual
nueva_disponible = nueva_declarada − consumido
rechazar si       nueva_declarada < consumido        (FR-004)
delta             = nueva_declarada − declarada_anterior  → tipo de ajuste
```

Verificado contra el escenario 5 del spec: declarada 5, disponible 3 ⇒ consumido 2; corregir a 8 ⇒ disponible 6, delta +3, movimiento `ajuste_positivo` de 3 con `saldo_resultante` 6. Y contra el escenario 6: corregir a 1 con consumido 2 ⇒ rechazo.

**Alternativa considerada**: contar unidades en `tarja_detalles` para el mínimo. Descartada: dejaría fuera lo consumido por transferencias y novedades, y permitiría corregir por debajo del disponible real.

---

## D-004 — La corrección viaja en el `PUT` del ingreso que ya existe

**Decisión**: el formulario de `ingreso/editar` envía `referencias[<id>] = <cantidad>`; `UpdateIngresoRequest` las valida y `IngresoMercanciaService::actualizar` las aplica dentro de la transacción que ya abre.

**Rationale**:

- El partial `_referencias.blade.php` **ya está dentro** del `<form>` de `editar.blade.php` (línea 55, entre `<form>` en 27 y `</form>` en 65): convertir la celda de cantidad en un `input` basta para que viaje en el mismo envío. Sin formulario nuevo, sin endpoint nuevo.
- El requisito de "todo o nada" (FR-003, edge case de guardado parcial) sale gratis: `actualizar()` ya corre dentro de `DB::transaction` y la validación del FormRequest es previa a cualquier escritura.
- La pertenencia de cada referencia al ingreso editado se valida en `withValidator`, replicando el control que ya existe para `nueva_referencia.contenedor_id`. Sin ese control, un `referencias[<id de otro BL>]` alteraría mercancía ajena: es el agujero de autorización más probable de esta historia.

**Alternativas consideradas**:

| Alternativa | Por qué se descarta |
|-------------|---------------------|
| Endpoint AJAX por referencia (`PATCH /referencias/{id}/cantidad`) | Superficie nueva que autorizar y testear, y rompe el "todo o nada": quedarían correcciones aplicadas a medias si falla la segunda |
| Pantalla aparte de corrección de cantidades | Un formulario más para mantener, cuando el de edición ya muestra exactamente esas filas |

---

## D-005 — La raíz del sitio: `Route::redirect('/', '/login')`

**Decisión**: reemplazar el closure actual (`if (auth()->check()) … return view('welcome')`) por una redirección fija al login, y borrar `welcome.blade.php`.

**Rationale**: el middleware `guest` que protege la ruta `login` ya redirige a `/dashboard` a quien tenga sesión (`RedirectIfAuthenticated` resuelve la ruta `dashboard` por defecto), y en `/dashboard` el middleware `primer_login` intercepta a quien deba cambiar contraseña o correo. Es decir: **la cadena de la US3 ya está construida**; solo hay que apuntar la raíz al principio de ella.

Esto cumple literalmente lo pedido —"siempre que ingresen a la URL muestre el login"— sin dejar a un usuario con sesión mirando un formulario de acceso: el propio framework lo pasa a su tablero. Costo: un 302 extra para el usuario autenticado, irrelevante frente a duplicar en la raíz una decisión que el middleware ya toma.

**Alternativa considerada**: conservar la rama `auth()->check()` en la raíz y redirigir a `dashboard` directamente. Ahorra un redirect, pero duplica lógica del framework en código propio (principio IV) y vuelve a dejar dos caminos que mantener sincronizados.

---

## D-006 — El registro público se cierra por rutas, no escondiendo el enlace

**Decisión**: eliminar las dos rutas `register` de `routes/auth.php`, junto con `RegisteredUserController`, `auth/register.blade.php` y `tests/Feature/Auth/RegistrationTest.php`.

**Rationale**:

- `layouts/app.blade.php:181` envuelve el enlace en `@if (Route::has('register'))`: al quitar las rutas, el enlace desaparece **solo**, sin tocar la vista. La vista ya estaba escrita para este caso.
- Quitar la ruta y no solo el enlace es lo que cumple FR-024 y el escenario 5 de la US3 (acceso directo por URL).
- `RegistrationTest` prueba una funcionalidad que deja de existir: mantenerlo haría fallar la suite. Se elimina con el resto.
- El alta de usuarios soportada sigue siendo `UserController` (módulo Usuarios, solo administrador), con el primer login forzado de la feature 009 encima.

**Alternativa considerada**: dejar las rutas y protegerlas con un middleware que siempre rechace. Más código para lograr menos; el registro no tiene ningún uso previsto.

---

## D-007 — El permiso `inventario.retirar` viaja como migración idempotente

**Decisión**: migración `2026_09_25_000002_crear_permiso_retirar_inventario.php` siguiendo el patrón de `2026_07_27_000010_crear_permiso_eliminar_ingreso.php`, concediendo el permiso a `administrador` y `coordinador`. El seeder se actualiza en paralelo para instalaciones nuevas.

**Rationale**:

- **No hay SSH en producción**: `db:seed` no es ejecutable allí, pero las migraciones sí corren por el flujo de despliegue ya usado en las features 008 y 009. Un permiso que solo viva en el seeder nunca llegaría a producción.
- Se modela como **permiso** y no como `role:` en la ruta para poder concederlo o quitarlo (por ejemplo, dárselo a `supervisor`) sin tocar código, igual que se hizo con `ingreso.eliminar`.
- Los roles elegidos son exactamente los que hoy pueden editar referencias en inventario (`role:administrador|coordinador` en `inventario.editar`), que es el alcance que fijó la especificación. `coordinador` está retirado de circulación pero conserva sus permisos, así que incluirlo mantiene la coherencia con las instalaciones existentes.
- La migración es idempotente (`firstOrCreate` + `givePermissionTo` sobre rol existente) y limpia la caché de permisos antes y después, como las anteriores.

**Alternativa considerada**: reutilizar `inventario.ubicar`. Descartada: lo tienen `supervisor` y `operador`, que no deben poder dar de baja mercancía, y mezclaría dos capacidades de riesgo muy distinto.

---

## D-008 — Al retirar, el disponible se lleva a cero con un movimiento `baja`

**Decisión**: `InventarioService::retirar` registra un movimiento `baja` por las unidades disponibles al momento del retiro, deja `cantidad_actual` en 0 y luego aplica el soft delete.

**Rationale**: FR-016 pide que las existencias del cliente sigan cuadrando con la suma de sus movimientos. Si la referencia se retirara conservando su `cantidad_actual`, el ledger diría que el cliente tiene unidades que ya no están en ningún listado. Con la baja explícita, la suma de movimientos y el inventario visible coinciden, y además cualquier consulta que en el futuro olvidara el alcance de soft delete mostraría 0 en vez de existencias fantasma: es una segunda línea de defensa gratis.

`cantidad_inicial` **no se toca**: sigue siendo lo que se declaró al ingresar, que es lo que da sentido al historial.

**Alternativa considerada**: dejar `cantidad_actual` intacta y confiar solo en el alcance del soft delete. Descartada por lo anterior: una sola consulta mal escrita reintroduciría stock fantasma.

---

## Riesgos y mitigaciones

| Riesgo | Impacto | Mitigación |
|--------|---------|------------|
| Olvidar `forceDelete()` al eliminar un ingreso | Referencias huérfanas apuntando a contenedores borrados; el inventario podría "resucitar" con `withTrashed()` | Test de regresión explícito en `InventarioRetiroTest`: tras eliminar un ingreso, `Referencia::withTrashed()` no devuelve nada |
| Olvidar `withTrashed()` en una relación histórica | El reporte de movimientos o la trazabilidad muestran filas vacías tras un retiro (rompe SC-005) | Las 6 relaciones están enumeradas en el plan y en `data-model.md`; test que retira una referencia con historial y verifica que sus movimientos siguen resolviendo |
| El filtro "incluir retiradas" se cuela al rol cliente | Un cliente vería mercancía dada de baja | El filtro se condiciona al permiso `inventario.retirar` en el controlador, no al parámetro del request; caso negativo cubierto en test |
| Corregir cantidades de un ingreso ajeno vía `referencias[<id>]` | Alteración de mercancía de otro BL | Validación de pertenencia en `withValidator` + test con id de otro ingreso |
| Despliegue en producción sin `public/build` | El login se ve sin estilos, como hoy la página `welcome` | Recordatorio explícito en `quickstart.md`: el despliegue incluye `public/build` |
| La suite falla por `RegistrationTest` | Pipeline rojo por una funcionalidad eliminada a propósito | Se elimina el test junto con la funcionalidad, en el mismo commit |
