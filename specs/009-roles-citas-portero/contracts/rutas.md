# Contrato de rutas HTTP — Feature 009

**Feature**: `009-roles-citas-portero` | **Fecha**: 2026-07-27

Todas las rutas viven dentro del grupo autenticado de `routes/web.php` (`auth` + `primer_login`), siguiendo la estructura de los grupos existentes.

---

## Módulo Citas

Grupo: `Route::prefix('citas')->name('citas.')->middleware(['modulo:citas', 'permission:citas.ver'])`

| Método | URI | Nombre | Middleware adicional | Controlador | Requisito |
|--------|-----|--------|---------------------|-------------|-----------|
| GET | `/citas` | `citas.index` | — | `CitaController@index` | FR-007 |
| GET | `/citas/crear` | `citas.create` | `permission:citas.crear` | `CitaController@create` | FR-001 |
| POST | `/citas` | `citas.store` | `permission:citas.crear` | `CitaController@store` | FR-001, FR-002, FR-003 |
| GET | `/citas/{cita}` | `citas.show` | — | `CitaController@show` | FR-007 |
| GET | `/citas/{cita}/editar` | `citas.editar` | `permission:citas.editar` | `CitaController@edit` | FR-008 |
| PUT | `/citas/{cita}` | `citas.update` | `permission:citas.editar` | `CitaController@update` | FR-008, FR-009 |
| POST | `/citas/{cita}/cancelar` | `citas.cancelar` | `permission:citas.editar` | `CitaController@cancelar` | FR-008 |
| GET | `/citas/ingreso/{ingreso}/contenedores` | `citas.contenedores` | `permission:citas.crear` | `CitaController@contenedoresDeIngreso` | FR-002 |

**Contrato de `citas.index`**

- Filtros aceptados por query string: `fecha_esperada`, `bl`, `numero_contenedor`, `placa`, `estado` (FR-007).
- Resultado paginado (15 por página, como los demás listados del proyecto).
- Cada fila muestra el **estado efectivo**, no el persistido: una cita `programada` con `fecha_esperada` anterior a hoy se muestra como **Vencida** (D-001).

**Contrato de `citas.store`**

- Éxito → `302` a `citas.show` con `success` en sesión.
- Validación fallida → `302` de vuelta con `errors` y `old input` conservado (FR-003).
- Si el contenedor ya tiene una cita en estado `programada`, se guarda igual pero la respuesta incluye un mensaje de advertencia en sesión (`warning`) — advertir sin bloquear (FR-011).
- Si `contenedor_id` no pertenece a `ingreso_id`, error de validación en el campo `contenedor_id`.

**Contrato de `citas.update` / `citas.cancelar`**

- Sobre una cita en estado `atendida` → `403`. Una cita atendida no se edita ni se cancela (FR-008).
- Sobre una cita `vencida` (efectiva), editar `fecha_esperada` es válido y equivale a reprogramar (FR-010).

**Contrato de `citas.contenedores`**

- Devuelve JSON con los contenedores del ingreso indicado, para poblar el selector dependiente del formulario: `[{id, numero, tipo_mercancia, tiene_cita_programada}]`.
- Es el único endpoint JSON de la feature; el resto son vistas Blade, como el resto del proyecto.

---

## Módulo Portero

Grupo: `Route::prefix('porteria')->name('porteria.')->middleware(['modulo:porteria', 'permission:porteria.ver'])`

| Método | URI | Nombre | Middleware adicional | Controlador | Requisito |
|--------|-----|--------|---------------------|-------------|-----------|
| GET | `/porteria` | `porteria.index` | — | `PorteriaController@index` | FR-014, FR-025 |
| GET | `/porteria/{cita}` | `porteria.show` | — | `PorteriaController@show` | FR-016, FR-021 |
| POST | `/porteria/{cita}/llegada` | `porteria.llegada` | `permission:porteria.registrar` | `PorteriaController@registrarLlegada` | FR-018 a FR-021 |
| GET | `/porteria/novedad/crear` | `porteria.novedad.create` | `permission:porteria.registrar` | `PorteriaController@crearNovedad` | FR-022 |
| POST | `/porteria/novedad` | `porteria.novedad.store` | `permission:porteria.registrar` | `PorteriaController@guardarNovedad` | FR-022 |

**Contrato de `porteria.index`**

- Devuelve **dos** conjuntos (FR-014): `citas` con `fecha_esperada = hoy` (accionables) y `proximas` con `fecha_esperada > hoy` (solo seguimiento). Las de fechas pasadas no aparecen bajo ninguna combinación de filtros.
- Acepta el parámetro `q` para buscar por placa o número de contenedor dentro de ese conjunto (FR-015). El término se normaliza antes de comparar (D-010).
- Si `q` no encuentra nada, la respuesta indica explícitamente "sin cita para hoy" y ofrece el enlace a reportar novedad (FR-017).
- Sin citas para hoy → mensaje explícito, no tabla vacía (FR-025).

