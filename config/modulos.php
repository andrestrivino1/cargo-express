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

    // Reactivados (el sistema vuelve a operar con varios usuarios; datos e historial intactos)
    'solicitudes' => true,
    'gate_in' => true,       // ingreso por pasos
    'entregas' => true,      // Orden de Cargue / Tarja
    'transferencias' => true,
    'gate_out' => true,
    'importaciones' => true, // importación histórica + pendientes por completar (reactivado para importar el inventario actual)
];
