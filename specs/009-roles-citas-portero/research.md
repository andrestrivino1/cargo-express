# Phase 0 — Research: Reorganización de roles + módulos Citas y Portero

**Feature**: `009-roles-citas-portero` | **Fecha**: 2026-07-27

Todas las incógnitas del Technical Context quedaron resueltas. Cada decisión se contrastó contra el código existente antes de adoptarla.

---

## D-001: El estado "Vencida" se deriva en lectura, no se persiste

**Decisión**: `citas.estado` almacena únicamente `programada`, `atendida` y `cancelada`. El estado **Vencida** se calcula en tiempo de lectura: una cita está vencida si `estado = programada` **y** `fecha_esperada < hoy`. Se expone como accesor `estadoEfectivo()` en el modelo y como scope `scopeVencidas()`.

**Rationale**:
- El proyecto **no tiene scheduler configurado**. `routes/console.php` solo contiene el comando `inspire` de fábrica y no hay ninguna llamada a `Schedule::` en el repositorio. En hosting compartido de GoDaddy el cron de Laravel requiere configuración manual en cPanel que hoy no existe.
- Un estado derivado **nunca queda obsoleto**: es correcto en el instante en que se consulta. Un estado persistido depende de que una tarea corra puntualmente; si el cron falla un día, quedan citas "Programadas" con fecha pasada — exactamente lo que SC-008 prohíbe.
- Cumple FR-010 sin infraestructura nueva (principio V, KISS).

**Alternativas consideradas**:
- *Comando artisan + cron en cPanel*: introduce una dependencia operativa frágil y no verificable desde el código. Rechazada.
- *Marcar al vuelo con un observer al leer*: escrituras inesperadas en operaciones de lectura, con riesgo de deadlock en listados. Rechazada.
- *Columna `vencida` mantenida por la aplicación*: duplica una verdad ya contenida en `fecha_esperada` + `estado` (viola DRY). Rechazada.

---

## D-002: Los cambios de roles y permisos viajan como migración idempotente

**Decisión**: una migración `2026_07_27_000004_reorganizar_roles_y_permisos.php` crea los permisos nuevos, crea los roles `citas` y `operaciones`, y ajusta las asignaciones de `portero`, `supervisor` y `cliente`. El `RolesAndPermissionsSeeder` se actualiza en paralelo para reflejar el estado final en instalaciones limpias.

**Rationale**:
- Es el patrón ya establecido en el proyecto: `2026_06_25_000009_grant_ingreso_salida_permissions_to_roles.php` hace exactamente esto y está documentado como *"Idempotente: se puede correr varias veces sin duplicar ni romper datos"*.
- En producción **no hay SSH**; correr un seeder no es viable, pero las migraciones sí se ejecutan en el despliegue.
- El seeder usa `Role::create()` (no `firstOrCreate`), así que re-ejecutarlo sobre una base con datos falla. La migración usa `firstOrCreate` + `Role::where(...)->first()` con asignación condicional.

**Detalles de implementación**:
- `revokePermissionTo()` para quitar `ingreso.*` y `salida.*` de `portero`, y `entregas.*`/`reportes.ver`/`referencias.ver` de `cliente`.
- `forgetCachedPermissions()` al inicio y al final, como en la migración precedente.
- `down()` revierte permisos y roles nuevos sin borrar asignaciones históricas.

**Alternativas consideradas**:
- *Seeder ejecutado manualmente*: imposible sin SSH. Rechazada.
- *Editar permisos por interfaz de administración*: no existe esa pantalla y crearla excede el alcance. Rechazada.

---

## D-003: El retiro de roles se implementa con una bandera de configuración

**Decisión**: nuevo archivo `config/roles.php` con la lista de roles retirados. `UserController::create/edit` filtra el selector por esa lista y `store/update` la validan con una regla `not_in`. Los roles siguen existiendo en base de datos con todas sus asignaciones.

**Rationale**:
- Reproduce el mecanismo ya probado de `config/modulos.php`, que el proyecto usa desde la feature 005 para ocultar módulos sin borrarlos, con test de cobertura (`VisibilidadModulosTest`).
- Satisface FR-044 (reversibilidad) editando un archivo, sin migración ni pérdida de datos.
- Satisface R-004: los usuarios ya asignados **conservan sus permisos**, porque no se toca ninguna asignación en `model_has_roles`. El retiro solo afecta el selector de asignación.

**Alternativas consideradas**:
- *Columna `disponible` en la tabla `roles` de Spatie*: migrar una tabla de un paquete de terceros, con riesgo en futuras actualizaciones. Rechazada.
- *Borrar los roles*: contradice FR-040 (conservar histórico y autoría) y R-004. Rechazada.

---