**Contrato de `porteria.llegada`**

Entrada (multipart/form-data):

| Campo | Regla |
|-------|-------|
| `foto_vehiculo` | `required\|image\|mimes:jpg,jpeg,png\|max:10240` |
| `foto_contenedor` | `required\|image\|mimes:jpg,jpeg,png\|max:10240` |
| `foto_sello` | `required\|image\|mimes:jpg,jpeg,png\|max:10240` |
| `foto_tiquete` | `required\|image\|mimes:jpg,jpeg,png\|max:10240` |
| `observaciones` | `nullable\|string\|max:500` |

Respuestas:

| Situación | Respuesta |
|-----------|-----------|
| Éxito | `302` a `porteria.show` con `success`; cita en estado `atendida`, `llegada_at` sellada, 4 fotos guardadas |
| Falta al menos una foto | `302` back con error que **nombra la evidencia faltante** (FR-019, SC-010) |
| La cita ya está `atendida` | `403` — no se reconfirma (FR-021) |
| La cita es **futura** | `302` back con error en `cita` que indica la fecha en que se habilita (FR-014a). Visible para seguimiento, no confirmable |
| La cita es de una fecha **pasada** | `404` — fuera del alcance del módulo |
| Fallo a mitad de la subida | Transacción revertida; la cita sigue `programada`, sin registro ni fotos huérfanas (D-005) |

Todo el manejo ocurre dentro de `DB::transaction()`; el índice único en `porteria_registros.cita_id` es la red de seguridad ante envíos concurrentes (D-008).

**Contrato de `porteria.novedad.store`**

- Crea un `PorteriaNovedad`. **No** crea ni modifica ninguna cita (FR-022).
- `placa` y `numero_contenedor` son opcionales por separado pero se exige al menos uno (`required_without`).

---

## Rutas modificadas

| Ruta | Cambio | Requisito |
|------|--------|-----------|
| `ingreso.show` | La vista muestra el estado de cita de cada contenedor y las evidencias de portería si ya llegó | FR-047 |
| `inventario.index`, `inventario.export-excel`, `inventario.export-pdf` | El servicio fuerza `cliente_id` al id del usuario cuando tiene rol `cliente`, ignorando el valor del request | FR-035 |
| `admin.usuarios.create`, `admin.usuarios.edit` | El selector de roles excluye los retirados | FR-038 |
| `admin.usuarios.store`, `admin.usuarios.update` | Validación rechaza roles retirados (`not_in`) | FR-039 |
| `admin.usuarios.index` | Marca visualmente los usuarios con rol retirado | FR-042 |

**Sin cambios** en las rutas de Ingreso, Salida, Vaciado ni Transferencias: solo cambia qué roles tienen los permisos que ya se verifican allí.

---

## Contrato de navegación (sidebar)

`resources/views/layouts/app.blade.php` — cada ítem se condiciona a módulo visible **y** permiso del usuario (FR-045, D-007):

| Ítem | Condición actual | Condición nueva |
|------|-----------------|-----------------|
| Ingreso | `@if (config('modulos.ingreso'))` | `@if (config('modulos.ingreso')) @can('ingreso.ver')` |
| Salida | `@if (config('modulos.salida'))` | `@if (config('modulos.salida')) @can('salida.ver')` |
| Vaciado | `@if (config('modulos.vaciado'))` | `@if (config('modulos.vaciado')) @can('vaciado.ver')` |
| Almacenamiento | `@if (config('modulos.inventario'))` | `@if (config('modulos.inventario')) @can('inventario.ver')` |
| Transferencias | `@if (config('modulos.transferencias'))` | `@if (config('modulos.transferencias')) @can('inventario.ubicar')` |
| Productos | `@if (config('modulos.productos'))` | `@if (config('modulos.productos')) @can('inventario.ver')` |
| Trazabilidad | *(sin condición)* | `@can('reportes.ver')` |
| **Citas** | — | `@if (config('modulos.citas')) @can('citas.ver')` — **nuevo** |
| **Portero** | — | `@if (config('modulos.porteria')) @can('porteria.ver')` — **nuevo** |
| Reportes | `@role('supervisor\|gerente\|administrador')` | `@can('reportes.ver')` — deja de depender de nombres de rol |

Bloque `@role('cliente')`: conserva solo "Mi Inventario". Se retiran Orden de Cargue, Mis Entregas y Trazabilidad (FR-036).
