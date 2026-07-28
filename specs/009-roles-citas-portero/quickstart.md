# Quickstart — Feature 009: Citas y Portero

**Feature**: `009-roles-citas-portero` | **Fecha**: 2026-07-27

---

## Puesta en marcha local

```bash
git checkout 009-roles-citas-portero

# Migraciones nuevas (3 tablas + reorganización de roles)
php artisan migrate

# Limpiar caché de permisos: obligatorio tras tocar roles con Spatie
php artisan permission:cache-reset
php artisan config:clear

# Instalación limpia (base vacía)
php artisan migrate:fresh --seed
```

**Comprobar que los módulos están visibles** en `config/modulos.php`:

```php
'citas'    => true,
'porteria' => true,
```

---

## Usuarios de prueba

Crear uno por rol nuevo desde Administración → Usuarios, o vía tinker:

```php
$u = User::factory()->create(['name' => 'Ana Citas', 'email' => 'citas@test.local']);
$u->assignRole('citas');

$u = User::factory()->create(['name' => 'Beto Operaciones', 'email' => 'ops@test.local']);
$u->assignRole('operaciones');

$u = User::factory()->create(['name' => 'Carlos Portero', 'email' => 'portero@test.local']);
$u->assignRole('portero');
```

---

## Recorrido de validación manual

Sigue la cadena completa **Ingreso → Cita → Portero**. Cada paso corresponde a una historia del spec.

### 1. Preparar el ingreso (rol `operaciones`)

1. Entrar como `ops@test.local` → **Ingreso → Nuevo**.
2. Diligenciar BL, cliente, fecha, los tres documentos y al menos un contenedor con una referencia.
3. Guardar.

✅ **Verificar**: en el sidebar solo aparecen Ingreso y Salida. No hay enlaces a Citas, Portero, Vaciado ni Almacenamiento (FR-029, FR-045).

### 2. Agendar la cita (rol `citas`) — US1

1. Entrar como `citas@test.local` → **Citas → Nueva**.
2. Seleccionar el ingreso del paso 1; el selector de contenedores se puebla con los suyos.
3. Diligenciar tipo, tamaño, condición (full/vacío), fecha esperada = **hoy**, conductor, cédula, placa y empresa.
4. Guardar.

✅ **Verificar**:
- La cita aparece en el listado con estado **Programada** (US1-1).
- Intentar guardar sin un campo → el error nombra exactamente el campo faltante y conserva lo digitado (US1-2).
- Agendar otra cita para el mismo contenedor → aparece una **advertencia**, pero permite continuar (US1-4, FR-011).
- Entrar a `/salida` o `/vaciado` → **403** (US1-5).
- Crear una cita con fecha de **ayer** → se guarda y el listado la muestra como **Vencida**, sin que corra ninguna tarea programada (FR-010, D-001).

### 3. Control en portería (rol `portero`) — US2

Hazlo **desde el móvil** o con las herramientas de desarrollo en modo móvil.

1. Entrar como `portero@test.local` → **Portero**.
2. Confirmar que solo aparece la cita de **hoy**. Las de ayer y mañana no están (US2-1, SC-004).
3. Buscar por la placa — probar con guiones, espacios y minúsculas: `abc 123`, `ABC-123` (US2-2, SC-012).
4. Abrir la cita, contrastar los datos mostrados con el "vehículo".
5. Adjuntar solo tres fotos y confirmar → **se rechaza nombrando la evidencia faltante** (US2-4, SC-010).
6. Adjuntar las cuatro (vehículo, contenedor, sello, tiquete) y confirmar.

✅ **Verificar**:
- La cita queda **Atendida** con fecha y hora reales y el nombre del portero (US2-3).
- Volver a abrirla → modo consulta, sin botón de confirmar (US2-7, FR-021).
- Buscar una placa inexistente → mensaje explícito de "sin cita para hoy" + enlace a reportar novedad (US2-5).
- Reportar la novedad → se guarda **sin** crear ninguna cita (FR-022).
- Entrar a `/ingreso` o `/salida` → **403** (US2-8, FR-026).
- Con la agenda del día vacía → mensaje "sin citas para hoy", no una tabla vacía (US2-6).

### 4. Supervisor: vaciado y ubicación — US4

1. Entrar con un usuario `supervisor`.
2. Programar, iniciar y finalizar un vaciado; registrar una novedad.
3. Ir a **Almacenamiento → Ubicar** y asignar ubicación a una referencia.

✅ **Verificar**: ambas acciones son permitidas; `POST /salida` y `POST /ingreso` responden **403** (US4-3).