## D-004: Las evidencias de portería reutilizan la tabla `photos` polimórfica

**Decisión**: las cuatro fotos se guardan en `photos` con `photoable_type = App\Models\PorteriaRegistro`, `tipo = 'foto'` y `categoria` ∈ {`vehiculo`, `contenedor`, `sello`, `tiquete`}, usando el trait `HasPhotos` y su método `guardarArchivo($archivo, $carpeta, 'foto', $categoria)`.

**Rationale**:
- La columna `categoria` ya existe (migración `2026_06_25_000007_add_categoria_to_photos_table.php`) y `guardarArchivo()` ya la acepta. **Cero código nuevo de almacenamiento**.
- El acceso a los archivos ya está resuelto por la ruta `media/` (`Photo::getUrlAttribute`), que evita el symlink de storage —problemático en hosting compartido, según el comentario en `Photo.php`.
- Cumple FR-023 (evidencias identificadas por tipo y consultables después) sin tabla nueva.

**Alternativas consideradas**:
- *Cuatro columnas de ruta en `porteria_registros`*: rígido, duplica la lógica de subida y no aprovecha `HasPhotos`. Rechazada por DRY.
- *Tabla propia de evidencias*: `photos` ya es exactamente eso, polimórfica. Rechazada por KISS.

---

## D-005: La confirmación de llegada es un POST atómico, no un borrador incremental

**Decisión**: las cuatro fotos se envían en un solo formulario. `PorteriaService::confirmarLlegada()` corre dentro de `DB::transaction()`: crea el `PorteriaRegistro`, guarda las cuatro fotos y cambia el estado de la cita. Si algo falla, no queda nada a medias. El formulario usa `capture="environment"` para abrir la cámara en móvil y `accept="image/*"`.

**Rationale**:
- Es el patrón ya usado en Salida (`StoreSalidaMercanciaRequest` exige `foto_mercancia` y `foto_conductor` como `required|image|max:10240` en un solo POST), así que se mantiene la convención (principio II).
- Un flujo de borrador incremental implicaría estados intermedios, limpieza de huérfanos y una máquina de estados que el volumen operativo (decenas de citas/día) no justifica (principio V).

**Riesgo aceptado y mitigación**: FR-024 pide conservar las evidencias ya subidas si la confirmación falla a mitad. Con POST atómico, un fallo obliga a volver a seleccionar las cuatro fotos. Se mitiga con:
1. Límite de 10 MB por archivo, igual que Salida.
2. Mensaje de error explícito que conserva el resto del formulario.
3. La cita nunca queda medio confirmada — que es la parte crítica del requisito.

Si en operación real la reconexión resulta un problema, la evolución natural es subir cada foto por separado contra el registro; queda anotado como mejora futura, no como alcance de esta feature.

---

## D-006: El scoping de cliente se aplica en `InventarioService`, no en el controlador

**Decisión**: `InventarioService::consultarInventario()` y `exportarInventario()`/`exportarInventarioPdf()` reciben el usuario autenticado y, si tiene rol `cliente`, **fuerzan** `cliente_id` a su propio id, ignorando cualquier valor que venga del request.

**Rationale**:
- Hoy existe un hueco real de seguridad: `AlmacenamientoController::index()` toma `cliente_id` directamente de `$request->only([...])` sin verificar propiedad, y lo mismo hacen `exportExcel` y `exportPdf`. Un cliente autenticado puede consultar el inventario de otro cambiando el parámetro.
- Aplicarlo en el servicio cubre **los tres puntos de entrada de una sola vez** (principio IV, DRY). Aplicarlo en el controlador obligaría a repetir la comprobación tres veces.
- Las rutas de edición individual (`inventario/{referencia}/editar`) ya están protegidas por `role:administrador|coordinador`, así que el enlace directo a un registro ajeno ya está cerrado por esa vía.

**Alternativas consideradas**:
- *Global scope en el modelo `Referencia`*: afectaría también a los flujos operativos internos (vaciado, salida, transferencias) con efectos difíciles de prever. Rechazada por riesgo.
- *Policy de Laravel*: útil para registros individuales, pero no filtra listados. Se descarta como mecanismo principal; las rutas individuales ya están cubiertas por rol.

---

## D-007: El sidebar pasa a filtrarse por permiso, no solo por visibilidad de módulo

**Decisión**: cada ítem del sidebar en `resources/views/layouts/app.blade.php` se envuelve en `@can('<permiso>')` además del `@if (config('modulos.<clave>'))` que ya tiene.

