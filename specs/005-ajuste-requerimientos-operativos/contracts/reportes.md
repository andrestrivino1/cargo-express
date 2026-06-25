# Contrato HTTP: Reportes

Rutas protegidas por RBAC (`reportes.ver`). Todas paginan y permiten filtros por cliente y rango de fechas.

## `GET /reportes/inventario-por-cliente`
- **FR-020**. Saldo (`cantidad_actual`) agregado por cliente y referencia. Export PDF/Excel.

## `GET /reportes/ingresos`
- **FR-021**. Movimientos `entrada` del ledger en el rango. Columnas: fecha, cliente, contenedor/BL, referencia, cantidad, responsable.

## `GET /reportes/salidas`
- **FR-021**. Movimientos `salida` del ledger. Columnas: fecha, cliente, referencia, cantidad, consecutivo ODC, responsable.

## `GET /reportes/movimientos`
- **FR-021**. Historial completo del ledger (`movimientos_inventario`), entradas y salidas, con saldo resultante. Paginado.

## `GET /reportes/novedades`
- **FR-021**. Novedades (`novedades`) por tipo, con su contenedor/referencia y responsable.

## `GET /reportes/evidencias`
- **FR-022**. Evidencias fotográficas (`photos`) y enlace a la trazabilidad del contenedor/referencia (`TrazabilidadService`).

**Contrato de datos común**: cada reporte expone filtros `cliente_id?`, `fecha_desde?`, `fecha_hasta?` y un export (`?export=pdf|excel`). Las consultas usan paginación e índices (constitución VII).
