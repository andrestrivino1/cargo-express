# Quickstart: Ajustes a ingreso y vaciado

Guía para implementar y verificar en desarrollo local. Construye sobre la feature 005.

## 1. Migraciones

```bash
php artisan migrate
```
Crea `ingresos` y agrega `ingreso_id` (nullable) a `contenedores`. (Opcional) backfill de contenedores-con-bl legados a `ingresos`.

> Para producción (cPanel sin SSH): agregar estas migraciones al dump SQL con el procedimiento habitual (CREATE TABLE `ingresos`, ALTER `contenedores` ADD `ingreso_id`, registrar en `migrations`, y prelude `DROP TABLE IF EXISTS` para reimportar).

## 2. Verificación funcional

### US1 — Ingreso multi-contenedor (P1)
1. `/ingreso/crear`: capturar BL, fecha, cliente, documentos.
2. Agregar **2 contenedores**; al primero 2 referencias, al segundo 1 referencia que **repita el código** de una del primero.
3. Guardar → en `/ingreso/{ingreso}` ambos contenedores cuelgan del mismo BL; cada referencia entra al inventario con su cantidad; el código repetido mantiene cantidades separadas por contenedor.
4. **Negativo**: guardar con un contenedor sin referencias, o dos contenedores con el mismo número → error.

### US2 — Fecha retroactiva (P2)
1. En el ingreso, elegir una fecha de **días atrás** → guardar.
2. En `/inventario` y en el reporte de ingresos la fecha mostrada es la capturada (no la de hoy).
3. **Negativo**: fecha **futura** → rechazada.

### Ubicación opcional (US1 / FR-002a)
1. Dejar una o más referencias **sin ubicación** → guardar.
2. Entran al inventario como **"sin ubicar"**; desde el módulo de inventario se les asigna ubicación después.

### US3 — Vaciado con varias fotos (P3)
1. Crear un vaciado adjuntando **2 fotos** a la vez → quedan ambas.
2. En `/vaciado/{id}` usar **"Agregar fotos"** para subir otra → se suma sin borrar las anteriores.
3. El detalle y la trazabilidad muestran todas.

## 3. Pruebas

```bash
php artisan test --filter=IngresoMultiContenedor
php artisan test --filter=IngresoFechaYUbicacion
php artisan test --filter=VaciadoFotos
```

Invariantes a verificar:
- Cada referencia de cada contenedor del ingreso entra al inventario (suma correcta).
- `referencia.fecha_ingreso` == fecha capturada; rechazo de fecha futura.
- Referencia sin ubicación → `ubicacion_patio_id` NULL, visible en inventario.
- Números de contenedor duplicados dentro del ingreso → rechazados.
- Fotos agregadas a un vaciado existente se suman a las previas.

## 4. Checklist de compatibilidad ("no romper")
- [ ] Ingresos/vaciados previos a este ajuste siguen consultables.
- [ ] Migración solo agrega (`ingresos`, `ingreso_id` nullable); ningún `dropColumn`/`dropTable`.
- [ ] Contenedores con `ingreso_id` NULL no causan errores en listados ni reportes.
