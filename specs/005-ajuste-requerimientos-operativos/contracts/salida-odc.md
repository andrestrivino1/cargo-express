# Contrato HTTP: Módulo de Salida de Mercancía + Orden de Salida (ODC)

Rutas server-side, protegidas por RBAC (rol despachador/operativo/coordinador/administrador) y middleware `ModuloVisible:salida`.

## `GET /salida`
- **Propósito**: Listar salidas registradas (paginado), con su consecutivo ODC.
- **Permiso**: `salida.ver`.

## `GET /salida/crear`
- **Propósito**: Formulario consolidado de salida.
- **Permiso**: `salida.crear`.
- **Respuesta**: vista `salida.create`; al elegir cliente, lista referencias con `cantidad_actual > 0`.

## `POST /salida`
- **Propósito**: Registrar la salida, descontar inventario y generar el ODC (FR-007..FR-012).
- **Permiso**: `salida.crear`.
- **Request** (`StoreSalidaMercanciaRequest`):

| Campo | Regla |
|---|---|
| `cliente_id` | required, exists:users,id |
| `fecha_salida` | required, date |
| `conductor` | required, string, max:150 |
| `conductor_cedula` | nullable, string, max:20 |
| `placa_vehiculo` | required, string, max:20 |
| `transportador` | required, string, max:150 |
| `destino` | required, string, max:150 |
| `observaciones` | nullable, string |
| `detalles` | required, array, min:1 |
| `detalles.*.referencia_id` | required, exists:referencias,id |
| `detalles.*.cantidad` | required, integer, min:1 |
| `foto_mercancia` | required, image, mimes:jpg,jpeg,png, max:10240 |
| `foto_conductor` | required, image, mimes:jpg,jpeg,png, max:10240 |

- **Comportamiento** (`SalidaMercanciaService::registrar`): en transacción con `lockForUpdate()` por referencia →
  1. valida `cantidad <= cantidad_actual` (si no → 422 con saldo disponible, FR-011);
  2. `decrement('cantidad_actual')`;
  3. registra movimiento `salida` (saldo_resultante, usuario, timestamp, doc = Tarja);
  4. asigna `consecutivo_odc = ConsecutivoService::siguiente('odc')`;
  5. guarda `foto_mercancia` y `foto_conductor` como `Photo` con `categoria`;
  6. marca `OrdenCargue.estado = completada`.
- **Respuesta**: redirect a `salida.show` (o descarga directa del ODC) con mensaje de éxito.
- **Errores**: 422 (campos faltantes, fotos faltantes, saldo insuficiente); 403; 404 (módulo oculto).

## `GET /salida/{tarja}`
- **Propósito**: Ver detalle de la salida y evidencias (FR-008/FR-009).
- **Permiso**: `salida.ver`.

## `GET /salida/{tarja}/orden-salida.pdf`
- **Propósito**: Generar/descargar la **Orden de Salida (ODC)** (FR-013..FR-017).
- **Permiso**: `salida.ver`.
- **Contenido del documento** (verificable, SC-004):
  - Encabezado: razón social (config empresa), título "ORDEN DE SALIDA", `ODC-###` (consecutivo), Cliente, NIT del cliente, Fecha de salida, logo.
  - Tabla "Detalle de la carga": filas (Contenedor, Descripción, Observaciones, Cantidad) + Total de unidades.
  - "Datos del conductor y vehículo": Nombre, Cédula, Placa, Transportador, Destino.
  - Fotos: foto del conductor y foto de la carga embebidas.
  - Firmas: espacios "Firma conductor" y "Firma empresa".
- **Respuesta**: `application/pdf`.
