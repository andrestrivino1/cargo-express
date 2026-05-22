# HTTP Contracts — Importación de Inventario

**Date**: 2026-05-21
**Plan**: [../plan.md](../plan.md)
**Routing file destino**: [routes/web.php](../../../routes/web.php)

Todas las rutas se sirven bajo el grupo `web` con autenticación Laravel Breeze. Los grupos de permisos usan el middleware Spatie ya registrado en `bootstrap/app.php` (alias `role`).

---

## Grupo: Importación (rol `administrador` o `coordinador`)

Prefix `/admin/importaciones`, name prefix `importaciones.`, middleware `auth`, `role:administrador|coordinador`, `primer_login` (excluido — el admin no es un cliente auto-creado, pero el middleware se queda inerte si los flags están en false).

### POST `/admin/importaciones`

Sube el archivo y dispara el job en el modo elegido.

**Request** (`multipart/form-data`):

| Campo | Tipo | Reglas | Notas |
|---|---|---|---|
| `archivo` | file | required, mimes:xlsx, max:51200 (KB) | El Excel a procesar |
| `modo` | string | required, in:`validar`,`importar` | (R2) |
| `politica_duplicados` | string | required_if:modo,importar, in:`omitir`,`actualizar_saldo`,`abortar` | (R7) |
| `fecha_corte` | date | nullable, formato `Y-m-d` | Default 2026-02-27 |
| `confirmar_clientes_autocreados` | boolean | required_if:modo,importar | El reporte previo de validación debe haber listado los clientes a auto-crear; el admin confirma explícitamente |

**Response 202 Accepted**:

```json
{
  "import_batch_id": 12,
  "estado": "pendiente",
  "modo": "validar",
  "url_seguimiento": "/admin/importaciones/12"
}
```

**Errores**:
- 422 con detalle por campo si validación falla.
- 413 si el archivo excede 50 MB (servidor PHP devuelve antes del controller).

---

### GET `/admin/importaciones`

Listado paginado de batches recientes.

**Query params**: `page` (int), `estado` (filtro opcional), `modo` (filtro opcional), `usuario_id` (filtro opcional, solo admin).

**Response 200**: Vista HTML `importacion.index` con paginator de 15 batches.

---

### GET `/admin/importaciones/{batch}`

Detalle del batch + resumen (sirve también como endpoint de polling).

**Response 200**: Vista HTML `importacion.reporte` con:
- Cabecera: `archivo_nombre`, `modo`, `estado`, `started_at`, `finished_at`, `usuario.name`.
- Contadores: `total_filas`, `importables`, `errores`, `advertencias`, `ignoradas`, `clientes_autocreados`, `contenedores_creados`, `referencias_creadas`, `despachos_historicos_creados`.
- Tabla agrupada por `hoja` con conteo por `estado` y `tipo`.
- Sección "Clientes a auto-crear" / "Auto-creados" con preview del email placeholder.
- Sección "Conflictos de contenedor" (`CONTENEDOR_CONFLICTO_CLIENTE`).
- Sección "Hojas ignoradas" (`HOJA_VACIA`, `HOJA_COPIA`, `HOJA_DUPLICADA`).
- Botones: "Descargar reporte (Excel)", "Descargar reporte (PDF)", "Descargar filas en error", y si es dry-run y no hay errores bloqueantes: **"Confirmar e importar"** (POST a `/admin/importaciones` con `modo=importar` reutilizando el hash y conservando el archivo).

**Header HTTP** adicional para polling JSON: si la request manda `Accept: application/json`, responde con el mismo payload en JSON (estado + contadores) — usado por el frontend para refrescar mientras `estado='procesando'`.

---

### GET `/admin/importaciones/{batch}/reporte.xlsx`

Descarga el reporte de validación en Excel (Maatwebsite `Excel::download(new ReporteValidacionExport($batch))`).

### GET `/admin/importaciones/{batch}/reporte.pdf`

Descarga el reporte en PDF (DomPDF).

### GET `/admin/importaciones/{batch}/errores.xlsx`

Descarga las filas en error replicando la estructura de columnas del Excel original más una columna `_motivo` al final (FR-013, SC-005).

### POST `/admin/importaciones/{batch}/cancelar`

