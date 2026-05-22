<?php

return [

    'password_generica' => env('IMPORT_PASSWORD_GENERICA', 'Cliente2026!'),

    'dominio_placeholder' => env('IMPORT_DOMINIO_PLACEHOLDER', 'cargo-express.placeholder'),

    'fecha_corte_default' => '2026-02-27',

    'origen_default' => 'carga_historica_27_02_2026',

    'chunk_size' => 500,

    'max_pares_despacho' => 8,

    'disco' => 'imports',

    'max_file_size_kb' => 51200,
];
