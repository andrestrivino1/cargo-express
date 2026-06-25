# Contrato HTTP: Módulo de Ingreso de Mercancía

Rutas server-side (Blade), protegidas por RBAC (rol operativo/coordinador/administrador) y middleware `ModuloVisible:ingreso`.

## `GET /ingreso`
- **Propósito**: Listar ingresos registrados (paginado).
- **Permiso**: `ingreso.ver`.
- **Respuesta**: vista `ingreso.index` con lista de contenedores/ingresos y sus referencias.

## `GET /ingreso/crear`
- **Propósito**: Mostrar el formulario consolidado de ingreso.
- **Permiso**: `ingreso.crear`.
- **Respuesta**: vista `ingreso.create` con selects de cliente, ubicaciones y productos.

## `POST /ingreso`
- **Propósito**: Registrar un ingreso completo en una sola operación (FR-001..FR-004).
- **Permiso**: `ingreso.crear`.
- **Request** (`StoreIngresoMercanciaRequest`):

| Campo | Regla |
|---|---|
| `bl` | required, string, max:100 |
| `numero_contenedor` | required, string, max:20 |
| `cliente_id` | required, exists:users,id |
| `tipo_mercancia` | required, string, max:100 |
| `referencias` | required, array, min:1 |
| `referencias.*.codigo` | required, string, max:100 |
| `referencias.*.descripcion` | required, string, max:255 |
| `referencias.*.unidad_medida` | required, string, max:50 |
| `referencias.*.peso` | required, numeric, min:0 |
| `referencias.*.cantidad` | required, integer, min:1 |
| `referencias.*.ubicacion_patio_id` | required, exists:ubicaciones_patio,id |
| `documento_bl` | required, file, mimes:pdf,jpg,jpeg,png, max:10240 |
| `documento_dim` | required, file, mimes:pdf,jpg,jpeg,png, max:10240 |
| `documento_lista_empaque` | required, file, mimes:pdf,jpg,jpeg,png, max:10240 |

- **Comportamiento** (`IngresoMercanciaService::registrar`): en transacción → crea/ubica `Contenedor` (bl, tipo_mercancia, cliente), crea cada `Referencia` (cantidad_inicial = cantidad_actual = cantidad, peso, ubicación), registra movimiento `entrada` por referencia, guarda los 3 documentos como `Photo` con `categoria`.
- **Respuesta**: redirect a `ingreso.index` con mensaje de éxito.
- **Errores**: 422 con errores de validación (campos faltantes); 403 sin permiso; 404 si el módulo está oculto.

## `GET /ingreso/{contenedor}`
- **Propósito**: Ver detalle del ingreso y descargar documentos (FR-002).
- **Permiso**: `ingreso.ver`.
- **Respuesta**: vista `ingreso.show` con referencias y enlaces a documentos (BL, DIM, Lista de empaque).
