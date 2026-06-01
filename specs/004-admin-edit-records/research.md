# Research: Edición de registros operativos por administrador/coordinador

**Feature**: 004-admin-edit-records
**Date**: 2026-06-01

Las cuatro decisiones de mayor impacto se resolvieron con el usuario en la fase de especificación (edición correctiva sin recalcular inventario; editable en cualquier estado; auditoría obligatoria; roles administrador y coordinador). Aquí se consolidan las decisiones técnicas restantes a partir del código actual.

## Decisión 1 — Auditoría: tabla propia vs paquete externo

- **Decision**: Implementar la auditoría con una **tabla propia** `cambios_auditoria` (polimórfica) + `AuditoriaService` + trait `Auditable`. No se añade un paquete (p. ej. owen-it/laravel-auditing).
- **Rationale**: El hosting es GoDaddy compartido **sin SSH**, con `vendor/` versionado a mano; añadir un paquete obliga a commitear todo su árbol y arriesga el deploy (ver lecciones de deploy del proyecto). El requisito de auditoría es simple (quién, cuándo, antes/después) y se cubre con una tabla y un servicio de pocas líneas (KISS). Solo se depende de lo ya instalado (spatie/laravel-permission).
- **Alternatives considered**: *owen-it/laravel-auditing* (potente pero dependencia pesada e innecesaria para el alcance) y *spatie/laravel-activitylog* (igual, dependencia extra). Rechazados por costo/riesgo en este hosting.

## Decisión 2 — Forma de la auditoría

- **Decision**: Una fila por edición con un campo `cambios` en JSON: `{ "campo": {"anterior": x, "nuevo": y}, ... }`, más `auditable_type`, `auditable_id`, `usuario_id`, `created_at`.
- **Rationale**: Captura el diff completo de una edición en un solo registro, fácil de listar como historial (FR-009). El `AuditoriaService` toma los atributos `dirty` del modelo antes de `save()` y arma el diff; si no hay cambios, no inserta (FR-011).
- **Alternatives considered**: *Una fila por campo cambiado* (más granular pero más ruidoso para listar) y *snapshot completo del modelo* (más espacio, más difícil de leer). El JSON de diff es el punto medio simple.

## Decisión 3 — Autorización

- **Decision**: Proteger las rutas `edit`/`update` con el middleware de rol `role:administrador|coordinador`.
- **Rationale**: El proyecto ya usa exactamente ese patrón en `admin/importaciones` (`role:administrador|coordinador`). Cubre FR-001/FR-002 sin inventar permisos nuevos ni tocar el seeder de permisos. Convención sobre configuración.
- **Alternatives considered**: *Permiso dedicado `registros.editar`* (más granular, pero requiere actualizar el seeder y asignarlo; innecesario para dos roles fijos) y *Policies por modelo* (más código repetido para una regla uniforme). Rechazados por simplicidad.

## Decisión 4 — Campos editables por módulo (solo correctivos)

Derivados de los `$fillable` reales de cada modelo, excluyendo identificadores, vínculos estructurales y cantidades/derivados de inventario:

| Módulo | Modelo | Editables | Excluidos |
|---|---|---|---|
| Solicitudes | `Solicitud` | cliente_id, numero_contenedor, naviera, puerto_origen, descripcion, fecha_solicitud | estado, import_batch_id, id |
| Ingresos | `GateEvent` (ingreso) | hora, estado_fisico, notas | contenedor_id, tipo, usuario_id |
| Vaciado | `OrdenVaciado` | fecha_programada, supervisor_id, notas | contenedor_id, fecha_inicio, fecha_fin, estado |
| Salidas | `GateEvent` (salida) | hora, estado_fisico, notas | contenedor_id, tipo, usuario_id |
| Almacenamiento | `Referencia` | ubicacion_patio_id, codigo, descripcion, unidad_medida, fecha_ingreso | cantidad_inicial, cantidad_actual, contenedor_id, cliente_id |
| Transferencias | `Transferencia` | motivo, autorizacion_cliente | cantidad, referencia/ubicacion/cliente origen-destino, tipo |
| Entregas | `OrdenCargue` | cliente_id, fecha_despacho, notas | estado, tarjas (derivado) |

- **Rationale**: Cumple FR-003 (no exponer estructurales/derivados) y FR-004 (no tocar cantidades). Las cantidades de inventario se ajustan por su flujo propio (movimientos/transferencias), no por esta corrección.
- **Alternatives considered**: *Exponer todos los campos* (rechazado: rompería consistencia de inventario y vínculos).

## Decisión 5 — Validación: reusar reglas de creación

- **Decision**: Cada `Update*Request` reutiliza el subconjunto correspondiente de reglas del `Store*Request` del módulo (cuando existe), aplicado solo a los campos editables.
- **Rationale**: DRY y consistencia: la edición valida con los mismos criterios que la creación (FR-006). Donde no exista un Store equivalente (gate-in/gate-out usan flujos propios), se definen las reglas mínimas de los campos editables.
- **Alternatives considered**: *Duplicar reglas inline* (rechazado por DRY).

## Decisión 6 — Identidad del registro a editar en gate-in / gate-out

- **Decision**: La edición de "ingreso" apunta al `GateEvent` de tipo ingreso del contenedor; la de "salida" al `GateEvent` de tipo salida. Las rutas de edición referencian el `GateEvent` directamente (`gate-in/{gateEvent}/editar`, `gate-out/{gateEvent}/editar`).
- **Rationale**: El dato corregible (hora, estado físico, notas) vive en el `GateEvent`. Mantiene el registro identificable y auditable de forma directa.
- **Alternatives considered**: *Editar vía contenedor* (ambiguo si hubiera múltiples eventos; el evento es la unidad correcta).

## Decisión 7 — Consulta del historial (FR-009)

- **Decision**: Un parcial reutilizable `components/historial-auditoria.blade.php` que lista las entradas de `cambios_auditoria` del registro, embebido en la vista `show`/`editar` de cada módulo. Visible para administrador/coordinador.
- **Rationale**: Reutiliza una sola vista para los siete módulos (DRY); no requiere rutas nuevas. La relación `cambiosAuditoria()` del trait `Auditable` provee los datos.
- **Alternatives considered**: *Ruta/página dedicada de auditoría* (más navegación; innecesaria para el alcance actual).

## Decisión 8 — Estrategia de pruebas

- **Decision**: Pruebas Feature por módulo (editar campo correctivo → persiste + audita; inválido → rechaza; rol no autorizado → bloqueado; inventario derivado intacto) + pruebas del `AuditoriaService` (diff correcto; sin cambios → sin entrada).
- **Rationale**: Cumple el principio VI y cubre FR-002, FR-004, FR-006, FR-007, FR-011 por módulo.
- **Alternatives considered**: *Solo pruebas del servicio* (insuficiente: no valida autorización ni el flujo HTTP por módulo).
