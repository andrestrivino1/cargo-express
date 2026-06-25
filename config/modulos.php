<?php

/*
|--------------------------------------------------------------------------
| Visibilidad de módulos (ocultar sin eliminar)
|--------------------------------------------------------------------------
|
| Cada bandera controla si el módulo aparece en la navegación y si sus rutas
| están accesibles (vía middleware `modulo:<clave>`). Poner en false OCULTA el
| módulo sin eliminar datos, rutas ni controladores; volver a true lo reactiva.
|
*/

return [
    // Flujo ajustado (visibles)
    'ingreso' => true,
    'vaciado' => true,
    'inventario' => true,
    'salida' => true,
    'reportes' => true,
    'trazabilidad' => true,
    'productos' => true,   // soporte de administración (catálogo)
    'usuarios' => true,    // administración
    'ubicaciones' => true, // administración

    // No utilizados en el flujo ajustado (ocultos — datos e historial intactos)
    'solicitudes' => false,
    'gate_in' => false,       // ingreso por pasos (reemplazado por 'ingreso')
    'entregas' => false,      // Orden de Cargue / Tarja (reemplazado por 'salida')
    'transferencias' => false,
    'gate_out' => false,
    'importaciones' => true, // importación histórica + pendientes por completar (reactivado para importar el inventario actual)
];