### 5. Cliente: solo lo suyo — US5 (seguridad)

Requiere **dos clientes** con mercancía distinta.

1. Entrar como cliente A → **Mi Inventario**.
2. Manipular la URL: `/inventario?cliente_id={id_del_cliente_B}`.

✅ **Verificar**:
- Solo aparece mercancía del cliente A, **incluso con el `cliente_id` manipulado** (US5-2, SC-005).
- Lo mismo en las exportaciones a Excel y PDF.
- El sidebar solo muestra "Mi Inventario": no hay Orden de Cargue, Mis Entregas ni Trazabilidad (US5-3, FR-036).
- Un cliente sin mercancía ve un mensaje explícito, no una tabla vacía (US5-4).

### 6. Roles retirados — US6

1. Entrar como `administrador` → **Administración → Usuarios → Crear**.

✅ **Verificar**:
- El selector **no** ofrece `coordinador`, `despachador`, `gerente` ni `operador` (US6-1).
- Forzar el envío con `role=coordinador` (desde devtools) → **rechazado** (US6-2, FR-039).
- Un usuario que **ya** tenía uno de esos roles inicia sesión y **sigue operando igual** (US6-3, R-004).
- El listado de usuarios marca quiénes tienen rol retirado (US6-4, FR-042).
- Los registros históricos creados por esos usuarios siguen visibles y con su autoría (US6-5).
- Editar un ingreso como administrador sigue funcionando (US6-6, FR-043).

### 7. Seguimiento desde el ingreso — US7

1. Entrar como `administrador` → abrir el ingreso del paso 1.

✅ **Verificar**: se ve el estado de cita de cada contenedor, cuál no tiene cita, y las cuatro evidencias del contenedor ya llegado (US7-1, US7-2, FR-047).

---

## Pruebas automatizadas

```bash
# Suite de la feature
php artisan test --filter="Citas|Porteria|MatrizRoles|ClienteAlcance|IngresoCitas"

# Suite completa
php artisan test
```

⚠️ **Baseline conocido**: existen ~15 fallos preexistentes en los unitarios de importación (`Class "config" does not exist`), ajenos a esta feature. No los cuentes como regresión; compara contra el estado de `main`.

---

## Despliegue a producción

El hosting es compartido (GoDaddy/cPanel), **sin SSH y sin cron**. Consecuencias:

1. **Los permisos viajan en la migración**, no en el seeder. `php artisan migrate` basta; no hay que correr `db:seed` (el seeder usa `Role::create()` y fallaría sobre una base con datos).
2. **Ninguna funcionalidad depende de una tarea programada.** El estado Vencida se calcula al leer (D-001).
3. Tras desplegar, limpiar cachés:
   ```
   php artisan config:clear
   php artisan permission:cache-reset
   php artisan view:clear
   ```

### Orden de despliegue recomendado

Por el riesgo del cambio de permisos, desplegar en dos tandas:

| Tanda | Contenido | Reversión |
|-------|-----------|-----------|
| 1 | Tablas nuevas + módulos Citas y Portero (`config/modulos.php` en `false` al inicio) | Poner las banderas en `false` |
| 2 | Migración de roles y permisos + sidebar por permiso + scoping de cliente | `php artisan migrate:rollback` de esa migración |

Activar `citas` y `porteria` en `config/modulos.php` solo cuando la tanda 2 esté validada — así los usuarios no ven módulos a los que aún no tienen permiso.

### Lista de verificación post-despliegue

- [ ] `administrador` accede a **todos** los módulos, incluidos Citas y Portero (SC-009, FR-046)
- [ ] Ningún rol quedó sin permisos por efecto de los `revoke`
- [ ] Un `portero` real ya no ve Ingreso ni Salida en su sidebar
- [ ] Un `cliente` real solo ve su propia mercancía
- [ ] Los usuarios con roles retirados siguen entrando y operando (R-004)
- [ ] La subida de las cuatro fotos funciona desde un móvil con datos móviles, no solo por WiFi

---

## Notas de contexto para quien implemente

- **El módulo Ingreso no se toca** (decisión R-002 del spec). El inventario se sigue contando al registrar el ingreso, aunque el vehículo llegue después.
- Las limitaciones conocidas del vaciado (las novedades solo descuentan, no escriben en el ledger `movimientos_inventario`, y no notifican al cliente en el flujo nuevo) están documentadas en el spec como **riesgos aceptados** y quedan **fuera del alcance** de esta feature.
- El proyecto no tiene worker de cola corriendo; no introducir trabajos asíncronos.
