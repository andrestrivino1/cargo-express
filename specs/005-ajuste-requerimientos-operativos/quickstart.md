# Quickstart: Ajuste de requerimientos operativos

Guía mínima para implementar y verificar la feature en desarrollo local.

## Requisitos previos

- Stack ya instalado: PHP 8.2, Laravel 12, MySQL 8, Composer (solo local), Node para assets.
- Sin nuevas dependencias de Composer (restricción de hosting).

## 1. Migraciones (esquema)

```bash
php artisan migrate
```

Crea `movimientos_inventario` y `secuencias`; agrega columnas a `contenedores`, `referencias`, `tarjas`, `users`, `photos`. Sembrar la secuencia ODC:

```bash
php artisan db:seed --class=SecuenciaOdcSeeder   # inserta ('odc', 570)
```

## 2. Configuración de visibilidad y empresa

- `config/modulos.php`: módulos visibles/ocultos (ver `contracts/visibilidad-modulos.md`).
- `config/empresa.php` (o `.env`): razón social y NIT de Carga Trans Xpress (901615219-4) y ruta del logo para el ODC.

## 3. Verificación funcional (orden sugerido = prioridades del spec)

### US1 — Ingreso (P1)
1. Entrar a `/ingreso/crear`.
2. Diligenciar BL, contenedor, cliente, tipo de mercancía, una referencia (código, descripción, unidad, peso, cantidad, ubicación).
3. Adjuntar BL, DIM y Lista de empaque.
4. Guardar → la referencia aparece en `/inventario` con su saldo; los 3 documentos se descargan desde el detalle.
5. **Negativo**: guardar sin algún campo obligatorio → error de validación.

### US2 + US3 — Salida + ODC (P1)
1. Entrar a `/salida/crear`, elegir cliente con saldo.
2. Agregar detalle (referencia + cantidad ≤ saldo), datos de conductor/vehículo/transportador/destino.
3. Adjuntar foto de mercancía y foto de conductor.
4. Guardar → saldo de la referencia disminuye exactamente la cantidad; se crea un movimiento `salida`.
5. Descargar `/salida/{tarja}/orden-salida.pdf` → verificar contra la imagen: ODC-###, cliente, NIT, fecha, detalle con total, datos de conductor/vehículo, 2 fotos, firmas.
6. **Negativos**: cantidad > saldo → rechazo con saldo informado; falta una foto → no confirma.

### US4 — Vaciado (P2)
- Confirmar que `/vaciado` sigue permitiendo fotos + novedades (sin regresión).

### US5 — Reportes (P2)
- `/reportes/inventario-por-cliente`, `/reportes/ingresos`, `/reportes/salidas`, `/reportes/movimientos`, `/reportes/novedades`, `/reportes/evidencias` → datos coherentes con los movimientos registrados.

### US6 — Ocultar módulos (P2)
- Con un usuario operativo: el menú **no** muestra Solicitudes, Entregas, Transferencias, Gate-In, Gate-Out ni Importaciones.
- Acceder directo a una ruta oculta (p. ej. `/transferencias`) → 404.
- Cambiar la bandera a `true` en `config/modulos.php` → el módulo reaparece con sus datos.

## 4. Pruebas

```bash
php artisan test --filter=Ingreso
php artisan test --filter=Salida
php artisan test --filter=OrdenSalidaPdf
php artisan test --filter=MovimientoInventario
php artisan test --filter=VisibilidadModulos
```

Verificar invariante SC-002: para cada referencia, `cantidad_actual == sum(entradas) - sum(salidas)` en `movimientos_inventario`.

## 5. Checklist de "no eliminar"

- [ ] Ninguna migración hace `dropTable`/`dropColumn` sobre módulos ocultos.
- [ ] Rutas/controladores de módulos ocultos siguen existiendo (solo guardados por middleware).
- [ ] Reportes/trazabilidad leen datos históricos de módulos ocultos sin error.
