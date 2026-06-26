# Quickstart — Editar ingreso con referencias e imágenes del BL

Guía rápida para implementar y verificar la feature. No hay migraciones ni dependencias nuevas.

## Archivos a tocar

| Archivo | Cambio |
|---|---|
| `app/Http/Controllers/IngresoMercanciaController.php` | `edit()`: eager-load `contenedores.referencias.producto`, `contenedores.referencias.ubicacionPatio`, `fotos`; pasar `$ubicaciones` a la vista. `update()`: delegar a `IngresoMercanciaService` (fotos + referencia nueva). |
| `app/Http/Requests/UpdateIngresoRequest.php` | Agregar reglas `fotos`, `fotos.*`, `nueva_referencia.*`; validar en `withValidator` que el contenedor destino pertenezca al ingreso. |
| `app/Services/IngresoMercanciaService.php` | Nuevo método `actualizar(Ingreso $ingreso, array $data, array $fotos, ?array $nuevaReferencia, User $usuario)`; extraer `crearReferencia(...)` reutilizable desde `registrar()`. |
| `resources/views/ingreso/editar.blade.php` | `enctype="multipart/form-data"`; bloque lista de referencias (solo lectura); galería + input `fotos[]`; sub-form "agregar referencia" (selector de contenedor). |
| `tests/Feature/IngresoEditarTest.php` | NUEVO. |
| `tests/Unit/IngresoMercanciaServiceTest.php` | NUEVO/ampliado. |

> Si `editar.blade.php` se acerca a 300 líneas, extraer parciales: `ingreso/partials/_referencias.blade.php`, `_imagenes.blade.php`, `_agregar-referencia.blade.php` (Principio I).

## Refactor recomendado (DRY)

En `IngresoMercanciaService`, extraer de `registrar()` el bloque que crea la referencia y su movimiento:

```php
private function crearReferencia(Contenedor $contenedor, array $fila, User $usuario, Ingreso $ingreso): Referencia
{
    $referencia = $contenedor->referencias()->create([
        'cliente_id'       => $ingreso->cliente_id,
        'codigo'           => $fila['codigo'],
        'descripcion'      => $fila['descripcion'],
        'cantidad_inicial' => $fila['cantidad'],
        'cantidad_actual'  => $fila['cantidad'],
        'unidad_medida'    => $fila['unidad_medida'],
        'peso'             => $fila['peso'] ?? null,
        'ubicacion_patio_id' => $fila['ubicacion_patio_id'] ?? null,
        'fecha_ingreso'    => $ingreso->fecha_ingreso,
    ]);

    $this->movimientos->registrarEntrada($referencia, (int) $fila['cantidad'], $usuario, $ingreso);

    return $referencia;
}
```

`registrar()` lo invoca dentro de su loop; `actualizar()` lo invoca para la referencia nueva. El método `actualizar()` envuelve en `DB::transaction` la actualización del ingreso + fotos + referencia.

## Verificación manual

1. Importar un Excel que deje ingresos con BL provisional (feature 006).
2. Ir a Ingreso → abrir uno con BL provisional → **Editar**.
3. Confirmar que se ven todas las referencias del BL agrupadas por contenedor.
4. Escribir el BL real, adjuntar 1–2 imágenes, (opcional) agregar una referencia a un contenedor → **Guardar**.
5. Verificar en `ingreso.show`: BL confirmado (sin aviso provisional), imágenes visibles, referencia nueva listada.

## Pruebas automatizadas (mapa)

| Test | FR |
|---|---|
| `edit` muestra referencias del ingreso | FR-001/002 |
| `update` con `fotos[]` crea `Photo` tipo 'foto' y conserva previas | FR-005/006 |
| `update` rechaza archivo no-imagen sin perder datos | FR-007/010 |
| `update` con `nueva_referencia` crea Referencia + MovimientoInventario en contenedor del ingreso | FR-008 |
| `update` rechaza referencia incompleta (`required_with`) | FR-009 |
| `update` baja `bl_por_confirmar` | FR-004 |
| usuario sin rol admin/coordinador → 403 | FR-011 |
| ingreso sin referencias → vista con "Sin referencias" y permite guardar | FR-012 |
| contenedor destino que no pertenece al ingreso → error de validación | integridad D5 |

## Comandos

```bash
# correr la suite de la feature
php artisan test --filter=IngresoEditarTest
php artisan test --filter=IngresoMercanciaServiceTest
```

## Checklist de "Definition of Done"

- [ ] Sin migraciones nuevas; `php artisan migrate:status` sin pendientes inesperados.
- [ ] Vista con `enctype` multipart y galería de imágenes.
- [ ] Referencias visibles (solo lectura) agrupadas por contenedor.
- [ ] Fotos aditivas (no reemplazan).
- [ ] Referencia nueva → contenedor del ingreso + movimiento de inventario.
- [ ] RBAC admin/coordinador respetado.
- [ ] Operación atómica ante errores de validación.
- [ ] Tests Feature + Unit en verde; archivos < 300 líneas, funciones < 40.
