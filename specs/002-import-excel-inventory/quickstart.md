# Quickstart — Importación de Inventario Histórico

**Date**: 2026-05-21
**Plan**: [plan.md](./plan.md)
**Audiencia**: desarrollador o administrador que ejecuta la importación del archivo real por primera vez en un entorno con la feature ya construida.

---

## Preparación del entorno

1. **Migraciones aplicadas** — desde la raíz del proyecto:
   ```powershell
   php artisan migrate
   ```
   Deben quedar las 5 migraciones nuevas listadas en [data-model.md § Migration order](./data-model.md#migration-order-delta-sobre-feature-001).

2. **Configuración de `php.ini` del entorno** (XAMPP local o PHP de producción):
   ```ini
   upload_max_filesize = 64M
   post_max_size       = 64M
   max_execution_time  = 60
   memory_limit        = 256M
   ```
   La subida y el job se han diseñado para no exceder estos valores con el archivo real (~< 10 MB, ~< 200 MB de pico de memoria por chunk).

3. **Configuración `config/importacion.php`** (creada por la feature):
   ```php
   return [
       'password_generica' => env('IMPORT_PASSWORD_GENERICA', 'Cliente2026!'),
       'dominio_placeholder' => env('IMPORT_DOMINIO_PLACEHOLDER', 'cargo-express.placeholder'),
       'fecha_corte_default' => '2026-02-27',
       'origen_default' => 'carga_historica_27_02_2026',
       'chunk_size' => 500,
       'max_pares_despacho' => 8,
   ];
   ```
   La `password_generica` se documenta al equipo de operación. No se commitea un valor real — se setea por `.env` del cliente.

4. **Queue worker corriendo**:
   ```powershell
   php artisan queue:listen --tries=1 --timeout=0
   ```
   (el script `composer dev` ya lo levanta junto con `php artisan serve` y `npm run dev`).

5. **Usuario administrador con permisos**:
   ```powershell
   php artisan tinker
   >>> User::factory()->create(['name'=>'Admin','email'=>'admin@cargo-express.local'])->assignRole('administrador');
   ```

---

## Flujo paso a paso: validar el archivo real

1. Login como administrador → `/admin/importaciones`.
2. Botón "Nueva importación" → formulario.
3. Adjuntar `INVENTARIO TOTAL CONTROLCARGA 27022026.xlsx`.
4. Modo: **Validar (dry-run)**.
5. Submit. La página redirige a `/admin/importaciones/{id}` y empieza a hacer polling.
6. Esperar a que `estado = completado` (≤ 3 min para el archivo real).
7. **Revisar el reporte**:
   - Contadores globales coinciden con el orden de magnitud esperado (~ 20.870 filas totales, ~ 20.000+ importables).
   - Sección "Clientes a auto-crear": verificar la lista contra el negocio. Si algún cliente "no debe existir", abortar y limpiar el Excel en origen.
   - Sección "Conflictos de contenedor": cualquier entrada aquí es un caso a resolver con el equipo de patio antes de importar.
   - Sección "Hojas ignoradas": confirmar que las hojas `Hoja1` y `Copia de LADRILLOS Y TUBOS DEL …` aparecen aquí.
   - Filas en error: descargar el Excel de errores, corregir en origen, reintentar validación.

---

## Flujo paso a paso: importar definitivamente

**Precondición**: una validación reciente del **mismo archivo** sin errores bloqueantes.

1. En la pantalla del reporte de validación, botón "Confirmar e importar".
2. Confirmar (modal): "Se crearán N clientes nuevos con password genérica. Esta acción no es trivial de revertir." → Sí.
3. La página redirige al detalle del nuevo batch (`modo=importar`).
4. Polling hasta `estado=completado`.
5. Verificar contadores: `contenedores_creados`, `referencias_creadas`, `despachos_historicos_creados`, `clientes_autocreados`.
6. Verificar manualmente en BD:
   ```sql
   SELECT COUNT(*) FROM contenedores WHERE import_batch_id = {id};
   SELECT COUNT(*) FROM referencias r
     JOIN contenedores c ON r.contenedor_id = c.id
     WHERE c.import_batch_id = {id};
   SELECT COUNT(*) FROM import_pending_records WHERE import_batch_id = {id} AND completado_at IS NULL;
   ```

---

## Verificación del flujo de completado progresivo

1. Login como `coordinador` → `/pendientes`.
2. Debe haber ≈ tantos pendientes vivos como contenedores y tarjas creados por el batch.
3. Abrir uno cualquiera tipo `Contenedor` → formulario de completado con `placa_vehiculo`, `tipo`, `destino_salida`.
4. Llenar y guardar → redirige al siguiente pendiente del mismo registro (si los hubiera) o al detalle del contenedor.
5. Repetir queries en BD para confirmar que `import_pending_records.completado_at IS NOT NULL` para esa fila.

---

## Verificación del primer login forzado

1. En el reporte, copiar el email placeholder de uno de los clientes auto-creados (formato `aduvidrios-116-sas@cargo-express.placeholder`).
2. Logout. Intentar login con ese email y la `IMPORT_PASSWORD_GENERICA` configurada.
3. Tras autenticarse, **cualquier ruta** debe redirigir a `/primer-login/password`.
4. Cambiar password → debe redirigir a `/primer-login/email`.
5. Actualizar email a uno real (ej. `cliente.real@gmail.com`) → debe redirigir a `/` y el flujo normal queda habilitado.
6. Verificar en BD:
   ```sql
   SELECT email, requiere_cambio_password, email_placeholder, password_actualizada_at
   FROM users WHERE id = {id_del_cliente};
   ```
   `requiere_cambio_password = 0`, `email_placeholder = 0`, `password_actualizada_at` no nulo.

---

## Comandos útiles para troubleshooting

- **Ver el último batch fallido**:
  ```sql
  SELECT id, archivo_nombre, estado, error_mensaje FROM import_batches WHERE estado='fallido' ORDER BY id DESC LIMIT 1;
  ```
- **Reintentar un job atorado**:
  ```powershell
  php artisan queue:retry all
  ```
- **Limpiar todo el batch (solo en dev)**:
  ```sql
  DELETE FROM import_row_results WHERE import_batch_id = {id};
  DELETE FROM import_pending_records WHERE import_batch_id = {id};
  UPDATE contenedores SET import_batch_id = NULL WHERE import_batch_id = {id};
  -- ... idem para referencias, ordenes_servicio, etc.
  DELETE FROM import_batches WHERE id = {id};
  ```
  (Producción: la cancelación se hace por la UI antes de procesar, o se contacta al usuario admin para coordinar; no se borran batches completados.)

---

## Prueba de humo manual con el archivo real

Documentada aquí porque la suite automatizada usa un fixture sintético (ver `tests/Feature/Importacion/ValidarExcelTest.php`), no el archivo real.

1. Copiar `INVENTARIO TOTAL CONTROLCARGA 27022026.xlsx` de la carpeta de Descargas a `storage/app/imports-prueba/` del entorno de pruebas.
2. En un entorno con BD limpia (sin contenedores cargados de feature 001), ejecutar el flujo Validar → Importar.
3. Confirmar:
   - Validación termina en ≤ 3 min.
   - El número de `referencias` creadas en BD coincide con el de "importables" del reporte (varianza 0).
   - Subir el mismo archivo otra vez en modo `importar` con política `omitir` reporta el 100 % como duplicados, no crea nada nuevo.