Cancela un batch en estado `pendiente`. Devuelve 409 si está en otro estado.

---

## Grupo: Pendientes de completar (cualquier rol operativo)

Prefix `/pendientes`, middleware `auth`, `primer_login`. Los policies determinan qué `pendienteable_type` puede ver cada rol.

### GET `/pendientes`

**Query params**: `tipo` (filtro: `contenedor`, `orden-servicio`, `tarja`, `orden-cargue`, `solicitud`), `import_batch_id` (filtro).

**Response 200**: Vista HTML `pendientes.index` con paginator de 25 registros, ordenado por `prioridad DESC, created_at ASC`. Cada fila muestra: tipo, identificador funcional (número de contenedor, nro de tarja, etc.), cliente, campos faltantes (chips), botón "Completar".

### GET `/pendientes/{type}/{id}/completar`

Renderiza el formulario específico (`pendientes.completar.contenedor` etc.) prellenado con lo que se tenga y con inputs solo para los campos en `campos_pendientes`. El formulario incluye una sección read-only de contexto (archivo origen, fila Excel) tomada del `ImportRowResult` enlazado.

### POST `/pendientes/{type}/{id}/completar`

**Request**: form con los campos solicitados, validado por un FormRequest dedicado por tipo.

**Comportamiento**: 
1. Aplica los valores al modelo objetivo.
2. Marca el `ImportPendingRecord` como `completado_at = now()`, `completado_por_id = auth()->id()`.
3. Si quedan otros `ImportPendingRecord` vivos sobre la misma entidad, redirige al siguiente.
4. Si no quedan, redirige al detalle natural del modelo con flash `"Registro completado"`.

**Response 200/302** (Laravel típico).

---

## Grupo: Primer login forzado (cualquier usuario con flags activos)

Prefix `/primer-login`, middleware `auth` (sin `primer_login` para evitar loops). Aplicable solo si `user.requiere_cambio_password=true` OR `user.email_placeholder=true`.

### GET `/primer-login/password`

Renderiza formulario "Cambia tu contraseña" si `requiere_cambio_password=true`. Si no, redirige a `/primer-login/email` o `/`.

### POST `/primer-login/password`

**Request**:

| Campo | Reglas |
|---|---|
| `password_actual` | required (acepta la password genérica) |
| `password_nueva` | required, confirmed, min 8, mixedCase, numbers, symbols (regla `Password::defaults()`) |

**Comportamiento**: actualiza la password, setea `requiere_cambio_password=false` y `password_actualizada_at=now()`, redirige a `/primer-login/email` si `email_placeholder=true`, sino a `/`.

### GET `/primer-login/email`

Formulario "Actualiza tu email" si `email_placeholder=true`.

### POST `/primer-login/email`

**Request**:

| Campo | Reglas |
|---|---|
| `email` | required, email, unique:users,email,{auth()->id()} |

**Comportamiento**: setea `email`, `email_placeholder=false`, `email_verified_at=null` (dispara reverificación por correo, opcional). Redirige a `/`.

---

## Middleware contract: `primer_login`

Alias registrado en `bootstrap/app.php`. Cuando se aplica a una ruta:

| Condición del usuario | Comportamiento |
|---|---|
| `requiere_cambio_password=true` | Redirige a `/primer-login/password` (excepto si la request actual ya es a `/primer-login/password` o a logout) |
| `email_placeholder=true` (y password ya OK) | Redirige a `/primer-login/email` |
| ambos `false` | `next($request)` — flujo normal |

---

## Notification contract: `ImportacionFinalizada`

Canal: `database` (notification center del admin) + `mail` opcional.

**Payload**:

```php
[
    'import_batch_id' => 12,
    'archivo_nombre' => 'INVENTARIO TOTAL CONTROLCARGA 27022026.xlsx',
    'modo' => 'importar',
    'estado_final' => 'completado',
    'resumen' => [
        'total_filas' => 20870,
        'importables' => 19234,
        'errores' => 1500,
        'advertencias' => 136,
        'ignoradas' => 0,
        'contenedores_creados' => 412,
        'referencias_creadas' => 19234,
        'clientes_autocreados' => 8,
        'despachos_historicos_creados' => 7423,
    ],
    'url' => '/admin/importaciones/12',
]
```
