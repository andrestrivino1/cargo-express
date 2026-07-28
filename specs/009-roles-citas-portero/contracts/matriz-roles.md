# Contrato de la matriz rol × permiso — Feature 009

**Feature**: `009-roles-citas-portero` | **Fecha**: 2026-07-27

Este documento es el contrato verificable de SC-006 ("0 accesos exitosos a módulos fuera de su alcance en la matriz completa de rol × módulo"). `MatrizRolesTest` debe recorrerlo entero.

---

## Estado actual (antes de la feature)

Tomado de `RolesAndPermissionsSeeder.php` y de la migración `2026_06_25_000009`.

| Rol | Permisos |
|-----|----------|
| `cliente` | referencias.ver, inventario.ver, entregas.ver, entregas.crear, reportes.ver |
| `portero` | ingreso.ver, ingreso.crear, salida.ver, salida.crear |
| `operador` | gate-in.ver, ingreso.ver, ingreso.crear, referencias.ver, referencias.crear, vaciado.ver, vaciado.registrar-novedad, inventario.ver, inventario.ubicar |
| `coordinador` | solicitudes.ver, solicitudes.asignar, gate-in.ver, ingreso.\*, salida.\*, inventario.ver, reportes.ver |
| `supervisor` | vaciado.ver, vaciado.programar, inventario.ver, reportes.ver |
| `despachador` | entregas.\*, salida.\*, inventario.ver, referencias.ver |
| `gerente` | todos |
| `administrador` | todos |

---

## Estado objetivo (después de la feature)

### Roles vigentes

| Rol | Permisos | Módulos visibles | Cambio |
|-----|----------|------------------|--------|
| **`citas`** 🆕 | `citas.ver`, `citas.crear`, `citas.editar`, `ingreso.ver` | Citas, Ingreso (solo consulta) | Rol nuevo (FR-012) |
| **`operaciones`** 🆕 | `ingreso.ver`, `ingreso.crear`, `salida.ver`, `salida.crear` | Ingreso, Salida | Rol nuevo (FR-028) |
| **`portero`** ✏️ | `porteria.ver`, `porteria.registrar` | Portero | **Pierde** ingreso.\*, salida.\* **y gate-in.\*/gate-out.\*** (FR-026) |
| **`supervisor`** ✏️ | `vaciado.ver`, `vaciado.programar`, `vaciado.registrar-novedad`, `inventario.ver`, `inventario.ubicar`, `reportes.ver` | Vaciado, Almacenamiento, Transferencias, Reportes, Trazabilidad | **Gana** vaciado.registrar-novedad e inventario.ubicar (FR-031, FR-032) |
| **`cliente`** ✏️ | `inventario.ver` | Almacenamiento (solo lo propio) | **Pierde** referencias.ver, entregas.\*, reportes.ver (FR-034, FR-036) |
| **`administrador`** ✏️ | Todos, incluidos `citas.*` y `porteria.*` | Todos | **Gana** los permisos nuevos (FR-046) |

### Roles retirados

No se borran. Sus permisos quedan **exactamente como están hoy** (R-004, FR-041); solo desaparecen del selector de asignación vía `config/roles.php`.

| Rol | Qué pasa |
|-----|----------|
| `coordinador` | Retirado del selector. Los usuarios que lo tienen siguen operando igual |
| `despachador` | Idem |
| `gerente` | Idem |
| `operador` | Idem |

⚠️ **`gerente` y `administrador` reciben los permisos nuevos** en la migración, porque hoy tienen "todos los permisos". Si un `gerente` no recibiera `citas.*` y `porteria.*`, dejaría de tener acceso total sin que nadie lo haya decidido. El retiro es del selector, no de las capacidades.

---

## Matriz de verificación rol × módulo

Leyenda: ✅ acceso permitido · ❌ debe responder 403 · ⬜ 404 (módulo oculto)

| Rol | Citas | Portero | Ingreso | Salida | Vaciado | Almacén | Transfer. | Reportes | Admin |
|-----|:-----:|:-------:|:-------:|:------:|:-------:|:-------:|:---------:|:--------:|:-----:|
| `citas` | ✅ | ❌ | ✅ solo ver | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `operaciones` | ❌ | ❌ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `portero` | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `supervisor` | ❌ | ❌ | ❌ | ❌ | ✅ | ✅ | ✅ | ✅ | ❌ |
| `cliente` | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ solo propio | ❌ | ❌ | ❌ |
| `administrador` | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |

Casos negativos que `MatrizRolesTest` debe cubrir explícitamente por ser los que la feature cambia:

1. `portero` → `POST /ingreso` ⇒ 403 (US3, escenario 3)
2. `portero` → `GET /salida` ⇒ 403
3. `portero` → `GET /citas` ⇒ 403 (FR-027)
4. `citas` → `POST /ingreso` ⇒ 403 (FR-013)
5. `citas` → `GET /vaciado` ⇒ 403
6. `operaciones` → `GET /citas` ⇒ 403 (FR-029)
7. `operaciones` → `GET /porteria` ⇒ 403
8. `supervisor` → `POST /salida` ⇒ 403 (US4, escenario 3)
9. `supervisor` → `GET /inventario/ubicar` ⇒ 200 (FR-032)
10. `cliente` → `GET /reportes` ⇒ 403 (FR-036)
11. `cliente` → `GET /inventario?cliente_id={otro}` ⇒ 200 pero **solo con sus propios registros** (FR-035, SC-005)

---

## Contrato de la migración de permisos

`2026_07_27_000004_reorganizar_roles_y_permisos.php` — idempotente, siguiendo el patrón de `2026_06_25_000009`.

**`up()`** en este orden:

1. `forgetCachedPermissions()`
2. `Permission::firstOrCreate()` para los 5 permisos nuevos
3. `Role::firstOrCreate()` para `citas` y `operaciones`, con sus permisos
4. `portero`: `givePermissionTo(['porteria.ver','porteria.registrar'])` + `revokePermissionTo(['ingreso.*','salida.*','gate-in.*','gate-out.*'])`

   ⚠️ **Hallazgo al importar la base de producción (2026-07-27)**: el `portero` real tenía además `gate-in.ver/crear` y `gate-out.ver/crear` de la cadena vieja — permisos que el seeder local nunca le dio, así que los tests con base sembrada no lo detectaban. Hoy no son alcanzables (`gate_in` y `gate_out` están ocultos y devuelven 404), pero si esos módulos se reactivaran, el portero recuperaría acceso a Gate-In/Gate-Out, que son la versión anterior de Ingreso y Salida. La migración los revoca también, filtrando previamente por los permisos que existen para no fallar en entornos donde no estén creados.
5. `supervisor`: `givePermissionTo(['vaciado.registrar-novedad','inventario.ubicar'])`
6. `cliente`: `revokePermissionTo(['referencias.ver','entregas.ver','entregas.crear','reportes.ver'])`
7. `administrador` y `gerente`: `givePermissionTo(['citas.ver','citas.crear','citas.editar','porteria.ver','porteria.registrar'])`
8. `forgetCachedPermissions()`

Cada paso usa `Role::where('name', ...)->first()` con `?->`, para que la migración no falle si un rol no existe en un entorno dado.

**`down()`**: elimina los 5 permisos nuevos y los roles `citas` y `operaciones`, y devuelve a `portero` y `cliente` sus permisos previos. No restaura asignaciones de usuarios (no se tocan en `up()`).

**Verificación post-despliegue** (ver `quickstart.md`): confirmar que ningún rol quedó sin permisos y que `administrador` conserva el total.
