# ADR 0001 — Ledger de inventario, consecutivo ODC y visibilidad de módulos

**Estado**: Aceptada
**Fecha**: 2026-06-25
**Feature**: 005-ajuste-requerimientos-operativos

## Contexto

El instructivo de Carga Trans Xpress ajusta el sistema a cuatro capacidades (ingreso, vaciado, salida, reportes), pide una Orden de Salida (ODC) con formato específico, y exige ocultar —sin eliminar— lo que no se use. El sistema ya es un monolito Laravel con módulos por pasos (Solicitud → Gate-In, Entregas → Tarja) y descuento de inventario disperso.

## Decisiones

### 1. Ledger `movimientos_inventario`
Se introduce una tabla de movimientos (entrada/salida) con `saldo_resultante`, `usuario_id`, `created_at` y referencia polimórfica al documento origen.

- **Por qué**: fuente única para los reportes de ingresos/salidas/historial y para la trazabilidad por movimiento (FR-019/FR-021), con invariante `cantidad_actual = Σ entradas − Σ salidas` verificable (SC-002).
- **Alternativa rechazada**: derivar de `referencias` + `tarja_detalles` (no captura saldo resultante ni unifica entradas/salidas).

### 2. Consecutivo ODC con tabla `secuencias`
`ConsecutivoService::siguiente('odc')` incrementa bajo `lockForUpdate()`. Semilla 570 (la muestra es ODC-570 → siguiente 571).

- **Por qué**: consecutivo único, no reutilizable y a prueba de concurrencia (FR-013).
- **Alternativa rechazada**: `MAX(consecutivo)+1` (condiciones de carrera, se rompe al anular).

### 3. Descuento de inventario atómico
`SalidaMercanciaService` descuenta dentro de una transacción con `lockForUpdate()` por referencia, valida saldo y escribe el ledger.

- **Por qué**: impide saldos negativos en concurrencia (FR-010/FR-011) y centraliza la lógica antes dispersa en `EntregaService` (DRY/SRP).

### 4. Ocultar sin eliminar: `config/modulos.php` + middleware `modulo`
Banderas booleanas por módulo. El sidebar las consulta con `@if(config('modulos.<clave>'))`; el middleware `ModuloVisible` devuelve 404 en rutas de módulos ocultos. Nada se borra; reactivar = poner la bandera en `true`.

- **Por qué**: cumple FR-023..FR-026 (conservar datos/historial, reversible) sin tocar migraciones ni datos.
- **Nota de pruebas**: en el entorno de testing todos los módulos se fuerzan a visibles para validar que el código de los módulos ocultos sigue intacto.
- **Alternativa rechazada**: eliminar rutas/controladores (viola "no eliminar").

## Consecuencias

- 2 tablas nuevas y columnas aditivas nullable; cero borrados de esquema.
- Ingreso y Salida quedan como formularios consolidados; Solicitudes, Gate-In, Entregas/Tarja, Transferencias, Gate-Out e Importación histórica quedan ocultos pero intactos.
- Los reportes y la trazabilidad siguen leyendo datos de módulos ocultos.
- Pre-existente fuera de alcance: 15 pruebas unitarias de `Importacion` fallan por requerir el contenedor de la app (`config()`); no relacionadas con esta feature.
