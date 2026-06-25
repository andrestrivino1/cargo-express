# Contrato: Visibilidad de módulos (ocultar sin eliminar)

## Configuración — `config/modulos.php`

```php
return [
    // Visibles (flujo ajustado)
    'ingreso'        => true,
    'vaciado'        => true,
    'inventario'     => true,
    'salida'         => true,
    'reportes'       => true,
    'trazabilidad'   => true,
    'productos'      => true,   // soporte de administración (Q4)
    'usuarios'       => true,   // administración
    'ubicaciones'    => true,   // administración

    // Ocultos (no eliminados — datos e historial intactos)
    'solicitudes'    => false,
    'gate_in'        => false,  // ingreso por pasos (reemplazado por 'ingreso')
    'entregas'       => false,  // Orden de Cargue / Tarja (reemplazado por 'salida')
    'transferencias' => false,
    'gate_out'       => false,
    'importaciones'  => false,  // importación histórica + pendientes
];
```

## Middleware `ModuloVisible`

- Uso en rutas: `->middleware('modulo:solicitudes')`.
- Si la bandera del módulo es `false` → responde **404** (el módulo "no existe" para el usuario) sin eliminar la ruta ni el controlador.
- Si es `true` → continúa la cadena normal (incluye los checks RBAC existentes).
- **Contrato**: cambiar una bandera de `false` a `true` reactiva el módulo completo sin migraciones ni recuperación de datos (FR-025).

## Sidebar — `resources/views/layouts/app.blade.php`

- Cada ítem del menú se envuelve con `@if(config('modulos.<clave>'))`.
- Resultado: la navegación de cada rol muestra solo los módulos con bandera `true` (FR-023, SC-006).

## Helper

- `modulo_visible(string $clave): bool` → `config("modulos.$clave", false)`. Centraliza la lectura (DRY) para vistas y middleware.

## Garantías (FR-024/FR-026)

- No se eliminan tablas, columnas, controladores ni rutas de los módulos ocultos.
- Los reportes y la trazabilidad **siguen leyendo** las tablas de módulos ocultos (p. ej. históricos de `tarjas`, `transferencias`, importaciones), porque el guard solo afecta las rutas de esos módulos, no las consultas internas.
