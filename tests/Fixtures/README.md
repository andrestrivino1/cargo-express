# Test Fixtures — Importación

## inventario_minimo.xlsx

Fixture sintético usado por los tests de feature 002. **No** es el archivo real del usuario (research.md §R11 explica por qué).

### Generar el fixture localmente

Ejecuta una vez:

```powershell
php tests/Fixtures/generar_inventario_minimo.php
```

El script crea `tests/Fixtures/inventario_minimo.xlsx` con 3 hojas:

| Hoja | Particularidad | Filas |
|---|---|---|
| `Hoja1` | Vacía (se ignora) | 0 |
| `CLIENTE STANDARD SAS` | Encabezado estándar, 1 cliente nuevo, mezcla de fechas, 1 fila con par despacho | ~20 |
| `CLIENTE BLANCO SAS` | Primera columna en blanco, sin columna `Mercancia`, una fila con `'#'` en cantidad, una con fecha basura | ~15 |

El archivo binario `.xlsx` **no se versiona** (está en `.gitignore`); cada desarrollador lo regenera al clonar.

### CI

El CI debe correr `php tests/Fixtures/generar_inventario_minimo.php` como paso previo a `php artisan test`.
