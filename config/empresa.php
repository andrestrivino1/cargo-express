<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Datos de la empresa emisora
    |--------------------------------------------------------------------------
    |
    | Usados en documentos oficiales como la Orden de Salida (ODC).
    |
    */

    'razon_social' => env('EMPRESA_RAZON_SOCIAL', 'CARGA TRANS XPRESS S.A.S'),
    'nit' => env('EMPRESA_NIT', '901615219-4'),
    'direccion' => env('EMPRESA_DIRECCION', 'Cl 12 km 12 14 Buenaventura'),
    'telefono' => env('EMPRESA_TELEFONO', '3186988247'),
    'email' => env('EMPRESA_EMAIL', 'admoncargaxpress@gmail.com'),

    // Ruta del logo (relativa a public/) para incrustar en los PDF.
    'logo' => env('EMPRESA_LOGO', 'images/logo-carga-trans-xpress.png'),
];
