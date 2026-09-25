# Contrato de rutas HTTP

**Feature**: 010-ajustes-ingreso-almacen-ruta

Las rutas marcadas **NUEVA** se agregan; las **MOD** cambian de comportamiento; las **ELIMINADA** desaparecen. Todo lo no listado queda igual.

---

## 1. Corrección de cantidades (US1)

### `PUT /ingreso/{ingreso}` — MOD

**Nombre**: `ingreso.update` · **Middleware**: `auth`, `primer_login`, `permission:ingreso.ver`, `role:administrador|coordinador` · **Sin cambios de ruta ni de middleware**: solo acepta campos nuevos.

**Payload agregado**:

| Campo | Tipo | Reglas |
|-------|------|--------|
| `referencias` | `array` | opcional |
| `referencias.{id}` | `integer` | `min:1`; la clave debe ser el id de una referencia **vigente** perteneciente a un contenedor de `{ingreso}`; el valor no puede ser menor que lo ya consumido de esa referencia |

**Respuestas**:

| Situación | Código | Resultado |
|-----------|--------|-----------|
| Correcciones válidas | 302 → `ingreso.show` | Flash de éxito; cantidades aplicadas; auditoría y movimientos de ajuste escritos |
| Alguna cantidad < 1 o no entera | 302 back + errores | **Ninguna** corrección aplicada (todo o nada) |
| Alguna cantidad < lo ya consumido | 302 back + error nombrando las unidades consumidas | Ninguna corrección aplicada |
| `referencias[<id>]` de otro ingreso | 302 back + error de validación | Ninguna corrección aplicada. **Caso negativo obligatorio en tests** |
| `referencias[<id>]` de una referencia retirada | 302 back + error de validación | El id no resuelve: la referencia no es vigente |
| Usuario sin `administrador`/`coordinador` | 403 | Sin cambios (el `authorize()` del FormRequest ya lo cubre) |
| Cantidades iguales a las actuales | 302 → `ingreso.show` | Éxito, pero **sin** auditoría ni movimientos |

---

## 2. Retiro en almacenamiento (US2)

### `DELETE /inventario/{referencia}` — NUEVA

**Nombre**: `inventario.retirar` · **Middleware**: `auth`, `primer_login`, `permission:inventario.ver`, `permission:inventario.retirar`

**Payload**: ninguno. El retiro se confirma, no se diligencia.

**Respuestas**:

| Situación | Código | Resultado |
|-----------|--------|-----------|
| Retiro válido | 302 → `inventario.index` | Flash de éxito con el código de la referencia; `deleted_at` y `retirado_por` escritos; movimiento `baja` por el disponible; `cantidad_actual` en 0; auditoría registrada |
| Referencia con historial (despachos, transferencias, vaciado) | 302 → `inventario.index` | **Procede igual** (decisión Q2): el historial se conserva intacto |
| Usuario sin `inventario.retirar` | 403 | Nada se retira. **Caso negativo obligatorio**, incluido el rol `cliente` |
| `{referencia}` ya retirada | 404 | El *route model binding* no resuelve referencias con soft delete |

### `GET /inventario` — MOD

**Nombre**: `inventario.index` · Middleware sin cambios.

| Parámetro | Tipo | Comportamiento |
|-----------|------|----------------|
| `incluir_retiradas` | `bool` | Solo se honra si el usuario tiene `inventario.retirar`; en cualquier otro caso se ignora. Cuando aplica, el listado incluye las referencias retiradas marcadas como tales, con responsable y fecha |

**Invariantes del listado**:

- Sin el parámetro, ninguna referencia retirada aparece, **para ningún rol**.
- Un usuario con rol `cliente` nunca ve retiradas, aunque envíe `incluir_retiradas=1`.
- Los totales del listado no cuentan retiradas.

### Rutas de inventario que NO cambian pero deben verificarse

| Ruta | Verificación |
|------|--------------|
| `GET /inventario/export/excel` | Excluye retiradas, siempre |
| `GET /inventario/export/pdf` | Excluye retiradas, siempre |
| `GET /inventario/ubicar` | No ofrece retiradas para ubicar |
| `POST /inventario/ubicar` | Rechaza un `referencia_id` retirado |

### Otros módulos que heredan la exclusión

Ninguno cambia de código; todos deben tener verificación de que una referencia retirada **no** es seleccionable ni procesable:

| Módulo | Ruta de entrada |
|--------|-----------------|
| Salida de mercancía | `GET/POST /salida` |
| Transferencias | `GET/POST /transferencias/entre-modulos`, `/entre-clientes` |
| Vaciado | registro de novedades |
| Entregas | armado de tarja |

---

## 3. Entrada al sitio (US3)

### `GET /` — MOD

**Antes**: closure que devolvía `view('welcome')` a invitados y redirigía a `dashboard` a los autenticados.
**Ahora**: redirección a `/login`.

| Quién entra | Cadena | Destino final |
|-------------|--------|---------------|
| Invitado | `/` → `/login` | Formulario de acceso de Cargo Express |
| Usuario con sesión | `/` → `/login` → (middleware `guest`) `/dashboard` | Tablero |
| Usuario con primer login pendiente | `/` → `/login` → `/dashboard` → (middleware `primer_login`) `/primer-login/password` | Cambio de contraseña |
| Sesión expirada | `/` → `/login` | Formulario de acceso |

### `GET /register` y `POST /register` — ELIMINADAS

| Situación | Antes | Ahora |
|-----------|-------|-------|
| `GET /register` | 200, formulario | 404 |
| `POST /register` con datos válidos | 302 + usuario creado y autenticado | 404, ningún usuario creado |
| Enlace "Registrarse" en el layout | Visible | No se renderiza: `layouts/app.blade.php` ya lo envuelve en `@if (Route::has('register'))` |

El alta de usuarios soportada sigue siendo el módulo Usuarios (`UserController`, solo administrador), con primer login forzado.

---

## 4. Matriz de autorización

| Ruta | administrador | coordinador | operaciones | supervisor | cliente | portero / citas |
|------|---------------|-------------|-------------|------------|---------|-----------------|
| `PUT /ingreso/{id}` con `referencias[]` | ✅ | ✅ | ❌ 403 | ❌ 403 | ❌ 403 | ❌ 403 |
| `DELETE /inventario/{referencia}` | ✅ | ✅ | ❌ 403 | ❌ 403 | ❌ 403 | ❌ 403 |
| `GET /inventario?incluir_retiradas=1` | ✅ ve retiradas | ✅ ve retiradas | ❌ sin acceso al módulo | ✅ acceso, parámetro ignorado | ✅ acceso propio, parámetro ignorado | ❌ sin acceso al módulo |
| `GET /` | login → tablero | login → tablero | login → tablero | login → tablero | login → tablero | login → tablero |
| `GET /register` | 404 | 404 | 404 | 404 | 404 | 404 |