**Rationale**:
- Hoy el sidebar solo consulta `config('modulos.*')`. Un usuario del rol `citas` vería los enlaces de Ingreso, Vaciado, Salida y Almacenamiento y recibiría 403 al hacer clic — justo lo que FR-045 prohíbe.
- Los permisos ya existen y el middleware ya los verifica en las rutas; se trata de alinear la navegación con la autorización que ya está definida, sin lógica nueva.
- El bloque de cliente (`@role('cliente')`) pierde los enlaces de Entregas y Trazabilidad, coherente con FR-036.

**Alternativas consideradas**:
- *Un menú por rol, construido en PHP*: duplicaría la matriz de permisos en un segundo lugar (viola DRY) y se desincronizaría. Rechazada.

---

## D-008: Idempotencia de la llegada por restricción de unicidad, no por `idempotency_keys`

**Decisión**: `porteria_registros.cita_id` lleva un índice **único**. `PorteriaService::confirmarLlegada()` verifica el estado dentro de la transacción y la restricción de base de datos actúa como red de seguridad.

**Rationale**:
- Cumple FR-021 (una cita Atendida no se vuelve a confirmar) con una garantía de base de datos, que es más fuerte que una verificación en aplicación.
- La tabla `idempotency_keys` de la feature 008 resuelve un problema distinto: evitar que **dos envíos idénticos creen dos registros nuevos con consecutivo propio**. Aquí el registro está anclado a una cita concreta que ya existe, así que la unicidad natural basta.

**Alternativas consideradas**:
- *Reutilizar `idempotency_keys`*: maquinaria innecesaria para un caso que la unicidad resuelve. Rechazada por KISS.

---

## D-009: Tipo, tamaño y condición se modelan como enums de PHP

**Decisión**: se crean `TipoContenedor`, `TamanoContenedor`, `CitaCondicion`, `CitaEstado` y `PorteriaFotoCategoria` como enums respaldados por string en `app/Enums/`, y se castean en el modelo.

**Rationale**:
- El proyecto ya tiene 13 enums en `app/Enums/` y los castea en los modelos (`ContenedorEstado`, `NovedadTipo`, `MovimientoTipo`...). Mantener la convención (principio II).
- La constitución exige que las constantes de negocio se definan una sola vez en un módulo centralizado (principio IV).
- `contenedores.tipo` ya existe como columna libre, pero **el formulario de ingreso no la usa** (`StoreIngresoMercanciaRequest` solo pide `numero` y `tipo_mercancia`), así que no hay catálogo previo que respetar: la cita es el primer lugar donde tipo y tamaño se capturan de forma estructurada.

**Valores adoptados** (nomenclatura estándar de la industria, ajustable antes de implementar):
- Tipo: `dry`, `reefer`, `open_top`, `flat_rack`, `tank`
- Tamaño: `20`, `40`, `40hc`, `45`

---

## D-010: Normalización de placa y cédula para que la búsqueda del portero funcione

**Decisión**: `CitaService` normaliza antes de guardar — mayúsculas y sin espacios, guiones ni puntos — y guarda además el valor tal como lo digitó el usuario para mostrarlo. La búsqueda del portero normaliza el término antes de comparar.

**Rationale**:
- SC-012 exige que el portero localice la cita en menos de 15 segundos "sin importar cómo se hayan digitado esos datos". Con datos digitados desde un teléfono (`ABC-123`, `abc 123`, `ABC123`), una comparación literal falla.
- Normalizar al escribir permite que la búsqueda use un índice, en vez de una función sobre la columna que impediría usarlo (principio VII, escalabilidad).

**Alternativas consideradas**:
- *Normalizar solo al buscar, con `LIKE` sobre expresiones*: invalida el índice y degrada con el volumen. Rechazada.

---

## Resumen de incógnitas resueltas

| Incógnita del Technical Context | Resolución |
|--------------------------------|-----------|
| ¿Hay scheduler para vencer citas? | No existe. Estado derivado en lectura (D-001) |
| ¿Cómo cambiar permisos sin SSH? | Migración idempotente, patrón `2026_06_25_000009` (D-002) |
| ¿Cómo retirar roles sin borrarlos? | Bandera en `config/roles.php` (D-003) |
| ¿Dónde guardar 4 fotos por tipo? | `photos` polimórfica + `categoria` (D-004) |
| ¿Cómo manejar subidas desde móvil? | POST atómico, patrón de Salida (D-005) |
| ¿Dónde aplicar el scoping de cliente? | `InventarioService`, un solo punto (D-006) |
| ¿Cómo evitar enlaces a 403? | Sidebar con `@can` (D-007) |
| ¿Cómo evitar llegadas duplicadas? | Índice único en `cita_id` (D-008) |
| ¿Catálogo de tipo y tamaño? | Enums nuevos; no hay catálogo previo que respetar (D-009) |
| ¿Cómo buscar por placa con formato irregular? | Normalizar al escribir (D-010) |

**Ningún NEEDS CLARIFICATION pendiente.**
