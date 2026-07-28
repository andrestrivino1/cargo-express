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
    'citas' => true,     // Agenda la llegada física de un contenedor ya declarado en un ingreso
    'porteria' => true,  // Control en puerta: valida la cita del día y captura 4 evidencias
    'vaciado' => true,
    'inventario' => true,
    'salida' => true,
    'reportes' => true,
    'trazabilidad' => true,
    'productos' => true,   // soporte de administración (catálogo)
    'usuarios' => true,    // administración
    'ubicaciones' => true, // administración

    // Transferencias: única forma de reubicar mercancía entre ubicaciones y de cambiar
    // titularidad entre clientes (el flujo nuevo no lo cubre). Se mantiene visible.
    'transferencias' => true,

    // Cadena vieja redundante — reemplazada por el flujo nuevo Ingreso/Salida y oculta
    // de nuevo (datos e historial intactos). No escriben en el ledger movimientos_inventario.
    'solicitudes' => false,   // solo alimentaba a Gate-In
    'gate_in' => false,       // reemplazado por 'ingreso'
    'entregas' => false,      // Orden de Cargue / Tarja, reemplazado por 'salida' (ODC)
    'gate_out' => false,      // reemplazado por 'salida'
    'importaciones' => true, // importación histórica + pendientes por completar (reactivado para importar el inventario actual)
];
