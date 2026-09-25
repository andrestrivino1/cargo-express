# Quickstart — Feature 010

**Branch**: `010-ajustes-ingreso-almacen-ruta`

Cómo poner la feature en marcha en local, cómo validarla a mano y qué hace falta al desplegar.

---

## 1. Puesta en marcha local

```bash
git checkout 010-ajustes-ingreso-almacen-ruta
php artisan migrate --force    # 2 migraciones: columnas de retiro + permiso inventario.retirar
php artisan db:seed --class=RolesAndPermissionsSeeder --force   # solo local; en producción lo hace la migración
php artisan optimize:clear
npm run dev                    # o npm run build si vas a probar el login con estilos
```

> **`--force` es necesario en este entorno**: el `.env` local tiene `APP_ENV=production`,
> así que `migrate` pide confirmación por consola y se queda esperando. La base es
> local (`DB_HOST=127.0.0.1`), pero conviene mirar el `.env` antes de correrlo.
> Los tests **no** necesitan esta migración: usan SQLite en memoria.

La migración de permisos es idempotente: correrla dos veces no duplica nada.

### Baseline de la suite (medido el 2026-09-25, antes de tocar código)

```text
Tests: 15 failed, 11 skipped, 256 passed (695 assertions) — 84.66s
```

Los **15 fallos son preexistentes** y ajenos a esta feature: todos en
`tests/Unit/Services/Importacion/*` (`RowValidatorTest`, `ReferenciaMapperTest` y
compañía) con `BindingResolutionException: Target class [config] does not exist`
— tests unitarios que usan `config()` sin levantar la aplicación. Cualquier fallo
distinto de esos 15 tras implementar la feature **es una regresión**.

---

## 2. Validación manual

### US1 — Corregir cantidades en el ingreso

Con un usuario **administrador**:

1. Ingreso → abrir un BL con referencias → **Editar**.
2. En "Referencias del BL", la columna **Cantidad** ahora es editable.
3. Cambiar una cantidad de `5` a `8` y guardar.
   - ✅ El detalle del ingreso muestra `8 / 8`.
   - ✅ Almacenamiento muestra 8 para esa referencia.
   - ✅ Reportes → Movimientos muestra una fila **Ajuste (+)** de 3.
   - ✅ Reportes → Ingresos **no** cambió de total.
4. Repetir sobre una referencia de la que ya salió mercancía (despachar 2 antes con una ODC):
   - Corregir a `8` ⇒ queda `8 / 6`.
   - Corregir a `1` ⇒ ❌ rechazo con mensaje que menciona las 2 unidades ya despachadas.
5. Guardar sin tocar ninguna cantidad ⇒ no aparece nada nuevo en el historial de cambios del ingreso.
6. Con un usuario **operaciones** ⇒ el botón Editar no está y el `PUT` directo devuelve 403.

### US2 — Retirar un producto en almacenamiento

Con un usuario **administrador**:

1. Almacenamiento → localizar una referencia → botón **Retirar**.
2. El modal informa qué referencia y de qué cliente se retira, y pide confirmar.
3. Confirmar.
   - ✅ Desaparece del listado.
   - ✅ No aparece en los exportables Excel ni PDF.
   - ✅ No aparece al armar una Orden de Salida ni una Transferencia ni en Ubicar.
   - ✅ Reportes → Movimientos muestra una fila **Baja** con el disponible que tenía.
4. Activar el filtro **Incluir retiradas** ⇒ aparece marcada, con responsable y fecha.
5. Si la referencia tenía salidas previas: abrir esa Orden de Salida ⇒ ✅ sigue mostrando el detalle completo.
6. Con el usuario **cliente** del que era la mercancía ⇒ no la ve, ni siquiera agregando `?incluir_retiradas=1` a la URL.

### US3 — Entrada al sitio

1. Cerrar sesión y entrar a la raíz del sitio ⇒ ✅ formulario de acceso de Cargo Express, sin logo de Laravel ni enlaces a documentación.
2. Con sesión abierta, entrar a la raíz ⇒ ✅ tablero, sin pasar por el formulario.
3. Con un usuario recién creado (primer login pendiente) ⇒ ✅ va a cambiar contraseña.
4. Entrar a `/register` a mano ⇒ ✅ 404.
5. Mirar la barra de navegación de invitado ⇒ ✅ no hay enlace "Registrarse".

---

## 3. Regresiones a vigilar

Son los puntos que esta feature toca de refilón. Verificarlos antes de dar por buena la entrega:

| Qué probar | Por qué |
|------------|---------|
| **Eliminar un ingreso completo** (módulo Ingreso, permiso `ingreso.eliminar`) | Las referencias deben borrarse de verdad (`forceDelete`), no quedar como retiradas. Si quedaran, el ingreso borrado dejaría rastro resucitable |
| **Consolidar duplicados en Pendientes por completar** | Mismo motivo: el borrado de referencias del contenedor duplicado debe ser físico |
| **Reporte de Movimientos** | Las filas nuevas deben renderizar con etiqueta y color, no en blanco |
| **Orden de Salida ya emitida de una referencia retirada** | Debe seguir mostrando el detalle (`withTrashed` en `TarjaDetalle::referencia`) |
| **Borrar un producto del catálogo** que solo tiene referencias retiradas | Ahora se puede borrar. Es el comportamiento correcto, pero es un cambio respecto de hoy |
| **Suite completa** | `tests/Feature/Auth/RegistrationTest.php` se elimina junto con la funcionalidad. Los ~15 fallos preexistentes de los unitarios de importación siguen ahí y no son de esta feature |

```bash
php artisan test --filter="IngresoCorregirCantidad|InventarioRetiro|EntradaSitio"
php artisan test        # suite completa
```

---

## 4. Despliegue a producción

Hosting compartido GoDaddy, sin SSH. El orden importa:

1. Subir el código (zip / gestor de archivos), **incluyendo `public/build`** — el login usa `@vite`, y sin los assets compilados se ve sin estilos, que es justo el problema que la US3 viene a resolver.
2. Correr las **2 migraciones** por el mecanismo habitual del proyecto:
   - `add_retiro_to_referencias_table` — 3 columnas en `referencias`.
   - `crear_permiso_retirar_inventario` — permiso `inventario.retirar` a `administrador` y `coordinador`.
3. Limpiar cachés de configuración, rutas y vistas.
4. Verificar en producción, en este orden:
   - Entrar a la URL del sitio sin sesión ⇒ login con estilos.
   - `/register` ⇒ 404.
   - Corregir una cantidad en un ingreso de prueba.
   - Retirar una referencia de prueba y comprobar que el cliente no la ve.

**No hace falta** `composer install` (sin dependencias nuevas) ni worker de cola (todo síncrono).

### Reversa

- Las migraciones tienen `down()`: revertir devuelve las referencias retiradas al inventario vigente, que es el estado previo.
- Los movimientos `ajuste_*` y `baja` quedarían en el ledger con tipos que el enum ya no conoce; si se revierte, hay que borrarlos o volver a desplegar el enum. Vale la pena mencionarlo antes de revertir, no después.
