# Quickstart: Edición de registros operativos por administrador/coordinador

**Feature**: 004-admin-edit-records
**Date**: 2026-06-01

## Objetivo

Permitir que administrador y coordinador **corrijan** registros existentes en 7 módulos, con auditoría de cada cambio y sin tocar inventario.

## Componentes a construir (resumen)

**Transversal (una vez):**
1. Migración `cambios_auditoria` (polimórfica: auditable_type/id, usuario_id, cambios JSON, created_at).
2. Modelo `CambioAuditoria` + trait `Auditable` (morphMany `cambiosAuditoria`).
3. `AuditoriaService::registrarCambios($modelo, $usuario)` — diff de `dirty`, inserta solo si hay cambios.
4. Parcial `components/historial-auditoria.blade.php`.

**Por módulo (×7):**
5. Rutas `edit` (GET) y `update` (PUT) con `role:administrador|coordinador`.
6. Acciones `edit()`/`update()` en el controlador (delgadas; delegan a Form Request y `AuditoriaService`).
7. `Update*Request` con las reglas de los campos editables (reusa las del `Store*Request`).
8. Vista `editar.blade.php` del módulo.

## Patrón del controlador (ejemplo)

```php
public function edit(Solicitud $solicitud)
{
    return view('solicitudes.editar', compact('solicitud'));
}

public function update(UpdateSolicitudRequest $request, Solicitud $solicitud, AuditoriaService $auditoria)
{
    $solicitud->fill($request->validated());        // solo campos editables
    $auditoria->registrarCambios($solicitud, $request->user());  // diff antes de guardar
    $solicitud->save();

    return redirect()->route('solicitudes.show', $solicitud)
        ->with('success', 'Registro actualizado correctamente.');
}
```

## Verificación manual (por módulo)

1. Iniciar sesión como **administrador** o **coordinador**.
2. Abrir un registro existente → "Editar".
3. Cambiar un campo correctivo (p. ej. fecha o notas) → guardar → ver el nuevo valor en la consulta.
4. Ver el **historial de auditoría** del registro: aparece el cambio (quién, cuándo, anterior → nuevo).
5. Probar un valor inválido (obligatorio vacío) → error, valor anterior conservado.
6. Iniciar sesión con un rol distinto (p. ej. portero) → no puede acceder a la edición.
7. Editar un registro con inventario derivado → confirmar que las cantidades no cambian.

## Verificación automatizada

```bash
php artisan test --filter=Edicion
```

Casos por módulo: edición válida (persiste + audita), inválida (rechaza, sin auditoría), rol no autorizado (403), inventario derivado intacto. Más pruebas del `AuditoriaService` (diff correcto, sin cambios → sin entrada).

## Orden recomendado (incremental)

1. Transversal (migración + modelo + servicio + trait + parcial).
2. MVP: Solicitudes → probar de extremo a extremo.
3. Ingresos (gate-in).
4. Vaciado, Salidas, Almacenamiento.
5. Transferencias, Entregas.

## Criterios de aceptación (referencia)

- FR-001/002: solo administrador y coordinador editan; otros bloqueados.
- FR-003/004: solo campos correctivos; inventario derivado intacto.
- FR-005: editable en cualquier estado.
- FR-006: mismas validaciones que crear.
- FR-007/009/011: auditoría con diff; consultable; sin entrada si no hay cambios.
- FR-008: confirmación y reflejo inmediato.
- FR-010: el estado no cambia por editar otros campos.
