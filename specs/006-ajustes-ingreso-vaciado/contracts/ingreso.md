# Contrato HTTP: Ingreso por BL con varios contenedores

Rutas existentes (feature 005), con el `POST /ingreso` reestructurado. RBAC `ingreso.*`.

## `GET /ingreso/crear`
- Formulario consolidado: BL, fecha de ingreso, cliente, documentos (BL/DIM/Lista), y repetidor anidado **contenedores → referencias**.
- Permiso: `ingreso.crear`.

## `POST /ingreso`
- **Propósito**: Registrar un ingreso (1 BL → N contenedores → M referencias) en una sola operación.
- **Permiso**: `ingreso.crear`.
- **Request** (`StoreIngresoMercanciaRequest`):

| Campo | Regla |
|---|---|
| `bl` | required, string, max:100 |
| `cliente_id` | required, exists:users,id |
| `fecha_ingreso` | required, date, **before_or_equal:today** |
| `documento_bl` | required, file, mimes:pdf,jpg,jpeg,png, max:10240 |
| `documento_dim` | required, file, mimes:pdf,jpg,jpeg,png, max:10240 |
| `documento_lista_empaque` | required, file, mimes:pdf,jpg,jpeg,png, max:10240 |
| `contenedores` | required, array, min:1 |
| `contenedores.*.numero` | required, string, max:20, **distinct** (único en el ingreso) |
| `contenedores.*.tipo_mercancia` | required, string, max:100 |
| `contenedores.*.referencias` | required, array, min:1 |
| `contenedores.*.referencias.*.codigo` | required, string, max:100 |
| `contenedores.*.referencias.*.descripcion` | required, string, max:255 |
| `contenedores.*.referencias.*.unidad_medida` | required, string, max:50 |
| `contenedores.*.referencias.*.peso` | required, numeric, min:0 |
| `contenedores.*.referencias.*.cantidad` | required, integer, min:1 |
| `contenedores.*.referencias.*.ubicacion_patio_id` | **nullable**, exists:ubicaciones_patio,id |

- **Comportamiento** (`IngresoMercanciaService::registrar`): en transacción → crea `Ingreso` (bl, cliente, fecha) + guarda los 3 documentos en el ingreso → por cada contenedor crea `Contenedor(ingreso_id, numero, tipo_mercancia, fecha_ingreso=fecha del ingreso)` → por cada referencia crea `Referencia(fecha_ingreso, ubicacion opcional)` + movimiento `entrada`.
- **Respuesta**: redirect a `ingreso.show` (del Ingreso) con mensaje de éxito.
- **Errores**: 422 (campos faltantes, fecha futura, números de contenedor duplicados, contenedor sin referencias); 403; 404 si el módulo está oculto.

## `GET /ingreso` y `GET /ingreso/{ingreso}`
- **index**: lista de ingresos (por BL) con su cliente, fecha, nº de contenedores y referencias.
- **show**: detalle del ingreso con sus contenedores y, dentro, sus referencias (incluye "sin ubicar"); enlaces a documentos del BL.
- Permiso: `ingreso.ver`.

> Compatibilidad: los ingresos de un solo contenedor de la feature 005 (sin `ingreso_id`) siguen consultables; opcionalmente migrados a `ingresos` por backfill.
