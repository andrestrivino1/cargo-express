# UI / HTTP Contract — Pantalla de edición de Ingreso

Esta feature no expone una API pública; su "contrato" es el de la pantalla de edición server-rendered (Blade) y sus rutas HTTP. Documenta la interfaz que el operador y el navegador consumen.

## Rutas (existentes — sin cambios de ruta)

| Método | URI | Nombre | Middleware | Cambio |
|---|---|---|---|---|
| GET | `/ingreso/{ingreso}/editar` | `ingreso.editar` | `permission:ingreso.ver` + `role:administrador\|coordinador` | El controlador `edit` ahora eager-loadea referencias y fotos. |
| PUT | `/ingreso/{ingreso}` | `ingreso.update` | `permission:ingreso.ver` + `role:administrador\|coordinador` | Acepta `multipart/form-data`: campos actuales + `fotos[]` + `nueva_referencia[...]`. |

> Alternativa opcional (solo si el repeater inline complica la vista): `POST /ingreso/{ingreso}/referencias` (`ingreso.referencias.store`) dedicado para agregar referencia. Por defecto se integra en el `update`.

## GET `ingreso.editar` — datos que la vista recibe

```
$ingreso        // con: contenedores.referencias.producto, contenedores.referencias.ubicacionPatio, fotos
$clientes        // colección de usuarios rol 'cliente'
// para el bloque "agregar referencia":
$ingreso->contenedores   // para el selector de contenedor destino
$ubicaciones             // UbicacionPatio activas (opcional, para ubicación de la referencia nueva)
```

### Render esperado (regiones de la pantalla)
1. **Aviso BL provisional** (existente) si `bl_por_confirmar`.
2. **Campos editables** (existente): BL, Cliente, Fecha de ingreso.
3. **Referencias del BL** (NUEVO, solo lectura): tabla/lista agrupada por contenedor (`numero`), cada referencia muestra `codigo`, `descripcion` (o producto), `cantidad_actual`/`cantidad_inicial`, `unidad_medida`, ubicación si existe. Si no hay referencias → mensaje "Sin referencias".
4. **Imágenes del BL** (NUEVO): galería de `$ingreso->fotos` (miniaturas vía `Storage::url($foto->ruta)`); input `fotos[]` múltiple para agregar.
5. **Agregar referencia** (NUEVO, opcional): selector de contenedor destino + campos de la referencia nueva. Deshabilitado con aviso si `$ingreso->contenedores` está vacío.
6. **Botones**: Guardar / Cancelar.

El `<form>` DEBE declarar `enctype="multipart/form-data"`.

## PUT `ingreso.update` — request

`Content-Type: multipart/form-data`

| Campo | Requerido | Reglas |
|---|---|---|
| `bl` | sí | `string\|max:100` |
| `cliente_id` | sí | `exists:users,id` |
| `fecha_ingreso` | sí | `date\|before_or_equal:today` |
| `fotos[]` | no | cada uno `image\|mimes:jpg,jpeg,png,webp\|max:5120` |
| `nueva_referencia[contenedor_id]` | condicional | `required_with:nueva_referencia[codigo]`, `exists:contenedores,id`, debe pertenecer al ingreso |
| `nueva_referencia[codigo]` | no | `string\|max:100` (dispara el resto si está presente) |
| `nueva_referencia[descripcion]` | condicional | `required_with:codigo`, `string\|max:255` |
| `nueva_referencia[unidad_medida]` | condicional | `required_with:codigo`, `string\|max:50` |
| `nueva_referencia[cantidad]` | condicional | `required_with:codigo`, `integer\|min:1` |
| `nueva_referencia[peso]` | no | `numeric\|min:0` |
| `nueva_referencia[ubicacion_patio_id]` | no | `exists:ubicaciones_patio,id` |

### Autorización
`UpdateIngresoRequest::authorize()` → `true` solo si el usuario tiene rol `administrador` o `coordinador`. En caso contrario, `403`.

## PUT `ingreso.update` — responses

| Caso | Resultado |
|---|---|
| Éxito | `302` redirect a `ingreso.show` con flash `success`. `bl_por_confirmar=false`; fotos creadas; referencia creada (+ movimiento de inventario) si se diligenció. |
| Validación falla (BL/cliente/fecha, foto inválida o referencia incompleta) | `302` back con `errors` y `old()` input; **ninguna** escritura parcial (transacción) y se conservan los datos del formulario. |
| Sin permiso | `403`. |
| Ingreso inexistente | `404`. |

### Garantías de integridad (post-condiciones)
- Las fotos previas y referencias previas **no** se modifican ni eliminan.
- Si se crea una referencia, su `contenedor_id` pertenece al `$ingreso`; se genera el movimiento de inventario de entrada; `cliente_id` y `fecha_ingreso` se heredan del ingreso.
- Toda la operación (BL + fotos + referencia) es atómica.

## Criterios de aceptación verificables (mapeo a la spec)

| Contrato | FR |
|---|---|
| GET muestra lista de referencias del BL | FR-001, FR-002 |
| GET/PUT editan BL/cliente/fecha conservando referencias e imágenes | FR-003 |
| PUT baja `bl_por_confirmar` | FR-004 |
| `fotos[]` sube y se acumula; galería visible | FR-005, FR-006 |
| Validación de archivos sin perder datos | FR-007, FR-010 |
| `nueva_referencia` crea referencia asociada al BL | FR-008 |
| Validación de referencia incompleta | FR-009 |
| RBAC admin/coordinador | FR-011 |
| Mensaje "Sin referencias" y aún permite confirmar/subir | FR-012 |
